<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contato de confiança (RF-SEG-01).
 *
 * Guarda dados de quem não é usuário e nunca aceitou termo algum. A recusa
 * desliga o vínculo: a partir dela, nenhum aviso é enviado (05 §14.3).
 */
class ContatoConfianca extends Model
{
    protected $table = 'contatos_confianca';

    protected $fillable = ['usuario_id', 'nome', 'telefone', 'permissao_declarada_em'];

    protected function casts(): array
    {
        return [
            'permissao_declarada_em' => 'datetime',
            'avisado_em' => 'datetime',
            'recusado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function podeReceberAviso(): bool
    {
        return $this->recusado_em === null;
    }

    /**
     * O primeiro aviso precisa explicar o que é a plataforma, por que a
     * pessoa recebeu a mensagem e como pedir para não receber mais.
     */
    public function precisaDeApresentacao(): bool
    {
        return $this->avisado_em === null;
    }
}
