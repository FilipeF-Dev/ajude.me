<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um ticket dentro de uma versão da proposta (05 §4.5).
 *
 * `lado` diz de quem o ticket sai: solicitado é o que o remetente quer
 * receber; contrapartida é o que ele oferece em troca.
 */
class ItemProposta extends Model
{
    public const SOLICITADO = 'solicitado';
    public const CONTRAPARTIDA = 'contrapartida';

    protected $table = 'itens_proposta';

    protected $fillable = ['versao_proposta_id', 'ticket_id', 'lado'];

    public function versao(): BelongsTo
    {
        return $this->belongsTo(VersaoProposta::class, 'versao_proposta_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
