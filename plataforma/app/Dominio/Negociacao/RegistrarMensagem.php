<?php

namespace App\Dominio\Negociacao;

use App\Dominio\Notificacao\Notificar;
use App\Models\MensagemNegociacao;
use App\Models\Notificacao;
use App\Models\Proposta;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Mensagem no chat vinculado à proposta (RF-NEG-07).
 *
 * O chat existe enquanto a negociação existe: proposta encerrada não recebe
 * mensagem nova, mas as antigas continuam consultáveis pelo prazo de retenção
 * (05 §12). Conversa é prova, e prova apagada não serve a chamado nenhum.
 */
class RegistrarMensagem
{
    public function executar(
        Proposta $proposta,
        Usuario $autor,
        string $texto,
        ?Carbon $momento = null,
    ): MensagemNegociacao {
        if (! $proposta->ehParte($autor)) {
            throw new LogicException('Somente as partes conversam na negociação.');
        }

        if (! $proposta->estaAtiva()) {
            throw new LogicException(
                "A proposta está em \"{$proposta->estado}\": a conversa não recebe mais mensagens."
            );
        }

        $texto = trim($texto);

        if ($texto === '') {
            throw new LogicException('A mensagem não pode ser vazia.');
        }

        $momento ??= Carbon::now();

        $mensagem = MensagemNegociacao::create([
            'proposta_id' => $proposta->id,
            'autor_id' => $autor->id,
            'texto' => $texto,
            'enviada_em' => $momento,
        ]);

        // RF-NOT-04 — a conversa só serve se a contraparte souber que ela
        // aconteceu. O texto não vai na notificação: quem lê, lê no chat.
        (new Notificar())->enviar(
            $proposta->contraparte($autor),
            Notificacao::MENSAGEM_NA_NEGOCIACAO,
            $proposta,
            ['autor' => $autor->nome],
            $momento,
        );

        return $mensagem;
    }
}
