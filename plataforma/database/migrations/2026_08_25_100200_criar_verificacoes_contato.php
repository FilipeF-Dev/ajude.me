<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verificação de e-mail e telefone (RF-CAD-04, RF-CAD-05).
 *
 * Entidade separada, e não dois campos booleanos no usuário, por decisão de
 * arquitetura (05 §10.1): a verificação documental de identidade entra depois
 * como mais um tipo, sem alterar a estrutura.
 *
 * O selo "Contato verificado" (RF-CAD-06) é derivado daqui, nunca armazenado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verificacoes_contato', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->string('tipo', 20);
            $tabela->string('valor');
            $tabela->string('codigo', 10)->nullable();
            $tabela->timestampTz('codigo_expira_em')->nullable();
            $tabela->unsignedSmallInteger('tentativas')->default(0);
            $tabela->timestampTz('verificado_em')->nullable();
            $tabela->timestampsTz();

            $tabela->unique(['usuario_id', 'tipo']);
        });

        DB::statement("
            ALTER TABLE verificacoes_contato
            ADD CONSTRAINT verificacoes_contato_tipo_valido
            CHECK (tipo IN ('email', 'telefone'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('verificacoes_contato');
    }
};
