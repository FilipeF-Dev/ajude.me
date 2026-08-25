<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Catálogo (02 §3.1, §3.4).
 *
 * O catálogo é mais que taxonomia: é a camada de correspondência entre a
 * necessidade escrita em linguagem natural e a competência que a resolve
 * (05 §4.2). Por isso os termos populares moram aqui.
 *
 * `restrita` implementa a curadoria de categorias de risco (RF-FAV-13):
 * impede novas ofertas sem invalidar as que já existem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome');
            $tabela->boolean('ativa')->default(true);
            $tabela->unsignedSmallInteger('ordem')->default(0);
            $tabela->timestampsTz();

            $tabela->unique('nome');
        });

        Schema::create('subcategorias', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $tabela->string('nome');
            $tabela->boolean('ativa')->default(true);
            $tabela->unsignedSmallInteger('ordem')->default(0);
            $tabela->timestampsTz();

            $tabela->unique(['categoria_id', 'nome']);
        });

        Schema::create('proficiencias', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('subcategoria_id')->constrained('subcategorias')->restrictOnDelete();
            $tabela->string('nome');
            $tabela->boolean('ativa')->default(true);
            $tabela->boolean('restrita')->default(false);
            $tabela->string('motivo_restricao')->nullable();
            $tabela->timestampsTz();

            $tabela->unique(['subcategoria_id', 'nome']);
            $tabela->index('restrita');
        });

        Schema::create('termos_populares', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('proficiencia_id')->constrained('proficiencias')->cascadeOnDelete();
            $tabela->string('termo');
            // Chave real de busca: minúsculas, sem acento, sem pontuação.
            $tabela->string('termo_normalizado');
            $tabela->foreignId('criado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $tabela->timestampsTz();

            $tabela->unique(['proficiencia_id', 'termo_normalizado']);
            $tabela->index('termo_normalizado');
        });

        Schema::create('proficiencias_usuario', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->foreignId('proficiencia_id')->constrained('proficiencias')->restrictOnDelete();
            $tabela->string('nivel', 20);
            $tabela->timestampTz('declarada_em');
            $tabela->timestampsTz();

            $tabela->unique(['usuario_id', 'proficiencia_id']);
        });

        DB::statement("
            ALTER TABLE proficiencias_usuario
            ADD CONSTRAINT proficiencias_usuario_nivel_valido
            CHECK (nivel IN ('basico', 'intermediario', 'avancado', 'especialista'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('proficiencias_usuario');
        Schema::dropIfExists('termos_populares');
        Schema::dropIfExists('proficiencias');
        Schema::dropIfExists('subcategorias');
        Schema::dropIfExists('categorias');
    }
};
