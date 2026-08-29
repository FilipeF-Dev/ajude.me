<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Avaliação de uma parte pela outra, por favor realizado (02 §11).
 *
 * Obrigatória, pública, imutável e bilateral. A imutabilidade existe para
 * reduzir conflito posterior e tentativa de manipulação (02 §11.1) — e é
 * garantida por gatilho no banco, não por convenção daqui.
 *
 * **I27 é uma invariante de ausência:** nenhuma avaliação altera
 * `ProficienciaUsuario.nivel`. Não há mecanismo a construir — há um
 * mecanismo a nunca construir. Proficiência é o que a pessoa declara;
 * reputação é o que os outros dizem. Deixar uma mexer na outra apagaria a
 * distinção que 02 §3.3 estabelece.
 */
class Avaliacao extends Model
{
    /** Critérios de quem foi Ajudante (02 §11.3). */
    public const COMUNICACAO = 'comunicacao';
    public const QUALIDADE = 'qualidade';
    public const SATISFACAO = 'satisfacao';

    /** Critérios de quem foi Solicitante (02 §11.3). */
    public const CORDIALIDADE = 'cordialidade';
    public const CONDICOES = 'condicoes';

    public const NOTA_MINIMA = 0;
    public const NOTA_MAXIMA = 7;

    /**
     * Motivos pré-determinados do critério Qualidade (RF-AVA-05).
     *
     * Descritores neutros de propósito: dizem sobre o quê a nota fala, e não
     * que alguém foi ruim. É o que reduz acusação genérica sem transformar o
     * formulário em lista de defeitos.
     */
    public const MOTIVOS_QUALIDADE = [
        'pontualidade',
        'acabamento',
        'preparo_tecnico',
        'cuidado_com_o_espaco',
        'clareza_do_combinado',
        'tempo_de_execucao',
    ];

    protected $table = 'avaliacoes';

    protected $fillable = [
        'solicitacao_id', 'avaliador_id', 'avaliado_id',
        'papel_avaliado', 'comentario', 'criada_em',
    ];

    protected function casts(): array
    {
        return ['criada_em' => 'datetime'];
    }

    /**
     * Os critérios que se aplicam a quem está sendo avaliado.
     *
     * Os conjuntos são distintos porque os papéis são distintos, e a mesma
     * pessoa passa pelos dois em momentos diferentes (02 §1).
     *
     * @return array<int, string>
     */
    public static function criteriosDe(string $papelAvaliado): array
    {
        return match ($papelAvaliado) {
            Solicitacao::PARTE_AJUDANTE => [self::COMUNICACAO, self::QUALIDADE, self::SATISFACAO],
            Solicitacao::PARTE_SOLICITANTE => [self::CORDIALIDADE, self::COMUNICACAO, self::CONDICOES],
            default => throw new LogicException("Papel desconhecido: \"{$papelAvaliado}\"."),
        };
    }

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class);
    }

    public function avaliador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'avaliador_id');
    }

    public function avaliado(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'avaliado_id');
    }

    public function notas(): HasMany
    {
        return $this->hasMany(NotaCriterio::class);
    }

    public function motivosQualidade(): HasMany
    {
        return $this->hasMany(MotivoQualidade::class);
    }

    public function nota(string $criterio): ?int
    {
        return $this->notas->firstWhere('criterio', $criterio)?->nota;
    }
}
