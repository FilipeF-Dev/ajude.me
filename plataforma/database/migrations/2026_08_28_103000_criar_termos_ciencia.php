<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Termo de ciência de riscos (02 §8.2, RF-SEG-03, RF-SEG-04, RF-ADM-07).
 *
 * > **Termo de ciência é documento, e documento não socorre ninguém.**
 *
 * Ele protege a plataforma juridicamente, o que é necessário, e não reduz o
 * risco de ninguém. Está aqui por isso, e não como substituto da curadoria
 * de categorias nem do registro de comparecimento.
 *
 * O texto é versionado e o aceite guarda **qual versão** foi aceita. Um termo
 * sem esse registro não sustenta a finalidade probatória para a qual existe
 * (RF-SEG-04) — meses depois, ninguém consegue dizer o que a pessoa leu.
 *
 * O momento de exibição é regra, não detalhe de implementação: no aceite do
 * atendimento presencial, nunca no cadastro. Termo apresentado no cadastro
 * não é lido, e portanto não conscientiza ninguém.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termos_ciencia', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->unsignedSmallInteger('versao')->unique();
            $tabela->text('texto');
            $tabela->timestampTz('publicado_em');
            $tabela->foreignId('publicado_por_id')->nullable()->constrained('operadores')->nullOnDelete();
            $tabela->timestampTz('encerrado_em')->nullable();
            $tabela->timestampsTz();
        });

        DB::statement('
            ALTER TABLE termos_ciencia
            ADD CONSTRAINT termos_versao_positiva
            CHECK (versao > 0)
        ');

        Schema::create('aceites_termo', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('termo_ciencia_id')->constrained('termos_ciencia')->restrictOnDelete();
            $tabela->foreignId('solicitacao_id')->constrained('solicitacoes')->restrictOnDelete();
            $tabela->timestampTz('aceito_em');
            $tabela->timestampsTz();

            // Cada parte aceita uma vez por atendimento.
            $tabela->unique(['solicitacao_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aceites_termo');
        Schema::dropIfExists('termos_ciencia');
    }
};
