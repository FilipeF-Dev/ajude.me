@php
    $atual = fn (string ...$rotas) => collect($rotas)->contains(fn ($r) => request()->routeIs($r));
@endphp

{{--
    Barra inferior: Início · Carteira · [Oferecer] · Conversas · Perfil.

    O círculo central é deliberado (00 §7.2): dá a ofertar o mesmo peso que a
    descobrir, contra o risco de a rede encher de demanda e esvaziar de oferta
    (06 R2). Um MVP cheio de solicitantes e vazio de ajudantes é um MVP
    fracassado, mesmo com número alto de cadastro (03 §2, H5).
--}}
<nav class="fixed inset-x-0 bottom-0 z-30 px-4 pb-[max(16px,env(safe-area-inset-bottom))] pt-3"
     aria-label="Navegação principal">
    <div class="mx-auto flex h-[68px] max-w-[488px] items-center justify-between rounded-[22px] border border-borda bg-superficie px-1.5 shadow-[var(--shadow-barra)]">

        <x-barra-item rota="inicio" rotulo="Início" :ativo="$atual('inicio')">
            <path d="M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1Z"/>
        </x-barra-item>

        <x-barra-item rota="carteira" rotulo="Carteira" :ativo="$atual('carteira')">
            <path d="M3 8a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v2"/>
            <path d="M3 8v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6a1 1 0 0 0-1-1H5a2 2 0 0 1-2-2Z"/>
        </x-barra-item>

        <a href="{{ route('oferecer') }}"
           class="flex w-[64px] shrink-0 flex-col items-center"
           aria-label="Oferecer ajuda">
            <span class="flex h-[52px] w-[52px] items-center justify-center rounded-full bg-acento text-white shadow-[0_4px_12px_rgba(46,107,79,0.28)]">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
            </span>
        </a>

        <x-barra-item rota="conversas" rotulo="Conversas" :ativo="$atual('conversas')">
            <path d="M20 15a2 2 0 0 1-2 2H8l-4 3V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2Z"/>
        </x-barra-item>

        <x-barra-item rota="perfil" rotulo="Perfil" :ativo="$atual('perfil')">
            <circle cx="12" cy="8" r="3.5"/>
            <path d="M5 20c0-3.3 3.1-5.5 7-5.5s7 2.2 7 5.5"/>
        </x-barra-item>
    </div>
</nav>
