<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parâmetros de negócio versionados por vigência (P6, 05 §9).
 *
 * Prazo é parâmetro, não constante. E a vigência não é luxo: uma solicitação
 * aberta sob janela de contestação de 72 h permanece regida por 72 h ainda que
 * o parâmetro mude depois. Sem isso, alterar uma configuração reescreveria as
 * condições de relações em curso — o que violaria P5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros_negocio', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('chave', 100);
            $tabela->string('valor', 100);
            $tabela->string('descricao')->nullable();
            $tabela->timestampTz('vigente_de');
            $tabela->timestampTz('vigente_ate')->nullable();
            $tabela->foreignId('alterado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $tabela->timestampsTz();

            $tabela->index(['chave', 'vigente_de']);
        });

        // Uma única vigência aberta por chave.
        DB::statement('
            CREATE UNIQUE INDEX parametros_negocio_chave_vigencia_aberta
            ON parametros_negocio (chave)
            WHERE vigente_ate IS NULL
        ');

        DB::statement('
            ALTER TABLE parametros_negocio
            ADD CONSTRAINT parametros_negocio_vigencia_coerente
            CHECK (vigente_ate IS NULL OR vigente_ate > vigente_de)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_negocio');
    }
};
