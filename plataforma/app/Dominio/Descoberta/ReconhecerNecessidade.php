<?php

namespace App\Dominio\Descoberta;

use App\Models\Proficiencia;
use App\Models\TermoPopular;
use Illuminate\Support\Collection;

/**
 * Tradução de sintoma em competência (02 §3.4, RF-BUS-17).
 *
 * > **O usuário informa o sintoma. O sistema encontra a competência.**
 *
 * Quem digita "trocar a resistência do chuveiro" não sabe que precisa de um
 * eletricista — sabe que o chuveiro não esquenta. Fazer essa tradução é o
 * trabalho do sistema, e é o que distingue esta busca de uma busca por texto.
 *
 * O reconhecimento é por **termos populares curados** (decisão A7, 05 §15):
 * auditável, corrigível de imediato pela Operação e viável em qualquer
 * aparelho. Uma busca que não reconhece o que deveria é falha de
 * vocabulário, não falha de algoritmo — e se conserta com um termo novo.
 *
 * Reconhecimento semântico fica para depois (RF-BUS-30), como complemento
 * acionado quando nada for reconhecido, sem alterar o conceito.
 */
class ReconhecerNecessidade
{
    /**
     * Proficiências relacionadas ao texto, da mais para a menos aderente.
     *
     * @return Collection<int, Proficiencia>
     */
    public function executar(string $texto): Collection
    {
        $normalizado = TermoPopular::normalizar($texto);

        if ($normalizado === '') {
            return collect();
        }

        $palavras = array_filter(
            explode(' ', $normalizado),
            // Palavras de uma ou duas letras são conectivos; casá-las traria
            // proficiência por acidente.
            fn (string $palavra) => mb_strlen($palavra) > 2
        );

        if ($palavras === []) {
            return collect();
        }

        $termos = TermoPopular::query()
            ->with('proficiencia.subcategoria')
            ->where(function ($consulta) use ($normalizado, $palavras) {
                // O termo inteiro dentro da frase digitada resolve o caso
                // comum: "queimou a resistência do chuveiro" contém "chuveiro".
                //
                // O `::text` é exigência do PostgreSQL: sem ele o parâmetro à
                // esquerda de LIKE fica sem tipo determinável.
                $consulta->whereRaw("?::text LIKE '%' || termo_normalizado || '%'", [$normalizado]);

                foreach ($palavras as $palavra) {
                    $consulta->orWhere('termo_normalizado', 'LIKE', "%{$palavra}%");
                }
            })
            ->get();

        return $termos
            ->filter(fn (TermoPopular $termo) => $termo->proficiencia?->ativa)
            // Quantos termos daquela proficiência a frase acionou. Mais
            // termos em comum é sinal mais forte, e ordenar por isso põe a
            // interpretação mais provável em primeiro lugar.
            ->groupBy('proficiencia_id')
            ->sortByDesc(fn (Collection $doGrupo) => $doGrupo->count())
            ->map(fn (Collection $doGrupo) => $doGrupo->first()->proficiencia)
            ->values();
    }
}
