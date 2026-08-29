<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensagem do chat vinculado à proposta (RF-NEG-07).
 *
 * Retida por um ano após o encerramento da negociação (05 §12): é a prova do
 * que foi combinado quando um chamado precisa ser analisado meses depois.
 */
class MensagemNegociacao extends Model
{
    protected $table = 'mensagens_negociacao';

    protected $fillable = ['proposta_id', 'autor_id', 'texto', 'enviada_em'];

    protected function casts(): array
    {
        return ['enviada_em' => 'datetime'];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autor_id');
    }
}
