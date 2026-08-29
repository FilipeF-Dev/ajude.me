<?php

namespace App\Livewire;

use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A carteira (02 §7).
 *
 * **Não é saldo.** É a lista dos direitos que a pessoa tem agora, cada um
 * preso a um favor concreto e a alguém que se dispôs a prestá-lo. Não existe
 * total, nem soma, nem contador de valor — a ausência dessa estrutura é o
 * que impede a moeda interna de aparecer por conveniência (P8).
 *
 * A contagem por estado que aparece na tela é quantidade de direitos, não
 * quantia: "3 tickets" responde "quantos favores posso pedir", nunca
 * "quanto eu tenho".
 */
#[Layout('components.layouts.app')]
class Carteira extends Component
{
    public string $aba = Ticket::DISPONIVEL;

    public function mostrar(string $aba): void
    {
        $this->aba = $aba;
    }

    public function render()
    {
        $carteira = auth()->user()->carteira()
            ->with(['favor.proficienciaUsuario.usuario', 'favor.proficienciaUsuario.proficiencia'])
            ->orderBy('expira_em')
            ->get();

        return view('livewire.carteira', [
            'disponiveis' => $carteira->where('estado', Ticket::DISPONIVEL),
            'emUso' => $carteira->whereIn('estado', [
                Ticket::EM_NEGOCIACAO,
                Ticket::RESERVADO,
                Ticket::EM_ANALISE,
            ]),
            'encerrados' => $carteira->whereIn('estado', [
                Ticket::CONSUMIDO,
                Ticket::ENCERRADO,
                Ticket::EXPIRADO,
                Ticket::DESCARTADO,
            ]),
        ]);
    }
}
