<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Favor — a ajuda que alguém se dispõe a prestar (02 §4).
 *
 * O ajudante não é campo deste modelo: vem de `proficienciaUsuario`. Isso
 * torna a invariante I9 inexprimível de violar — não existe favor apontando
 * para proficiência que a pessoa não declarou.
 */
class Favor extends Model
{
    public const ESTADO_RASCUNHO = 'rascunho';
    public const ESTADO_ATIVO = 'ativo';
    public const ESTADO_DESATIVADO = 'desativado';

    public const MODALIDADE_PRESENCIAL = 'presencial';
    public const MODALIDADE_REMOTO = 'remoto';

    /**
     * Campos que compõem a condição publicada. Depois do congelamento,
     * nenhum deles muda (P5, RF-FAV-10, RF-FAV-20).
     */
    private const CAMPOS_DE_CONDICAO = [
        'proficiencia_usuario_id',
        'titulo',
        'descricao',
        'tempo_estimado_minutos',
        'modalidade',
        'area_atendimento_km',
        'antecedencia_minima_dias',
        'validade_ticket_dias',
        'maximo_circulacao',
        'trocavel',
        'condicoes',
    ];

    protected $table = 'favores';

    protected $fillable = [
        'proficiencia_usuario_id',
        'titulo',
        'descricao',
        'tempo_estimado_minutos',
        'modalidade',
        'area_atendimento_km',
        'antecedencia_minima_dias',
        'validade_ticket_dias',
        'maximo_circulacao',
        'trocavel',
        'condicoes',
    ];

    protected function casts(): array
    {
        return [
            'trocavel' => 'boolean',
            'congelado_em' => 'datetime',
            'desativado_em' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        /**
         * RF-FAV-10 e RF-FAV-20 — condição publicada é imutável.
         *
         * A guarda fica aqui, e não em um CHECK, porque o banco não distingue
         * "alterou a condição" de "alterou qualquer coluna": desativar o favor
         * continua permitido depois do congelamento, mudar o preço das
         * condições não.
         */
        static::updating(function (Favor $favor) {
            if ($favor->congelado_em === null) {
                return;
            }

            foreach (self::CAMPOS_DE_CONDICAO as $campo) {
                if ($favor->isDirty($campo)) {
                    throw new LogicException(
                        "O favor já emitiu ticket: a condição \"{$campo}\" não pode mudar. "
                        .'Quem adquiriu o ticket contou com o que estava publicado. '
                        .'Para condições diferentes, crie um novo favor.'
                    );
                }
            }
        });
    }

    public function proficienciaUsuario(): BelongsTo
    {
        return $this->belongsTo(ProficienciaUsuario::class);
    }

    public function janelas(): HasMany
    {
        return $this->hasMany(JanelaDisponibilidade::class);
    }

    public function ajudante(): ?Usuario
    {
        return $this->proficienciaUsuario?->usuario;
    }

    /**
     * RF-FAV-04 e RF-FAV-05 — o descritor de dois eixos.
     *
     * Ocupa o lugar onde normalmente estaria o preço. Enquanto o favor não
     * congelou, o nível deriva da proficiência declarada; depois, é a condição
     * histórica daquele favor (RF-FAV-22).
     */
    public function nivel(): string
    {
        return $this->nivel_congelado
            ?? $this->proficienciaUsuario->nivel;
    }

    public function descritor(): string
    {
        return $this->tempoPorExtenso().' · '.ProficienciaUsuario::niveis()[$this->nivel()];
    }

    public function tempoPorExtenso(): string
    {
        $minutos = $this->tempo_estimado_minutos;

        if ($minutos < 60) {
            return "≈ {$minutos}min";
        }

        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return $resto === 0 ? "≈ {$horas}h" : "≈ {$horas}h{$resto}";
    }

    /**
     * Congela a condição publicada. Chamado na emissão do primeiro ticket,
     * nunca antes — enquanto não há ticket, o favor é livremente editável
     * (RF-FAV-09).
     */
    public function congelar(): void
    {
        if ($this->congelado_em !== null) {
            return;
        }

        $this->forceFill([
            'nivel_congelado' => $this->proficienciaUsuario->nivel,
            'congelado_em' => Carbon::now(),
        ])->save();
    }

    public function estaCongelado(): bool
    {
        return $this->congelado_em !== null;
    }

    /**
     * RF-FAV-19 e I12 — favor desativado não emite ticket, ainda que haja vaga.
     * RF-FAV-13 — proficiência restrita pela curadoria também impede.
     */
    public function podeEmitirTicket(): bool
    {
        if ($this->estado !== self::ESTADO_ATIVO) {
            return false;
        }

        if (! $this->proficienciaUsuario->proficiencia->aceitaNovosFavores()) {
            return false;
        }

        return $this->ajudante()?->podeOfertar() === true;
    }

    /**
     * Publica o favor.
     *
     * `estado` fica fora de `$fillable` de propósito: publicar é transição de
     * estado, não preenchimento de campo. Passar por aqui garante que a
     * curadoria seja verificada — atribuição em massa contornaria a checagem
     * sem que ninguém percebesse (RF-FAV-13).
     */
    public function ativar(): void
    {
        if (! $this->proficienciaUsuario->proficiencia->aceitaNovosFavores()) {
            throw new LogicException(
                'Esta proficiência está restrita pela curadoria e não aceita novos favores.'
            );
        }

        $this->forceFill([
            'estado' => self::ESTADO_ATIVO,
            'desativado_em' => null,
        ])->save();
    }

    /**
     * RF-FAV-11 e RF-FAV-12 — desativar impede novas emissões e **não**
     * invalida os tickets já emitidos (02 §4.3).
     */
    public function desativar(): void
    {
        $this->forceFill([
            'estado' => self::ESTADO_DESATIVADO,
            'desativado_em' => Carbon::now(),
        ])->save();
    }
}
