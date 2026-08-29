<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de mudança de estado de um ticket (P2, RF-GER-01).
 *
 * Existe para que nenhuma transição aconteça sem rastro. É a base da trilha
 * de auditoria e do que a Operação consulta ao analisar um chamado.
 */
class TransicaoTicket extends Model
{
    protected $table = 'transicoes_ticket';

    protected $fillable = [
        'ticket_id', 'de_estado', 'para_estado', 'evento', 'ator_id', 'ocorrido_em',
    ];

    protected function casts(): array
    {
        return ['ocorrido_em' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
