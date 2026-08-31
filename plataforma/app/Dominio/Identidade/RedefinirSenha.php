<?php

namespace App\Dominio\Identidade;

use App\Models\ParametroNegocio;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

/**
 * Troca a senha a partir do link recebido (RF-CAD-08).
 *
 * Aqui, ao contrário do pedido, recusar é obrigatório — mas a recusa é sempre
 * a mesma frase. Token inexistente, vencido ou errado dizem "link inválido ou
 * vencido", e nunca qual dos três: distinguir contaria a quem testa links o
 * que ele precisa saber para continuar testando.
 */
class RedefinirSenha
{
    public function executar(
        string $email,
        string $token,
        string $novaSenha,
        ?Carbon $momento = null,
    ): Usuario {
        $momento ??= Carbon::now();
        $email = mb_strtolower(trim($email));

        $recusa = fn () => throw new LogicException(
            'Link inválido ou vencido. Peça a recuperação outra vez.'
        );

        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($registro === null) {
            $recusa();
        }

        $validade = ParametroNegocio::inteiroEm(
            ParametroNegocio::RECUPERACAO_VALIDADE_MINUTOS,
            $momento,
        ) ?? 60;

        $vence = Carbon::parse($registro->created_at)->addMinutes($validade);

        if ($vence->lessThanOrEqualTo($momento)) {
            // Vencido some: não há razão para guardar credencial morta.
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            $recusa();
        }

        if (! Hash::check($token, $registro->token)) {
            $recusa();
        }

        $usuario = Usuario::where('email', $email)->first();

        if ($usuario === null) {
            $recusa();
        }

        return DB::transaction(function () use ($usuario, $email, $novaSenha) {
            $usuario->forceFill([
                'senha' => $novaSenha,
                // Quem trocou a senha derruba as sessões que ficaram abertas.
                // Se a conta foi tomada, é isto que expulsa quem a tomou.
                'remember_token' => Str::random(60),
            ])->save();

            // Uso único: o link não serve duas vezes.
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return $usuario;
        });
    }
}
