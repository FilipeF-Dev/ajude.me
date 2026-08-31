@php
    $numero = fn ($valor, $sufixo = '') => $valor === null ? '—' : $valor . $sufixo;
@endphp

<x-filament-panels::page>

    {{-- As duas que decidem o produto vêm primeiro e maiores. Ordem de tela
         é hierarquia de atenção: se reciprocidade e circulação ficassem no meio
         de doze números iguais, ninguém olharia para elas. --}}
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ([
            [
                'titulo' => 'Reciprocidade',
                'valor' => $numero($panorama['reciprocidade'], '%'),
                'legenda' => 'Prestaram e receberam ajuda',
                'porque' => 'A hipótese mais importante (H5). Uma rede cheia de solicitantes e vazia de ajudantes fracassa mesmo com cadastro alto — e falha em silêncio, porque parece crescimento.',
            ],
            [
                'titulo' => 'Profundidade de circulação',
                'valor' => $numero($panorama['profundidade_circulacao']),
                'legenda' => 'Trocas por ticket até o consumo',
                'porque' => 'Testa o conceito central. Se ficar em 0, a transferibilidade não está em uso e o ticket é um vale nominal: o produto é catálogo de favores, não rede de circulação.',
            ],
        ] as $destaque)
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $destaque['titulo'] }}</div>
                <div class="mt-2 text-4xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $destaque['valor'] }}
                </div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $destaque['legenda'] }}</div>
                <p class="mt-3 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $destaque['porque'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ([
            ['Participantes', $numero($panorama['participantes']), 'Contas criadas'],
            ['Com ao menos um favor', $numero($panorama['com_favor']), 'A oferta existe (H1)'],
            ['Propostas enviadas', $numero($panorama['propostas_enviadas']), 'Intenção real (H2)'],
            ['Taxa de aceite', $numero($panorama['taxa_aceite'], '%'), 'Troca sem dinheiro funciona (H3)'],
            ['Favores realizados', $numero($panorama['consumos_concluidos']), 'A ajuda saiu do papel (H4)'],
            ['Tickets transferidos', $numero($panorama['tickets_transferidos']), 'Trocas fechadas'],
            ['Cancelamento — ajudante', $numero($panorama['taxa_cancelamento_ajudante'], '%'), 'Confiabilidade de quem oferece'],
            ['Cancelamento — solicitante', $numero($panorama['taxa_cancelamento_solicitante'], '%'), 'Confiabilidade de quem pede'],
            ['Contestação', $numero($panorama['taxa_contestacao'], '%'), 'Quanto depende de arbitragem'],
        ] as [$titulo, $valor, $legenda])
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $titulo }}</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $valor }}</div>
                <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $legenda }}</div>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <div class="border-b border-gray-200 p-5 dark:border-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Coortes mensais</h2>
            <p class="mt-1 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                Cada linha é a turma que entrou naquele mês, e mede o que <strong>essa turma</strong> fez —
                não o que a plataforma toda fez no mês. A pergunta não é qual o número, e sim se a coorte
                deste mês está melhor que a do mês passado.
            </p>
            <p class="mt-2 text-xs leading-relaxed text-gray-400 dark:text-gray-500">
                Não existe referência publicada para redes de troca sem dinheiro em comunidades de baixa
                renda no Brasil. Fixar limiar absoluto seria inventar, e critério direcional ainda resiste
                ao viés dos primeiros usuários, que são sempre os mais entusiasmados.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 dark:text-gray-400">
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="p-4 text-left font-medium">Coorte</th>
                        <th class="p-4 text-right font-medium">Entraram</th>
                        <th class="p-4 text-right font-medium">Com favor</th>
                        <th class="p-4 text-right font-medium">Reciprocidade</th>
                        <th class="p-4 text-right font-medium">Dias até a 1ª troca</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($coortes as $coorte)
                        <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                            <td class="p-4 font-medium text-gray-950 dark:text-white">{{ $coorte['mes'] }}</td>
                            <td class="p-4 text-right text-gray-700 dark:text-gray-300">{{ $coorte['integrantes'] }}</td>
                            <td class="p-4 text-right text-gray-700 dark:text-gray-300">{{ $numero($coorte['com_favor'], '%') }}</td>
                            <td class="p-4 text-right font-semibold text-gray-950 dark:text-white">{{ $numero($coorte['reciprocidade'], '%') }}</td>
                            <td class="p-4 text-right text-gray-700 dark:text-gray-300">{{ $numero($coorte['dias_ate_primeira_troca']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/5">
        <p class="text-sm leading-relaxed text-amber-900 dark:text-amber-200">
            <strong>O que não medir:</strong> instalações, cadastros totais, acessos e tempo de sessão não
            são indicadores de sucesso deste produto e não devem orientar decisão. Quem resolveu a própria
            necessidade e ficou meses sem abrir o aplicativo é caso de sucesso, não de evasão.
        </p>
    </div>

</x-filament-panels::page>
