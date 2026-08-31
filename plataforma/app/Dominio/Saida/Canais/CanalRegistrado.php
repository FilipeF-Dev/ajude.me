<?php

namespace App\Dominio\Saida\Canais;

use App\Dominio\Saida\CanalDeSaida;
use App\Dominio\Saida\Mensagem;
use App\Models\Envio;
use Illuminate\Support\Facades\Log;

/**
 * O canal que não entrega — e diz isso.
 *
 * Serve enquanto não há provedor contratado (05 §15.1). Grava a mensagem,
 * escreve no log para quem estiver desenvolvendo, e **deixa o envio em
 * `registrado`**: nunca marca entregue.
 *
 * Essa recusa é o ponto. Um canal de mentira que marcasse "entregue" faria o
 * painel afirmar que a pessoa recebeu um código que nunca saiu daqui, e a
 * primeira vez que isso enganaria alguém seria durante o piloto, com gente
 * de verdade esperando uma mensagem.
 */
class CanalRegistrado implements CanalDeSaida
{
    public function entregar(Envio $envio, Mensagem $mensagem): void
    {
        Log::channel(config('saida.log'))->info('Mensagem registrada, não enviada.', [
            'envio' => $envio->id,
            'canal' => $mensagem->canal,
            'destino' => $mensagem->destino,
            'tipo' => $mensagem->tipo,
            'assunto' => $mensagem->assunto,
            'corpo' => $mensagem->corpo,
        ]);
    }

    public function entregaDeVerdade(): bool
    {
        return false;
    }
}
