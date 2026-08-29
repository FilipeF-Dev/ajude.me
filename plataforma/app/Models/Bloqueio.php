<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloqueio entre usuários (02 §18.2).
 *
 * O registro é direcional; o efeito é mútuo. Quem bloqueia deixa de ver e de
 * ser visto, e nenhuma proposta atravessa em qualquer sentido (I17).
 *
 * O bloqueio **não** é ocorrência nem avaliação negativa (RF-BLO-05): é
 * decisão individual, e não uma acusação que a plataforma deva apurar.
 */
class Bloqueio extends Model
{
    protected $table = 'bloqueios';

    protected $fillable = ['usuario_id', 'usuario_bloqueado_id', 'criado_em'];

    protected function casts(): array
    {
        return ['criado_em' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function usuarioBloqueado(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_bloqueado_id');
    }

    /**
     * I17 — existe bloqueio entre as duas pessoas, em qualquer direção.
     *
     * A consulta ignora quem bloqueou quem de propósito: para efeito de
     * proposta e de visibilidade, as duas direções valem igual.
     */
    public static function existeEntre(int $umId, int $outroId): bool
    {
        return static::query()
            ->where(fn ($consulta) => $consulta
                ->where('usuario_id', $umId)
                ->where('usuario_bloqueado_id', $outroId))
            ->orWhere(fn ($consulta) => $consulta
                ->where('usuario_id', $outroId)
                ->where('usuario_bloqueado_id', $umId))
            ->exists();
    }
}
