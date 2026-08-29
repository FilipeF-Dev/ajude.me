<?php

namespace App\Livewire;

use App\Dominio\Reputacao\RegistrarAvaliacao;
use App\Models\Avaliacao;
use App\Models\Solicitacao;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;

/**
 * Avaliação bilateral, obrigatória e imutável (02 §11).
 *
 * A tela avisa da imutabilidade **antes** do envio, e não depois: a
 * impossibilidade de alterar existe para reduzir conflito posterior e
 * tentativa de manipulação (02 §11.1), e quem não souber disso na hora vai
 * descobrir do pior jeito.
 */
#[Layout('components.layouts.app')]
class Avaliar extends Component
{
    public Solicitacao $solicitacao;

    /** @var array<string, int> critério => nota de 0 a 7 */
    public array $notas = [];

    /** @var array<int, string> */
    public array $motivos = [];

    public string $comentario = '';

    public function mount(Solicitacao $solicitacao): void
    {
        abort_unless($solicitacao->ehParte(auth()->user()), 403);

        $this->solicitacao = $solicitacao;

        foreach ($this->criterios() as $criterio) {
            $this->notas[$criterio] = 5;
        }
    }

    public function enviar(): void
    {
        try {
            app(RegistrarAvaliacao::class)->executar(
                $this->solicitacao,
                auth()->user(),
                array_map('intval', $this->notas),
                $this->motivos,
                $this->comentario,
            );
        } catch (LogicException $erro) {
            $this->addError('avaliacao', $erro->getMessage());

            return;
        }

        session()->flash('avaliado', 'Avaliação registrada.');

        $this->redirect(route('carteira'));
    }

    /** @return array<int, string> */
    public function criterios(): array
    {
        return Avaliacao::criteriosDe($this->papelAvaliado());
    }

    public function papelAvaliado(): string
    {
        return $this->solicitacao->papelDe(auth()->user()) === Solicitacao::PARTE_SOLICITANTE
            ? Solicitacao::PARTE_AJUDANTE
            : Solicitacao::PARTE_SOLICITANTE;
    }

    public function render()
    {
        return view('livewire.avaliar', [
            'avaliado' => $this->solicitacao->contraparteDe(auth()->user()),
            'criterios' => $this->criterios(),
            'exigeMotivo' => in_array(Avaliacao::QUALIDADE, $this->criterios(), true),
            'motivosDisponiveis' => Avaliacao::MOTIVOS_QUALIDADE,
            'jaAvaliei' => $this->solicitacao->avaliacoes()
                ->where('avaliador_id', auth()->id())
                ->exists(),
        ]);
    }
}
