<?php

namespace App\Dominio\Negociacao;

use App\Dominio\Notificacao\Notificar;
use App\Models\ItemProposta;
use App\Models\Notificacao;
use App\Models\Proposta;
use App\Models\Ticket;
use App\Models\TransferenciaTicket;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Aceite da proposta e transferência atômica dos tickets
 * (RF-NEG-10, I16, 05 §7.6).
 *
 * Tudo o que o aceite provoca — mudança de proprietário de N tickets, N
 * registros de cadeia, liberação dos bloqueios e mudança de estado da
 * proposta — pertence a uma única transação. Efeitos externos, como
 * notificações, ficam para depois da confirmação, nunca dentro dela.
 *
 * A elegibilidade de cada ticket é reconferida **aqui**, sob bloqueio, e não
 * no envio (04 §9.2). O caso que isso resolve é o de 05 §7.1: duas propostas
 * distintas pedem o mesmo ticket, e o dono aceita as duas em sequência
 * rápida. A segunda falha dizendo qual ticket ficou indisponível.
 */
class AceitarProposta
{
    public function __construct(
        private readonly VerificarComposicao $verificador = new VerificarComposicao(),
        private readonly Notificar $notificacoes = new Notificar(),
    ) {
    }

    public function executar(
        Proposta $proposta,
        Usuario $aceitante,
        int $versaoAceita,
        ?Carbon $momento = null,
    ): Proposta {
        $momento ??= Carbon::now();

        return DB::transaction(function () use ($proposta, $aceitante, $versaoAceita, $momento) {
            $proposta = Proposta::query()
                ->whereKey($proposta->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $proposta->podeSerTransicionadaPara(Proposta::ACEITA)) {
                throw new LogicException(
                    "A proposta está em \"{$proposta->estado}\" e não pode ser aceita."
                );
            }

            if ($proposta->estaVencida($momento)) {
                throw new LogicException('O prazo de resposta desta proposta já venceu.');
            }

            if (! $proposta->podeSerAceitaPor($aceitante)) {
                throw new LogicException(
                    'Esta proposta não está aguardando resposta sua. '
                    .'Quem revisou a oferta por último aguarda a decisão da outra parte.'
                );
            }

            /**
             * I15 — o aceite vale para a versão que a pessoa viu.
             *
             * Se a outra parte revisou a oferta enquanto esta pessoa decidia,
             * o aceite recai sobre uma composição que não existe mais. Recusar
             * é o que dá sentido a RF-NEG-09: revisar invalida o aceite.
             */
            if ($versaoAceita !== $proposta->versao_atual) {
                throw new LogicException(
                    "A oferta foi revisada: você aceitou a versão {$versaoAceita}, "
                    ."e a vigente é a {$proposta->versao_atual}. Confira a composição atual."
                );
            }

            $versao = $proposta->versaoAtual();
            $itens = $versao->itens()->get();

            $composicao = Composicao::de(
                $itens->where('lado', ItemProposta::SOLICITADO)->pluck('ticket_id')->all(),
                $itens->where('lado', ItemProposta::CONTRAPARTIDA)->pluck('ticket_id')->all(),
            );

            $tickets = $this->verificador->executar(
                $composicao,
                $proposta->remetente,
                $proposta->destinatario,
                bloqueadosPelaProposta: $composicao->todos(),
                momento: $momento,
            );

            foreach ($itens as $item) {
                $ticket = $tickets->get($item->ticket_id);

                $destino = $item->lado === ItemProposta::SOLICITADO
                    ? $proposta->remetente
                    : $proposta->destinatario;

                // O bloqueio é desfeito antes da entrega: o ticket chega à
                // carteira nova livre para ser usado ou trocado de novo
                // (05 §6.1 — Proposta.ACEITA → ticket volta a DISPONIVEL).
                $ticket->transicionar(
                    Ticket::DISPONIVEL,
                    'proposta_aceita',
                    $aceitante->id,
                );

                $ticket->transferirPara($destino, TransferenciaTicket::MOTIVO_PROPOSTA_ACEITA);
            }

            $proposta->forceFill([
                'estado' => Proposta::ACEITA,
                'versao_aceita' => $versaoAceita,
                'encerrada_em' => $momento,
            ])->save();

            // RF-NOT-02 — quem propôs precisa saber o desfecho.
            $this->notificacoes->enviar(
                $proposta->contraparte($aceitante),
                Notificacao::PROPOSTA_RESPONDIDA,
                $proposta,
                ['desfecho' => Proposta::ACEITA],
                $momento,
            );

            return $proposta;
        });
    }
}
