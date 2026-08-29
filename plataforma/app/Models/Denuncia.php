<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Denúncia de um usuário ou de um favor (02 §18.2, RF-BLO-07).
 *
 * Diferente do bloqueio: bloquear é decisão individual e não pede nada à
 * plataforma; denunciar aciona a Operação. Por isso a denúncia tem estado e
 * desfecho, e o bloqueio não.
 */
class Denuncia extends Model
{
    public const ABERTA = 'aberta';
    public const PROCEDENTE = 'procedente';
    public const IMPROCEDENTE = 'improcedente';

    protected $table = 'denuncias';

    protected $fillable = [
        'autor_id', 'alvo_usuario_id', 'alvo_favor_id', 'motivo', 'registrada_em',
    ];

    /**
     * Espelha o default da coluna, como em `Usuario::$attributes`.
     *
     * Sem isto, a denúncia recém-criada fica com `estado` nulo em memória
     * enquanto o banco já gravou "aberta" — e quem checasse `estaAberta()`
     * logo depois de registrar receberia a resposta errada.
     */
    protected $attributes = [
        'estado' => self::ABERTA,
    ];

    protected function casts(): array
    {
        return [
            'registrada_em' => 'datetime',
            'analisada_em' => 'datetime',
        ];
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autor_id');
    }

    public function alvoUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'alvo_usuario_id');
    }

    public function alvoFavor(): BelongsTo
    {
        return $this->belongsTo(Favor::class, 'alvo_favor_id');
    }

    public function analisadaPor(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'analisada_por_id');
    }

    public function estaAberta(): bool
    {
        return $this->estado === self::ABERTA;
    }
}
