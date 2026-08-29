<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Trilha de auditoria das ações da Operação (RF-ADM-10, 05 §4.9).
 *
 * É a contrapartida do poder que a Operação tem sobre o domínio: ela decide
 * chamados, suspende contas e restringe categorias — e cada uma dessas ações
 * fica com autor, momento e motivo.
 *
 * Só recebe inserção, e nunca é apagada (P3).
 */
class RegistroAuditoria extends Model
{
    protected $table = 'registros_auditoria';

    protected $fillable = [
        'operador_id', 'acao', 'alvo_tipo', 'alvo_id', 'motivo', 'ocorrido_em',
    ];

    protected function casts(): array
    {
        return ['ocorrido_em' => 'datetime'];
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(Operador::class);
    }

    public static function registrar(
        ?Operador $operador,
        string $acao,
        Model $alvo,
        ?string $motivo = null,
        ?Carbon $momento = null,
    ): self {
        return static::create([
            'operador_id' => $operador?->id,
            'acao' => $acao,
            'alvo_tipo' => $alvo::class,
            'alvo_id' => $alvo->getKey(),
            'motivo' => $motivo,
            'ocorrido_em' => $momento ?? Carbon::now(),
        ]);
    }
}
