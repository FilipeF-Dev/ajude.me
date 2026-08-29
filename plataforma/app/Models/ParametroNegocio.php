<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
        $agora = Carbon::now();

        static::query()
            ->where('chave', $chave)
            ->whereNull('vigente_ate')
            ->update(['vigente_ate' => $agora, 'updated_at' => $agora]);

        return static::create([
            'chave' => $chave,
            'valor' => $valor,
            'vigente_de' => $agora,
            'alterado_por' => $alteradoPor,
        ]);
    }
}
