<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Integrante da Operação (04 §2).
 *
 * Não é um Usuario com poderes: é outro ator. Ver a migração
 * 2026_08_25_100700_criar_operadores para o porquê.
 */
class Operador extends Authenticatable implements FilamentUser, HasName
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
     * O contrato `HasName` é obrigatório, não decorativo.
     *
     * Sem ele o Filament ignora este método e vai buscar o atributo `name`,
     * que não existe em `operadores` porque o domínio fala português. O
     * resultado era a página inteira do painel quebrar ao montar o menu do
     * usuário — falha que só aparece em renderização completa, e por isso
     * passou despercebida enquanto os testes exercitavam só componentes.
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
