<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Uma composição da oferta em um momento (05 §4.5).
 *
 * Versão não é histórico de conveniência: é o que o aceite referencia (I15).
 * Por isso os itens pertencem a ela, e não à proposta — uma versão publicada
 * nunca muda de conteúdo, e revisar é criar outra.
 */
class VersaoProposta extends Model
{
    protected $table = 'versoes_proposta';

    protected $fillable = ['proposta_id', 'numero', 'criada_por_id', 'criada_em'];

    protected function casts(): array
    {
        return ['criada_em' => 'datetime'];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function criadaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criada_por_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemProposta::class, 'versao_proposta_id');
    }

    public function solicitados(): HasMany
    {
        return $this->itens()->where('lado', ItemProposta::SOLICITADO);
    }

    public function contrapartida(): HasMany
    {
        return $this->itens()->where('lado', ItemProposta::CONTRAPARTIDA);
    }

    /**
     * Todos os tickets desta versão, dos dois lados. É o conjunto que o envio
     * bloqueia e que o encerramento libera (RF-NEG-05, RF-NEG-11).
     *
     * @return Collection<int, Ticket>
     */
    public function tickets(): Collection
    {
        return $this->itens()->with('ticket')->get()->map(
            fn (ItemProposta $item) => $item->ticket
        );
    }
}
