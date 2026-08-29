<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Chamado aberto por contestação da realização (02 §8.5).
 *
 * > O termo *ticket* é reservado ao direito de receber um favor. O registro
 * > de atendimento da Operação chama-se **chamado** — usar "ticket" para
 * > suporte tornaria ambíguo o conceito central do produto.
 *
 * Quem decide é a Operação, não um usuário: `decidido_por_id` aponta para
 * `operadores`.
 */
class Chamado extends Model
{
    public const ABERTO = 'aberto';
    public const DECIDIDO = 'decidido';

    public const REALIZADO = 'realizado';
    public const NAO_REALIZADO = 'nao_realizado';
    public const INCONCLUSIVO = 'inconclusivo';

    protected $table = 'chamados';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'aberto_em' => 'datetime',
            'manifestacao_ate' => 'datetime',
            'decidido_em' => 'datetime',
        ];
    }

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class);
    }

    public function abertoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aberto_por_id');
    }

    public function decididoPor(): BelongsTo
    {
        return $this->belongsTo(Operador::class, 'decidido_por_id');
    }

    public function manifestacoes(): HasMany
    {
        return $this->hasMany(ManifestacaoChamado::class)->orderBy('enviada_em');
    }

    public function estaAberto(): bool
    {
        return $this->estado === self::ABERTO;
    }

    /**
     * RF-SOL-15 — a ausência de manifestação não impede o prosseguimento.
     *
     * O prazo existe para dar chance às partes, não para travar a análise.
     * Esgotado, a Operação decide com os elementos que tiver.
     */
    public function prazoDeManifestacaoEncerrou(?Carbon $momento = null): bool
    {
        return $this->manifestacao_ate->lessThanOrEqualTo($momento ?? Carbon::now());
    }

    public function manifestacaoDe(string $parte): ?ManifestacaoChamado
    {
        return $this->manifestacoes()->where('parte', $parte)->first();
    }
}
