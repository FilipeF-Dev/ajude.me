<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Suspensão de um usuário ou de um favor pela Operação (RF-ADM-06).
 *
 * Versionada por vigência, como os parâmetros de negócio: encerrar uma
 * suspensão não apaga o fato de ela ter existido, e a Operação precisa poder
 * consultar o passado ao analisar reincidência.
 *
 * Suspender **não** invalida ticket já emitido: nem a suspensão pode extinguir
 * direito de terceiro que confiou no que estava publicado (02 §20.3).
 */
class Suspensao extends Model
{
    protected $table = 'suspensoes';

    protected $fillable = [
        'alvo_usuario_id', 'alvo_favor_id', 'motivo',
        'aplicada_por_id', 'vigente_de', 'vigente_ate',
    ];

    protected function casts(): array
    {
        return [
            'vigente_de' => 'datetime',
            'vigente_ate' => 'datetime',
        ];
    }

    public function alvoUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'alvo_usuario_id');
    }

    public function alvoFavor(): BelongsTo
    {
        return $this->belongsTo(Favor::class, 'alvo_favor_id');
    }

    public function aplicadaPor(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'aplicada_por_id');
    }

    public function estaVigente(?Carbon $momento = null): bool
    {
        $momento ??= Carbon::now();

        return $this->vigente_de->lessThanOrEqualTo($momento)
            && ($this->vigente_ate === null || $this->vigente_ate->greaterThan($momento));
    }

    public static function favorSuspenso(int $favorId, ?Carbon $momento = null): bool
    {
        return static::vigentesEm($momento)->where('alvo_favor_id', $favorId)->exists();
    }

    private static function vigentesEm(?Carbon $momento = null)
    {
        $momento ??= Carbon::now();

        return static::query()
            ->where('vigente_de', '<=', $momento)
            ->where(fn ($consulta) => $consulta
                ->whereNull('vigente_ate')
                ->orWhere('vigente_ate', '>', $momento));
    }
}
