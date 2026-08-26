<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operação — a equipe da plataforma (04 §2).
 *
 * Tabela separada de `usuarios`, e não um campo de permissão, porque os dois
 * são atores distintos no domínio. A fronteira de 05 §3.1 é explícita: a
 * Operação DECIDE, e os contextos Ticket e Consumo EXECUTAM.
 *
 * Um usuário com poderes administrativos borraria essa fronteira e abriria
 * caminho para alterar um ticket por fora da máquina de estados. Além disso,
 * a trilha de auditoria (RF-ADM-10) precisa saber que agiu a Operação, e não
 * uma pessoa que também troca favores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operadores', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome');
            $tabela->string('email')->unique();
            $tabela->string('senha');
            $tabela->timestampTz('desativado_em')->nullable();
            $tabela->rememberToken();
            $tabela->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operadores');
    }
};
