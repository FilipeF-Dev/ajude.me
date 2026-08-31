<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Uma mensagem endereçada para fora da plataforma.
 *
 * `registrado` não é `entregue`. Enquanto não houver provedor, tudo fica no
 * primeiro estado — e é assim que se sabe, olhando, que nada saiu daqui.
 */
class Envio extends Model
{
    public const EMAIL = 'email';
    public const SMS = 'sms';

    public const REGISTRADO = 'registrado';
    public const ENTREGUE = 'entregue';
    public const FALHOU = 'falhou';

    protected $table = 'envios';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'despachado_em' => 'datetime',
            'entregue_em' => 'datetime',
        ];
    }

    public function marcarEntregue(?Carbon $momento = null): void
    {
        $this->forceFill([
            'estado' => self::ENTREGUE,
            'entregue_em' => $momento ?? Carbon::now(),
            'erro' => null,
        ])->save();
    }

    public function marcarFalha(string $erro): void
    {
        $this->forceFill([
            'estado' => self::FALHOU,
            'erro' => $erro,
            'tentativas' => $this->tentativas + 1,
        ])->save();
    }

    public function saiuDaPlataforma(): bool
    {
        return $this->estado === self::ENTREGUE;
    }

    /** NF-DIS-03 — falha de canal é incidente, não linha de log. */
    public function scopeFalhados(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::FALHOU);
    }

    /** O que ficou só registrado: nada disso chegou a ninguém. */
    public function scopeNaoEntregues(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::REGISTRADO);
    }
}
