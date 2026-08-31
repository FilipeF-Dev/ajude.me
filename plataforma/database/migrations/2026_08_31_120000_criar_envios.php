<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saída de mensagens para fora da plataforma (05 §10).
 *
 * Distinta de `notificacoes`, e não redundante: aquela é a fila do que
 * precisa ser dito **a uma conta**, e alimenta a interface. Esta é a entrega
 * **a um endereço** — e-mail ou telefone.
 *
 * A diferença deixa de ser sutil no contato de confiança: ele não é usuário,
 * nunca aceitou termo algum e não tem conta (05 §14.3), então não cabe na
 * tabela endereçada a contas. Sem esta tabela, `RF-SEG-02` não teria onde
 * existir.
 *
 * Registrar o que sai também atende NF-DIS-02 (falha reprocessável sem
 * duplicar efeito) e NF-DIS-03 (falha do canal do contato de confiança
 * detectada, não engolida em log).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envios', function (Blueprint $tabela) {
            $tabela->id();

            // NF-DIS-04 — reenviar o mesmo comando não duplica a mensagem.
            $tabela->string('chave_idempotencia', 120)->unique();

            $tabela->string('canal', 20);
            $tabela->string('destino');
            $tabela->string('tipo', 60);
            $tabela->string('assunto')->nullable();
            $tabela->text('corpo');

            $tabela->string('referencia_tipo')->nullable();
            $tabela->unsignedBigInteger('referencia_id')->nullable();

            $tabela->string('estado', 20)->default('registrado');
            $tabela->unsignedSmallInteger('tentativas')->default(0);
            $tabela->text('erro')->nullable();

            $tabela->timestampTz('despachado_em');
            $tabela->timestampTz('entregue_em')->nullable();
            $tabela->timestampsTz();

            $tabela->index(['estado', 'canal']);
            $tabela->index(['referencia_tipo', 'referencia_id']);
            $tabela->index('tipo');
        });

        DB::statement("
            ALTER TABLE envios
            ADD CONSTRAINT envios_canal_valido
            CHECK (canal IN ('email', 'sms'))
        ");

        /**
         * Três estados, e `registrado` não é sinônimo de entregue.
         *
         * Enquanto não houver provedor, o canal de mentira grava e não manda
         * nada. Chamar isso de "entregue" faria o painel afirmar que a pessoa
         * recebeu um código que nunca saiu daqui — exatamente a mentira que
         * um piloto não pode contar a si mesmo.
         */
        DB::statement("
            ALTER TABLE envios
            ADD CONSTRAINT envios_estado_valido
            CHECK (estado IN ('registrado', 'entregue', 'falhou'))
        ");

        DB::statement("
            ALTER TABLE envios
            ADD CONSTRAINT envios_falha_tem_motivo
            CHECK (estado <> 'falhou' OR erro IS NOT NULL)
        ");

        DB::statement("
            ALTER TABLE envios
            ADD CONSTRAINT envios_entrega_tem_momento
            CHECK (estado <> 'entregue' OR entregue_em IS NOT NULL)
        ");

        DB::statement("
            ALTER TABLE envios
            ADD CONSTRAINT envios_assunto_apenas_em_email
            CHECK (canal = 'email' OR assunto IS NULL)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('envios');
    }
};
