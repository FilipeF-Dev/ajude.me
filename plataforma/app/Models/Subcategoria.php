<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subcategoria extends Model
{
    protected $table = 'subcategorias';

    protected $fillable = ['categoria_id', 'nome', 'ativa', 'ordem'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function proficiencias(): HasMany
    {
        return $this->hasMany(Proficiencia::class);
    }
}
