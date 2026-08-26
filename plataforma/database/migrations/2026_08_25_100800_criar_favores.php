<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Oferta — o favor (02 §4).
 *
 * Duas invariantes ficam a cargo do banco, não do código:
 *
 *  I9  — todo favor referencia proficiência declarada PELO PRÓPRIO ajudante.
 *        Garantido pela chave estrangeira apontar para `proficiencias_usuario`,
 *        que já une usuário e proficiência. Não existe combinação inválida a
 *        validar: ela é inexprimível no esquema.
 *
 *  I10 — proficiência com favor vinculado não pode ser removida.
 *        Garantido por `restrictOnDelete`. O banco recusa, qualquer que seja
 *        o caminho da aplicação.
 *
 * O ajudante NÃO é coluna daqui: sai de `proficiencia_usuario`. Duplicá-lo
 * abriria espaço para os dois divergirem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favores', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('proficiencia_usuario_id')
                ->constrained('proficiencias_usuario')
                ->restrictOnDelete();

            $tabela->string('titulo');
            $tabela->text('descricao');
            $tabela->unsignedInteger('tempo_estimado_minutos');
            $tabela->string('modalidade', 20);
            $tabela->unsignedSmallInteger('area_atendimento_km')->nullable();
            $tabela->unsignedSmallInteger('antecedencia_minima_dias');
            $tabela->unsignedSmallInteger('validade_ticket_dias');
            $tabela->unsignedSmallInteger('maximo_circulacao');
            $tabela->boolean('trocavel')->default(true);
            $tabela->text('condicoes')->nullable();

            $tabela->string('estado', 20)->default('rascunho');

            // RF-FAV-22 — o nível vira condição histórica no congelamento.
            // Enquanto nulo, o descritor deriva da proficiência declarada.
            $tabela->string('nivel_congelado', 20)->nullable();
            $tabela->timestampTz('congelado_em')->nullable();
            $tabela->timestampTz('desativado_em')->nullable();

            $tabela->timestampsTz();

            $tabela->index(['estado', 'modalidade']);
        });

        DB::statement("
            ALTER TABLE favores
            ADD CONSTRAINT favores_modalidade_valida
            CHECK (modalidade IN ('presencial', 'remoto'))
        ");

        DB::statement("
            ALTER TABLE favores
            ADD CONSTRAINT favores_estado_valido
            CHECK (estado IN ('rascunho', 'ativo', 'desativado'))
        ");

        DB::statement("
            ALTER TABLE favores
            ADD CONSTRAINT favores_nivel_congelado_valido
            CHECK (nivel_congelado IS NULL OR nivel_congelado IN
                ('basico', 'intermediario', 'avancado', 'especialista'))
        ");

        // Presencial exige área de atendimento; remoto não a admite.
        DB::statement("
            ALTER TABLE favores
            ADD CONSTRAINT favores_area_coerente_com_modalidade
            CHECK (
                (modalidade = 'presencial' AND area_atendimento_km IS NOT NULL)
                OR (modalidade = 'remoto' AND area_atendimento_km IS NULL)
            )
        ");

        DB::statement('
            ALTER TABLE favores
            ADD CONSTRAINT favores_numeros_positivos
            CHECK (
                tempo_estimado_minutos > 0
                AND validade_ticket_dias > 0
                AND maximo_circulacao > 0
            )
        ');

        // Teto rígido de validade. O valor de negócio vive em
        // ParametroNegocio (365 dias hoje) e é aplicado no modelo; este CHECK
        // é a rede de segurança, porque restrição de banco não lê tabela.
        DB::statement('
            ALTER TABLE favores
            ADD CONSTRAINT favores_validade_teto_absoluto
            CHECK (validade_ticket_dias <= 3650)
        ');

        // Congelamento é par: ou os dois campos estão preenchidos, ou nenhum.
        DB::statement('
            ALTER TABLE favores
            ADD CONSTRAINT favores_congelamento_completo
            CHECK (
                (congelado_em IS NULL AND nivel_congelado IS NULL)
                OR (congelado_em IS NOT NULL AND nivel_congelado IS NOT NULL)
            )
        ');

        Schema::create('janelas_disponibilidade', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('favor_id')->constrained('favores')->cascadeOnDelete();
            $tabela->unsignedTinyInteger('dia_semana'); // 0 = domingo
            $tabela->time('hora_inicio');
            $tabela->time('hora_fim');
            $tabela->timestampsTz();

            $tabela->unique(['favor_id', 'dia_semana', 'hora_inicio']);
        });

        DB::statement('
            ALTER TABLE janelas_disponibilidade
            ADD CONSTRAINT janelas_dia_semana_valido
            CHECK (dia_semana BETWEEN 0 AND 6)
        ');

        DB::statement('
            ALTER TABLE janelas_disponibilidade
            ADD CONSTRAINT janelas_horario_coerente
            CHECK (hora_fim > hora_inicio)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('janelas_disponibilidade');
        Schema::dropIfExists('favores');
    }
};
