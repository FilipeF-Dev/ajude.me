<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Ticket — o agregado central (P1, 05 §2).
 *
 * O ticket é o direito de receber um favor. Não é dinheiro, não acumula e
 * não representa valor: por isso não existe aqui nenhuma coluna de saldo,
 * crédito ou quantia (P8). A ausência dessa estrutura é a garantia técnica
 * de que a moeda interna não aparece por conveniência de implementação.
 *
 * `validade_suspensa_em` sustenta I7: enquanto o ticket está em análise, a
 * validade não corre. A demora da própria plataforma não pode extinguir um
 * direito em disputa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('codigo', 20)->unique();
            $tabela->foreignId('favor_id')->constrained('favores')->restrictOnDelete();
            $tabela->foreignId('proprietario_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->string('estado', 20)->default('disponivel');
            $tabela->timestampTz('emitido_em');
            $tabela->timestampTz('expira_em');
            $tabela->timestampTz('validade_suspensa_em')->nullable();
            $tabela->timestampsTz();

            $tabela->index(['favor_id', 'estado']);
            $tabela->index(['proprietario_id', 'estado']);
            $tabela->index('expira_em');
        });

        DB::statement("
            ALTER TABLE tickets
            ADD CONSTRAINT tickets_estado_valido
            CHECK (estado IN (
                'disponivel', 'em_negociacao', 'reservado', 'em_analise',
                'consumido', 'encerrado', 'expirado', 'descartado'
            ))
        ");

        DB::statement('
            ALTER TABLE tickets
            ADD CONSTRAINT tickets_validade_posterior_a_emissao
            CHECK (expira_em > emitido_em)
        ');

        // I7 — só faz sentido suspender validade em análise.
        DB::statement("
            ALTER TABLE tickets
            ADD CONSTRAINT tickets_suspensao_apenas_em_analise
            CHECK (validade_suspensa_em IS NULL OR estado = 'em_analise')
        ");

        /**
         * I8 — toda mudança de proprietário gera exatamente um registro.
         *
         * `de_usuario_id` nulo marca a emissão: o ticket nasce na carteira de
         * quem o emitiu, vindo de lugar nenhum.
         */
        Schema::create('transferencias_ticket', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('ticket_id')->constrained('tickets')->restrictOnDelete();
            $tabela->foreignId('de_usuario_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('para_usuario_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->string('motivo', 30);
            $tabela->timestampTz('ocorrido_em');
            $tabela->timestampsTz();

            $tabela->index(['ticket_id', 'ocorrido_em']);
        });

        DB::statement("
            ALTER TABLE transferencias_ticket
            ADD CONSTRAINT transferencias_motivo_valido
            CHECK (motivo IN ('emissao', 'proposta_aceita'))
        ");

        DB::statement('
            ALTER TABLE transferencias_ticket
            ADD CONSTRAINT transferencias_origem_diferente_do_destino
            CHECK (de_usuario_id IS NULL OR de_usuario_id <> para_usuario_id)
        ');

        /**
         * P2 — todo estado é explícito e toda transição é registrada.
         * Não existe estado inferido, e nenhuma transição acontece sem rastro.
         */
        Schema::create('transicoes_ticket', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('ticket_id')->constrained('tickets')->restrictOnDelete();
            $tabela->string('de_estado', 20)->nullable();
            $tabela->string('para_estado', 20);
            $tabela->string('evento', 40);
            $tabela->foreignId('ator_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $tabela->timestampTz('ocorrido_em');
            $tabela->timestampsTz();

            $tabela->index(['ticket_id', 'ocorrido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transicoes_ticket');
        Schema::dropIfExists('transferencias_ticket');
        Schema::dropIfExists('tickets');
    }
};
