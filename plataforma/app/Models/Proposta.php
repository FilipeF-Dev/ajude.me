<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Proposta de troca de tickets (02 §5.4).
 *
 * A proposta não guarda a composição da oferta: guarda a sequência de versões
 * dela. Cada revisão cria uma versão nova, e o aceite aponta para a versão que
 * foi aceita — é assim que RF-NEG-09 deixa de ser promessa e vira verificação.
 *
 * A contrapartida é composta exclusivamente por tickets. Não existe, e não
 * deve passar a existir, campo de valor em dinheiro aqui (02 §5.4, P8).
 */
class Proposta extends Model
{
    public const RASCUNHO = 'rascunho';
    public const ENVIADA = 'enviada';
    public const EM_NEGOCIACAO = 'em_negociacao';
    public const ACEITA = 'aceita';
    public const RECUSADA = 'recusada';
    public const RETIRADA = 'retirada';
    public const EXPIRADA = 'expirada';

    /**
     * Estados em que a proposta ainda prende tickets (RF-NEG-05).
     * Sair de qualquer um deles obriga a liberar o que estava bloqueado.
     */
    public const ATIVA = [
        self::ENVIADA,
        self::EM_NEGOCIACAO,
    ];

    /** Transições permitidas (04 §9.1). */
    private const TRANSICOES = [
        self::RASCUNHO => [self::ENVIADA, self::RETIRADA],
        self::ENVIADA => [self::EM_NEGOCIACAO, self::ACEITA, self::RECUSADA, self::RETIRADA, self::EXPIRADA],
        self::EM_NEGOCIACAO => [self::ACEITA, self::RECUSADA, self::RETIRADA, self::EXPIRADA],
        self::ACEITA => [],
        self::RECUSADA => [],
        self::RETIRADA => [],
        self::EXPIRADA => [],
    ];

    protected $table = 'propostas';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'enviada_em' => 'datetime',
            'expira_em' => 'datetime',
            'encerrada_em' => 'datetime',
        ];
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'remetente_id');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'destinatario_id');
    }

    public function versoes(): HasMany
    {
        return $this->hasMany(VersaoProposta::class)->orderBy('numero');
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(MensagemNegociacao::class)->orderBy('enviada_em');
    }

    public function versaoAtual(): ?VersaoProposta
    {
        return $this->versoes()
            ->where('numero', $this->versao_atual)
            ->first();
    }

    public function estaAtiva(): bool
    {
        return in_array($this->estado, self::ATIVA, true);
    }

    public function podeSerTransicionadaPara(string $estado): bool
    {
        return in_array($estado, self::TRANSICOES[$this->estado] ?? [], true);
    }

    /**
     * A6 aplicada à proposta — vencimento é avaliação de estado.
     *
     * A proposta está vencida quando o prazo passou e ela ainda prende
     * tickets. Quem consulta descobre no ato; não depende de um processo ter
     * rodado na hora certa.
     */
    public function estaVencida(?Carbon $momento = null): bool
    {
        if (! $this->estaAtiva() || $this->expira_em === null) {
            return false;
        }

        return $this->expira_em->lessThanOrEqualTo($momento ?? Carbon::now());
    }

    public function ehParte(Usuario $usuario): bool
    {
        return in_array($usuario->id, [$this->remetente_id, $this->destinatario_id], true);
    }

    public function contraparte(Usuario $usuario): Usuario
    {
        if (! $this->ehParte($usuario)) {
            throw new LogicException('Este usuário não participa da proposta.');
        }

        return $usuario->id === $this->remetente_id
            ? $this->destinatario
            : $this->remetente;
    }

    /**
     * RF-NEG-09 — quem revisou a oferta não aceita a própria revisão.
     *
     * É a forma direta de a revisão invalidar o aceite anterior: depois de
     * revisar, a decisão passa para a outra parte, seja ela quem for. Sem
     * isso, alguém poderia revisar e aceitar no mesmo instante, e a
     * negociação viraria imposição.
     */
    public function podeSerAceitaPor(Usuario $usuario): bool
    {
        if (! $this->estaAtiva() || ! $this->ehParte($usuario)) {
            return false;
        }

        return $this->versaoAtual()?->criada_por_id !== $usuario->id;
    }
}
