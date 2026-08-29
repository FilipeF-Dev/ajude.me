<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Ticket — o direito de receber um favor (02 §5).
 *
 * Não é dinheiro. Não acumula. Não tem valor. É por isso que este modelo não
 * possui, e nunca deve possuir, campo de saldo ou quantia (P8).
 */
class Ticket extends Model
{
    public const DISPONIVEL = 'disponivel';
    public const EM_NEGOCIACAO = 'em_negociacao';
    public const RESERVADO = 'reservado';
    public const EM_ANALISE = 'em_analise';
    public const CONSUMIDO = 'consumido';
    public const ENCERRADO = 'encerrado';
    public const EXPIRADO = 'expirado';
    public const DESCARTADO = 'descartado';

    /**
     * Estados que ocupam vaga de circulação (RF-FAV-16).
     *
     * O limite do favor é de circulação simultânea, não vitalício: consumido,
     * expirado e descartado liberam vaga (02 §5.1). É isso que faz um favor
     * bem-sucedido nunca se esgotar.
     */
    public const EM_CIRCULACAO = [
        self::DISPONIVEL,
        self::EM_NEGOCIACAO,
        self::RESERVADO,
        self::EM_ANALISE,
    ];

    /**
     * Transições permitidas (04 §7.2). Qualquer outra é recusada — não
     * ignorada em silêncio.
     */
    private const TRANSICOES = [
        self::DISPONIVEL => [self::EM_NEGOCIACAO, self::RESERVADO, self::EXPIRADO],
        self::EM_NEGOCIACAO => [self::DISPONIVEL, self::EXPIRADO],
        self::RESERVADO => [self::DISPONIVEL, self::CONSUMIDO, self::EM_ANALISE],
        self::EM_ANALISE => [self::CONSUMIDO, self::DISPONIVEL],
        self::CONSUMIDO => [self::ENCERRADO],
        self::EXPIRADO => [self::DESCARTADO],
        self::ENCERRADO => [],
        self::DESCARTADO => [],
    ];

    protected $table = 'tickets';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'emitido_em' => 'datetime',
            'expira_em' => 'datetime',
            'validade_suspensa_em' => 'datetime',
        ];
    }

    public function favor(): BelongsTo
    {
        return $this->belongsTo(Favor::class);
    }

    public function proprietario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'proprietario_id');
    }

    public function transferencias(): HasMany
    {
        return $this->hasMany(TransferenciaTicket::class)->orderBy('ocorrido_em');
    }

    public function transicoes(): HasMany
    {
        return $this->hasMany(TransicaoTicket::class)->orderBy('ocorrido_em');
    }

    /**
     * 02 §5.2 — 1 ticket = 1 realização.
     *
     * A lista tem mais de um item quando pedidos anteriores foram recusados
     * ou cancelados: só uma solicitação fica viva por vez, e o índice único
     * parcial da tabela é quem garante isso.
     */
    public function solicitacoes(): HasMany
    {
        return $this->hasMany(Solicitacao::class)->orderBy('inicio_previsto');
    }

    public function ocupaVaga(): bool
    {
        return in_array($this->estado, self::EM_CIRCULACAO, true);
    }

    /**
     * RF-TIC-13 — o sistema recusa qualquer transição não prevista.
     *
     * P2 exige que toda transição seja registrada; por isso mudar de estado
     * passa obrigatoriamente por aqui, e nunca por atribuição direta.
     */
    public function transicionar(string $paraEstado, string $evento, ?int $atorId = null): void
    {
        $deEstado = $this->estado;

        if (! in_array($paraEstado, self::TRANSICOES[$deEstado] ?? [], true)) {
            throw new LogicException(
                "Transição inválida de \"{$deEstado}\" para \"{$paraEstado}\" no ticket {$this->codigo}."
            );
        }

        DB::transaction(function () use ($deEstado, $paraEstado, $evento, $atorId) {
            $this->estado = $paraEstado;

            // I7 — a validade só corre fora da análise.
            $this->validade_suspensa_em = $paraEstado === self::EM_ANALISE
                ? Carbon::now()
                : null;

            $this->save();

            TransicaoTicket::create([
                'ticket_id' => $this->id,
                'de_estado' => $deEstado,
                'para_estado' => $paraEstado,
                'evento' => $evento,
                'ator_id' => $atorId,
                'ocorrido_em' => Carbon::now(),
            ]);
        });
    }

    /**
     * A6 — expiração é avaliação de estado, não processo agendado.
     *
     * Um ticket está expirado quando a validade passou **e** ele não está
     * protegido. Assim a condição é verificável a qualquer momento, sem
     * depender de um processo ter rodado na hora certa.
     *
     * I6 — reservado com realização agendada não expira.
     * I7 — em análise não expira.
     */
    public function estaProtegidoDaExpiracao(): bool
    {
        return in_array($this->estado, [self::RESERVADO, self::EM_ANALISE], true);
    }

    public function deveExpirar(?Carbon $momento = null): bool
    {
        $momento ??= Carbon::now();

        if (! $this->ocupaVaga()) {
            return false;
        }

        if ($this->estaProtegidoDaExpiracao()) {
            return false;
        }

        return $this->expira_em->lessThanOrEqualTo($momento);
    }

    /**
     * Transfere a propriedade e registra a cadeia (I8, RF-TIC-04).
     *
     * Chamado dentro da transação do aceite de proposta, nunca isolado: a
     * transferência é atômica para todos os tickets envolvidos (I16).
     */
    public function transferirPara(Usuario $novoProprietario, string $motivo): void
    {
        if ($this->proprietario_id === $novoProprietario->id) {
            throw new LogicException('O ticket já pertence a este usuário.');
        }

        $anterior = $this->proprietario_id;

        $this->proprietario_id = $novoProprietario->id;
        $this->save();

        TransferenciaTicket::create([
            'ticket_id' => $this->id,
            'de_usuario_id' => $anterior,
            'para_usuario_id' => $novoProprietario->id,
            'motivo' => $motivo,
            'ocorrido_em' => Carbon::now(),
        ]);
    }

    /**
     * I13 e RF-NEG-03 — a condição de troca é do favor, não do ticket.
     *
     * Separada de `podeSerTrocado()` porque o aceite reconfere a
     * trocabilidade com o ticket já bloqueado em `EM_NEGOCIACAO` (04 §9.2),
     * momento em que ele legitimamente não está disponível.
     */
    public function ehTrocavel(): bool
    {
        return (bool) $this->favor->trocavel;
    }

    /**
     * RF-TIC-14 — ticket em análise não é trocado, solicitado nem excluído.
     */
    public function podeSerTrocado(): bool
    {
        return $this->estado === self::DISPONIVEL && $this->ehTrocavel();
    }

    public function podeSerSolicitado(): bool
    {
        return $this->estado === self::DISPONIVEL;
    }
}
