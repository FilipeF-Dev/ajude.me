<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ocorrência no histórico de um usuário (02 §10).
 *
 * Não é punição: é registro. O que a plataforma faz com o acúmulo delas —
 * análise, penalidade — é decisão da Operação, e as penalidades automáticas
 * seguem fora do escopo do MVP (RF-CAN-12).
 *
 * Nunca é removida (P3): é o que permite reconhecer comportamento recorrente
 * meses depois.
 */
class Ocorrencia extends Model
{
    public const ORIGEM_USUARIO = 'usuario';
    public const ORIGEM_SISTEMA = 'sistema';

    /** Cancelamento pelo ajudante — gera ocorrência em qualquer prazo (02 §9.1). */
    public const CANCELAMENTO_DO_AJUDANTE = 'cancelamento_do_ajudante';

    /** Cancelamento do solicitante sem a antecedência mínima (RF-CAN-15). */
    public const CANCELAMENTO_EM_CIMA_DA_HORA = 'cancelamento_em_cima_da_hora';

    /** Contestação que a Operação julgou infundada (RF-SOL-16). */
    public const CONTESTACAO_SEM_FUNDAMENTO = 'contestacao_sem_fundamento';

    /** Favor que a Operação decidiu não ter acontecido (RF-SOL-17). */
    public const FAVOR_NAO_REALIZADO = 'favor_nao_realizado';

    /** Relato de uma parte contra a outra (RF-CAN-10). */
    public const RELATO_DA_PARTE = 'relato_da_parte';

    protected $table = 'ocorrencias';

    protected $fillable = [
        'tipo', 'origem', 'autor_id', 'contra_usuario_id',
        'solicitacao_id', 'descricao', 'registrada_em',
    ];

    protected function casts(): array
    {
        return ['registrada_em' => 'datetime'];
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autor_id');
    }

    public function contraUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'contra_usuario_id');
    }

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class);
    }

    public function ehAutomatica(): bool
    {
        return $this->origem === self::ORIGEM_SISTEMA;
    }
}
