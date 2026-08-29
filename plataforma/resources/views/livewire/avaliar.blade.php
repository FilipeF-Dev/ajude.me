@php
    $rotulos = [
        'comunicacao' => ['Comunicação', 'Facilidade de contato e clareza na interação.'],
        'qualidade' => ['Qualidade', 'Qualidade da ajuda efetivamente prestada.'],
        'satisfacao' => ['Satisfação', 'Satisfação geral em relação ao favor.'],
        'cordialidade' => ['Cordialidade', 'Educação e hospitalidade.'],
        'condicoes' => ['Condições', 'Condições reais em que o favor foi prestado.'],
    ];

    $motivosRotulo = [
        'pontualidade' => 'Pontualidade',
        'acabamento' => 'Acabamento',
        'preparo_tecnico' => 'Preparo técnico',
        'cuidado_com_o_espaco' => 'Cuidado com o espaço',
        'clareza_do_combinado' => 'Clareza do combinado',
        'tempo_de_execucao' => 'Tempo de execução',
    ];
@endphp

<div>
    <x-cabecalho titulo="Avaliar" :voltar="route('carteira')" />

    @if ($jaAvaliei)
        <div class="px-5">
            <div class="cartao p-5 text-[14px] leading-relaxed text-tinta-media">
                Você já avaliou este favor. A avaliação é imutável — é o que reduz conflito
                posterior e tentativa de manipulação.
            </div>
        </div>
    @else
        <form wire:submit="enviar" class="flex flex-col gap-4 px-5">
            <div class="cartao flex items-center gap-3.5 p-5">
                <x-avatar :nome="$avaliado->nome" :tamanho="52" />
                <div class="flex flex-col">
                    <span class="text-[16px] font-semibold">{{ $avaliado->nome }}</span>
                    <span class="text-[13px] text-tinta-suave">
                        {{ $solicitacao->ticket->favor->titulo }}
                    </span>
                </div>
            </div>

            @foreach ($criterios as $criterio)
                @php [$titulo, $ajuda] = $rotulos[$criterio]; @endphp

                <div class="cartao flex flex-col gap-3 p-5">
                    <div class="flex flex-col gap-1">
                        <span class="text-[16px] font-semibold">{{ $titulo }}</span>
                        <span class="text-[13px] leading-relaxed text-tinta-suave">{{ $ajuda }}</span>
                    </div>

                    {{-- Barras de 7 segmentos, e não estrelas: a escala é de 0
                         a 7 (02 §11.3), e estrela sugere cinco. --}}
                    <div class="flex items-center gap-2">
                        <div class="flex flex-grow gap-1.5" role="group"
                             aria-label="Nota de {{ $titulo }}, de 0 a 7">
                            @for ($i = 0; $i <= 7; $i++)
                                <button type="button"
                                        wire:click="$set('notas.{{ $criterio }}', {{ $i }})"
                                        aria-label="{{ $i }}"
                                        aria-pressed="{{ ($notas[$criterio] ?? 5) === $i ? 'true' : 'false' }}"
                                        class="h-9 flex-1 rounded-lg text-[12px] font-semibold transition-colors
                                               {{ ($notas[$criterio] ?? 5) >= $i && $i > 0
                                                  ? 'bg-acento text-white'
                                                  : (($notas[$criterio] ?? 5) === 0 && $i === 0
                                                     ? 'bg-tinta-fraca text-white'
                                                     : 'bg-neutro text-tinta-fraca') }}">
                                    {{ $i }}
                                </button>
                            @endfor
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- RF-AVA-05 — Qualidade vem acompanhada de motivos
                 pré-determinados. São descritores neutros: dizem sobre o quê
                 a nota fala, não que alguém foi ruim. --}}
            @if ($exigeMotivo)
                <div class="cartao flex flex-col gap-3 p-5">
                    <div class="flex flex-col gap-1">
                        <span class="text-[16px] font-semibold">Sobre o quê é a nota de qualidade?</span>
                        <span class="text-[13px] leading-relaxed text-tinta-suave">
                            Escolha ao menos um. Apontar a dimensão torna a avaliação legível para
                            quem a lê depois.
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($motivosDisponiveis as $motivo)
                            <label class="cursor-pointer">
                                <input type="checkbox" wire:model="motivos" value="{{ $motivo }}"
                                       class="peer sr-only">
                                <span class="pilula block border border-borda bg-superficie text-tinta-media peer-checked:border-acento peer-checked:bg-acento peer-checked:font-semibold peer-checked:text-white">
                                    {{ $motivosRotulo[$motivo] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="cartao flex flex-col gap-3 p-5">
                <span class="text-[16px] font-semibold">Comentário (opcional)</span>
                <textarea wire:model="comentario" class="campo"
                          placeholder="Algo que ajude quem for ler depois."></textarea>
            </div>

            {{-- 02 §11.1 — dito antes do envio, não depois. --}}
            <div class="aviso">
                A avaliação é <strong class="font-semibold">pública e não pode ser alterada</strong>
                depois de enviada. É o que reduz conflito posterior e tentativa de manipulação.
            </div>

            @error('avaliacao')
                <div class="aviso">{{ $message }}</div>
            @enderror

            <button type="submit" class="botao botao-principal w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="enviar">Enviar avaliação</span>
                <span wire:loading wire:target="enviar">Enviando…</span>
            </button>
        </form>
    @endif
</div>
