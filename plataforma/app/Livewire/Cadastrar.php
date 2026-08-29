<?php

namespace App\Livewire;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Cadastro (RF-CAD-01 a RF-CAD-03).
 *
 * Pede o mínimo para existir na plataforma. Endereço, proficiências e
 * contato de confiança vêm depois, cada um no momento em que faz sentido —
 * pedir tudo de uma vez é a forma mais eficiente de perder a pessoa na
 * primeira tela.
 *
 * O termo de ciência **não** aparece aqui, por regra: ele é exibido no aceite
 * do atendimento presencial (02 §8.2). Termo apresentado no cadastro não é
 * lido, e portanto não conscientiza ninguém.
 */
#[Layout('components.layouts.entrada')]
class Cadastrar extends Component
{
    public string $nome = '';

    public string $email = '';

    public string $telefone = '';

    public string $cpf = '';

    public string $senha = '';

    public string $senha_confirmation = '';

    protected function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('usuarios', 'email')],
            'telefone' => ['required', 'string', 'min:10', 'max:20'],
            'cpf' => ['required', 'string'],
            'senha' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.unique' => 'Já existe uma conta com este e-mail.',
            'senha.confirmed' => 'As duas senhas precisam ser iguais.',
            'senha.min' => 'A senha precisa ter ao menos 8 caracteres.',
        ];
    }

    public function cadastrar(): void
    {
        $dados = $this->validate();

        $cpf = preg_replace('/\D/', '', $dados['cpf']);

        if (! $this->cpfEhValido($cpf)) {
            $this->addError('cpf', 'Este CPF não é válido.');

            return;
        }

        if (Usuario::where('cpf', $cpf)->exists()) {
            // RF-CAD-09 — um CPF, uma conta.
            $this->addError('cpf', 'Já existe uma conta com este CPF.');

            return;
        }

        $usuario = DB::transaction(fn () => Usuario::create([
            'nome' => trim($dados['nome']),
            'email' => mb_strtolower(trim($dados['email'])),
            'telefone' => $dados['telefone'],
            'cpf' => $cpf,
            'senha' => $dados['senha'],
        ]));

        Auth::login($usuario);
        session()->regenerate();

        // Carregamento completo, pela mesma razão da tela de entrada: a
        // travessia é entre layouts, e a sessão foi regenerada.
        $this->redirect(route('inicio'));
    }

    /**
     * Validação do dígito verificador.
     *
     * Não prova identidade — isso é o que a verificação de contato e, mais
     * tarde, a curadoria fazem (02 §12). Serve para barrar erro de digitação
     * antes que ele vire uma conta impossível de corrigir, já que o CPF é
     * imutável depois do cadastro (RF-CAD-10).
     */
    private function cpfEhValido(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        foreach ([9, 10] as $posicao) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $digito = ((10 * $soma) % 11) % 10;

            if ($digito !== (int) $cpf[$posicao]) {
                return false;
            }
        }

        return true;
    }

    public function render()
    {
        return view('livewire.cadastrar');
    }
}
