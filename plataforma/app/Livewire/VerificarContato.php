<?php

namespace App\Livewire;

use App\Dominio\Identidade\ConfirmarVerificacao;
use App\Dominio\Identidade\SolicitarVerificacao;
use App\Models\Usuario;
use App\Models\VerificacaoContato;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;

/**
 * Confirmar e-mail e telefone (RF-CAD-04 a RF-CAD-06).
 *
 * A tela não reimplementa regra: pede o código a `SolicitarVerificacao`,
 * confere com `ConfirmarVerificacao` e traduz `LogicException` em erro de
 * formulário. Prazo, limite de erros e intervalo de reenvio vivem nos
 * parâmetros, não aqui.
 */
#[Layout('components.layouts.app')]
class VerificarContato extends Component
{
    public string $tipo = VerificacaoContato::TIPO_TELEFONE;

    public string $codigo = '';

    public ?string $recado = null;

    public function mount(string $tipo = VerificacaoContato::TIPO_TELEFONE): void
    {
        $this->tipo = in_array($tipo, [
            VerificacaoContato::TIPO_EMAIL,
            VerificacaoContato::TIPO_TELEFONE,
        ], true) ? $tipo : VerificacaoContato::TIPO_TELEFONE;
    }

    public function escolher(string $tipo): void
    {
        $this->mount($tipo);
        $this->codigo = '';
        $this->recado = null;
        $this->resetErrorBag();
    }

    public function pedirCodigo(SolicitarVerificacao $solicitar): void
    {
        $this->recado = null;
        $this->resetErrorBag();

        try {
            $solicitar->executar($this->usuario(), $this->tipo);
        } catch (LogicException $erro) {
            $this->addError('codigo', $erro->getMessage());

            return;
        }

        $this->recado = $this->tipo === VerificacaoContato::TIPO_EMAIL
            ? 'Enviamos um código para o seu e-mail.'
            : 'Enviamos um código por mensagem.';
    }

    public function confirmar(ConfirmarVerificacao $confirmar): void
    {
        $this->validate(
            ['codigo' => ['required', 'digits:6']],
            [
                'codigo.required' => 'Digite o código que você recebeu.',
                'codigo.digits' => 'O código tem seis números.',
            ],
        );

        try {
            $confirmar->executar($this->usuario(), $this->tipo, $this->codigo);
        } catch (LogicException $erro) {
            $this->addError('codigo', $erro->getMessage());

            return;
        }

        $this->codigo = '';
        $this->recado = 'Contato confirmado.';
    }

    public function render()
    {
        $usuario = $this->usuario();

        $verificados = $usuario->verificacoes
            ->whereNotNull('verificado_em')
            ->pluck('tipo')
            ->all();

        return view('livewire.verificar-contato', [
            'usuario' => $usuario,
            'emailVerificado' => in_array(VerificacaoContato::TIPO_EMAIL, $verificados, true),
            'telefoneVerificado' => in_array(VerificacaoContato::TIPO_TELEFONE, $verificados, true),
            'temSelo' => $usuario->contatoVerificado(),
        ]);
    }

    private function usuario(): Usuario
    {
        return Auth::user()->fresh();
    }
}
