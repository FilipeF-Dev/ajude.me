<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Moderação (02 §8.5, §10 e §18.2, 05 §4.7).
 *
 * É o contexto que decide. Os contextos Ticket e Consumo executam o que ele
 * decidiu — nunca o contrário.
 *
 * `chamados.decidido_por_id` aponta para `operadores`, não para `usuarios`:
 * são atores distintos, com tabelas e guards separados. Um usuário jamais
 * decide um chamado, e o esquema é o lugar onde isso fica impossível.
 */
return new class extends Migration
{
    public function up(): void
    {
        /**
         * Bloqueio (02 §18.2, RF-BLO-01 a RF-BLO-06).
         *
         * Direcional no registro, mútuo no efeito: quem bloqueia deixa de ver
         * e de ser visto. Guardar as duas direções seria inventar uma decisão
         * que a outra pessoa não tomou.
         *
         * O bloqueio **não** é ocorrência nem avaliação negativa (RF-BLO-05),
         * e por isso não mora na tabela de ocorrências.
         */
        Schema::create('bloqueios', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->foreignId('usuario_bloqueado_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->timestampTz('criado_em');
            $tabela->timestampsTz();

            $tabela->unique(['usuario_id', 'usuario_bloqueado_id']);
            $tabela->index('usuario_bloqueado_id');
        });

        DB::statement('
            ALTER TABLE bloqueios
            ADD CONSTRAINT bloqueios_partes_distintas
            CHECK (usuario_id <> usuario_bloqueado_id)
        ');

        /**
         * Ocorrência (02 §10, RF-CAN-06, RF-CAN-10, RF-CAN-15).
         *
         * `origem` distingue o que o sistema registrou sozinho — um
         * cancelamento em cima da hora — do que uma parte relatou. A mesma
         * tabela guarda os dois porque a Operação analisa os dois juntos, mas
         * confundi-los na leitura seria dar peso de relato a uma consequência
         * automática.
         */
        Schema::create('ocorrencias', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('tipo', 40);
            $tabela->string('origem', 10);
            $tabela->foreignId('autor_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('contra_usuario_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('solicitacao_id')->nullable()->constrained('solicitacoes')->restrictOnDelete();
            $tabela->text('descricao')->nullable();
            $tabela->timestampTz('registrada_em');
            $tabela->timestampsTz();

            $tabela->index(['contra_usuario_id', 'registrada_em']);
            $tabela->index('solicitacao_id');
        });

        DB::statement("
            ALTER TABLE ocorrencias
            ADD CONSTRAINT ocorrencias_origem_valida
            CHECK (origem IN ('usuario', 'sistema'))
        ");

        // Ocorrência de origem sistema não tem autor; a de origem usuário tem.
        DB::statement("
            ALTER TABLE ocorrencias
            ADD CONSTRAINT ocorrencias_autor_coerente_com_origem
            CHECK (
                (origem = 'sistema' AND autor_id IS NULL)
                OR (origem = 'usuario' AND autor_id IS NOT NULL)
            )
        ");

        DB::statement('
            ALTER TABLE ocorrencias
            ADD CONSTRAINT ocorrencias_autor_diferente_do_alvo
            CHECK (autor_id IS NULL OR autor_id <> contra_usuario_id)
        ');

        /**
         * Chamado (02 §8.5, RF-ADM-11 a RF-ADM-14).
         *
         * Um por solicitação: a contestação leva a solicitação à análise uma
         * única vez, e abrir dois chamados sobre o mesmo episódio produziria
         * duas decisões possíveis para o mesmo fato.
         */
        Schema::create('chamados', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('solicitacao_id')->unique()->constrained('solicitacoes')->restrictOnDelete();
            $tabela->string('estado', 15)->default('aberto');
            $tabela->foreignId('aberto_por_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->timestampTz('aberto_em');
            $tabela->timestampTz('manifestacao_ate');

            $tabela->string('decisao', 20)->nullable();
            $tabela->text('fundamento')->nullable();
            $tabela->foreignId('decidido_por_id')->nullable()->constrained('operadores')->nullOnDelete();
            $tabela->timestampTz('decidido_em')->nullable();

            $tabela->timestampsTz();

            $tabela->index(['estado', 'aberto_em']);
        });

        DB::statement("
            ALTER TABLE chamados
            ADD CONSTRAINT chamados_estado_valido
            CHECK (estado IN ('aberto', 'decidido'))
        ");

        DB::statement("
            ALTER TABLE chamados
            ADD CONSTRAINT chamados_decisao_valida
            CHECK (decisao IS NULL OR decisao IN ('realizado', 'nao_realizado', 'inconclusivo'))
        ");

        // RF-ADM-13 — decidir exige registrar o fundamento. Uma decisão sem
        // fundamento não é comunicável às partes (RF-SOL-19).
        DB::statement("
            ALTER TABLE chamados
            ADD CONSTRAINT chamados_decisao_fundamentada
            CHECK (
                (estado = 'aberto' AND decisao IS NULL AND fundamento IS NULL AND decidido_em IS NULL)
                OR (estado = 'decidido' AND decisao IS NOT NULL AND fundamento IS NOT NULL AND decidido_em IS NOT NULL)
            )
        ");

        Schema::create('manifestacoes_chamado', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $tabela->string('parte', 15);
            $tabela->text('texto');
            $tabela->timestampTz('enviada_em');
            $tabela->timestampsTz();

            $tabela->unique(['chamado_id', 'parte']);
        });

        DB::statement("
            ALTER TABLE manifestacoes_chamado
            ADD CONSTRAINT manifestacoes_chamado_parte_valida
            CHECK (parte IN ('ajudante', 'solicitante'))
        ");

        /**
         * Denúncia (02 §18.2, RF-BLO-07, RF-BLO-08).
         *
         * Alvo é usuário **ou** favor, nunca os dois nem nenhum.
         */
        Schema::create('denuncias', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('autor_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('alvo_usuario_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('alvo_favor_id')->nullable()->constrained('favores')->restrictOnDelete();
            $tabela->text('motivo');
            $tabela->string('estado', 15)->default('aberta');
            $tabela->text('desfecho')->nullable();
            $tabela->foreignId('analisada_por_id')->nullable()->constrained('operadores')->nullOnDelete();
            $tabela->timestampTz('analisada_em')->nullable();
            $tabela->timestampTz('registrada_em');
            $tabela->timestampsTz();

            $tabela->index(['estado', 'registrada_em']);
        });

        DB::statement("
            ALTER TABLE denuncias
            ADD CONSTRAINT denuncias_estado_valido
            CHECK (estado IN ('aberta', 'procedente', 'improcedente'))
        ");

        DB::statement('
            ALTER TABLE denuncias
            ADD CONSTRAINT denuncias_alvo_unico
            CHECK (
                (alvo_usuario_id IS NOT NULL AND alvo_favor_id IS NULL)
                OR (alvo_usuario_id IS NULL AND alvo_favor_id IS NOT NULL)
            )
        ');

        DB::statement('
            ALTER TABLE denuncias
            ADD CONSTRAINT denuncias_autor_diferente_do_alvo
            CHECK (alvo_usuario_id IS NULL OR autor_id <> alvo_usuario_id)
        ');

        /**
         * Suspensão (RF-ADM-06).
         *
         * Versionada por vigência, como os parâmetros de negócio: encerrar
         * uma suspensão não apaga o fato de ela ter existido.
         */
        Schema::create('suspensoes', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('alvo_usuario_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('alvo_favor_id')->nullable()->constrained('favores')->restrictOnDelete();
            $tabela->text('motivo');
            $tabela->foreignId('aplicada_por_id')->constrained('operadores')->restrictOnDelete();
            $tabela->timestampTz('vigente_de');
            $tabela->timestampTz('vigente_ate')->nullable();
            $tabela->timestampsTz();

            $tabela->index(['alvo_usuario_id', 'vigente_ate']);
            $tabela->index(['alvo_favor_id', 'vigente_ate']);
        });

        DB::statement('
            ALTER TABLE suspensoes
            ADD CONSTRAINT suspensoes_alvo_unico
            CHECK (
                (alvo_usuario_id IS NOT NULL AND alvo_favor_id IS NULL)
                OR (alvo_usuario_id IS NULL AND alvo_favor_id IS NOT NULL)
            )
        ');

        /**
         * Trilha de auditoria (RF-ADM-10, 05 §4.9).
         *
         * Toda ação da Operação passa por aqui, com autor, momento e motivo.
         * É a contrapartida do poder que a Operação tem sobre o domínio: ela
         * decide, e fica registrado quem decidiu o quê e por quê.
         */
        Schema::create('registros_auditoria', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('operador_id')->nullable()->constrained('operadores')->nullOnDelete();
            $tabela->string('acao', 60);
            $tabela->string('alvo_tipo', 60);
            $tabela->unsignedBigInteger('alvo_id');
            $tabela->text('motivo')->nullable();
            $tabela->timestampTz('ocorrido_em');
            $tabela->timestampsTz();

            $tabela->index(['alvo_tipo', 'alvo_id']);
            $tabela->index('ocorrido_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_auditoria');
        Schema::dropIfExists('suspensoes');
        Schema::dropIfExists('denuncias');
        Schema::dropIfExists('manifestacoes_chamado');
        Schema::dropIfExists('chamados');
        Schema::dropIfExists('ocorrencias');
        Schema::dropIfExists('bloqueios');
    }
};
