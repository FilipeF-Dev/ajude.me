<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Integrante da Operação (04 §2).
 *
 * Não é um Usuario com poderes: é outro ator. Ver a migração
 * 2026_08_25_100700_criar_operadores para o porquê.
 */
class Operador extends Authenticatable implements FilamentUser
{
    protected $table = 'operadores';

    protected $fillable = ['nome', 'email', 'senha'];

    protected $hidden = ['senha', 'remember_token'];

    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'desativado_em' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    /**
     * Filament usa `name` para exibir quem está autenticado.
     */
    public function getFilamentName(): string
    {
        return $this->nome;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->desativado_em === null;
    }
}
