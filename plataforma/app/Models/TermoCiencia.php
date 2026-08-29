<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Termo de ciência de riscos, versionado (02 §8.2, RF-ADM-07).
 *
 * O texto nunca é editado: publicar uma redação nova encerra a anterior e
 * cria outra versão. Quem aceitou a versão 2 aceitou aquele texto, e é isso
 * que RF-SEG-04 exige poder demonstrar depois.
 *
 * **Status:** a redação depende de apoio jurídico (02 §8.2, 00 §6.3). O que
 * está construído é o mecanismo — versão, publicação e registro do aceite —
 * e ele funciona com qualquer texto que venha a ser aprovado.
 */
class TermoCiencia extends Model
{
    protected $table = 'termos_ciencia';

    protected $fillable = ['versao', 'texto', 'publicado_em', 'publicado_por_id'];

    protected function casts(): array
    {
        return [
            'publicado_em' => 'datetime',
            'encerrado_em' => 'datetime',
        ];
    }

    public function publicadoPor(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'publicado_por_id');
    }

    public function aceites(): HasMany
    {
        return $this->hasMany(AceiteTermo::class);
    }

    public static function vigente(): ?self
    {
        return static::query()
            ->whereNull('encerrado_em')
            ->orderByDesc('versao')
            ->first();
    }

    public static function exigirVigente(): self
    {
        return static::vigente() ?? throw new LogicException(
            'Não há termo de ciência publicado. Favor presencial não pode ser '
            .'aceito sem ele (RF-SEG-03).'
        );
    }
}
