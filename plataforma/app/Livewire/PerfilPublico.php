<?php

namespace App\Livewire;

use App\Dominio\Reputacao\ConsultarReputacao;
use App\Models\Bloqueio;
use App\Models\Favor;
use App\Models\Solicitacao;
use App\Models\Ticket;
use App\Models\Usuario;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Perfil público de outra pessoa (RF-BUS-13).
 *
 * Mostra o que 02 §18.1 permite antes de qualquer proposta: nome, região
 * aproximada, reputação e favores ativos. Telefone e endereço exato **nunca**
 * são públicos — só são liberados após o agendamento, e às partes.
 */
#[Layout('components.layouts.app')]
class PerfilPublico extends Component
{
    public Usuario $usuario;

    public function mount(Usuario $usuario): void
    {
        // RF-BUS-11 — o bloqueio oculta mutuamente os perfis.
        abort_if(Bloqueio::existeEntre(auth()->id(), $usuario->id), 404);

        $this->usuario = $usuario;
    }

    public function render()
    {
        $reputacao = app(ConsultarReputacao::class);

        return view('livewire.perfil-publico', [
            'media' => $reputacao->mediaGeral($this->usuario, Solicitacao::PARTE_AJUDANTE),
            'suficiente' => $reputacao->temAvaliacoesSuficientes($this->usuario, Solicitacao::PARTE_AJUDANTE),
            'porProficiencia' => $reputacao->porProficiencia($this->usuario),
            'prestados' => $reputacao->favoresPrestados($this->usuario),
            'dias' => $reputacao->diasDeParticipacao($this->usuario),
            'favores' => Favor::query()
                ->whereIn('proficiencia_usuario_id', $this->usuario->proficiencias()->pluck('id'))
                ->where('estado', Favor::ESTADO_ATIVO)
                ->whereExists(fn ($sub) => $sub
                    ->selectRaw('1')
                    ->from('tickets')
                    ->whereColumn('tickets.favor_id', 'favores.id')
                    ->where('tickets.estado', Ticket::DISPONIVEL))
                ->with('proficienciaUsuario.usuario')
                ->get(),
        ]);
    }
}
