<div>
    <header class="flex items-center justify-between gap-3 px-5 pb-3.5 pt-[max(40px,env(safe-area-inset-top))]">
        <div class="flex flex-col gap-0.5">
            <span class="text-[13px] text-tinta-suave">Você está em</span>
            <span class="flex items-center gap-1.5 text-[18px] font-semibold tracking-[-0.01em]">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--color-acento)"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                </svg>
                {{ auth()->user()?->endereco?->bairro ?? 'Defina sua região' }}
            </span>
        </div>
        <a href="{{ route('perfil') }}" wire:navigate aria-label="Seu perfil">
            <x-avatar :nome="auth()->user()->nome" />
        </a>
    </header>

    <div class="flex flex-col gap-2.5 px-5 pb-4">
        <h2 class="text-[20px] font-bold tracking-[-0.02em]">Do que você precisa de ajuda?</h2>

        <form wire:submit="buscar" class="flex items-center gap-3 rounded-[16px] border border-borda bg-superficie px-4 shadow-[var(--shadow-cartao)]"
              style="height: 54px;">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="var(--color-acento)"
                 stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
            <input type="search" wire:model="necessidade"
                   class="w-full bg-transparent text-[15px] outline-none placeholder:text-tinta-fraca"
                   placeholder="Ex.: trocar a resistência do chuveiro"
                   aria-label="Descreva o que você precisa">
            @if ($necessidade !== '')
                <button type="button" wire:click="limpar" class="-mr-1 shrink-0 text-tinta-fraca"
                        style="min-height: 32px;" aria-label="Limpar busca">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </form>
    </div>

    {{-- RF-BUS-18 — a interpretação aparece ANTES dos resultados.
         Não é detalhe de interface: o reconhecimento pode errar, e resultado
         inexplicado parece defeito. --}}
    @if ($buscou && $aplicaveis->isNotEmpty())
        <div class="flex flex-col gap-2 px-5 pb-4">
            <span class="text-[13px] text-tinta-suave">Entendi que você procura</span>
            <div class="flex flex-wrap gap-2">
                @foreach ($aplicaveis as $proficiencia)
                    <span class="pilula flex items-center gap-2 border border-borda bg-superficie text-tinta-media">
                        {{ $proficiencia->nome }}
                        <button type="button" wire:click="descartar({{ $proficiencia->id }})"
                                class="-mr-1 text-tinta-fraca" style="min-height: 24px;"
                                aria-label="Não é {{ $proficiencia->nome }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @unless ($buscou)
        <div class="flex gap-2 overflow-x-auto px-5 pb-4.5 [scrollbar-width:none]">
            <button type="button" wire:click="filtrarPorCategoria(null)"
                    class="pilula {{ $categoriaId === null ? 'bg-acento font-semibold text-white' : 'border border-borda bg-superficie text-tinta-media' }}">
                Tudo
            </button>
            @foreach ($categorias as $categoria)
                <button type="button" wire:click="filtrarPorCategoria({{ $categoria->id }})"
                        class="pilula {{ $categoriaId === $categoria->id ? 'bg-acento font-semibold text-white' : 'border border-borda bg-superficie text-tinta-media' }}">
                    {{ $categoria->nome }}
                </button>
            @endforeach
        </div>
    @endunless

    <div class="flex flex-col gap-3 px-5">
        @if ($favores->isNotEmpty())
            <h3 class="text-[15px] font-semibold text-tinta-media">
                {{ $buscou ? 'Quem pode ajudar' : 'Ajuda disponível perto de você' }}
            </h3>

            @foreach ($favores as $favor)
                <x-cartao-favor :favor="$favor" />
            @endforeach

        @elseif ($buscou)
            {{-- RF-BUS-21 — uma busca sem resposta não é resultado vazio.
                 É o principal sinal de demanda da plataforma (02 §3.5). --}}
            <div class="cartao flex flex-col gap-4 p-5">
                <div class="flex flex-col gap-2">
                    <h3 class="text-[17px] font-semibold">Ainda não há ninguém oferecendo isso por aqui</h3>
                    <p class="text-[14px] leading-relaxed text-tinta-suave">
                        Sua busca não some: ela mostra à plataforma o que falta na sua região.
                    </p>
                </div>

                @if ($aplicaveis->isNotEmpty())
                    <div class="flex flex-col gap-2">
                        @foreach ($aplicaveis as $proficiencia)
                            <livewire:pedir-aviso :proficiencia="$proficiencia"
                                                  :key="'aviso-'.$proficiencia->id" />
                        @endforeach
                    </div>
                @else
                    <p class="text-[14px] leading-relaxed text-tinta-suave">
                        Não reconheci nenhuma habilidade nessa descrição. Tente com outras palavras —
                        e saiba que a Operação lê estes casos para corrigir o vocabulário.
                    </p>
                @endif

                @if ($raioKm !== null)
                    <button type="button" wire:click="ampliarArea" class="botao botao-secundario w-full">
                        Procurar numa área maior
                    </button>
                @endif
            </div>

        @else
            <div class="cartao flex flex-col gap-3 p-5">
                <h3 class="text-[17px] font-semibold">Ainda não há favores por aqui</h3>
                <p class="text-[14px] leading-relaxed text-tinta-suave">
                    Que tal começar oferecendo o que você sabe fazer? Uma rede de troca
                    precisa de quem ofereça tanto quanto de quem precise.
                </p>
                <a href="{{ route('oferecer') }}" wire:navigate class="botao botao-principal mt-1 w-full">
                    Oferecer ajuda
                </a>
            </div>
        @endif
    </div>
</div>
