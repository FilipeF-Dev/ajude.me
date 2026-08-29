<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de mudança de estado de uma solicitação (P2, RF-GER-01).
 *
 * É o que a Operação lê ao apurar um chamado: quem fez o quê, e quando.
 */
class TransicaoSolicitacao extends Model
{
    protected $table = 'transicoes_solicitacao';

    protected $fillable = [
        'solicitacao_id', 'de_estado', 'para_estado', 'evento', 'ator_id', 'ocorrido_em',
    ];

    protected function casts(): array
    {
        return ['ocorrido_em' => 'datetime'];
    }

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class);
    }
}
