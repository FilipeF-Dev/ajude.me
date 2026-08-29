<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de início e fim do atendimento presencial (02 §8.1).
 *
 * Cumpre duas funções ao mesmo tempo: é evidência de comparecimento para a
 * análise de um chamado, e delimita a janela em que os mecanismos de
 * segurança ficam ativos (RF-SEG-08).
 *
 * Só recebe inserção. Evidência que se edita depois não é evidência.
 */
class RegistroComparecimento extends Model
{
    public const INICIO = 'inicio';
    public const FIM = 'fim';

    protected $table = 'registros_comparecimento';

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
