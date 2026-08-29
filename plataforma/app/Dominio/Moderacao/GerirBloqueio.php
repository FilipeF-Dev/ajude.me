<?php

namespace App\Dominio\Moderacao;

use App\Models\Bloqueio;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Bloqueio e desbloqueio entre usuários (RF-BLO-01, RF-BLO-04).
 *
 * O bloqueio impede propostas e oculta perfis mutuamente. O que ele **não**
 * faz é igualmente importante (RF-BLO-06): não interrompe solicitação já
 * agendada nem invalida ticket já transferido entre as partes.
 *
 * A razão é a mesma que sustenta 02 §20.3 — ticket emitido não desaparece.
 * Se bloquear apagasse compromissos, bloquear viraria forma de escapar de um
 * atendimento combinado, e o direito de quem está do outro lado dependeria da
 * vontade de quem se comprometeu.
 */
class GerirBloqueio
{
    public function bloquear(Usuario $usuario, Usuario $bloqueado, ?Carbon $momento = null): Bloqueio
    {
        if ($usuario->id === $bloqueado->id) {
            throw new LogicException('Não se bloqueia a si mesmo.');
        }

        try {
            return Bloqueio::create([
                'usuario_id' => $usuario->id,
                'usuario_bloqueado_id' => $bloqueado->id,
                'criado_em' => $momento ?? Carbon::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new LogicException("{$bloqueado->nome} já está bloqueado.");
        }
    }

    public function desbloquear(Usuario $usuario, Usuario $bloqueado): void
    {
        $removidos = Bloqueio::query()
            ->where('usuario_id', $usuario->id)
            ->where('usuario_bloqueado_id', $bloqueado->id)
            ->delete();

        if ($removidos === 0) {
            throw new LogicException("{$bloqueado->nome} não está bloqueado por você.");
        }
    }
}
