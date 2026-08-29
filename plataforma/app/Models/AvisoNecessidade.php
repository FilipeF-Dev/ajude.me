<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido de aviso quando alguém passar a oferecer certa proficiência
 * na região (02 §3.5, RF-BUS-22).
 *
 * Guarda proficiência e região, **jamais** o texto digitado (RF-BUS-29).
 * A pessoa aguarda "elétrica residencial no seu município", não "o chuveiro
 * que ela disse que queimou".
 *
 * Uma busca sem resultado não é resultado vazio: é o mapa do que falta na
 * rede. Mas o mapa se desenha com agregado, não com o que cada um digitou.
 */
class AvisoNecessidade extends Model
{
    protected $table = 'avisos_necessidade';

    protected $fillable = [
        'usuario_id', 'proficiencia_id', 'municipio', 'uf', 'criado_em',
    ];

    protected function casts(): array
    {
        return [
            'criado_em' => 'datetime',
            'atendido_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function proficiencia(): BelongsTo
    {
        return $this->belongsTo(Proficiencia::class);
    }

    public function estaAguardando(): bool
    {
        return $this->atendido_em === null;
    }
}
