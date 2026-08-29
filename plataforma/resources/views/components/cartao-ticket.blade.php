@props(['ticket'])

@php
    use App\Models\Ticket;

    $favor = $ticket->favor;
    $ajudante = $favor->proficienciaUsuario->usuario;

    $rotulos = [
        Ticket::DISPONIVEL => ['Disponível', 'text-acento', 'bg-acento-fraco'],
        Ticket::EM_NEGOCIACAO => ['Em negociação', 'text-aviso-tinta', 'bg-aviso-fundo'],
        Ticket::RESERVADO => ['Agendado', 'text-aviso-tinta', 'bg-aviso-fundo'],
        Ticket::EM_ANALISE => ['Em análise', 'text-aviso-tinta', 'bg-aviso-fundo'],
        Ticket::CONSUMIDO => ['Recebido', 'text-tinta-suave', 'bg-neutro'],
        Ticket::ENCERRADO => ['Encerrado', 'text-tinta-suave', 'bg-neutro'],
        Ticket::EXPIRADO => ['Vencido', 'text-tinta-suave', 'bg-neutro'],
        Ticket::DESCARTADO => ['Descartado', 'text-tinta-suave', 'bg-neutro'],
    ];

    [$rotulo, $cor, $fundo] = $rotulos[$ticket->estado];
    $diasParaVencer = (int) now()->diffInDays($ticket->expira_em, false);
@endphp

<div class="cartao flex flex-col gap-3.5 p-4">
    <div class="flex items-start gap-3.5">
        <x-avatar :nome="$ajudante->nome" />
        <div class="flex min-w-0 flex-grow flex-col gap-1">
            <span class="text-[16px] font-semibold leading-tight tracking-[-0.01em]">{{ $favor->titulo }}</span>
            <span class="text-[14px] text-tinta-suave">{{ $ajudante->nome }}</span>
        </div>
        <span class="pilula shrink-0 {{ $fundo }} {{ $cor }} text-[12px] font-semibold">{{ $rotulo }}</span>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <x-descritor :favor="$favor" />

        @if ($ticket->ocupaVaga() && ! $ticket->estaProtegidoDaExpiracao())
            <span class="text-[13px] font-medium {{ $diasParaVencer <= 7 ? 'text-aviso-tinta' : 'text-tinta-suave' }}">
                @if ($diasParaVencer < 0)
                    Vencido
                @elseif ($diasParaVencer === 0)
                    Vence hoje
                @else
                    Vence em {{ $diasParaVencer }} dia{{ $diasParaVencer === 1 ? '' : 's' }}
                @endif
            </span>
        @elseif ($ticket->estado === \App\Models\Ticket::EM_ANALISE)
            {{-- I7 — a demora da plataforma não extingue direito em disputa. --}}
            <span class="text-[13px] font-medium text-tinta-suave">Prazo suspenso durante a análise</span>
        @endif
    </div>

    @if ($ticket->podeSerSolicitado())
        <a href="{{ route('solicitar', $ticket) }}" wire:navigate class="botao botao-principal w-full">
            Pedir este favor
        </a>
    @endif
</div>
