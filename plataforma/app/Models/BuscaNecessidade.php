<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma busca por necessidade (02 §3.4).
 *
 * Não é pedido de ajuda: o Ajude.me não tem pedido como entidade. Ninguém
 * publica demanda à espera de resposta — a pessoa descreve o sintoma e o
 * sistema encontra a competência.
 *
 * A busca **não é pública** e não aparece em perfil, feed ou recomendação
 * (RF-BUS-26). Nenhum usuário é avisado de que outro procurou alguma coisa
 * (RF-BUS-27) — o que exclui, por decisão, o recurso aparentemente útil de
 * "alguém perto precisa disso".
 */
class BuscaNecessidade extends Model
{
    protected $table = 'buscas_necessidade';

    protected $fillable = [
        'usuario_id', 'texto', 'municipio', 'uf', 'houve_resultado', 'buscada_em',
    ];

    protected function casts(): array
    {
        return [
            'houve_resultado' => 'boolean',
            'buscada_em' => 'datetime',
            'texto_apagado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function reconhecidas(): HasMany
    {
        return $this->hasMany(ProficienciaReconhecida::class, 'busca_necessidade_id');
    }

    public function textoFoiApagado(): bool
    {
        return $this->texto_apagado_em !== null;
    }
}
