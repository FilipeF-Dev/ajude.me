<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Elo da cadeia de propriedade de um ticket (I8, RF-TIC-04).
 *
 * `de_usuario_id` nulo marca a emissão: o ticket nasce na carteira de quem o
 * emitiu, vindo de lugar nenhum. A cadeia nunca é apagada — é o que permite
 * apurar um conflito meses depois (P3).
 */
class TransferenciaTicket extends Model
{
    public const MOTIVO_EMISSAO = 'emissao';
    public const MOTIVO_PROPOSTA_ACEITA = 'proposta_aceita';

    protected $table = 'transferencias_ticket';

    protected $fillable = [
        'ticket_id', 'de_usuario_id', 'para_usuario_id', 'motivo', 'ocorrido_em',
    ];

    protected function casts(): array
    {
        return ['ocorrido_em' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function deUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'de_usuario_id');
    }

    public function paraUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'para_usuario_id');
    }

    public function ehEmissao(): bool
    {
        return $this->de_usuario_id === null;
    }
}
