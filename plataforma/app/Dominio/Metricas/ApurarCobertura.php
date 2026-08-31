<?php

namespace App\Dominio\Metricas;

use App\Models\AvisoNecessidade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Necessidade não atendida, separada em duas leituras (RF-ADM-16, RF-ADM-17).
 *
 * A distinção é a razão de existir desta classe:
 *
 *  - **Falha de vocabulário** — a busca não reconheceu proficiência alguma.
 *    Corrige-se acrescentando um termo popular. Não falta gente; falta palavra.
 *
 *  - **Ausência de oferta** — reconheceu a proficiência e não achou favor.
 *    Corrige-se recrutando quem saiba fazer aquilo. A palavra existe; a
 *    pessoa não.
 *
 * Medir as duas juntas mede a coisa errada, porque elas pedem ações opostas
 * (05 §4.2). Um único indicador de "busca sem resultado" faria a Operação
 * recrutar quando bastava um sinônimo.
 *
 * Tudo aqui é **agregado por proficiência e região** — nunca busca individual
 * atribuível (02 §18.3, RF-BUS-28). O que uma pessoa procura revela o que lhe
 * falta, e isso não é dado para circular em relatório.
 */
class ApurarCobertura
{
    /**
     * RF-ADM-17 — buscas que não reconheceram nada.
     *
     * O texto é o que a Operação precisa ler para descobrir qual palavra
     * faltava. Ele sobrevive 90 dias (02 §18.3); depois some, e resta só o
     * agregado. Por isso esta consulta ignora o que já foi apagado.
     *
     * @return array<int, array<string, mixed>>
     */
    public function vocabularioFaltante(int $limite = 50): array
    {
        return DB::table('buscas_necessidade')
            ->whereNotNull('texto')
            ->whereNull('texto_apagado_em')
            ->whereNotExists(function ($consulta) {
                $consulta->select(DB::raw(1))
                    ->from('proficiencias_reconhecidas')
                    ->whereColumn('proficiencias_reconhecidas.busca_necessidade_id', 'buscas_necessidade.id');
            })
            ->selectRaw('LOWER(texto) AS texto, municipio, uf, COUNT(*) AS vezes, MAX(buscada_em) AS ultima')
            ->groupByRaw('LOWER(texto), municipio, uf')
            ->orderByDesc('vezes')
            ->limit($limite)
            ->get()
            ->map(fn ($linha) => [
                'texto' => $linha->texto,
                'regiao' => $this->regiao($linha->municipio, $linha->uf),
                'vezes' => (int) $linha->vezes,
                'ultima' => $linha->ultima ? Carbon::parse($linha->ultima) : null,
            ])
            ->all();
    }

    /**
     * RF-ADM-16 — proficiência reconhecida, mas sem quem ofereça.
     *
     * É o mapa do que falta na rede (02 §3.5), e o dado que a Fase 3 usa para
     * saber quem recrutar em cada comunidade.
     *
     * @return array<int, array<string, mixed>>
     */
    public function coberturaFaltante(int $limite = 50): array
    {
        return DB::table('proficiencias_reconhecidas')
            ->join('buscas_necessidade', 'buscas_necessidade.id', '=', 'proficiencias_reconhecidas.busca_necessidade_id')
            ->join('proficiencias', 'proficiencias.id', '=', 'proficiencias_reconhecidas.proficiencia_id')
            ->where('buscas_necessidade.houve_resultado', false)
            ->where('proficiencias_reconhecidas.descartada', false)
            ->selectRaw(
                'proficiencias.id AS proficiencia_id,
                 proficiencias.nome AS proficiencia,
                 buscas_necessidade.municipio,
                 buscas_necessidade.uf,
                 COUNT(*) AS buscas,
                 MAX(buscas_necessidade.buscada_em) AS ultima'
            )
            ->groupBy('proficiencias.id', 'proficiencias.nome', 'buscas_necessidade.municipio', 'buscas_necessidade.uf')
            ->orderByDesc('buscas')
            ->limit($limite)
            ->get()
            ->map(fn ($linha) => [
                'proficiencia_id' => (int) $linha->proficiencia_id,
                'proficiencia' => $linha->proficiencia,
                'regiao' => $this->regiao($linha->municipio, $linha->uf),
                'buscas' => (int) $linha->buscas,
                'aguardando' => $this->aguardando((int) $linha->proficiencia_id, $linha->municipio, $linha->uf),
                'ultima' => $linha->ultima ? Carbon::parse($linha->ultima) : null,
            ])
            ->all();
    }

    /**
     * RF-ADM-18 — quantos esperam aviso.
     *
     * Diferente da contagem de buscas: uma pessoa pode buscar dez vezes e
     * continuar sendo uma pessoa. A fila mede gente, e é ela que diz o
     * tamanho real da demanda represada.
     */
    private function aguardando(int $proficienciaId, ?string $municipio, ?string $uf): int
    {
        return AvisoNecessidade::query()
            ->where('proficiencia_id', $proficienciaId)
            ->where('municipio', $municipio)
            ->where('uf', $uf)
            ->whereNull('atendido_em')
            ->distinct('usuario_id')
            ->count('usuario_id');
    }

    private function regiao(?string $municipio, ?string $uf): string
    {
        if ($municipio === null && $uf === null) {
            return 'Sem região';
        }

        return trim(($municipio ?? '—').($uf ? " · {$uf}" : ''));
    }
}
