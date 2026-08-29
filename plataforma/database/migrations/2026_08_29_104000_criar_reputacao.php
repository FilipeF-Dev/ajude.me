<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Reputação (02 §11, 05 §4.8).
 *
 * **A reputação é sempre calculada, nunca armazenada.** Não existe aqui
 * coluna de média, nota consolidada ou contador de estrelas. Média
 * materializada é cache, e cache que ninguém sabe reconstruir vira uma
 * segunda verdade — exatamente o que I27 proíbe encostar na proficiência.
 *
 * I23 é garantida por gatilho, e não por convenção da aplicação: a
 * imutabilidade da avaliação é o que reduz conflito posterior e tentativa de
 * manipulação (02 §11.1). Uma regra que vive só no código é uma regra que o
 * próximo caminho de escrita esquece.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacoes', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('solicitacao_id')->constrained('solicitacoes')->restrictOnDelete();
            $tabela->foreignId('avaliador_id')->constrained('usuarios')->restrictOnDelete();
            $tabela->foreignId('avaliado_id')->constrained('usuarios')->restrictOnDelete();

            // Permite que a mesma pessoa acumule reputações distintas como
            // Ajudante e como Solicitante (RF-REP-01, RF-REP-02).
            $tabela->string('papel_avaliado', 15);

            $tabela->text('comentario')->nullable();
            $tabela->timestampTz('criada_em');
            $tabela->timestampsTz();

            // I24 — no máximo uma avaliação por par (solicitação, avaliador).
            $tabela->unique(['solicitacao_id', 'avaliador_id']);
            $tabela->index(['avaliado_id', 'papel_avaliado']);
        });

        DB::statement("
            ALTER TABLE avaliacoes
            ADD CONSTRAINT avaliacoes_papel_valido
            CHECK (papel_avaliado IN ('ajudante', 'solicitante'))
        ");

        DB::statement('
            ALTER TABLE avaliacoes
            ADD CONSTRAINT avaliacoes_partes_distintas
            CHECK (avaliador_id <> avaliado_id)
        ');

        Schema::create('notas_criterio', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('avaliacao_id')->constrained('avaliacoes')->restrictOnDelete();
            $tabela->string('criterio', 20);
            $tabela->unsignedTinyInteger('nota');
            $tabela->timestampsTz();

            $tabela->unique(['avaliacao_id', 'criterio']);
        });

        // I25 — toda nota está no intervalo fechado de 0 a 7 (02 §11.3).
        DB::statement('
            ALTER TABLE notas_criterio
            ADD CONSTRAINT notas_intervalo_valido
            CHECK (nota BETWEEN 0 AND 7)
        ');

        DB::statement("
            ALTER TABLE notas_criterio
            ADD CONSTRAINT notas_criterio_valido
            CHECK (criterio IN (
                'comunicacao', 'qualidade', 'satisfacao',
                'cordialidade', 'condicoes'
            ))
        ");

        /**
         * RF-AVA-05 — o critério Qualidade vem acompanhado de motivos
         * pré-determinados, para reduzir acusação genérica e infundada.
         *
         * Os motivos são descritores neutros: dizem **sobre o quê** a nota
         * fala, não que alguém foi ruim. Por isso são exigidos em qualquer
         * nota, alta ou baixa — apontar a dimensão avaliada é o que torna a
         * avaliação legível para quem a lê depois.
         */
        Schema::create('motivos_qualidade', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('avaliacao_id')->constrained('avaliacoes')->restrictOnDelete();
            $tabela->string('motivo', 30);
            $tabela->timestampsTz();

            $tabela->unique(['avaliacao_id', 'motivo']);
        });

        DB::statement("
            ALTER TABLE motivos_qualidade
            ADD CONSTRAINT motivos_qualidade_valido
            CHECK (motivo IN (
                'pontualidade', 'acabamento', 'preparo_tecnico',
                'cuidado_com_o_espaco', 'clareza_do_combinado', 'tempo_de_execucao'
            ))
        ");

        /**
         * I23 — uma avaliação nunca é alterada após a criação.
         *
         * O gatilho recusa UPDATE e DELETE nas três tabelas. É a diferença
         * entre "a aplicação não altera" e "não é possível alterar": a
         * segunda continua valendo quando alguém escrever um caminho novo,
         * um comando de manutenção ou uma correção às pressas.
         */
        DB::unprepared("
            CREATE OR REPLACE FUNCTION avaliacao_e_imutavel()
            RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION
                    'Avaliação é imutável (I23, 02 §11.1): % em % foi recusado.',
                    TG_OP, TG_TABLE_NAME;
            END;
            $$ LANGUAGE plpgsql;
        ");

        foreach (['avaliacoes', 'notas_criterio', 'motivos_qualidade'] as $tabela) {
            DB::unprepared("
                CREATE TRIGGER {$tabela}_imutaveis
                BEFORE UPDATE OR DELETE ON {$tabela}
                FOR EACH ROW EXECUTE FUNCTION avaliacao_e_imutavel();
            ");
        }
    }

    public function down(): void
    {
        foreach (['avaliacoes', 'notas_criterio', 'motivos_qualidade'] as $tabela) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabela}_imutaveis ON {$tabela}");
        }

        DB::unprepared('DROP FUNCTION IF EXISTS avaliacao_e_imutavel()');

        Schema::dropIfExists('motivos_qualidade');
        Schema::dropIfExists('notas_criterio');
        Schema::dropIfExists('avaliacoes');
    }
};
