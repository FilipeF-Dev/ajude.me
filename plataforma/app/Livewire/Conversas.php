<?php

namespace App\Livewire;

use App\Models\Proposta;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * As negociações em curso e as encerradas (RF-NEG-17).
 *
 * Uma lista só, com os dois lados juntos: quem enviou e quem recebeu estão
 * na mesma conversa, e separá-las em abas por papel reforçaria uma divisão
 * que o produto não tem (02 §1).
 */
#[Layout('components.layouts.app')]
class Conversas extends Component
{
    public function render()
    {
        $usuario = auth()->user();

        $propostas = Proposta::query()
            ->where(fn ($consulta) => $consulta
                ->where('remetente_id', $usuario->id)
                ->orWhere('destinatario_id', $usuario->id))
            ->with(['remetente', 'destinatario'])
            ->orderByRaw('CASE WHEN estado IN (?, ?) THEN 0 ELSE 1 END', [
                Proposta::ENVIADA,
                Proposta::EM_NEGOCIACAO,
            ])
            ->orderByDesc('updated_at')
            ->get();

        return view('livewire.conversas', ['propostas' => $propostas]);
    }
}
