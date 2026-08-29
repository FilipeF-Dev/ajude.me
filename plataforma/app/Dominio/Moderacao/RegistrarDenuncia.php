<?php

namespace App\Dominio\Moderacao;

use App\Models\Denuncia;
use App\Models\Favor;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Denúncia de um usuário ou de um favor (RF-BLO-07, RF-BLO-08).
 *
 * Cai na fila da Operação. Diferente do bloqueio, que é decisão individual e
 * não pede nada a ninguém (RF-BLO-05).
 *
 * Denunciar não bloqueia automaticamente: são dois atos com propósitos
 * diferentes, e presumir que quem denuncia quer deixar de ver decidiria pela
 * pessoa algo que ela não pediu.
 */
class RegistrarDenuncia
{
    public function contraUsuario(
        Usuario $autor,
        Usuario $alvo,
        string $motivo,
        ?Carbon $momento = null,
    ): Denuncia {
        if ($autor->id === $alvo->id) {
            throw new LogicException('Não se denuncia a si mesmo.');
        }

        return $this->criar($autor, $motivo, $momento, alvoUsuario: $alvo);
    }

    public function contraFavor(
        Usuario $autor,
        Favor $alvo,
        string $motivo,
        ?Carbon $momento = null,
    ): Denuncia {
        return $this->criar($autor, $motivo, $momento, alvoFavor: $alvo);
    }

    private function criar(
        Usuario $autor,
        string $motivo,
        ?Carbon $momento,
        ?Usuario $alvoUsuario = null,
        ?Favor $alvoFavor = null,
    ): Denuncia {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new LogicException('A denúncia precisa informar o motivo.');
        }

        return Denuncia::create([
            'autor_id' => $autor->id,
            'alvo_usuario_id' => $alvoUsuario?->id,
            'alvo_favor_id' => $alvoFavor?->id,
            'motivo' => $motivo,
            'registrada_em' => $momento ?? Carbon::now(),
        ]);
    }
}
