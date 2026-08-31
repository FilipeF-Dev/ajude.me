<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O código de verificação passa a ser guardado cifrado.
 *
 * Ele é uma credencial de uso único: quem o tiver confirma um contato que não
 * é seu. Guardar em texto puro o coloca ao alcance de qualquer vazamento de
 * banco, e a curta validade não é proteção suficiente — é atenuante.
 *
 * A coluna precisa caber um hash, daí o alargamento. Os códigos pendentes são
 * descartados: não há como cifrar o que já foi gravado sem conhecer o valor,
 * e pedir outro código é o caminho normal de quem não recebeu o primeiro.
 *
 * **Consequência a encarar antes do provedor real:** `envios.corpo` guarda a
 * mensagem inteira, com o código legível. Hoje isso é deliberado — é assim
 * que se completa um cadastro sem canal de saída. Quando houver provedor,
 * essa coluna precisa de política de retenção própria.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('verificacoes_contato')
            ->whereNull('verificado_em')
            ->update(['codigo' => null, 'codigo_expira_em' => null]);

        Schema::table('verificacoes_contato', function (Blueprint $tabela) {
            $tabela->string('codigo', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('verificacoes_contato', function (Blueprint $tabela) {
            $tabela->string('codigo', 10)->nullable()->change();
        });
    }
};
