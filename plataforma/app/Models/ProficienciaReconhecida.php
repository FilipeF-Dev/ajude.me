<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proficiência que o sistema reconheceu em uma busca (RF-BUS-17, RF-BUS-18).
 *
 * A interpretação é exibida **antes** dos resultados. Não é detalhe de
 * interface: o reconhecimento pode errar, e resultado inexplicado parece
 * defeito. Mostrar o que o sistema entendeu permite ao usuário corrigir, e
 * torna o erro de vocabulário observável pela Operação.
 */
class ProficienciaReconhecida extends Model
{
    protected $table = 'proficiencias_reconhecidas';

    protected $fillable = ['busca_necessidade_id', 'proficiencia_id', 'descartada'];

    protected $attributes = ['descartada' => false];

    protected function casts(): array
    {
        return ['descartada' => 'boolean'];
    }

    public function busca(): BelongsTo
    {
        return $this->belongsTo(BuscaNecessidade::class, 'busca_necessidade_id');
    }

    public function proficiencia(): BelongsTo
    {
        return $this->belongsTo(Proficiencia::class);
    }
}
