<?php

namespace App\Livewire;

use App\Dominio\Reputacao\AvaliacaoPendente;
use App\Dominio\Reputacao\ConsultarReputacao;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Perfil próprio (04 §14).
 *
 * Mostra as duas reputações lado a lado, porque a mesma pessoa é Ajudante
 * quando oferece e Solicitante quando recebe (02 §1). Separá-las é o que
 * permite a alguém ser ótimo de um lado e estar começando do outro.
 */
#[Layout('components.layouts.app')]
class Perfil extends Component
{
    public function sair(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('entrar'));
    }

    public function render()
    {
        $usuario = auth()->user();
        $reputacao = app(ConsultarReputacao::class);

        return view('livewire.perfil', [
            'usuario' => $usuario,
            'comoAjudante' => $this->resumo($reputacao, $usuario, Solicitacao::PARTE_AJUDANTE),
            'comoSolicitante' => $this->resumo($reputacao, $usuario, Solicitacao::PARTE_SOLICITANTE),
            'porProficiencia' => $reputacao->porProficiencia($usuario),
            'prestados' => $reputacao->favoresPrestados($usuario),
            'recebidos' => $reputacao->favoresRecebidos($usuario),
            'dias' => $reputacao->diasDeParticipacao($usuario),
            'proficiencias' => $usuario->proficiencias()->with('proficiencia')->get(),
            'avaliacoesPendentes' => app(AvaliacaoPendente::class)->de($usuario),
        ]);
    }

    private function resumo(ConsultarReputacao $reputacao, Usuario $usuario, string $papel): array
    {
        return [
            'media' => $reputacao->mediaGeral($usuario, $papel),
            'suficiente' => $reputacao->temAvaliacoesSuficientes($usuario, $papel),
            'criterios' => $reputacao->mediasPorCriterio($usuario, $papel),
        ];
    }
}
