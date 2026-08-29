<?php

namespace App\Dominio\Descoberta;

use App\Models\BuscaNecessidade;
use App\Models\ParametroNegocio;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Retenção do texto das buscas (02 §18.3, 05 §11.5).
 *
 * > O texto só serve para uma coisa: a Operação descobrir que palavra faltava
 * > no vocabulário. Cumprida essa função, ele vira passivo sem contrapartida —
 * > é o dado mais sensível do produto e o de utilidade mais curta.
 *
 * Apagar o texto **não** apaga a busca. Permanecem a proficiência
 * reconhecida, a região e se houve resultado, que é o que alimenta o mapa de
 * cobertura (02 §3.5) e não precisa do texto para existir.
 */
class ApagarTextoDasBuscas
{
    /** @return int quantas buscas tiveram o texto apagado */
    public function executar(?Carbon $momento = null): int
    {
        $momento ??= Carbon::now();

        $dias = ParametroNegocio::inteiroEm(
            ParametroNegocio::RETENCAO_TEXTO_BUSCA_DIAS,
            $momento,
        );

        if ($dias === null) {
            throw new LogicException(
                'O parâmetro de retenção do texto de busca não está definido. '
                .'Rode o ParametrosNegocioSeeder.'
            );
        }

        return BuscaNecessidade::query()
            ->whereNotNull('texto')
            ->where('buscada_em', '<=', $momento->copy()->subDays($dias))
            ->update([
                'texto' => null,
                'texto_apagado_em' => $momento,
                'updated_at' => $momento,
            ]);
    }
}
