<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota de um critério dentro de uma avaliação (02 §11.3).
 *
 * A avaliação é **particionada**: cada parte avalia critérios específicos da
 * outra, e não uma nota única. Uma nota só esconde onde a experiência foi
 * boa e onde não foi — e é justamente essa distinção que serve a quem lê.
 */
class NotaCriterio extends Model
{
    protected $table = 'notas_criterio';

    protected $fillable = ['avaliacao_id', 'criterio', 'nota'];

    protected function casts(): array
    {
        return ['nota' => 'integer'];
    }

    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class);
    }
}
