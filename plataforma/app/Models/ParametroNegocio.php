<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Parâmetro de negócio versionado por vigência (P6, 05 §9).
 *
 * Prazo é parâmetro, não constante. E a vigência importa: uma solicitação
 * aberta sob janela de 72 h continua regida por 72 h ainda que o valor mude
 * depois. Alterar configuração não pode reescrever relação em curso (P5).
 */
class ParametroNegocio extends Model
{
    public const JANELA_CONTESTACAO_HORAS = 'janela_contestacao_horas';
    public const MANIFESTACAO_CHAMADO_DIAS = 'manifestacao_chamado_dias';
    public const AVALIACAO_PENDENTE_DIAS = 'avaliacao_pendente_dias';
    public const VALIDADE_PROPOSTA_DIAS = 'validade_proposta_dias';
    public const CANCELAMENTO_SEM_OCORRENCIA_HORAS = 'cancelamento_sem_ocorrencia_horas';
    public const VALIDADE_MAXIMA_TICKET_DIAS = 'validade_maxima_ticket_dias';
    public const RETENCAO_TEXTO_BUSCA_DIAS = 'retencao_texto_busca_dias';

    /**
     * Modo de base restrita (RF-ADM-08, 06 §5.2). `1` exige convite no
     * cadastro; `0` abre. Vive aqui, e não numa constante, porque abrir a
     * base é decisão de fase (06 §8) e não pode exigir reimplantação.
     */
    public const CADASTRO_POR_CONVITE = 'cadastro_por_convite';
    public const VERIFICACAO_VALIDADE_MINUTOS = 'verificacao_validade_minutos';
    public const VERIFICACAO_TENTATIVAS_MAXIMAS = 'verificacao_tentativas_maximas';
    public const VERIFICACAO_INTERVALO_REENVIO_SEGUNDOS = 'verificacao_intervalo_reenvio_segundos';
    public const RECUPERACAO_VALIDADE_MINUTOS = 'recuperacao_validade_minutos';
    public const RECUPERACAO_INTERVALO_SEGUNDOS = 'recuperacao_intervalo_segundos';
    public const CHECKOUT_ATRASO_MINUTOS = 'checkout_atraso_minutos';

    protected $table = 'parametros_negocio';

    protected $fillable = ['chave', 'valor', 'descricao', 'vigente_de', 'vigente_ate', 'alterado_por'];

    protected function casts(): array
    {
        return [
            'vigente_de' => 'datetime',
            'vigente_ate' => 'datetime',
        ];
    }

    /**
     * Valor vigente em um instante. O padrão é agora, mas relações abertas
     * consultam pelo momento em que nasceram.
     */
    public static function valorEm(string $chave, ?Carbon $momento = null): ?string
    {
        $momento ??= Carbon::now();

        return static::query()
            ->where('chave', $chave)
            ->where('vigente_de', '<=', $momento)
            ->where(function ($consulta) use ($momento) {
                $consulta->whereNull('vigente_ate')
                    ->orWhere('vigente_ate', '>', $momento);
            })
            ->value('valor');
    }

    public static function inteiroEm(string $chave, ?Carbon $momento = null): ?int
    {
        $valor = static::valorEm($chave, $momento);

        return $valor === null ? null : (int) $valor;
    }

    /**
     * Encerra a vigência atual e abre outra. Nunca sobrescreve o valor
     * anterior — o passado precisa continuar consultável.
     */
    public static function definir(string $chave, string $valor, ?int $alteradoPor = null): self
    {
        return DB::transaction(function () use ($chave, $valor, $alteradoPor) {
            $vigente = static::query()
                ->where('chave', $chave)
                ->whereNull('vigente_ate')
                ->lockForUpdate()
                ->first();

            /**
             * O corte é um só: onde a vigência anterior termina, a nova
             * começa. Sem vão e sem sobreposição.
             *
             * Se as duas caírem no mesmo segundo, a vigência anterior fica
             * com duração zero — o que é o registro fiel de um valor
             * corrigido no mesmo instante em que foi posto, e `valorEm()`
             * a ignora sozinha.
             */
            $corte = Carbon::now();

            $vigente?->forceFill(['vigente_ate' => $corte])->save();

            return static::create([
                'chave' => $chave,
                'valor' => $valor,
                'vigente_de' => $corte,
                'alterado_por' => $alteradoPor,
            ]);
        });
    }
}
