<div>
    <x-cabecalho titulo="O favor" :voltar="route('inicio')" />

    <div class="flex flex-col gap-4 px-5">
        <div class="cartao flex flex-col gap-4 p-5">
            <h2 class="text-[20px] font-bold leading-tight tracking-[-0.02em]">{{ $favor->titulo }}</h2>

            <div class="flex items-center gap-2">
                <x-descritor :favor="$favor" />
                <span class="text-[13px] font-medium text-tinta-suave">
                    {{ $favor->modalidade === \App\Models\Favor::MODALIDADE_PRESENCIAL ? 'Presencial' : 'Remoto' }}
                </span>
            </div>

            <p class="whitespace-pre-line text-[15px] leading-relaxed text-tinta-media">{{ $favor->descricao }}</p>
        </div>

        <a href="{{ route('perfil.publico', $ajudante) }}" wire:navigate class="cartao flex items-center gap-3.5 p-4">
            <x-avatar :nome="$ajudante->nome" :tamanho="52" />
            <div class="flex min-w-0 flex-grow flex-col gap-1">
                <span class="text-[16px] font-semibold">{{ $ajudante->nome }}</span>
                <span class="text-[14px] text-tinta-suave">
                    {{ $favor->proficienciaUsuario->proficiencia->nome }}
                </span>
                <span class="text-[13px] text-tinta-suave">
                    @if ($temReputacao)
                        ★ {{ number_format($media, 1, ',', '.') }} · {{ $favoresPrestados }} favores prestados
                    @else
                        {{-- RF-REP-08 — média sobre uma avaliação só é opinião
                             com aparência de estatística. --}}
                        Ainda sem avaliações suficientes
                    @endif
                </span>
            </div>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-tinta-fraca)"
                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m9 18 6-6-6-6"/>
            </svg>
        </a>

        {{-- RF-BUS-08 — todas as condições, antes de qualquer negociação. --}}
        <div class="cartao flex flex-col gap-4 p-5">
            <h3 class="text-[16px] font-semibold">Condições combinadas</h3>

            <dl class="flex flex-col gap-3.5 text-[14px]">
                <div class="flex justify-between gap-4">
                    <dt class="text-tinta-suave">Tempo estimado</dt>
                    <dd class="text-right font-medium">{{ $favor->tempoPorExtenso() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-tinta-suave">Nível declarado</dt>
                    <dd class="text-right font-medium">
                        {{ \App\Models\ProficienciaUsuario::niveis()[$favor->nivel()] }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-tinta-suave">Antecedência mínima</dt>
                    <dd class="text-right font-medium">
                        {{ $favor->antecedencia_minima_dias }} dia{{ $favor->antecedencia_minima_dias === 1 ? '' : 's' }}
                    </dd>
                </div>
                @if ($favor->modalidade === \App\Models\Favor::MODALIDADE_PRESENCIAL)
                    <div class="flex justify-between gap-4">
                        <dt class="text-tinta-suave">Área de atendimento</dt>
                        <dd class="text-right font-medium">Até {{ $favor->area_atendimento_km }} km</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-tinta-suave">Validade do ticket</dt>
                    <dd class="text-right font-medium">{{ $favor->validade_ticket_dias }} dias</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-tinta-suave">Pode ser trocado</dt>
                    <dd class="text-right font-medium">{{ $favor->trocavel ? 'Sim' : 'Não' }}</dd>
                </div>
            </dl>

            @if ($favor->janelas->isNotEmpty())
                <div class="flex flex-col gap-2 border-t border-borda pt-3.5">
                    <span class="text-[14px] text-tinta-suave">Atende</span>
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

            @if ($favor->condicoes)
                <div class="flex flex-col gap-2 border-t border-borda pt-3.5">
                    <span class="text-[14px] text-tinta-suave">Observações do ajudante</span>
                    <p class="whitespace-pre-line text-[14px] leading-relaxed">{{ $favor->condicoes }}</p>
                </div>
            @endif

            @if ($favor->estaCongelado())
                <p class="text-[13px] leading-relaxed text-tinta-fraca">
                    Estas condições estão congeladas desde a primeira emissão e não mudam mais.
                </p>
            @endif
        </div>

        <div class="cartao flex flex-col gap-3 p-5">
            @if ($disponiveis > 0)
                <p class="text-[14px] text-tinta-media">
                    <strong class="font-semibold">{{ $disponiveis }}</strong>
                    ticket{{ $disponiveis === 1 ? '' : 's' }} disponíve{{ $disponiveis === 1 ? 'l' : 'is' }} agora.
                </p>
                <a href="{{ route('propor', $favor) }}" wire:navigate class="botao botao-principal w-full">
                    Propor uma troca
                </a>
                <p class="text-[13px] leading-relaxed text-tinta-fraca">
                    Você oferece tickets da sua carteira em troca. Sem dinheiro envolvido.
                </p>
            @else
                <p class="text-[14px] text-tinta-suave">Não há tickets disponíveis neste favor agora.</p>
            @endif
        </div>
    </div>
</div>
