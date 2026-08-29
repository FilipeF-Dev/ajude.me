<?php

namespace App\Livewire;

use App\Dominio\Negociacao\Composicao;
use App\Dominio\Negociacao\EnviarProposta;
use App\Models\Favor;
use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;

/**
 * Montar e enviar uma proposta de troca (RF-NEG-01, RF-NEG-02).
 *
 * A contrapartida sai da carteira de quem propõe, e pode ser **vazia**
 * (02 §5.4): pedir sem ter o que oferecer é o caso de quem ainda não entrou
 * na rede de troca, e é justamente para ele que a plataforma existe.
 */
#[Layout('components.layouts.app')]
class Propor extends Component
{
    public Favor $favor;

    /** @var array<int, int> tickets deste favor que quero receber */
    public array $solicitados = [];

    /** @var array<int, int> tickets da minha carteira que ofereço */
    public array $contrapartida = [];

    public function mount(Favor $favor): void
    {
        $this->favor = $favor->load('proficienciaUsuario.usuario');

        // Um ticket já vem marcado: é o caso comum, e a proposta exige ao
        // menos um solicitado de qualquer forma.
        $primeiro = $this->disponiveisDoFavor()->first();

        if ($primeiro !== null) {
            $this->solicitados = [$primeiro->id];
        }
    }

    public function enviar(): void
    {
        try {
            $proposta = app(EnviarProposta::class)->executar(
                auth()->user(),
                $this->favor->proficienciaUsuario->usuario,
                Composicao::de($this->solicitados, $this->contrapartida),
            );
        } catch (LogicException $erro) {
            $this->addError('proposta', $erro->getMessage());

            return;
        }

        $this->redirectRoute('negociacao', ['proposta' => $proposta], navigate: true);
    }

    private function disponiveisDoFavor()
    {
        return Ticket::query()
            ->where('favor_id', $this->favor->id)
            ->where('estado', Ticket::DISPONIVEL)
            ->orderBy('expira_em')
            ->get();
    }

    public function render()
    {
        return view('livewire.propor', [
            'doFavor' => $this->disponiveisDoFavor(),
            'minhaCarteira' => auth()->user()->carteira()
                ->where('estado', Ticket::DISPONIVEL)
                ->with('favor.proficienciaUsuario.usuario')
                ->get()
                // I13 — ticket não trocável não circula por proposta.
                ->filter(fn (Ticket $ticket) => $ticket->ehTrocavel()),
        ]);
    }
}
