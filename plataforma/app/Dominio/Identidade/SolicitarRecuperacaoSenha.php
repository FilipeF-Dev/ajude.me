<?php

namespace App\Dominio\Identidade;

use App\Dominio\Saida\Despachar;
use App\Dominio\Saida\Mensagem;
use App\Models\ParametroNegocio;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pedido de recuperação de senha (RF-CAD-08).
 *
 * **Esta ação nunca falha, e isso é deliberado.**
 *
 * E-mail que não existe, conta desativada, pedido cedo demais: em todos os
 * casos ela termina em silêncio, e a tela diz sempre a mesma coisa. Qualquer
 * resposta diferente entre "existe" e "não existe" transforma o formulário em
 * consulta de cadastro — que é exatamente a enumeração de usuários que o
 * NF-SEG-05 proíbe.
 *
 * O preço é não poder avisar "espere um minuto" a quem pediu duas vezes. Vale
 * a pena: quem está tentando recuperar a própria conta reenvia; quem está
 * varrendo e-mails não descobre nada.
 */
class SolicitarRecuperacaoSenha
{
    public function __construct(
        private readonly Despachar $despachar,
    ) {
    }

    public function executar(string $email, ?Carbon $momento = null): void
    {
        $momento ??= Carbon::now();
        $email = mb_strtolower(trim($email));

        $usuario = Usuario::where('email', $email)->first();

        if ($usuario === null) {
            return;
        }

        // Conta encerrada ou suspensa não recupera senha — e também não diz
        // que não recupera.
        if (! in_array($usuario->estado_conta, [
            Usuario::ESTADO_ATIVA,
            Usuario::ESTADO_ENCERRANDO,
        ], true)) {
            return;
        }

        if ($this->pedidoRecente($email, $momento)) {
            return;
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                // Guardado cifrado: quem tiver o token entra na conta.
                'token' => Hash::make($token),
                'created_at' => $momento,
            ],
        );

        $this->despachar->executar(
            Mensagem::email(
                destino: $usuario->email,
                tipo: 'recuperacao_senha',
                assunto: 'Recuperar seu acesso ao Ajude.me',
                corpo: $this->corpo($usuario, $token),
                referencia: $usuario,
                // Pedir de novo é outro pedido: o token muda, e a mensagem
                // antiga não pode valer pela nova.
                chaveIdempotencia: 'recuperacao-'.$usuario->id.'-'.$momento->getTimestamp(),
            ),
            $momento,
        );
    }

    private function pedidoRecente(string $email, Carbon $momento): bool
    {
        $intervalo = ParametroNegocio::inteiroEm(
            ParametroNegocio::RECUPERACAO_INTERVALO_SEGUNDOS,
            $momento,
        ) ?? 60;

        $anterior = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->value('created_at');

        if ($anterior === null) {
            return false;
        }

        return Carbon::parse($anterior)->diffInSeconds($momento) < $intervalo;
    }

    private function corpo(Usuario $usuario, string $token): string
    {
        $link = route('redefinir-senha', [
            'token' => $token,
            'email' => $usuario->email,
        ]);

        return "Olá, {$usuario->nome}.\n\n"
            ."Para escolher uma senha nova, abra este endereço:\n{$link}\n\n"
            .'O link vale por pouco tempo. Se não foi você que pediu, pode ignorar '
            .'esta mensagem — sua senha continua a mesma.';
    }
}
