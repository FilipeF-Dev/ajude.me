<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Negociação — a proposta de troca (02 §5.4, 05 §4.5).
 *
 * O versionamento da oferta é estrutural, não histórico opcional: RF-NEG-09
 * exige que toda revisão invalide aceites anteriores, e isso só é verificável
 * se o aceite apontar para a versão exata que foi aceita (I15). Por isso a
 * composição não mora na proposta — mora na versão.
 *
 * I14 (um ticket não participa de duas propostas ativas) não é garantida por
 * restrição desta tabela, e sim pela máquina de estados do ticket: entrar em
 * uma proposta exige `DISPONIVEL` e move para `EM_NEGOCIACAO`. Um segundo
 * envio não encontra o ticket disponível. Uma restrição aqui duplicaria a
 * regra em dois lugares que poderiam divergir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propostas', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('remetente_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('destinatario_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->string('estado', 20)->default('rascunho');

            $tabela->unsignedSmallInteger('versao_atual')->default(0);

            // I15 — o aceite guarda a versão aceita, não apenas o instante.
            $tabela->unsignedSmallInteger('versao_aceita')->nullable();

            $tabela->timestampTz('enviada_em')->nullable();
            $tabela->timestampTz('expira_em')->nullable();
            $tabela->timestampTz('encerrada_em')->nullable();
            $tabela->timestampsTz();

            $tabela->index(['destinatario_id', 'estado']);
            $tabela->index(['remetente_id', 'estado']);
            $tabela->index('expira_em');
        });

        DB::statement("
            ALTER TABLE propostas
            ADD CONSTRAINT propostas_estado_valido
            CHECK (estado IN (
                'rascunho', 'enviada', 'em_negociacao',
                'aceita', 'recusada', 'retirada', 'expirada'
            ))
        ");

        // Ninguém negocia consigo mesmo: trocar ticket com a própria carteira
        // não transfere direito nenhum.
        DB::statement('
            ALTER TABLE propostas
            ADD CONSTRAINT propostas_partes_distintas
            CHECK (remetente_id <> destinatario_id)
        ');

        // O envio é o que dá prazo à proposta: os dois campos andam juntos.
        DB::statement('
            ALTER TABLE propostas
            ADD CONSTRAINT propostas_prazo_par_do_envio
            CHECK (
                (enviada_em IS NULL AND expira_em IS NULL)
                OR (enviada_em IS NOT NULL AND expira_em IS NOT NULL)
            )
        ');

        // Rascunho é o único estado anterior ao envio. Um rascunho descartado
        // vira `retirada` sem nunca ter tido prazo — por isso a exigência é
        // sobre os estados que pressupõem o envio, não sobre "não ser rascunho".
        DB::statement("
            ALTER TABLE propostas
            ADD CONSTRAINT propostas_estados_pos_envio_exigem_envio
            CHECK (
                estado NOT IN ('enviada', 'em_negociacao', 'aceita', 'recusada', 'expirada')
                OR enviada_em IS NOT NULL
            )
        ");

        DB::statement("
            ALTER TABLE propostas
            ADD CONSTRAINT propostas_aceite_registra_versao
            CHECK (
                (estado = 'aceita' AND versao_aceita IS NOT NULL)
                OR (estado <> 'aceita' AND versao_aceita IS NULL)
            )
        ");

        Schema::create('versoes_proposta', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
            $tabela->unsignedSmallInteger('numero');
            $tabela->foreignId('criada_por_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->timestampTz('criada_em');
            $tabela->timestampsTz();

            $tabela->unique(['proposta_id', 'numero']);
        });

        DB::statement('
            ALTER TABLE versoes_proposta
            ADD CONSTRAINT versoes_numero_positivo
            CHECK (numero > 0)
        ');

        Schema::create('itens_proposta', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('versao_proposta_id')->constrained('versoes_proposta')->cascadeOnDelete();
            $tabela->foreignId('ticket_id')->constrained('tickets')->restrictOnDelete();
            $tabela->string('lado', 15);
            $tabela->timestampsTz();

            // O mesmo ticket não aparece dos dois lados da mesma versão.
            $tabela->unique(['versao_proposta_id', 'ticket_id']);
            $tabela->index('ticket_id');
        });

        DB::statement("
            ALTER TABLE itens_proposta
            ADD CONSTRAINT itens_lado_valido
            CHECK (lado IN ('solicitado', 'contrapartida'))
        ");

        Schema::create('mensagens_negociacao', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
            $tabela->foreignId('autor_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->text('texto');
            $tabela->timestampTz('enviada_em');
            $tabela->timestampsTz();

            $tabela->index(['proposta_id', 'enviada_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagens_negociacao');
        Schema::dropIfExists('itens_proposta');
        Schema::dropIfExists('versoes_proposta');
        Schema::dropIfExists('propostas');
    }
};
