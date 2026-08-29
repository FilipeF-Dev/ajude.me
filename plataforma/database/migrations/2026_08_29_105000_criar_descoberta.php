<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Descoberta e o substrato de Notificações (02 §3.4, §3.5, §18.3).
 *
 * > *"alguém para dar banho na minha mãe acamada"* não é uma consulta.
 * > É um prontuário social.
 *
 * O que uma pessoa procura revela o que lhe falta — condição de saúde,
 * situação financeira, composição familiar, vulnerabilidade, às vezes de
 * terceiros. Por isso o esquema separa o que é sensível do que é útil:
 *
 *  - `texto` é o dado mais sensível do produto e o de utilidade mais curta.
 *    Serve para a Operação descobrir que palavra faltava no vocabulário
 *    (RF-ADM-17) e, cumprida essa função, vira passivo sem contrapartida.
 *    Apagado aos 90 dias (05 §11.5).
 *  - proficiência reconhecida + região + houve resultado são permanentes.
 *    É o agregado que alimenta o mapa de cobertura, e ele não precisa do
 *    texto para existir.
 *
 * `avisos_necessidade` guarda proficiência e região, **jamais** o texto
 * (RF-BUS-29).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buscas_necessidade', function (Blueprint $tabela) {
            $tabela->id();

            // Nulo para visitante não autenticado, que também busca (RF-BUS-12).
            $tabela->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();

            $tabela->text('texto')->nullable();
            $tabela->timestampTz('texto_apagado_em')->nullable();

            // Região, nunca coordenada: a análise de cobertura consome
            // agregado por região e proficiência (RF-BUS-28).
            $tabela->string('municipio')->nullable();
            $tabela->string('uf', 2)->nullable();

            $tabela->boolean('houve_resultado');
            $tabela->timestampTz('buscada_em');
            $tabela->timestampsTz();

            $tabela->index(['houve_resultado', 'buscada_em']);
            $tabela->index(['municipio', 'uf']);
            $tabela->index('texto_apagado_em');
        });

        // O texto ou existe, ou foi apagado com data. Nunca some sem registro.
        DB::statement('
            ALTER TABLE buscas_necessidade
            ADD CONSTRAINT buscas_texto_apagado_com_data
            CHECK (texto IS NOT NULL OR texto_apagado_em IS NOT NULL)
        ');

        /**
         * As proficiências que a busca reconheceu (RF-BUS-17, RF-BUS-18).
         *
         * `descartada` registra a correção do usuário (RF-BUS-19). Guardar o
         * descarte, e não apenas apagar a linha, é o que transforma erro de
         * vocabulário em algo observável pela Operação.
         */
        Schema::create('proficiencias_reconhecidas', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('busca_necessidade_id')->constrained('buscas_necessidade')->cascadeOnDelete();
            $tabela->foreignId('proficiencia_id')->constrained('proficiencias')->restrictOnDelete();
            $tabela->boolean('descartada')->default(false);
            $tabela->timestampsTz();

            $tabela->unique(['busca_necessidade_id', 'proficiencia_id']);
            $tabela->index('proficiencia_id');
        });

        /**
         * Fila de aviso por proficiência e região (RF-BUS-22, RF-BUS-29).
         *
         * Vinculada à proficiência, jamais ao texto digitado. Uma pessoa
         * aguarda "elétrica residencial na sua região", não "o chuveiro que
         * ela disse que queimou".
         */
        Schema::create('avisos_necessidade', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->foreignId('proficiencia_id')->constrained('proficiencias')->restrictOnDelete();
            $tabela->string('municipio')->nullable();
            $tabela->string('uf', 2)->nullable();
            $tabela->timestampTz('criado_em');
            $tabela->timestampTz('atendido_em')->nullable();
            $tabela->timestampsTz();

            // Um aviso vivo por proficiência; pedir de novo não duplica a fila.
            $tabela->unique(['usuario_id', 'proficiencia_id']);
            $tabela->index(['proficiencia_id', 'atendido_em']);
        });

        /**
         * Notificação (05 §4.9).
         *
         * RF-NOT-17 — não existe notificação de marketing, engajamento ou
         * recomendação no MVP. Tudo aqui decorre de um evento do loop que
         * alguém precisa saber que aconteceu.
         *
         * RF-BUS-27 — ninguém é notificado de que outra pessoa procurou algo.
         * A fila de aviso notifica quem pediu para ser avisado, e só ele.
         */
        Schema::create('notificacoes', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('destinatario_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->string('tipo', 40);
            $tabela->string('referencia_tipo', 60)->nullable();
            $tabela->unsignedBigInteger('referencia_id')->nullable();
            $tabela->jsonb('dados')->nullable();
            $tabela->timestampTz('enviada_em');
            $tabela->timestampTz('lida_em')->nullable();
            $tabela->timestampsTz();

            $tabela->index(['destinatario_id', 'lida_em']);
            $tabela->index(['referencia_tipo', 'referencia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
        Schema::dropIfExists('avisos_necessidade');
        Schema::dropIfExists('proficiencias_reconhecidas');
        Schema::dropIfExists('buscas_necessidade');
    }
};
