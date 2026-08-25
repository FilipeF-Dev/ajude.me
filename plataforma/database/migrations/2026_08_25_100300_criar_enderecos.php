<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Endereço do usuário (05 §4.1).
 *
 * A coordenada precisa é armazenada para cálculo de proximidade, mas nunca é
 * exposta: a visibilidade obedece à revelação por contexto (P7, 02 §18.1).
 * O que aparece antes de um agendamento é a região aproximada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enderecos', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->string('cep', 8);
            $tabela->string('logradouro');
            $tabela->string('numero', 20);
            $tabela->string('complemento')->nullable();
            $tabela->string('bairro');
            $tabela->string('municipio');
            $tabela->string('uf', 2);
            $tabela->string('referencia')->nullable();
            $tabela->timestampsTz();

            $tabela->unique('usuario_id');
        });

        DB::statement('ALTER TABLE enderecos ADD COLUMN coordenada geography(Point, 4326)');
        DB::statement('CREATE INDEX enderecos_coordenada_idx ON enderecos USING GIST (coordenada)');
    }

    public function down(): void
    {
        Schema::dropIfExists('enderecos');
    }
};
