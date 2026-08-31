<x-filament-panels::page>

    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
        <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
            Uma busca sem resposta pode ter duas causas, e elas pedem ações <strong>opostas</strong>.
            Por isso aparecem separadas aqui, e nunca somadas num único número de “busca sem resultado”.
        </p>
        <p class="mt-3 text-xs leading-relaxed text-gray-500 dark:text-gray-500">
            Tudo nesta página é agregado por proficiência e região. Buscas individuais não são exibidas:
            o que uma pessoa procura revela o que lhe falta, e isso não circula em relatório
            (<code>02 §18.3</code>).
        </p>
    </div>

    {{-- Falta de palavra. --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <div class="border-b border-gray-200 p-5 dark:border-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Falta vocabulário</h2>
            <p class="mt-1 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                O sistema não reconheceu nenhuma proficiência para o que foi digitado.
                <strong>Não falta gente, falta palavra</strong> — acrescente um termo popular à proficiência
                correspondente e a busca passa a funcionar.
            </p>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                O texto original vive 90 dias e depois é apagado; o que sumiu daqui não volta.
            </p>
        </div>

        @if (empty($vocabulario))
            <p class="p-5 text-sm text-gray-500 dark:text-gray-400">
                Nenhuma busca ficou sem reconhecimento. O vocabulário está dando conta.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 dark:text-gray-400">
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="p-4 text-left font-medium">O que digitaram</th>
                            <th class="p-4 text-left font-medium">Região</th>
                            <th class="p-4 text-right font-medium">Vezes</th>
                            <th class="p-4 text-right font-medium">Última</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vocabulario as $linha)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                <td class="p-4 font-medium text-gray-950 dark:text-white">{{ $linha['texto'] }}</td>
                                <td class="p-4 text-gray-600 dark:text-gray-400">{{ $linha['regiao'] }}</td>
                                <td class="p-4 text-right text-gray-700 dark:text-gray-300">{{ $linha['vezes'] }}</td>
                                <td class="p-4 text-right text-gray-500 dark:text-gray-500">
                                    {{ $linha['ultima']?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Falta de gente. --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <div class="border-b border-gray-200 p-5 dark:border-white/10">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Falta quem ofereça</h2>
            <p class="mt-1 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                A proficiência foi reconhecida, mas não havia favor disponível na região.
                <strong>A palavra existe, a pessoa não</strong> — é aqui que se decide quem recrutar,
                e é o mapa que a expansão da Fase 3 usa.
            </p>
        </div>

        @if (empty($cobertura))
            <p class="p-5 text-sm text-gray-500 dark:text-gray-400">
                Toda busca reconhecida encontrou alguém. A rede está cobrindo o que pedem dela.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 dark:text-gray-400">
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="p-4 text-left font-medium">Proficiência</th>
                            <th class="p-4 text-left font-medium">Região</th>
                            <th class="p-4 text-right font-medium">Buscas</th>
                            <th class="p-4 text-right font-medium">Pessoas na fila</th>
                            <th class="p-4 text-right font-medium">Última</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cobertura as $linha)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                                <td class="p-4 font-medium text-gray-950 dark:text-white">{{ $linha['proficiencia'] }}</td>
                                <td class="p-4 text-gray-600 dark:text-gray-400">{{ $linha['regiao'] }}</td>
                                <td class="p-4 text-right text-gray-700 dark:text-gray-300">{{ $linha['buscas'] }}</td>
                                <td class="p-4 text-right font-semibold text-gray-950 dark:text-white">{{ $linha['aguardando'] }}</td>
                                <td class="p-4 text-right text-gray-500 dark:text-gray-500">
                                    {{ $linha['ultima']?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <p class="border-t border-gray-200 p-4 text-xs leading-relaxed text-gray-500 dark:border-white/10 dark:text-gray-500">
            <strong>Pessoas na fila</strong> conta gente, não buscas: alguém pode procurar dez vezes e
            continuar sendo uma pessoa. É esse número que diz o tamanho real da demanda represada.
        </p>
    </div>

</x-filament-panels::page>
