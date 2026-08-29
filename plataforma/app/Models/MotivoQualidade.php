<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Motivo pré-determinado que acompanha o critério Qualidade (RF-AVA-05).
 *
 * A lista fechada é o mecanismo: obrigar a apontar uma dimensão concreta
 * reduz a acusação genérica, que é o que 02 §11.3 quer evitar.
 */
class MotivoQualidade extends Model
{
    protected $table = 'motivos_qualidade';

    protected $fillable = ['avaliacao_id', 'motivo'];

    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class);
    }
}
