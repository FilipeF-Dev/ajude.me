<?php

namespace App\Livewire;

use App\Dominio\Identidade\RedefinirSenha;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;

/**
 * Escolher senha nova a partir do link (RF-CAD-08).
 */
#[Layout('components.layouts.entrada')]
class EscolherSenha extends Component
{
    public string $token = '';

    public string $email = '';

    public string $senha = '';

    public string $confirmacao = '';

    public function mount(string $token = '', string $email = ''): void
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function redefinir(RedefinirSenha $redefinir): void
    {
        $this->validate(
            [
                'senha' => ['required', 'string', 'min:8'],
                'confirmacao' => ['required', 'same:senha'],
            ],
            [
                'senha.required' => 'Escolha uma senha.',
                'senha.min' => 'A senha precisa de pelo menos 8 caracteres.',
                'confirmacao.required' => 'Repita a senha.',
                'confirmacao.same' => 'As duas senhas precisam ser iguais.',
            ],
        );

        try {
            $redefinir->executar($this->email, $this->token, $this->senha);
        } catch (LogicException $erro) {
            $this->addError('senha', $erro->getMessage());

            return;
        }

        session()->flash('recado', 'Senha trocada. Entre com a senha nova.');

        $this->redirect(route('entrar'), navigate: true);
    }

    public function render()
    {
        return view('livewire.escolher-senha');
    }
}
