<?php

namespace App\Livewire;

use App\Dominio\Moderacao\GerirBloqueio;
use App\Dominio\Moderacao\RegistrarDenuncia;
use App\Models\Bloqueio;
use App\Models\Usuario;
use Livewire\Component;
use LogicException;

/**
 * Bloquear e denunciar, a partir do perfil de outra pessoa.
 *
 * São **dois atos com propósitos diferentes** (02 §18.2), e a tela precisa
 * deixar isso claro: bloquear é decisão individual e não pede nada à
 * plataforma; denunciar aciona a Operação. Um não implica o outro.
 *
 * O bloqueio também não desfaz o que já foi combinado (RF-BLO-06) — dizer
 * isso antes evita que alguém bloqueie esperando escapar de um atendimento.
 */
class Moderar extends Component
{
    public Usuario $alvo;

    public bool $bloqueado = false;

    public bool $denunciando = false;

    public string $motivo = '';

    public ?string $recado = null;

    public function mount(): void
    {
        $this->bloqueado = Bloqueio::query()
            ->where('usuario_id', auth()->id())
            ->where('usuario_bloqueado_id', $this->alvo->id)
            ->exists();
    }

    public function bloquear(): void
    {
        $this->executar(function () {
            app(GerirBloqueio::class)->bloquear(auth()->user(), $this->alvo);
            $this->bloqueado = true;
            $this->recado = "{$this->alvo->nome} não verá mais seus favores, e você não verá os dela.";
        });
    }

    public function desbloquear(): void
    {
        $this->executar(function () {
            app(GerirBloqueio::class)->desbloquear(auth()->user(), $this->alvo);
            $this->bloqueado = false;
            $this->recado = 'Bloqueio desfeito.';
        });
    }

    public function abrirDenuncia(): void
    {
        $this->denunciando = true;
        $this->recado = null;
    }

    public function fecharDenuncia(): void
    {
        $this->denunciando = false;
        $this->motivo = '';
    }

    public function denunciar(): void
    {
        $this->executar(function () {
            app(RegistrarDenuncia::class)->contraUsuario(auth()->user(), $this->alvo, $this->motivo);

            $this->denunciando = false;
            $this->motivo = '';
            $this->recado = 'Denúncia registrada. A Operação vai analisar.';
        });
    }

    private function executar(callable $acao): void
    {
        try {
            $acao();
        } catch (LogicException $erro) {
            $this->addError('moderacao', $erro->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.moderar');
    }
}
