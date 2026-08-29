<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A versão de uma parte sobre o ocorrido (02 §8.5, RF-SOL-14).
 *
 * Uma por parte, e só inserção: é peça de apuração, e peça que se reescreve
 * depois de ler a da outra parte não apura nada.
 */
class ManifestacaoChamado extends Model
{
    protected $table = 'manifestacoes_chamado';

    protected $fillable = ['chamado_id', 'parte', 'texto', 'enviada_em'];

    protected function casts(): array
    {
        return ['enviada_em' => 'datetime'];
    }

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }
}
