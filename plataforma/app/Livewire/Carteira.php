<?php

namespace App\Livewire;

use App\Models\Solicitacao;
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
            // Os atendimentos vivos aparecem acima dos tickets: é o que exige
            // ação hoje. Os dois papéis juntos, porque a mesma pessoa atende e
            // é atendida (02 §1).
            'atendimentos' => Solicitacao::query()
                ->where(fn ($consulta) => $consulta
                    ->where('ajudante_id', auth()->id())
                    ->orWhere('solicitante_id', auth()->id()))
                ->whereIn('estado', [
                    Solicitacao::SOLICITADA,
                    Solicitacao::AGENDADA,
                    Solicitacao::AGUARDANDO_CONFIRMACAO,
                    Solicitacao::EM_ANALISE,
                    Solicitacao::REALIZADA,
                ])
                ->with(['ticket.favor', 'ajudante', 'solicitante'])
                ->orderBy('inicio_previsto')
                ->get(),
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
