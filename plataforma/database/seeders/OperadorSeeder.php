<?php

namespace Database\Seeders;

use App\Models\Operador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

/**
 * Cria o primeiro integrante da Operação para desenvolvimento local.
 *
 * Não roda em produção: lá o operador é criado deliberadamente, com senha
 * escolhida por quem vai usá-la. Semear credencial conhecida em ambiente real
 * seria uma porta aberta.
 */
class OperadorSeeder extends Seeder
{
    public function run(): void
    {
        if (App::environment('production')) {
            $this->command?->warn('OperadorSeeder ignorado em produção.');

            return;
        }

        $email = env('OPERADOR_EMAIL', 'operacao@ajude.me');

        if (Operador::where('email', $email)->exists()) {
            return;
        }

        Operador::create([
            'nome' => env('OPERADOR_NOME', 'Operação'),
            'email' => $email,
            'senha' => env('OPERADOR_SENHA', 'ajudeme'),
        ]);

        $this->command?->info("Operador criado: {$email}");
    }
}
