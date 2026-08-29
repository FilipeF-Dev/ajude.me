<?php

namespace App\Dominio\Descoberta;

use App\Models\AvisoNecessidade;
use App\Models\BuscaNecessidade;
use App\Models\Proficiencia;
use App\Models\ProficienciaReconhecida;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * A busca por necessidade, de ponta a ponta (02 §3.4, §3.5).
 *
 * Devolve a interpretação **junto** com os resultados, e não apenas os
 * resultados: RF-BUS-18 exige que a proficiência reconhecida seja visível
 * antes deles. Um resultado inexplicado parece defeito; mostrar o que o
 * sistema entendeu permite corrigir.
 *
 * Uma busca sem correspondência não é resultado vazio — é o principal sinal
 * de demanda da plataforma (02 §3.5). O registro dela alimenta o mapa do que
 * falta em cada região, e é o dado que não existiria se a necessidade não
 * tivesse sido expressa.
 */
class BuscarPorNecessidade
{
    public function __construct(
        private readonly ReconhecerNecessidade $reconhecimento = new ReconhecerNecessidade(),
        private readonly BuscarFavores $favores = new BuscarFavores(),
    ) {
    }

    /**
     * @param  array<int, int>  $proficienciasDescartadas  correção do usuário (RF-BUS-19)
     * @return array{busca: BuscaNecessidade, proficiencias: Collection, favores: Collection}
     */
    public function executar(
        string $texto,
        ?Usuario $usuario = null,
        array $proficienciasDescartadas = [],
        ?int $raioKm = null,
        ?Carbon $momento = null,
    ): array {
        $texto = trim($texto);

        if ($texto === '') {
            throw new LogicException('Descreva o que você precisa.');
        }

        $momento ??= Carbon::now();

        $reconhecidas = $this->reconhecimento->executar($texto);

        $aplicaveis = $reconhecidas->reject(
            fn (Proficiencia $proficiencia) => in_array($proficiencia->id, $proficienciasDescartadas, true)
        )->values();

        $favores = $aplicaveis->isEmpty()
            ? collect()
            : $this->favores->executar(
                usuario: $usuario,
                proficienciaIds: $aplicaveis->pluck('id')->all(),
                raioKm: $raioKm,
            );

        $regiao = $this->regiaoDe($usuario);

        return DB::transaction(function () use (
            $texto, $usuario, $reconhecidas, $proficienciasDescartadas, $favores, $regiao, $momento
        ) {
            $busca = BuscaNecessidade::create([
                'usuario_id' => $usuario?->id,
                'texto' => $texto,
                'municipio' => $regiao['municipio'],
                'uf' => $regiao['uf'],
                'houve_resultado' => $favores->isNotEmpty(),
                'buscada_em' => $momento,
            ]);

            foreach ($reconhecidas as $proficiencia) {
                ProficienciaReconhecida::create([
                    'busca_necessidade_id' => $busca->id,
                    'proficiencia_id' => $proficiencia->id,
                    // Guardar o descarte, e não omitir a linha, é o que torna
                    // o erro de vocabulário observável pela Operação.
                    'descartada' => in_array($proficiencia->id, $proficienciasDescartadas, true),
                ]);
            }

            return [
                'busca' => $busca,
                'proficiencias' => $reconhecidas,
                'favores' => $favores,
            ];
        });
    }

    /**
     * RF-BUS-22 e RF-BUS-29 — entrar na fila de aviso da proficiência.
     *
     * Vinculado à proficiência e à região, jamais ao texto digitado. Pedir de
     * novo não duplica a fila: é o mesmo pedido, não dois.
     */
    public function pedirAviso(
        Usuario $usuario,
        Proficiencia $proficiencia,
        ?Carbon $momento = null,
    ): AvisoNecessidade {
        $regiao = $this->regiaoDe($usuario);

        return AvisoNecessidade::firstOrCreate(
            [
                'usuario_id' => $usuario->id,
                'proficiencia_id' => $proficiencia->id,
            ],
            [
                'municipio' => $regiao['municipio'],
                'uf' => $regiao['uf'],
                'criado_em' => $momento ?? Carbon::now(),
            ],
        );
    }

    /**
     * RF-ADM-16 — lacunas de cobertura, agregadas por proficiência e região.
     *
     * RF-BUS-28 proíbe a análise consumir buscas individuais atribuíveis, e é
     * por isso que esta consulta agrupa e conta em vez de listar: o formato do
     * retorno é parte da regra, não escolha de conveniência.
     *
     * @return array<int, array{proficiencia: ?string, municipio: ?string, uf: ?string, buscas: int}>
     */
    public function lacunasDeCobertura(?Carbon $desde = null): array
    {
        return DB::table('buscas_necessidade')
            ->leftJoin(
                'proficiencias_reconhecidas',
                'proficiencias_reconhecidas.busca_necessidade_id',
                '=',
                'buscas_necessidade.id'
            )
            ->leftJoin('proficiencias', 'proficiencias.id', '=', 'proficiencias_reconhecidas.proficiencia_id')
            ->where('buscas_necessidade.houve_resultado', false)
            ->when($desde !== null, fn ($consulta) => $consulta
                ->where('buscas_necessidade.buscada_em', '>=', $desde))
            ->groupBy('proficiencias.nome', 'buscas_necessidade.municipio', 'buscas_necessidade.uf')
            ->selectRaw(
                'proficiencias.nome AS proficiencia,
                 buscas_necessidade.municipio,
                 buscas_necessidade.uf,
                 COUNT(DISTINCT buscas_necessidade.id) AS buscas'
            )
            ->orderByDesc('buscas')
            ->get()
            ->map(fn ($linha) => [
                'proficiencia' => $linha->proficiencia,
                'municipio' => $linha->municipio,
                'uf' => $linha->uf,
                'buscas' => (int) $linha->buscas,
            ])
            ->all();
    }

    /**
     * RF-ADM-17 — buscas que não reconheceram proficiência alguma.
     *
     * Aqui o texto é o objeto: é a palavra que faltava no vocabulário. É a
     * única finalidade que justifica reter o texto, e é por isso que ele é
     * apagado aos 90 dias, cumprida a função (05 §11.5).
     *
     * @return Collection<int, BuscaNecessidade>
     */
    public function semReconhecimento(?Carbon $desde = null): Collection
    {
        return BuscaNecessidade::query()
            ->whereNotNull('texto')
            ->whereDoesntHave('reconhecidas')
            ->when($desde !== null, fn ($consulta) => $consulta->where('buscada_em', '>=', $desde))
            ->orderByDesc('buscada_em')
            ->get();
    }

    /** @return array{municipio: ?string, uf: ?string} */
    private function regiaoDe(?Usuario $usuario): array
    {
        $endereco = $usuario?->endereco;

        return [
            'municipio' => $endereco?->municipio,
            'uf' => $endereco?->uf,
        ];
    }
}
