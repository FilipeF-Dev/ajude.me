<?php

namespace App\Livewire;

use App\Dominio\Descoberta\BuscarPorNecessidade;
use App\Models\AvisoNecessidade;
use App\Models\Proficiencia;
use Livewire\Component;

/**
 * RF-BUS-22 — entrar na fila de aviso de uma proficiência.
 *
 * O aviso vincula-se à proficiência e à região, jamais ao texto digitado
 * (RF-BUS-29). E ninguém do outro lado é avisado de que alguém procurou
 * (RF-BUS-27): a fila é um pedido de quem espera, não uma lista de demanda
 * para quem oferta.
 */
// Sem `#[Layout]`: é componente aninhado, renderizado dentro do Início.
class PedirAviso extends Component
{
    public Proficiencia $proficiencia;

    public bool $naFila = false;

    public function mount(): void
    {
        $this->naFila = AvisoNecessidade::query()
            ->where('usuario_id', auth()->id())
            ->where('proficiencia_id', $this->proficiencia->id)
            ->whereNull('atendido_em')
            ->exists();
    }

    public function pedir(): void
    {
        app(BuscarPorNecessidade::class)->pedirAviso(auth()->user(), $this->proficiencia);

        $this->naFila = true;
    }

    public function render()
    {
        return view('livewire.pedir-aviso');
    }
}
