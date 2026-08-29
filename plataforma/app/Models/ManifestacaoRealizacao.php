<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Manifestação de uma parte sobre a realização do favor (02 §8.4).
 *
 * Confirmar é opcional; o silêncio já confirma. Contestar é o único ato que
 * impede o encerramento — e por isso é gratuito, não exige justificativa
 * prévia e vale durante toda a janela.
 *
 * Só recebe inserção: quem contestou não desfaz a contestação editando o
 * registro. A apuração cabe à Operação.
 */
class ManifestacaoRealizacao extends Model
{
    public const CONFIRMA = 'confirma';
    public const CONTESTA = 'contesta';

    protected $table = 'manifestacoes_realizacao';

    protected $fillable = ['solicitacao_id', 'parte', 'tipo', 'momento'];

    protected function casts(): array
    {
        return ['momento' => 'datetime'];
    }

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class);
    }
}
