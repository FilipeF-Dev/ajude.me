<?php

namespace App\Dominio\Negociacao;

use App\Models\Proposta;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Encerramento sem troca: recusa, retirada ou vencimento
 * (RF-NEG-11, RF-NEG-12, RF-NEG-13).
 *
 * Os três desfechos têm o mesmo efeito material — a oferta morre e os tickets
 * voltam a circular — e por isso compartilham um caminho só. O que muda é
 * quem encerra e sob qual estado a proposta fica registrada.
 *
 * Liberar os tickets é obrigação, não cortesia: um ticket preso a uma
 * proposta morta é um direito que o dono não consegue exercer nem trocar,
 * e a validade continua correndo contra ele.
 */
class EncerrarProposta
{
    /** RF-NEG-12 — o remetente retira enquanto não houver aceite. */
    public function retirar(Proposta $proposta, Usuario $remetente, ?Carbon $momento = null): Proposta
    {
        if ($proposta->remetente_id !== $remetente->id) {
            throw new LogicException('Somente quem enviou a proposta pode retirá-la.');
        }

        return $this->encerrar($proposta, Proposta::RETIRADA, $remetente, $momento);
    }

    /** RF-NEG-06 — o destinatário recusa. */
    public function recusar(Proposta $proposta, Usuario $destinatario, ?Carbon $momento = null): Proposta
    {
        if ($proposta->destinatario_id !== $destinatario->id) {
            throw new LogicException('Somente quem recebeu a proposta pode recusá-la.');
        }

        return $this->encerrar($proposta, Proposta::RECUSADA, $destinatario, $momento);
    }

    /**
     * RF-NEG-13 — vencimento do prazo.
     *
     * Não tem ator: ninguém decidiu, o prazo passou. Por isso a transição do
     * ticket é registrada sem `ator_id` (P2 exige o rastro, não um culpado).
     */
    public function expirar(Proposta $proposta, ?Carbon $momento = null): Proposta
    {
        $momento ??= Carbon::now();

        if (! $proposta->estaVencida($momento)) {
            throw new LogicException('Esta proposta ainda está no prazo de resposta.');
        }

        return $this->encerrar($proposta, Proposta::EXPIRADA, null, $momento);
    }

    private function encerrar(
        Proposta $proposta,
        string $estadoFinal,
        ?Usuario $ator,
        ?Carbon $momento,
    ): Proposta {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($proposta, $estadoFinal, $ator, $momento) {
            $proposta = Proposta::query()
                ->whereKey($proposta->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $proposta->podeSerTransicionadaPara($estadoFinal)) {
                throw new LogicException(
                    "A proposta está em \"{$proposta->estado}\" e não pode passar a \"{$estadoFinal}\"."
                );
            }

            $ticketsBloqueados = $proposta->versaoAtual()?->itens()->pluck('ticket_id')->all() ?? [];

            $tickets = Ticket::query()
                ->whereIn('id', $ticketsBloqueados)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($tickets as $ticket) {
                // Só solta o que esta proposta prendeu. Um ticket que já saiu
                // de `EM_NEGOCIACAO` por outro caminho — expirou, por
                // exemplo — não é arrastado de volta.
                if ($ticket->estado !== Ticket::EM_NEGOCIACAO) {
                    continue;
                }

                $ticket->transicionar(Ticket::DISPONIVEL, "proposta_{$estadoFinal}", $ator?->id);
            }

            $proposta->forceFill([
                'estado' => $estadoFinal,
                'encerrada_em' => $momento,
            ])->save();

            return $proposta;
        });
    }
}
