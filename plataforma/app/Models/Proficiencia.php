<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proficiência do catálogo (02 §3.1).
 *
 * `restrita` implementa a curadoria de categorias de risco (02 §8.3):
 * impede NOVAS ofertas sem invalidar as que já existem — a restrição é
 * atributo do catálogo, não do favor.
 */
class Proficiencia extends Model
{
    protected $table = 'proficiencias';

    protected $fillable = ['subcategoria_id', 'nome', 'ativa', 'restrita', 'motivo_restricao'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean', 'restrita' => 'boolean'];
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function termosPopulares(): HasMany
    {
        return $this->hasMany(TermoPopular::class);
    }

    public function aceitaNovosFavores(): bool
    {
        return $this->ativa && ! $this->restrita;
    }
}
