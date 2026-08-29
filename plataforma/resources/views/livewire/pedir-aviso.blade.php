<div>
    @if ($naFila)
        <div class="flex items-center gap-2.5 rounded-[14px] bg-acento-fraco px-4 py-3">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-acento)"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                 class="shrink-0">
                <path d="M20 6 9 17l-5-5"/>
            </svg>
            <span class="text-[14px] leading-snug text-tinta-media">
                Vou te avisar quando alguém oferecer <strong class="font-semibold">{{ $proficiencia->nome }}</strong>
                na sua região.
            </span>
        </div>
    @else
        <button type="button" wire:click="pedir" class="botao botao-secundario w-full">
            Avise-me sobre {{ $proficiencia->nome }}
        </button>
    @endif
</div>
