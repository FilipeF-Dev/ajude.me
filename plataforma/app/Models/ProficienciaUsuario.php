<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proficiência declarada por um usuário, com nível (02 §3.1).
 *
 * O nível é DECLARADO e permanece declarado. Nenhuma avaliação o altera
 * automaticamente (02 §3.3, RF-PRO-08) — proficiência e reputação são eixos
 * separados, e a divergência entre os dois é ela própria informação.
 */
class ProficienciaUsuario extends Model
{
    public const NIVEL_BASICO = 'basico';
    public const NIVEL_INTERMEDIARIO = 'intermediario';
    public const NIVEL_AVANCADO = 'avancado';
    public const NIVEL_ESPECIALISTA = 'especialista';

    protected $table = 'proficiencias_usuario';

    protected $fillable = ['usuario_id', 'proficiencia_id', 'nivel', 'declarada_em'];

    protected function casts(): array
    {
        return ['declarada_em' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function proficiencia(): BelongsTo
    {
        return $this->belongsTo(Proficiencia::class);
    }

    public static function niveis(): array
    {
        return [
            self::NIVEL_BASICO => 'Básico',
            self::NIVEL_INTERMEDIARIO => 'Intermediário',
            self::NIVEL_AVANCADO => 'Avançado',
            self::NIVEL_ESPECIALISTA => 'Especialista',
        ];
    }

    public function nivelPorExtenso(): string
    {
        return self::niveis()[$this->nivel] ?? $this->nivel;
    }

    public function favores(): HasMany
    {
        return $this->hasMany(Favor::class);
    }

    /**
     * RF-PRO-07 e I10 — não se remove proficiência com favor vinculado.
     *
     * O banco já recusa, pela chave estrangeira com `restrictOnDelete`. Este
     * método existe para que a interface avise **antes** de tentar, em vez de
     * deixar o usuário topar com um erro de integridade.
     */
    public function podeSerRemovida(): bool
    {
        return ! $this->favores()->exists();
    }
}
