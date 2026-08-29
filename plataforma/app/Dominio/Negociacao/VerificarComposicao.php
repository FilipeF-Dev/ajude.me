<?php

namespace App\Dominio\Negociacao;

use App\Models\Bloqueio;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use LogicException;

/**
 * Confere se uma composição pode existir agora, e devolve os tickets travados.
 *
 * A mesma verificação serve ao envio, à revisão e ao aceite — por exigência
 * de `04 §9.2`: a elegibilidade de cada ticket é apurada **no instante do
 * aceite**, não apenas no envio. Ter dois verificadores, um para cada momento,
 * seria a forma mais provável de eles divergirem com o tempo.
 *
 * Os tickets são travados em ordem de identificador. Duas propostas que
 * disputem o mesmo par de tickets adquirem os bloqueios na mesma sequência e
 * uma espera pela outra, em vez de as duas se travarem mutuamente (05 §7.1).
 */
class VerificarComposicao
{
    /**
     * @param  array<int, int>  $bloqueadosPelaProposta  tickets que esta proposta já prende
     * @return Collection<int, Ticket>                   indexada por id, travada para escrita
     */
    public function executar(
        Composicao $composicao,
        Usuario $remetente,
        Usuario $destinatario,
        array $bloqueadosPelaProposta = [],
        ?Carbon $momento = null,
    ): Collection {
        $momento ??= Carbon::now();

        // RF-NEG-16 — conta que não está Ativa não negocia, dos dois lados.
        foreach ([$remetente, $destinatario] as $parte) {
            if (! $parte->podeNegociar()) {
                throw new LogicException(
                    "A conta de {$parte->nome} não está ativa e não pode participar de uma troca."
                );
            }
        }

        /**
         * I17 e RF-NEG-14 — não existe proposta entre usuários com bloqueio
         * em qualquer direção.
         *
         * A mensagem não diz quem bloqueou quem: revelar isso entregaria a
         * decisão de uma pessoa à outra, que é justamente o que o bloqueio
         * evita (02 §18.2).
         */
        if (Bloqueio::existeEntre($remetente->id, $destinatario->id)) {
            throw new LogicException('Não é possível negociar com este usuário.');
        }

        $tickets = Ticket::query()
            ->whereIn('id', $composicao->todos())
            ->with('favor')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($composicao->todos() as $id) {
            $ticket = $tickets->get($id);

            if ($ticket === null) {
                throw new LogicException("O ticket {$id} não existe.");
            }

            $ehSolicitado = in_array($id, $composicao->solicitados, true);
            $dono = $ehSolicitado ? $destinatario : $remetente;

            if ($ticket->proprietario_id !== $dono->id) {
                throw new LogicException(
                    "O ticket {$ticket->codigo} não está na carteira de {$dono->nome}."
                );
            }

            // I13 e RF-NEG-03 — ticket não trocável não muda de carteira.
            // A regra vale para os dois lados: `trocavel` governa a
            // transferência do ticket (02 §5.3), não apenas o lado em que ele
            // aparece na proposta.
            if (! $ticket->ehTrocavel()) {
                throw new LogicException(
                    "O favor do ticket {$ticket->codigo} não admite troca."
                );
            }

            /**
             * I14 — um ticket não participa de duas propostas ativas.
             *
             * Quem já está preso por esta proposta continua em `EM_NEGOCIACAO`;
             * qualquer outro precisa estar `DISPONIVEL`. Um ticket em
             * `EM_NEGOCIACAO` que não conste da lista está preso por outra
             * proposta, e é exatamente o caso que a invariante barra.
             */
            $estadoExigido = in_array($id, $bloqueadosPelaProposta, true)
                ? Ticket::EM_NEGOCIACAO
                : Ticket::DISPONIVEL;

            if ($ticket->estado !== $estadoExigido) {
                throw new LogicException(
                    "O ticket {$ticket->codigo} está em \"{$ticket->estado}\" e não pode "
                    .'compor esta proposta agora.'
                );
            }

            // 02 §5.4 — ticket cuja validade é atingida durante a negociação
            // expira, e a proposta cai junto. Não se transfere direito vencido.
            if ($ticket->expira_em->lessThanOrEqualTo($momento)) {
                throw new LogicException(
                    "A validade do ticket {$ticket->codigo} já passou."
                );
            }
        }

        return $tickets;
    }
}
