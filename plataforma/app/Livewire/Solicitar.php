<?php

namespace App\Livewire;

use App\Dominio\Consumo\SolicitarFavor;
use App\Models\Favor;
use App\Models\TermoCiencia;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;

/**
 * Pedir o favor com o ticket que se tem (RF-SOL-01 a RF-SOL-03).
 *
 * O termo de ciência aparece **aqui**, e não no cadastro (02 §8.2): é no
 * aceite do atendimento presencial que ele cumpre a função de conscientizar.
 * Um termo apresentado na criação da conta não é lido.
 */
#[Layout('components.layouts.app')]
class Solicitar extends Component
{
    public Ticket $ticket;

    public string $data = '';

    public string $hora = '09:00';

    public bool $cienteDosRiscos = false;

    public function mount(Ticket $ticket): void
    {
        abort_unless($ticket->proprietario_id === auth()->id(), 403);

        $this->ticket = $ticket->load('favor.proficienciaUsuario.usuario', 'favor.janelas');

        // A primeira data possível já respeita a antecedência publicada.
        $this->data = now()
            ->addDays($this->ticket->favor->antecedencia_minima_dias + 1)
            ->format('Y-m-d');
    }

    public function pedir(): void
    {
        $this->validate([
            'data' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
        ]);

        try {
            $solicitacao = app(SolicitarFavor::class)->executar(
                $this->ticket,
                auth()->user(),
                Carbon::parse("{$this->data} {$this->hora}"),
                cienteDosRiscos: $this->cienteDosRiscos,
            );
        } catch (LogicException $erro) {
            $this->addError('pedido', $erro->getMessage());

            return;
        }

        session()->flash('solicitado', 'Pedido enviado. Agora é aguardar a confirmação.');

        $this->redirectRoute('carteira', navigate: true);
    }

    public function render()
    {
        return view('livewire.solicitar', [
            'favor' => $this->ticket->favor,
            'ehPresencial' => $this->ticket->favor->modalidade === Favor::MODALIDADE_PRESENCIAL,
            'termo' => TermoCiencia::vigente(),
        ]);
    }
}
