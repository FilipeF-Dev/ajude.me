<?php

namespace App\Dominio\Negociacao;

use App\Models\ItemProposta;
use App\Models\Ticket;
use LogicException;

/**
 * A composição de uma oferta: o que se pede e o que se dá em troca
 * (02 §5.4, RF-NEG-01, RF-NEG-02).
 *
 * Existe como objeto próprio porque a mesma composição é montada no envio,
 * remontada em cada revisão e reconferida no aceite. Sem um lugar único para
 * normalizar e comparar, as três leituras divergiriam.
 *
 * Guarda identificadores, não modelos: o que vale é o estado do ticket no
 * instante em que a operação acontece, lido sob bloqueio, e não uma cópia
 * carregada antes.
 */
final class Composicao
{
    /**
     * @param  array<int, int>  $solicitados     tickets que o remetente quer receber
     * @param  array<int, int>  $contrapartida   tickets que o remetente oferece
     */
    private function __construct(
        public readonly array $solicitados,
        public readonly array $contrapartida,
    ) {
    }

    /**
     * @param  array<int, Ticket|int>  $solicitados
     * @param  array<int, Ticket|int>  $contrapartida
     */
    public static function de(array $solicitados, array $contrapartida = []): self
    {
        $solicitados = self::identificadores($solicitados);
        $contrapartida = self::identificadores($contrapartida);

        // RF-NEG-01 — uma proposta sem nada solicitado não é proposta.
        // A contrapartida vazia, ao contrário, é legítima: pedir sem oferecer
        // é o caso de quem ainda não tem o que trocar, e a rede existe para
        // isso (02 §1).
        if ($solicitados === []) {
            throw new LogicException('A proposta precisa solicitar ao menos um ticket.');
        }

        $repetidos = array_intersect($solicitados, $contrapartida);

        if ($repetidos !== []) {
            throw new LogicException(
                'O mesmo ticket não pode ser solicitado e oferecido na mesma proposta.'
            );
        }

        return new self($solicitados, $contrapartida);
    }

    /** @return array<int, int> */
    public function todos(): array
    {
        return array_values(array_merge($this->solicitados, $this->contrapartida));
    }

    public function lado(int $ticketId): string
    {
        return in_array($ticketId, $this->solicitados, true)
            ? ItemProposta::SOLICITADO
            : ItemProposta::CONTRAPARTIDA;
    }

    /**
     * @param  array<int, Ticket|int>  $tickets
     * @return array<int, int>
     */
    private static function identificadores(array $tickets): array
    {
        $ids = array_map(
            fn (Ticket|int $ticket) => $ticket instanceof Ticket ? $ticket->id : $ticket,
            $tickets
        );

        $unicos = array_values(array_unique($ids));

        if (count($unicos) !== count($ids)) {
            throw new LogicException('O mesmo ticket foi informado duas vezes na composição.');
        }

        sort($unicos);

        return $unicos;
    }
}
