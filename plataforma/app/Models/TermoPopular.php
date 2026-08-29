<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vocabulário de reconhecimento de necessidade (02 §3.4).
 *
 * O usuário digita o sintoma — "trocar a resistência do chuveiro" — e não a
 * competência. Traduzir uma coisa na outra é o trabalho do sistema, e é o que
 * distingue esta busca de uma busca por texto.
 *
 * Uma necessidade que não reconhece proficiência alguma é falha de
 * vocabulário, corrigível pela Operação com um termo novo (RF-ADM-17).
 */
class TermoPopular extends Model
{
    protected $table = 'termos_populares';

    protected $fillable = ['proficiencia_id', 'termo', 'termo_normalizado', 'criado_por'];

    public function proficiencia(): BelongsTo
    {
        return $this->belongsTo(Proficiencia::class);
    }

    public function setTermoAttribute(string $valor): void
    {
        $this->attributes['termo'] = trim($valor);
        $this->attributes['termo_normalizado'] = self::normalizar($valor);
    }

    /**
     * Minúsculas, sem acento e sem pontuação. É a chave real de busca —
     * quem procura "resistencia" precisa encontrar "resistência".
     */
    public static function normalizar(string $texto): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $minusculo = mb_strtolower($semAcento ?: $texto, 'UTF-8');
        $limpo = preg_replace('/[^a-z0-9\s]/', ' ', $minusculo);

        return trim(preg_replace('/\s+/', ' ', $limpo));
    }
}
