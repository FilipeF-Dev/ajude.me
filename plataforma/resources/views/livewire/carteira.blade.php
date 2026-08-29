@php
    $abas = [
        \App\Models\Ticket::DISPONIVEL => ['Disponíveis', $disponiveis],
        'em_uso' => ['Em uso', $emUso],
        'encerrados' => ['Encerrados', $encerrados],
    ];
    $atual = $abas[$aba] ?? $abas[\App\Models\Ticket::DISPONIVEL];
@endphp

<div>
    <x-cabecalho titulo="Sua carteira" />

    <p class="px-5 pb-4 text-[14px] leading-relaxed text-tinta-suave">
        Cada ticket é o direito de receber um favor de alguém. Não vence de uso — vence de prazo.
    </p>

    @if ($atendimentos->isNotEmpty())
        <div class="flex flex-col gap-3 px-5 pb-5">
            <h2 class="text-[15px] font-semibold text-tinta-media">Atendimentos combinados</h2>

            @foreach ($atendimentos as $atendimento)
                @php
                    $souAjudante = $atendimento->ajudante_id === auth()->id();
                    $outro = $souAjudante ? $atendimento->solicitante : $atendimento->ajudante;
                    $precisaAgir = in_array($atendimento->estado, [
                        \App\Models\Solicitacao::AGUARDANDO_CONFIRMACAO,
                        \App\Models\Solicitacao::REALIZADA,
                    ], true) || ($souAjudante && $atendimento->estado === \App\Models\Solicitacao::SOLICITADA);
                @endphp

                <a href="{{ route('atendimento', $atendimento) }}" wire:navigate
                   class="cartao flex items-start gap-3.5 p-4">
                    <x-avatar :nome="$outro->nome" />
                    <div class="flex min-w-0 flex-grow flex-col gap-1">
                        <span class="text-[15px] font-semibold leading-tight">
                            {{ $atendimento->ticket->favor->titulo }}
                        </span>
                        <span class="text-[13px] text-tinta-suave">
                            {{ $souAjudante ? 'Você atende' : 'Com' }} {{ $outro->nome }} ·
                            {{ $atendimento->inicio_previsto->translatedFormat('d/m \à\s H:i') }}
                        </span>
                    </div>
                    @if ($precisaAgir)
                        <span class="pilula shrink-0 bg-aviso-fundo text-[12px] font-semibold text-aviso-tinta">
                            Precisa de você
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    <div class="flex gap-2 overflow-x-auto px-5 pb-4 [scrollbar-width:none]">
        @foreach ($abas as $chave => [$rotulo, $lista])
            <button type="button" wire:click="mostrar('{{ $chave }}')"
                    class="pilula {{ $aba === $chave ? 'bg-acento font-semibold text-white' : 'border border-borda bg-superficie text-tinta-media' }}">
                {{ $rotulo }} · {{ $lista->count() }}
            </button>
        @endforeach
    </div>

    <div class="flex flex-col gap-3 px-5">
        @forelse ($atual[1] as $ticket)
            <x-cartao-ticket :ticket="$ticket" />
        @empty
            <div class="cartao flex flex-col gap-3 p-5">
                @if ($aba === \App\Models\Ticket::DISPONIVEL)
                    <h3 class="text-[17px] font-semibold">Sua carteira está vazia</h3>
                    <p class="text-[14px] leading-relaxed text-tinta-suave">
                        Tickets chegam de duas formas: você oferece um favor e emite os seus,
                        ou alguém aceita uma troca com você.
                    </p>
                    <a href="{{ route('oferecer') }}" wire:navigate class="botao botao-principal mt-1 w-full">
                        Oferecer ajuda
                    </a>
                @else
                    <p class="text-[14px] leading-relaxed text-tinta-suave">Nada por aqui ainda.</p>
                @endif
            </div>
        @endforelse
    </div>
</div>
