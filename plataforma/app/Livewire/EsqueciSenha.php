<?php

namespace App\Livewire;

use App\Dominio\Identidade\SolicitarRecuperacaoSenha;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Pedir o link de recuperação (RF-CAD-08).
 *
 * A tela responde a mesma coisa em todos os casos, porque a ação também
 * responde a mesma coisa: e-mail que existe e e-mail que não existe precisam
 * ser indistinguíveis daqui de fora (NF-SEG-05).
 */
#[Layout('components.layouts.entrada')]
class EsqueciSenha extends Component
{
    public string $email = '';

    public bool $pedido = false;

    public function pedir(SolicitarRecuperacaoSenha $solicitar): void
    {
        $this->validate(
            ['email' => ['required', 'email']],
            [
                'email.required' => 'Digite o e-mail da sua conta.',
                'email.email' => 'Esse e-mail não parece certo.',
            ],
        );

        $solicitar->executar($this->email);

        $this->pedido = true;
    }

    public function render()
    {
        return view('livewire.esqueci-senha');
    }
}
