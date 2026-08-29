<?php

namespace App\Livewire;

use App\Dominio\Reputacao\ConsultarReputacao;
use App\Models\Favor;
use App\Models\Solicitacao;
use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A página do favor (RF-BUS-08).
 *
 * Exibe **todas** as condições antes de qualquer negociação. A regra existe
 * para evitar alegação posterior de desconhecimento (02 §8): quem adquire o
 * ticket precisa ter lido o que está adquirindo, e depois da primeira emissão
 * nada disso muda (P5).
 */
#[Layout('components.layouts.app')]
class PaginaFavor extends Component
{
    public Favor $favor;

    public function mount(Favor $favor): void
    {
        $this->favor = $favor->load([
            'proficienciaUsuario.usuario',
            'proficienciaUsuario.proficiencia.subcategoria.categoria',
            'janelas',
        ]);
    }

    public function render()
    {
        $ajudante = $this->favor->proficienciaUsuario->usuario;
        $reputacao = app(ConsultarReputacao::class);

        return view('livewire.pagina-favor', [
            'ajudante' => $ajudante,
            'disponiveis' => Ticket::query()
                ->where('favor_id', $this->favor->id)
                ->where('estado', Ticket::DISPONIVEL)
                ->count(),
            'media' => $reputacao->mediaGeral($ajudante, Solicitacao::PARTE_AJUDANTE),
            'temReputacao' => $reputacao->temAvaliacoesSuficientes($ajudante, Solicitacao::PARTE_AJUDANTE),
            'favoresPrestados' => $reputacao->favoresPrestados($ajudante),
        ]);
    }
}
