<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro do aceite do termo por uma parte, em um atendimento (RF-SEG-04).
 *
 * Guarda **qual versão** foi aceita, e não apenas que houve aceite: um termo
 * de ciência sem esse vínculo não sustenta a finalidade probatória para a
 * qual existe.
 *
 * O aceite é por atendimento, não por conta. Um termo assinado uma vez no
 * cadastro não conscientiza ninguém sobre o encontro de amanhã — é a mesma
 * razão pela qual 02 §8.2 proíbe exibi-lo no cadastro.
 */
class AceiteTermo extends Model
{
    protected $table = 'aceites_termo';

    protected $fillable = ['usuario_id', 'termo_ciencia_id', 'solicitacao_id', 'aceito_em'];

    protected function casts(): array
    {
        return ['aceito_em' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function termo(): BelongsTo
    {
        return $this->belongsTo(TermoCiencia::class, 'termo_ciencia_id');
    }

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class);
    }
}
