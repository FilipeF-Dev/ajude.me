<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Solicitação de realização de um favor (02 §8).
 *
 * É a quarta máquina de estados do sistema (05 §6) e a que carrega o
 * encontro real entre duas pessoas. O ticket reage a ela, nunca o contrário:
 * quem decide a transição do ticket é o próprio contexto Ticket (05 §6.1).
 */
class Solicitacao extends Model
{
    public const SOLICITADA = 'solicitada';
    public const AGENDADA = 'agendada';
    public const RECUSADA = 'recusada';
    public const AGUARDANDO_CONFIRMACAO = 'aguardando_confirmacao';
    public const EM_ANALISE = 'em_analise';
    public const REALIZADA = 'realizada';
    public const NAO_REALIZADA = 'nao_realizada';
    public const INCONCLUSIVA = 'inconclusiva';
    public const CANCELADA_AJUDANTE = 'cancelada_ajudante';
    public const CANCELADA_SOLICITANTE = 'cancelada_solicitante';
    public const ENCERRADA = 'encerrada';

    public const PARTE_AJUDANTE = 'ajudante';
    public const PARTE_SOLICITANTE = 'solicitante';

    /**
     * Transições permitidas (04 §10.1).
     *
     * `SOLICITADA → CANCELADA_SOLICITANTE` não consta da tabela de 04 §10.1,
     * que só previa cancelamento a partir de `AGENDADA`. Está aqui porque
     * RF-CAN-07 é explícito: o solicitante cancela **a qualquer momento** e o
     * sistema não bloqueia. Desistir antes de o ajudante confirmar é o caso
     * mais barato de todos — recusá-lo obrigaria a esperar a confirmação
     * para poder desmarcar.
     */
    private const TRANSICOES = [
        self::SOLICITADA => [self::AGENDADA, self::RECUSADA, self::CANCELADA_SOLICITANTE],
        self::AGENDADA => [
            self::AGUARDANDO_CONFIRMACAO,
            self::CANCELADA_AJUDANTE,
            self::CANCELADA_SOLICITANTE,
        ],
        self::AGUARDANDO_CONFIRMACAO => [self::REALIZADA, self::EM_ANALISE],
        self::EM_ANALISE => [self::REALIZADA, self::NAO_REALIZADA, self::INCONCLUSIVA],
        self::REALIZADA => [self::ENCERRADA],
        self::RECUSADA => [],
        self::NAO_REALIZADA => [],
        self::INCONCLUSIVA => [],
        self::CANCELADA_AJUDANTE => [],
        self::CANCELADA_SOLICITANTE => [],
        self::ENCERRADA => [],
    ];

    protected $table = 'solicitacoes';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'inicio_previsto' => 'datetime',
            'fim_previsto' => 'datetime',
            'janela_contestacao_ate' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }

    public function ajudante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'ajudante_id');
    }

    public function comparecimentos(): HasMany
    {
        return $this->hasMany(RegistroComparecimento::class)->orderBy('momento');
    }

    public function manifestacoes(): HasMany
    {
        return $this->hasMany(ManifestacaoRealizacao::class)->orderBy('momento');
    }

    public function transicoes(): HasMany
    {
        return $this->hasMany(TransicaoSolicitacao::class)->orderBy('ocorrido_em');
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    /**
     * O instante em que a avaliação foi liberada (RF-SOL-20).
     *
     * Sai da trilha de transições, e não de uma coluna própria: a trilha já é
     * a fonte de verdade sobre quando cada estado aconteceu, e duplicá-la
     * criaria duas datas que podem divergir.
     */
    public function realizadaEm(): ?Carbon
    {
        return $this->transicoes()
            ->where('para_estado', self::REALIZADA)
            ->first()?->ocorrido_em;
    }

    /** I21 — o ciclo encerra somente com as duas avaliações (RF-AVA-10). */
    public function ambasAvaliaram(): bool
    {
        return $this->avaliacoes()->count() === 2;
    }

    public function favor(): ?Favor
    {
        return $this->ticket?->favor;
    }

    public function papelDe(Usuario $usuario): string
    {
        return match ($usuario->id) {
            $this->ajudante_id => self::PARTE_AJUDANTE,
            $this->solicitante_id => self::PARTE_SOLICITANTE,
            default => throw new LogicException('Este usuário não participa da solicitação.'),
        };
    }

    public function podeSerTransicionadaPara(string $estado): bool
    {
        return in_array($estado, self::TRANSICOES[$this->estado] ?? [], true);
    }

    /**
     * RF-SOL-08 — a janela abre quando a data prevista é alcançada.
     *
     * Avaliação de estado, como a expiração do ticket (A6): a condição é
     * verificável a qualquer momento, sem depender de um processo ter rodado
     * no minuto certo.
     */
    public function janelaDeveAbrir(?Carbon $momento = null): bool
    {
        return $this->estado === self::AGENDADA
            && $this->fim_previsto->lessThanOrEqualTo($momento ?? Carbon::now());
    }

    /**
     * RF-SOL-10 e 05 §7.5 — a confirmação tácita é a **ausência** de
     * contestação ao final da janela, apurada depois do limite e nunca
     * antecipada. Uma contestação registrada dentro da janela prevalece.
     */
    public function janelaEncerrou(?Carbon $momento = null): bool
    {
        return $this->estado === self::AGUARDANDO_CONFIRMACAO
            && $this->janela_contestacao_ate->lessThanOrEqualTo($momento ?? Carbon::now());
    }

    public function manifestacaoDe(string $parte): ?ManifestacaoRealizacao
    {
        return $this->manifestacoes()->where('parte', $parte)->first();
    }

    public function foiContestada(): bool
    {
        return $this->manifestacoes()
            ->where('tipo', ManifestacaoRealizacao::CONTESTA)
            ->exists();
    }

    public function ambasConfirmaram(): bool
    {
        return $this->manifestacoes()
            ->where('tipo', ManifestacaoRealizacao::CONFIRMA)
            ->count() === 2;
    }

    /**
     * RF-SEG-08 — o acionamento de emergência vive entre o início e o fim do
     * atendimento. É a janela em que as duas pessoas estão no mesmo lugar.
     */
    public function atendimentoEmCurso(): bool
    {
        $registros = $this->comparecimentos()->get();

        return $registros->where('tipo', RegistroComparecimento::INICIO)->isNotEmpty()
            && $registros->where('tipo', RegistroComparecimento::FIM)->isEmpty();
    }

    /**
     * P2 — nenhuma transição sem rastro, aqui como no ticket.
     */
    public function transicionar(string $paraEstado, string $evento, ?int $atorId = null): void
    {
        $deEstado = $this->estado;

        if (! $this->podeSerTransicionadaPara($paraEstado)) {
            throw new LogicException(
                "Transição inválida de \"{$deEstado}\" para \"{$paraEstado}\" na solicitação {$this->id}."
            );
        }

        DB::transaction(function () use ($deEstado, $paraEstado, $evento, $atorId) {
            $this->estado = $paraEstado;
            $this->save();

            TransicaoSolicitacao::create([
                'solicitacao_id' => $this->id,
                'de_estado' => $deEstado,
                'para_estado' => $paraEstado,
                'evento' => $evento,
                'ator_id' => $atorId,
                'ocorrido_em' => Carbon::now(),
            ]);
        });
    }
}
