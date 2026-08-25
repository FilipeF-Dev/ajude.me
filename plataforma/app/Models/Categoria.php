<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nome', 'ativa', 'ordem'];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(Subcategoria::class);
    }
}
