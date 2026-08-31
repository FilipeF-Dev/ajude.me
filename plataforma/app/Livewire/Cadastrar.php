<?php

namespace App\Livewire;

use App\Dominio\Identidade\GerirConvites;
use App\Models\Convite;
use App\Models\Proficiencia;
use App\Models\ProficienciaUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use LogicException;

/**
 * Cadastro (RF-CAD-01 a RF-CAD-03, RF-ADM-08).
 *
 * Pede o mínimo para existir na plataforma. Endereço e contato de confiança
 * vêm depois, cada um no momento em que fazem sentido — pedir tudo de uma vez
 * é a forma mais eficiente de perder a pessoa na primeira tela.
 *
 * Duas exceções a esse mínimo, e as duas têm motivo:
 *
 *  **O convite**, enquanto a base for restrita (06 §5.2). É segurança e é
 *  liquidez ao mesmo tempo: convidar concentra em vez de espalhar, e uma rede
 *  de troca depende de densidade geográfica (06 §2.2).
 *
 *  **Uma proficiência declarada**, que 06 R2 estabelece como mitigação do
 *  risco de rede assimétrica: *convite condicionado à declaração de ao menos
 *  uma proficiência*. Um MVP cheio de solicitantes e vazio de ajudantes é um
 *  MVP fracassado, mesmo com número alto de cadastro (03 §2, H5). Perguntar
 *  o que a pessoa sabe fazer **no cadastro** é o ponto mais barato de
 *  equilibrar isso.
 *
 * O termo de ciência **não** aparece aqui, por regra: é exibido no aceite do
 * atendimento presencial (02 §8.2). Termo apresentado no cadastro não é lido.
 */
#[Layout('components.layouts.entrada')]
class Cadastrar extends Component
{
    #[Url(as: 'convite', except: '')]
    public string $codigoConvite = '';

    public string $nome = '';

    public string $email = '';

    public string $telefone = '';

    public string $cpf = '';

    public string $senha = '';

    public string $senha_confirmation = '';

    public ?int $proficienciaId = null;

    public string $nivel = ProficienciaUsuario::NIVEL_INTERMEDIARIO;

    public bool $exigeConvite = true;

    public function mount(): void
    {
        $this->exigeConvite = app(GerirConvites::class)->cadastroExigeConvite();
    }

    protected function rules(): array
    {
        return [
            'codigoConvite' => $this->exigeConvite ? ['required', 'string'] : ['nullable'],
            'nome' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('usuarios', 'email')],
            'telefone' => ['required', 'string', 'min:10', 'max:20'],
            'cpf' => ['required', 'string'],
            'senha' => ['required', 'string', 'min:8', 'confirmed'],
            // 06 R2 — a declaração é condição do convite, não item opcional.
            'proficienciaId' => ['required', 'exists:proficiencias,id'],
            'nivel' => ['required', 'in:'.implode(',', array_keys(ProficienciaUsuario::niveis()))],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.unique' => 'Já existe uma conta com este e-mail.',
            'senha.confirmed' => 'As duas senhas precisam ser iguais.',
            'senha.min' => 'A senha precisa ter ao menos 8 caracteres.',
            'codigoConvite.required' => 'O cadastro está aberto só por convite.',
            'proficienciaId.required' => 'Escolha ao menos uma coisa que você sabe fazer.',
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

        $convites = app(GerirConvites::class);
        $convite = null;

        if ($this->exigeConvite) {
            try {
                $convite = $convites->conferir($this->codigoConvite);
            } catch (LogicException $erro) {
                $this->addError('codigoConvite', $erro->getMessage());

                return;
            }
        }

        try {
            $usuario = DB::transaction(function () use ($dados, $cpf, $convite, $convites) {
                $usuario = Usuario::create([
                    'nome' => trim($dados['nome']),
                    'email' => mb_strtolower(trim($dados['email'])),
                    'telefone' => $dados['telefone'],
                    'cpf' => $cpf,
                    'senha' => $dados['senha'],
                ]);

                ProficienciaUsuario::create([
                    'usuario_id' => $usuario->id,
                    'proficiencia_id' => $dados['proficienciaId'],
                    'nivel' => $dados['nivel'],
                    'declarada_em' => now(),
                ]);

                // Resgatado dentro da transação: se o cadastro falhar, o
                // convite continua valendo.
                $convite !== null && $convites->resgatar($convite, $usuario);

                return $usuario;
            });
        } catch (LogicException $erro) {
            $this->addError('codigoConvite', $erro->getMessage());

            return;
        }

        Auth::login($usuario);
        session()->regenerate();

        // Carregamento completo: a travessia é entre layouts, e a sessão foi
        // regenerada.
        $this->redirect(route('inicio'));
    }

    /**
     * Validação do dígito verificador.
     *
     * Não prova identidade — isso é o que a verificação de contato e a
     * curadoria fazem (02 §12). Serve para barrar erro de digitação antes que
     * ele vire uma conta impossível de corrigir, já que o CPF é imutável
     * depois do cadastro (RF-CAD-10).
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
        return view('livewire.cadastrar', [
            'proficiencias' => Proficiencia::query()
                ->where('ativa', true)
                ->where('restrita', false)
                ->with('subcategoria.categoria')
                ->orderBy('nome')
                ->get()
                ->groupBy(fn (Proficiencia $p) => $p->subcategoria->categoria->nome),
            'niveis' => ProficienciaUsuario::niveis(),
        ]);
    }
}
