<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Consumo — a solicitação do favor (02 §8, 05 §4.6).
 *
 * `ajudante_id` é coluna, embora derivável de `ticket → favor →
 * proficiencia_usuario → usuario`. A prestação é obrigação de quem emitiu o
 * ticket, e essa vinculação não pode se perder se o favor for desativado
 * depois (05 §4.6).
 *
 * Duas invariantes ficam a cargo do banco:
 *
 *  02 §5.2 — 1 ticket = 1 realização. Um índice único parcial impede que o
 *            mesmo ticket sustente duas solicitações vivas ao mesmo tempo.
 *            Cancelada ou recusada, o ticket volta a poder ser solicitado.
 *
 *  I19     — o ajudante não tem dois atendimentos sobrepostos. Uma restrição
 *            de exclusão sobre o intervalo previsto resolve no armazenamento
 *            o que 05 §7.3 descreve como risco de concorrência: duas
 *            confirmações simultâneas não conseguem gravar intervalos que se
 *            cruzem, qualquer que seja o caminho da aplicação.
 */
return new class extends Migration
{
    public function up(): void
    {
        // `EXCLUDE` com igualdade em bigint exige btree_gist.
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('solicitacoes', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('ticket_id')->constrained('tickets')->restrictOnDelete();
            $tabela->foreignId('solicitante_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('ajudante_id')->constrained('usuarios')->restrictOnDelete();

            $tabela->string('estado', 25)->default('solicitada');

            $tabela->timestampTz('inicio_previsto');
            $tabela->timestampTz('fim_previsto');

            // Aberta quando a data prevista é alcançada (RF-SOL-08).
            $tabela->timestampTz('janela_contestacao_ate')->nullable();

            $tabela->text('motivo_recusa')->nullable();
            $tabela->text('justificativa_cancelamento')->nullable();
            $tabela->foreignId('cancelada_por_id')->nullable()->constrained('usuarios')->restrictOnDelete();

            $tabela->timestampsTz();

            $tabela->index(['ajudante_id', 'estado']);
            $tabela->index(['solicitante_id', 'estado']);
            $tabela->index('janela_contestacao_ate');
        });

        DB::statement("
            ALTER TABLE solicitacoes
            ADD CONSTRAINT solicitacoes_estado_valido
            CHECK (estado IN (
                'solicitada', 'agendada', 'recusada', 'aguardando_confirmacao',
                'em_analise', 'realizada', 'nao_realizada', 'inconclusiva',
                'cancelada_ajudante', 'cancelada_solicitante', 'encerrada'
            ))
        ");

        DB::statement('
            ALTER TABLE solicitacoes
            ADD CONSTRAINT solicitacoes_intervalo_coerente
            CHECK (fim_previsto > inicio_previsto)
        ');

        // Ninguém presta favor a si mesmo: seria consumir o próprio direito
        // sem que ajuda alguma mudasse de mãos.
        DB::statement('
            ALTER TABLE solicitacoes
            ADD CONSTRAINT solicitacoes_partes_distintas
            CHECK (solicitante_id <> ajudante_id)
        ');

        // RF-SOL-05 — recusar exige dizer qual condição foi violada.
        DB::statement("
            ALTER TABLE solicitacoes
            ADD CONSTRAINT solicitacoes_recusa_motivada
            CHECK (estado <> 'recusada' OR motivo_recusa IS NOT NULL)
        ");

        // RF-CAN-01 — o cancelamento do ajudante exige justificativa. O do
        // solicitante não a exige: 02 §9.2 proíbe criar obstáculo ao
        // cancelamento, e exigir texto de quem desiste é obstáculo.
        DB::statement("
            ALTER TABLE solicitacoes
            ADD CONSTRAINT solicitacoes_cancelamento_do_ajudante_justificado
            CHECK (estado <> 'cancelada_ajudante' OR justificativa_cancelamento IS NOT NULL)
        ");

        DB::statement("
            ALTER TABLE solicitacoes
            ADD CONSTRAINT solicitacoes_cancelamento_tem_autor
            CHECK (
                estado NOT IN ('cancelada_ajudante', 'cancelada_solicitante')
                OR cancelada_por_id IS NOT NULL
            )
        ");

        DB::statement("
            ALTER TABLE solicitacoes
            ADD CONSTRAINT solicitacoes_janela_aberta_tem_prazo
            CHECK (estado <> 'aguardando_confirmacao' OR janela_contestacao_ate IS NOT NULL)
        ");

        // 02 §5.2 — 1 ticket = 1 realização.
        DB::statement("
            CREATE UNIQUE INDEX solicitacoes_uma_viva_por_ticket
            ON solicitacoes (ticket_id)
            WHERE estado IN (
                'solicitada', 'agendada', 'aguardando_confirmacao',
                'em_analise', 'realizada'
            )
        ");

        // I19 — sem sobreposição na agenda do ajudante.
        DB::statement("
            ALTER TABLE solicitacoes
            ADD CONSTRAINT solicitacoes_sem_sobreposicao_do_ajudante
            EXCLUDE USING gist (
                ajudante_id WITH =,
                tstzrange(inicio_previsto, fim_previsto) WITH &&
            )
            WHERE (estado IN ('agendada', 'aguardando_confirmacao'))
        ");

        /**
         * Registro de comparecimento (02 §8.1, RF-SEG-05 a RF-SEG-07).
         *
         * Apenas inserção: é evidência, e evidência que se edita não é
         * evidência. Cada parte registra um início e um fim, no máximo.
         */
        Schema::create('registros_comparecimento', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('solicitacao_id')->constrained('solicitacoes')->restrictOnDelete();
            $tabela->string('parte', 15);
            $tabela->string('tipo', 10);
            $tabela->timestampTz('momento');
            $tabela->timestampsTz();

            $tabela->unique(['solicitacao_id', 'parte', 'tipo']);
        });

        DB::statement("
            ALTER TABLE registros_comparecimento
            ADD CONSTRAINT registros_parte_valida
            CHECK (parte IN ('ajudante', 'solicitante'))
        ");

        DB::statement("
            ALTER TABLE registros_comparecimento
            ADD CONSTRAINT registros_tipo_valido
            CHECK (tipo IN ('inicio', 'fim'))
        ");

        /**
         * I22 — cada parte registra no máximo uma manifestação por
         * solicitação. Garantido pela chave única, não por consulta prévia:
         * duas requisições simultâneas da mesma parte não passam as duas.
         */
        Schema::create('manifestacoes_realizacao', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('solicitacao_id')->constrained('solicitacoes')->restrictOnDelete();
            $tabela->string('parte', 15);
            $tabela->string('tipo', 10);
            $tabela->timestampTz('momento');
            $tabela->timestampsTz();

            $tabela->unique(['solicitacao_id', 'parte']);
        });

        DB::statement("
            ALTER TABLE manifestacoes_realizacao
            ADD CONSTRAINT manifestacoes_parte_valida
            CHECK (parte IN ('ajudante', 'solicitante'))
        ");

        DB::statement("
            ALTER TABLE manifestacoes_realizacao
            ADD CONSTRAINT manifestacoes_tipo_valido
            CHECK (tipo IN ('confirma', 'contesta'))
        ");

        // P2 — a solicitação é a quarta máquina de estados (05 §6), e obedece
        // à mesma exigência das outras: nenhuma transição sem rastro.
        Schema::create('transicoes_solicitacao', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('solicitacao_id')->constrained('solicitacoes')->restrictOnDelete();
            $tabela->string('de_estado', 25)->nullable();
            $tabela->string('para_estado', 25);
            $tabela->string('evento', 40);
            $tabela->foreignId('ator_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $tabela->timestampTz('ocorrido_em');
            $tabela->timestampsTz();

            $tabela->index(['solicitacao_id', 'ocorrido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transicoes_solicitacao');
        Schema::dropIfExists('manifestacoes_realizacao');
        Schema::dropIfExists('registros_comparecimento');
        Schema::dropIfExists('solicitacoes');
    }
};
