<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contato de confiança (02 §8.2, RF-SEG-01).
 *
 * ATENÇÃO: esta tabela guarda dados de uma pessoa que NÃO é usuária da
 * plataforma e nunca aceitou termo nenhum. É a questão de privacidade mais
 * delicada do produto (05 §11.3).
 *
 * Por isso os campos de consentimento existem desde a primeira versão:
 *  - `permissao_declarada_em`: o usuário afirma ter autorização da pessoa.
 *  - `avisado_em`: quando o contato recebeu a primeira mensagem explicativa.
 *  - `recusado_em`: quando pediu para não receber mais. A recusa desliga o
 *    vínculo e a pessoa deixa de ser contato de confiança de qualquer usuário.
 *
 * A base legal continua pendente de definição jurídica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contatos_confianca', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->string('nome');
            $tabela->string('telefone', 20);
            $tabela->timestampTz('permissao_declarada_em');
            $tabela->timestampTz('avisado_em')->nullable();
            $tabela->timestampTz('recusado_em')->nullable();
            $tabela->timestampsTz();

            $tabela->unique('usuario_id');
            $tabela->index('telefone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatos_confianca');
    }
};
