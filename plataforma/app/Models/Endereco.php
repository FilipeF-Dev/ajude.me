<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Endereço do usuário.
 *
 * A coordenada é gravada por expressão PostGIS e nunca é exposta a outro
 * usuário: a revelação obedece ao estágio da relação (P7, 02 §18.1).
 */
class Endereco extends Model
{
    protected $table = 'enderecos';

    protected $fillable = [
        'usuario_id', 'cep', 'logradouro', 'numero',
        'complemento', 'bairro', 'municipio', 'uf', 'referencia',
    ];

    protected $hidden = ['coordenada'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * O que pode aparecer antes de um agendamento (02 §18.1).
     */
    public function regiaoAproximada(): string
    {
        return "{$this->bairro}, {$this->municipio}";
    }
}
