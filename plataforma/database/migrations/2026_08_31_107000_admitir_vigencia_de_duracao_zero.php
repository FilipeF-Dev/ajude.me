<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Admite vigência de duração zero nos parâmetros de negócio.
 *
 * A restrição original exigia `vigente_ate > vigente_de`. Como as colunas
 * guardam segundos inteiros, isso tornava **impossível** trocar o valor de um
 * parâmetro no mesmo segundo em que ele foi criado — e esse é justamente o
 * caso de preparar um piloto: semear os parâmetros e ajustar um deles em
 * seguida.
 *
 * Uma vigência de duração zero não é incoerente. Ela diz uma coisa precisa:
 * **este valor nunca chegou a governar nada.** É o registro fiel de um valor
 * corrigido no mesmo instante em que foi posto.
 *
 * A consulta de `valorEm` já a ignora sozinha, porque exige
 * `vigente_de <= momento < vigente_ate` — um intervalo vazio nunca contém
 * momento algum. O que a restrição precisa impedir continua impedido:
 * vigência que termina **antes** de começar.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE parametros_negocio
            DROP CONSTRAINT parametros_negocio_vigencia_coerente
        ');

        DB::statement('
            ALTER TABLE parametros_negocio
            ADD CONSTRAINT parametros_negocio_vigencia_coerente
            CHECK (vigente_ate IS NULL OR vigente_ate >= vigente_de)
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE parametros_negocio
            DROP CONSTRAINT parametros_negocio_vigencia_coerente
        ');

        DB::statement('
            ALTER TABLE parametros_negocio
            ADD CONSTRAINT parametros_negocio_vigencia_coerente
            CHECK (vigente_ate IS NULL OR vigente_ate > vigente_de)
        ');
    }
};
