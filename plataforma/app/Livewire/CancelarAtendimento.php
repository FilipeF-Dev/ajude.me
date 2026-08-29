<?php

namespace App\Livewire;

use App\Dominio\Consumo\CancelarSolicitacao;
use App\Models\Solicitacao;
use Livewire\Component;
use LogicException;

/**
 * Cancelamento, com a consequência dita antes de confirmar (RF-CAN-16).
 *
 * A pergunta "isto vai gerar ocorrência?" é respondida **antes**, e não
 * depois: quem cancela precisa saber o que vai acontecer para decidir. O
 * sistema não bloqueia o cancelamento em nenhum caso — bloquear não faz
 * ninguém comparecer, só converte um aviso em ausência sem aviso (02 §9.2).
 */
class CancelarAtendimento extends Component
{
    public Solicitacao $solicitacao;

    public bool $confirmando = false;

    public string $justificativa = '';

    public function abrir(): void
    {
        $this->confirmando = true;
    }

    public function fechar(): void
    {
        $this->confirmando = false;
        $this->justificativa = '';
    }

    public function cancelar(): void
    {
        $usuario = auth()->user();
        $acao = app(CancelarSolicitacao::class);

        try {
            $this->solicitacao->papelDe($usuario) === Solicitacao::PARTE_AJUDANTE
                ? $acao->peloAjudante($this->solicitacao, $usuario, $this->justificativa)
                : $acao->peloSolicitante($this->solicitacao, $usuario, $this->justificativa ?: null);
        } catch (LogicException $erro) {
            $this->addError('cancelamento', $erro->getMessage());

            return;
        }

        $this->redirect(route('carteira'));
    }

    public function render()
    {
        $usuario = auth()->user();
        $papel = $this->solicitacao->papelDe($usuario);

        return view('livewire.cancelar-atendimento', [
            'papel' => $papel,
            'ehAjudante' => $papel === Solicitacao::PARTE_AJUDANTE,
            'geraOcorrencia' => app(CancelarSolicitacao::class)
                ->geraOcorrencia($this->solicitacao, $usuario),
        ]);
    }
}
