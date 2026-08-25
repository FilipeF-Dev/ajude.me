<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Verificação de um meio de contato (RF-CAD-04, RF-CAD-05).
 *
 * Entidade própria, e não campos booleanos no usuário: quando a verificação
 * documental de identidade entrar (RF-CAD-18), ela é mais um `tipo`.
 */
class VerificacaoContato extends Model
{
    public const TIPO_EMAIL = 'email';
    public const TIPO_TELEFONE = 'telefone';

    protected $table = 'verificacoes_contato';

    protected $fillable = ['usuario_id', 'tipo', 'valor', 'codigo', 'codigo_expira_em', 'tentativas'];

    protected function casts(): array
    {
        return [
            'codigo_expira_em' => 'datetime',
            'verificado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function estaVerificado(): bool
    {
        return $this->verificado_em !== null;
    }
}
