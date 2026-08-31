@php
    $rotulos = [
        'comunicacao' => 'Comunicação',
        'qualidade' => 'Qualidade',
        'satisfacao' => 'Satisfação',
        'cordialidade' => 'Cordialidade',
        'condicoes' => 'Condições',
    ];
@endphp

<div>
    <x-cabecalho titulo="Seu perfil" />

    <div class="flex flex-col gap-4 px-5">
        <div class="cartao flex items-center gap-4 p-5">
            <x-avatar :nome="$usuario->nome" :tamanho="60" />
            <div class="flex min-w-0 flex-col gap-1">
                <span class="text-[18px] font-semibold">{{ $usuario->nome }}</span>
                <span class="text-[14px] text-tinta-suave">
                    {{ $prestados }} prestados · {{ $recebidos }} recebidos
                </span>
                <span class="text-[13px] text-tinta-fraca">
                    Na plataforma há {{ $dias }} dia{{ $dias === 1 ? '' : 's' }}
                </span>
            </div>
        </div>

        {{-- RF-CAD-06. É "Contato verificado", e não "Identidade verificada":
             sem verificação documental, o selo mais forte seria mentira, e
             selo falso é pior que selo nenhum (03 D1). --}}
        @if ($usuario->contatoVerificado())
            <div class="flex items-center gap-2.5 rounded-[14px] bg-acento-fraco px-4 py-3">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"
                     class="shrink-0 text-acento">
                    <path d="M12 3 5 6v6c0 4.4 3 8.2 7 9 4-.8 7-4.6 7-9V6Z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span class="text-[14px] font-semibold text-tinta-media">Contato verificado</span>
            </div>
        @else
            <a
                href="{{ route('verificar-contato') }}"
                wire:navigate
                class="flex items-center gap-3 rounded-[14px] border border-borda bg-superficie p-4"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"
                     class="shrink-0 text-tinta-suave">
                    <path d="M12 3 5 6v6c0 4.4 3 8.2 7 9 4-.8 7-4.6 7-9V6Z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span class="flex min-w-0 flex-col">
                    <span class="text-[15px] font-semibold text-tinta">Confirmar contato</span>
                    <span class="text-[13px] leading-relaxed text-tinta-suave">
                        Quem confirma e-mail e telefone ganha o selo que aparece nas trocas.
                    </span>
                </span>
            </a>
        @endif

        @if ($avaliacoesPendentes->isNotEmpty())
            {{-- RF-AVA-12 — avisar antes de restringir. Aplicar a restrição
                 sem ter avisado transformaria a regra em armadilha. --}}
            <div class="aviso">
                Você tem {{ $avaliacoesPendentes->count() }}
                avaliação{{ $avaliacoesPendentes->count() === 1 ? '' : 'ões' }} pendente{{ $avaliacoesPendentes->count() === 1 ? '' : 's' }}.
                Enquanto não avaliar, o ciclo do favor fica aberto para as duas partes — e depois do
                prazo você deixa de poder enviar novas propostas.
            </div>
        @endif

        {{-- RF-REP-01 e RF-REP-02 — duas reputações, uma pessoa. --}}
        <div class="grid grid-cols-2 gap-3">
            @foreach ([['Como ajudante', $comoAjudante], ['Como solicitante', $comoSolicitante]] as [$titulo, $dados])
                <div class="cartao flex flex-col gap-2 p-4">
                    <span class="text-[13px] text-tinta-suave">{{ $titulo }}</span>
                    @if ($dados['suficiente'])
                        <span class="text-[24px] font-bold leading-none">
                            {{ number_format($dados['media'], 1, ',', '.') }}
                            <span class="text-[14px] font-medium text-tinta-fraca">/ 7</span>
                        </span>
                    @else
                        <span class="text-[14px] leading-snug text-tinta-fraca">
                            Ainda sem avaliações suficientes
                        </span>
                    @endif
                </div>
            @endforeach
        </div>

        @foreach ([['Como ajudante', $comoAjudante], ['Como solicitante', $comoSolicitante]] as [$titulo, $dados])
            @if ($dados['criterios'] !== [])
                <div class="cartao flex flex-col gap-3 p-5">
                    <h3 class="text-[15px] font-semibold">{{ $titulo }} · por critério</h3>
                    @foreach ($dados['criterios'] as $criterio => $nota)
                        <div class="flex items-center gap-3">
                            <span class="w-[110px] shrink-0 text-[14px] text-tinta-suave">
                                {{ $rotulos[$criterio] ?? $criterio }}
                            </span>
                            {{-- Barras de 7 segmentos, como na tela de avaliação. --}}
                            <span class="flex flex-grow gap-1" aria-hidden="true">
                                @for ($i = 1; $i <= 7; $i++)
                                    <span class="h-2 flex-1 rounded-full {{ $i <= round($nota) ? 'bg-acento' : 'bg-neutro' }}"></span>
                                @endfor
                            </span>
                            <span class="w-8 shrink-0 text-right text-[14px] font-semibold">
                                {{ number_format($nota, 1, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach

        {{-- RF-REP-04 e RF-REP-07 — reputação por proficiência, ao lado do
             nível declarado. Proficiência não é reputação (02 §3.3). --}}
        @if ($porProficiencia !== [])
            <div class="cartao flex flex-col gap-3 p-5">
                <h3 class="text-[15px] font-semibold">Por habilidade</h3>
                @foreach ($porProficiencia as $linha)
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 flex-col">
                            <span class="text-[14px] font-medium">{{ $linha['proficiencia'] }}</span>
                            <span class="text-[13px] text-tinta-suave">
                                Nível declarado: {{ \App\Models\ProficienciaUsuario::niveis()[$linha['nivel']] }}
                            </span>
                        </div>
                        <span class="text-[15px] font-semibold">
                            {{ number_format($linha['media'], 1, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="cartao flex flex-col gap-3 p-5">
            <h3 class="text-[15px] font-semibold">Suas habilidades</h3>
            @forelse ($proficiencias as $declarada)
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[14px]">{{ $declarada->proficiencia->nome }}</span>
                    <span class="pilula bg-neutro text-[13px] font-medium text-tinta-media">
                        {{ \App\Models\ProficienciaUsuario::niveis()[$declarada->nivel] }}
                    </span>
                </div>
            @empty
                <p class="text-[14px] leading-relaxed text-tinta-suave">
                    Você ainda não declarou nenhuma habilidade.
                </p>
            @endforelse
            <a href="{{ route('oferecer') }}" wire:navigate class="botao botao-secundario mt-1 w-full">
                Oferecer ajuda
            </a>
        </div>

        <button type="button" wire:click="sair" class="botao botao-secundario w-full">Sair</button>
    </div>
</div>
