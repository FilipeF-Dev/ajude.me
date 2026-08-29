<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Identidade — usuário.
 *
 * O domínio fala português (02 §Vocabulário), então a tabela é `usuarios`.
 * O modelo App\Models\Usuario aponta explicitamente para ela.
 *
 * Regras aplicadas aqui:
 *  - RF-CAD-03: CPF único, e a unicidade abrange contas desativadas.
 *  - RF-CAD-10: CPF imutável após o cadastro (garantido no modelo).
 *  - 04 §4.1: quatro estados de conta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome');
            $tabela->string('email')->unique();
            $tabela->string('telefone', 20);
            $tabela->string('cpf', 11)->unique();
            $tabela->string('senha');
            $tabela->string('estado_conta', 20)->default('ativa');
            $tabela->timestampTz('encerramento_solicitado_em')->nullable();
            $tabela->timestampTz('encerrada_em')->nullable();
            $tabela->string('foto_caminho')->nullable();
            $tabela->rememberToken();
            $tabela->timestampsTz();

            $tabela->index('estado_conta');
        });

        DB::statement("
            ALTER TABLE usuarios
            ADD CONSTRAINT usuarios_estado_conta_valido
            CHECK (estado_conta IN ('ativa', 'encerrando', 'desativada', 'suspensa'))
        ");

        DB::statement("
            ALTER TABLE usuarios
            ADD CONSTRAINT usuarios_cpf_apenas_digitos
            CHECK (cpf ~ '^[0-9]{11}$')
        ");

        Schema::create('password_reset_tokens', function (Blueprint $tabela) {
            $tabela->string('email')->primary();
            $tabela->string('token');
            $tabela->timestampTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $tabela) {
            $tabela->string('id')->primary();
            $tabela->foreignId('user_id')->nullable()->index();
            $tabela->string('ip_address', 45)->nullable();
            $tabela->text('user_agent')->nullable();
            $tabela->longText('payload');
            $tabela->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
