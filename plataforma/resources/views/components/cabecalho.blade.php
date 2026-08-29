@props(['titulo', 'voltar' => null])

<header class="flex items-center gap-3 px-5 pb-4 pt-[max(24px,env(safe-area-inset-top))]">
    @if ($voltar)
        <a href="{{ $voltar }}"
           class="-ml-2 flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-tinta-media"
           aria-label="Voltar">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </a>
    @endif

    <h1 class="text-[20px] font-bold tracking-[-0.02em]">{{ $titulo }}</h1>

    @if (isset($acao))
        <div class="ml-auto">{{ $acao }}</div>
    @endif
</header>
