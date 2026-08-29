<?php

namespace App\Dominio\Negociacao;

use App\Models\ItemProposta;
use App\Models\Proposta;
use App\Models\Ticket;
use App\Models\Usuario;
use App\Models\VersaoProposta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Revisão da oferta durante a negociação (RF-NEG-08, RF-NEG-09).
 *
 * Revisar não altera a versão vigente: cria a próxima. A versão publicada é
 * imutável porque o aceite aponta para ela (I15) — se o conteúdo de uma
 * versão pudesse mudar, "aceitei a versão 2" deixaria de significar algo.
 *
 * A revisão também move a proposta para `EM_NEGOCIACAO` e abre o chat
 * (RF-NEG-07): a partir daí a decisão volta para a outra parte.
 */
class RevisarProposta
{
    public function __construct(private readonly VerificarComposicao $verificador = new VerificarComposicao())
    {
    }

    public function executar(
        Proposta $proposta,
        Usuario $autor,
        Composicao $composicao,
        ?Carbon $momento = null,
    ): VersaoProposta {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($proposta, $autor, $composicao, $momento) {
            $proposta = Proposta::query()
                ->whereKey($proposta->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $proposta->estaAtiva()) {
                throw new LogicException(
                    "A proposta está em \"{$proposta->estado}\" e não admite mais revisão."
                );
            }

            if (! $proposta->ehParte($autor)) {
                throw new LogicException('Somente as partes revisam a oferta.');
            }

            $versaoAtual = $proposta->versaoAtual();
            $bloqueados = $versaoAtual->itens()->pluck('ticket_id')->all();

            $tickets = $this->verificador->executar(
                $composicao,
                $proposta->remetente,
                $proposta->destinatario,
                bloqueadosPelaProposta: $bloqueados,
                momento: $momento,
            );

            $novaVersao = VersaoProposta::create([
                'proposta_id' => $proposta->id,
                'numero' => $proposta->versao_atual + 1,
                'criada_por_id' => $autor->id,
                'criada_em' => $momento,
            ]);

            foreach ($composicao->todos() as $ticketId) {
                ItemProposta::create([
                    'versao_proposta_id' => $novaVersao->id,
                    'ticket_id' => $ticketId,
                    'lado' => $composicao->lado($ticketId),
                ]);

                // Quem já estava preso continua preso; só o que entrou agora
                // muda de estado.
                if (! in_array($ticketId, $bloqueados, true)) {
                    $tickets->get($ticketId)->transicionar(
                        Ticket::EM_NEGOCIACAO,
                        'proposta_revisada',
                        $autor->id,
                    );
                }
            }

            // RF-NEG-11 aplicado à revisão: o ticket retirado da oferta volta
            // à carteira livre na hora. Prender ticket que ninguém mais está
            // negociando é confisco silencioso.
            $removidos = array_diff($bloqueados, $composicao->todos());

            foreach (Ticket::whereIn('id', $removidos)->get() as $ticket) {
                $ticket->transicionar(
                    Ticket::DISPONIVEL,
                    'retirado_da_proposta',
                    $autor->id,
                );
            }

            $proposta->forceFill([
                'estado' => Proposta::EM_NEGOCIACAO,
                'versao_atual' => $novaVersao->numero,
            ])->save();

            return $novaVersao;
        });
    }
}
