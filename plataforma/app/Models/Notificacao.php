<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notificação de um evento do loop (04 §16, 05 §4.9).
 *
 * **RF-NOT-17 — não existe notificação de marketing, engajamento ou
 * recomendação no MVP.** Toda notificação daqui decorre de algo que
 * aconteceu e que o destinatário precisa saber para agir. Uma plataforma
 * que precisa lembrar a pessoa de voltar está resolvendo o problema errado.
 */
class Notificacao extends Model
{
    // Negociação
    public const PROPOSTA_RECEBIDA = 'proposta_recebida';
    public const PROPOSTA_RESPONDIDA = 'proposta_respondida';
    public const PROPOSTA_PERTO_DE_EXPIRAR = 'proposta_perto_de_expirar';
    public const MENSAGEM_NA_NEGOCIACAO = 'mensagem_na_negociacao';

    // Consumo
    public const SOLICITACAO_RECEBIDA = 'solicitacao_recebida';
    public const SOLICITACAO_RESPONDIDA = 'solicitacao_respondida';
    public const PRESTACAO_CANCELADA = 'prestacao_cancelada';
    public const CONFIRMACAO_PENDENTE = 'confirmacao_pendente';

    // Moderação
    public const CHAMADO_ABERTO = 'chamado_aberto';
    public const CHAMADO_DECIDIDO = 'chamado_decidido';

    // Reputação
    public const AVALIACAO_PENDENTE = 'avaliacao_pendente';
    public const RESTRICAO_POR_AVALIACAO = 'restricao_por_avaliacao';

    // Ticket
    public const TICKET_PERTO_DE_VENCER = 'ticket_perto_de_vencer';
    public const TICKET_EXPIRADO = 'ticket_expirado';

    // Descoberta
    public const PROFICIENCIA_AGUARDADA_DISPONIVEL = 'proficiencia_aguardada_disponivel';

    protected $table = 'notificacoes';

    protected $fillable = [
        'destinatario_id', 'tipo', 'referencia_tipo',
        'referencia_id', 'dados', 'enviada_em',
    ];

    protected function casts(): array
    {
        return [
            'dados' => 'array',
            'enviada_em' => 'datetime',
            'lida_em' => 'datetime',
        ];
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'destinatario_id');
    }

    public function foiLida(): bool
    {
        return $this->lida_em !== null;
    }
}
