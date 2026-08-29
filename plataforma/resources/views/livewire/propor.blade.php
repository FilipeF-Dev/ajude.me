<div>
    <x-cabecalho titulo="Montar proposta" :voltar="route('favor', $favor)" />

    <div class="flex flex-col gap-4 px-5">
        <div class="cartao flex flex-col gap-3 p-5">
            <h3 class="text-[16px] font-semibold">Você quer receber</h3>
            @forelse ($doFavor as $ticket)
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" wire:model="solicitados" value="{{ $ticket->id }}"
                           class="mt-1 h-5 w-5 shrink-0 rounded border-borda-forte accent-[var(--color-acento)]">
                    <span class="flex flex-col gap-1.5">
                        <span class="text-[15px] font-medium">{{ $favor->titulo }}</span>
                        <x-descritor :favor="$favor" />
                    </span>
                </label>
            @empty
                <p class="text-[14px] text-tinta-suave">Não há tickets disponíveis neste favor agora.</p>
            @endforelse
        </div>

        <div class="cartao flex flex-col gap-3 p-5">
            <h3 class="text-[16px] font-semibold">Você oferece em troca</h3>

            @forelse ($minhaCarteira as $ticket)
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" wire:model="contrapartida" value="{{ $ticket->id }}"
                           class="mt-1 h-5 w-5 shrink-0 rounded border-borda-forte accent-[var(--color-acento)]">
                    <span class="flex flex-col gap-1.5">
                        <span class="text-[15px] font-medium">{{ $ticket->favor->titulo }}</span>
                        <span class="text-[13px] text-tinta-suave">
                            de {{ $ticket->favor->proficienciaUsuario->usuario->nome }}
                        </span>
                        <x-descritor :favor="$ticket->favor" />
                    </span>
                </label>
            @empty
                {{-- 02 §5.4 — a contrapartida pode ser vazia. Pedir sem ter o
                     que oferecer é o caso de quem ainda não entrou na rede. --}}
                <p class="text-[14px] leading-relaxed text-tinta-suave">
                    Sua carteira está vazia — e tudo bem. Você pode pedir mesmo sem ter o que
                    oferecer agora; cabe à outra pessoa aceitar ou não.
                </p>
            @endforelse
        </div>

        @error('proposta')
            <div class="aviso">{{ $message }}</div>
        @enderror

        <button type="button" wire:click="enviar" class="botao botao-principal w-full"
                wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="enviar">Enviar proposta</span>
            <span wire:loading wire:target="enviar">Enviando…</span>
        </button>

        <p class="pb-2 text-center text-[13px] leading-relaxed text-tinta-fraca">
            Os tickets ficam bloqueados enquanto a proposta estiver de pé. Se ela for recusada
            ou vencer, voltam para as carteiras.
        </p>
    </div>
</div>
