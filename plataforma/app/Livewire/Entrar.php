<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Entrada na plataforma.
 *
 * O limite de tentativas é por endereço e por identificador (NF-SEG-06):
 * sem ele, a página de entrada é um oráculo de senhas.
 */
#[Layout('components.layouts.entrada')]
class Entrar extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $senha = '';

    public bool $lembrar = false;

    public function entrar(): void
    {
        $this->validate();

        $chave = 'entrar:'.mb_strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($chave, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Muitas tentativas. Tente de novo em '
                    .ceil(RateLimiter::availableIn($chave) / 60).' minuto(s).',
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->senha], $this->lembrar)) {
            RateLimiter::hit($chave, 300);

            // A mensagem não diz se o e-mail existe: dizer transformaria a
            // tela de entrada em uma lista de quem está cadastrado.
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha não conferem.',
            ]);
        }

        RateLimiter::clear($chave);
        session()->regenerate();

        // Carregamento completo, e não `navigate`: a entrada atravessa dois
        // layouts diferentes — o de quem não entrou não tem barra inferior —
        // e a sessão acabou de ser regenerada. Trocar só o corpo deixaria a
        // página sem navegação e com o token antigo.
        $this->redirect(route('inicio'));
    }

    public function render()
    {
        return view('livewire.entrar');
    }
}
