<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convites de acesso da base restrita (RF-ADM-08).
 *
 * O convite é pré-requisito do piloto fechado (06 §5.2) e serve a dois
 * propósitos que se reforçam:
 *
 *  1. **Segurança.** Base restrita e convidada é mitigação dos riscos D1 e D2
 *     (03 §12): o convívio regula o comportamento onde a curadoria ainda não
 *     alcança.
 *
 *  2. **Liquidez.** 06 §2.2 — a restrição deixa de ser só mitigação e vira
 *     estratégia: uma rede de troca depende de densidade geográfica, e
 *     convidar concentra em vez de espalhar.
 *
 * `usado_por_id` é único: um convite, uma conta. Sem isso, um código
 * circulando num grupo de mensagens abriria a base que o convite existe para
 * fechar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convites', function (Blueprint $tabela) {
            $tabela->id();

            // Curto e legível: vai ser lido em voz alta e digitado à mão.
            $tabela->string('codigo', 12)->unique();

            $tabela->foreignId('criado_por_id')->constrained('operadores')->restrictOnDelete();

            // Para quem a Operação preparou o convite. Ajuda a acompanhar o
            // piloto sem depender da memória de quem o entregou.
            $tabela->string('destinatario')->nullable();
            $tabela->text('observacao')->nullable();

            $tabela->timestampTz('expira_em')->nullable();

            $tabela->foreignId('usado_por_id')->nullable()->unique()->constrained('usuarios')->restrictOnDelete();
            $tabela->timestampTz('usado_em')->nullable();

            $tabela->timestampTz('revogado_em')->nullable();
            $tabela->foreignId('revogado_por_id')->nullable()->constrained('operadores')->nullOnDelete();

            $tabela->timestampsTz();

            $tabela->index('expira_em');
        });

        // Usar é par: ou os dois campos estão preenchidos, ou nenhum.
        DB::statement('
            ALTER TABLE convites
            ADD CONSTRAINT convites_uso_completo
            CHECK (
                (usado_por_id IS NULL AND usado_em IS NULL)
                OR (usado_por_id IS NOT NULL AND usado_em IS NOT NULL)
            )
        ');

        // Revogar um convite já usado não desfaz a conta criada — e fingir
        // que desfaz seria pior do que não permitir.
        DB::statement('
            ALTER TABLE convites
            ADD CONSTRAINT convites_usado_nao_se_revoga
            CHECK (usado_em IS NULL OR revogado_em IS NULL)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('convites');
    }
};
