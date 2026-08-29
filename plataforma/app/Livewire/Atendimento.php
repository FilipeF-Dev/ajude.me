<?php

namespace App\Livewire;

use App\Dominio\Consumo\ApurarRealizacao;
use App\Dominio\Consumo\CancelarSolicitacao;
use App\Dominio\Consumo\RegistrarComparecimento;
use App\Models\Favor;
use App\Models\ManifestacaoRealizacao;
use App\Models\RegistroComparecimento;
use App\Models\Solicitacao;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;

/**
 * O atendimento combinado, do agendamento à confirmação.
 *
 * Reúne numa tela só o que acontece em volta do encontro: os dados de
 * contato liberados pelo agendamento (RF-SOL-07), o registro de
 * comparecimento (RF-SEG-05 a RF-SEG-07), o acionamento de emergência
 * (RF-SEG-08) e a confirmação da realização (RF-SOL-09, RF-SOL-11).
 *
 * É a tela onde o produto encosta no risco real: duas pessoas que não se
 * conhecem, no mesmo espaço físico, frequentemente na casa de uma delas
 * (02 §8.2).
 */
#[Layout('components.layouts.app')]
class Atendimento extends Component
{
    public Solicitacao $solicitacao;

    public function mount(Solicitacao $solicitacao): void
    {
        abort_unless($solicitacao->ehParte(auth()->user()), 403);

        $this->solicitacao = $solicitacao;
    }

    public function registrarInicio(): void
    {
        $this->executar(fn () => app(RegistrarComparecimento::class)
            ->inicio($this->solicitacao, auth()->user()));
    }

    public function registrarFim(): void
    {
        $this->executar(fn () => app(RegistrarComparecimento::class)
            ->fim($this->solicitacao, auth()->user()));
    }

    public function confirmar(): void
    {
        $this->manifestar(ManifestacaoRealizacao::CONFIRMA);
    }

    public function contestar(): void
    {
        $this->manifestar(ManifestacaoRealizacao::CONTESTA);
    }

    public function cancelar(string $justificativa = ''): void
    {
        $this->executar(function () use ($justificativa) {
            $cancelar = app(CancelarSolicitacao::class);
            $usuario = auth()->user();

            $this->solicitacao->papelDe($usuario) === Solicitacao::PARTE_AJUDANTE
                ? $cancelar->peloAjudante($this->solicitacao, $usuario, $justificativa)
                : $cancelar->peloSolicitante($this->solicitacao, $usuario, $justificativa ?: null);
        });
    }

    private function manifestar(string $tipo): void
    {
        $this->executar(fn () => app(ApurarRealizacao::class)
            ->manifestar($this->solicitacao, auth()->user(), $tipo));
    }

    private function executar(callable $acao): void
    {
        try {
            $acao();
        } catch (LogicException $erro) {
            $this->addError('acao', $erro->getMessage());

            return;
        }

        $this->solicitacao = $this->solicitacao->fresh();
    }

    public function render()
    {
        $usuario = auth()->user();
        $papel = $this->solicitacao->papelDe($usuario);
        $contraparte = $this->solicitacao->contraparteDe($usuario);

        $registros = $this->solicitacao->comparecimentos()->get();
        $meus = $registros->where('parte', $papel);

        return view('livewire.atendimento', [
            'papel' => $papel,
            'contraparte' => $contraparte,
            'favor' => $this->solicitacao->ticket->favor,
            'ehPresencial' => $this->solicitacao->ticket->favor->modalidade === Favor::MODALIDADE_PRESENCIAL,
            'registrouInicio' => $meus->where('tipo', RegistroComparecimento::INICIO)->isNotEmpty(),
            'registrouFim' => $meus->where('tipo', RegistroComparecimento::FIM)->isNotEmpty(),
            'emCurso' => $this->solicitacao->atendimentoEmCurso(),
            'registros' => $registros,
            'minhaManifestacao' => $this->solicitacao->manifestacaoDe($papel),
            // RF-SOL-07 e 02 §18.1 — telefone e endereço só depois do
            // agendamento, e só às partes.
            'liberouContato' => in_array($this->solicitacao->estado, [
                Solicitacao::AGENDADA,
                Solicitacao::AGUARDANDO_CONFIRMACAO,
                Solicitacao::EM_ANALISE,
            ], true),
        ]);
    }
}
