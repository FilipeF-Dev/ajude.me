<?php

namespace App\Livewire;

use App\Dominio\Negociacao\AceitarProposta;
use App\Dominio\Negociacao\EncerrarProposta;
use App\Dominio\Negociacao\RegistrarMensagem;
use App\Models\ItemProposta;
use App\Models\Proposta;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;

/**
 * A negociação de uma proposta (RF-NEG-06 a RF-NEG-12).
 *
 * O aceite informa **qual versão** está sendo aceita (I15). Se a outra parte
 * revisou a oferta enquanto esta pessoa decidia, o aceite recai sobre uma
 * composição que não existe mais — e é recusado. É o que dá sentido a
 * RF-NEG-09: revisar invalida o aceite.
 */
#[Layout('components.layouts.app')]
class Negociacao extends Component
{
    public Proposta $proposta;

    public string $mensagem = '';

    public function mount(Proposta $proposta): void
    {
        abort_unless($proposta->ehParte(auth()->user()), 403);

        $this->proposta = $proposta;
    }

    public function aceitar(): void
    {
        $this->executar(fn () => app(AceitarProposta::class)->executar(
            $this->proposta,
            auth()->user(),
            $this->proposta->versao_atual,
        ));
    }

    public function recusar(): void
    {
        $this->executar(fn () => app(EncerrarProposta::class)->recusar($this->proposta, auth()->user()));
    }

    public function retirar(): void
    {
        $this->executar(fn () => app(EncerrarProposta::class)->retirar($this->proposta, auth()->user()));
    }

    public function enviarMensagem(): void
    {
        if (trim($this->mensagem) === '') {
            return;
        }

        $this->executar(function () {
            app(RegistrarMensagem::class)->executar($this->proposta, auth()->user(), $this->mensagem);
            $this->mensagem = '';
        });
    }

    private function executar(callable $acao): void
    {
        try {
            $acao();
        } catch (LogicException $erro) {
            $this->addError('acao', $erro->getMessage());

            return;
        }

        $this->proposta = $this->proposta->fresh();
    }

    public function render()
    {
        $versao = $this->proposta->versaoAtual();
        $itens = $versao?->itens()->with('ticket.favor')->get() ?? collect();

        return view('livewire.negociacao', [
            'contraparte' => $this->proposta->contraparte(auth()->user()),
            'solicitados' => $itens->where('lado', ItemProposta::SOLICITADO),
            'contrapartida' => $itens->where('lado', ItemProposta::CONTRAPARTIDA),
            'mensagens' => $this->proposta->mensagens()->with('autor')->get(),
            'podeAceitar' => $this->proposta->podeSerAceitaPor(auth()->user()),
            'souRemetente' => $this->proposta->remetente_id === auth()->id(),
        ]);
    }
}
