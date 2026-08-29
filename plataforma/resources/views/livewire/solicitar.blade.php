<div>
    <x-cabecalho titulo="Pedir o favor" :voltar="route('carteira')" />

    <form wire:submit="pedir" class="flex flex-col gap-4 px-5">
        <div class="cartao flex flex-col gap-3 p-5">
            <span class="text-[16px] font-semibold">{{ $favor->titulo }}</span>
            <span class="text-[14px] text-tinta-suave">
                com {{ $favor->proficienciaUsuario->usuario->nome }}
            </span>
            <x-descritor :favor="$favor" />
        </div>

        <div class="cartao flex flex-col gap-4 p-5">
            <h3 class="text-[16px] font-semibold">Quando</h3>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Data</span>
                <input type="date" wire:model="data" class="campo" required>
                @error('data') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Hora</span>
                <input type="time" wire:model="hora" class="campo" required>
                @error('hora') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
            </label>

            @if ($favor->janelas->isNotEmpty())
                <div class="flex flex-col gap-2">
                    <span class="text-[13px] text-tinta-suave">{{ $favor->proficienciaUsuario->usuario->nome }} atende</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($favor->janelas as $janela)
                            <span class="pilula border border-borda bg-neutro text-[13px] text-tinta-media">
                                {{ $janela->diaPorExtenso() }},
                                {{ substr($janela->hora_inicio, 0, 5) }}–{{ substr($janela->hora_fim, 0, 5) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="text-[13px] leading-relaxed text-tinta-fraca">
                Pede {{ $favor->antecedencia_minima_dias }}
                dia{{ $favor->antecedencia_minima_dias === 1 ? '' : 's' }} de antecedência.
                O atendimento leva cerca de {{ $favor->tempoPorExtenso() }}.
            </p>
        </div>

        {{-- RF-SEG-03 — o termo é exibido no aceite do atendimento presencial,
             e não no cadastro. Termo apresentado no cadastro não é lido, e
             portanto não cumpre a função de conscientização (02 §8.2). --}}
        @if ($ehPresencial)
            <div class="cartao flex flex-col gap-3 p-5">
                <h3 class="text-[16px] font-semibold">Antes de confirmar</h3>

                @if ($termo)
                    <p class="max-h-44 overflow-y-auto whitespace-pre-line rounded-[14px] bg-neutro p-4 text-[14px] leading-relaxed text-tinta-media">{{ $termo->texto }}</p>

                    <label class="flex items-start gap-3 py-1">
                        <input type="checkbox" wire:model="cienteDosRiscos"
                               class="mt-0.5 h-5 w-5 shrink-0 rounded border-borda-forte accent-[var(--color-acento)]">
                        <span class="text-[14px] leading-relaxed">
                            Li e estou ciente dos riscos deste atendimento presencial.
                        </span>
                    </label>
                @else
                    <div class="aviso">
                        O termo de ciência ainda não foi publicado pela Operação. Favores presenciais
                        não podem ser pedidos até lá.
                    </div>
                @endif
            </div>
        @endif

        @error('pedido')
            <div class="aviso">{{ $message }}</div>
        @enderror

        <button type="submit" class="botao botao-principal w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="pedir">Enviar pedido</span>
            <span wire:loading wire:target="pedir">Enviando…</span>
        </button>
    </form>
</div>
