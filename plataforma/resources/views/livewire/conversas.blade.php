@php
    use App\Models\Proposta;

    $rotulos = [
        Proposta::ENVIADA => ['Aguardando resposta', 'text-aviso-tinta', 'bg-aviso-fundo'],
        Proposta::EM_NEGOCIACAO => ['Em negociação', 'text-aviso-tinta', 'bg-aviso-fundo'],
        Proposta::ACEITA => ['Aceita', 'text-acento', 'bg-acento-fraco'],
        Proposta::RECUSADA => ['Recusada', 'text-tinta-suave', 'bg-neutro'],
        Proposta::RETIRADA => ['Retirada', 'text-tinta-suave', 'bg-neutro'],
        Proposta::EXPIRADA => ['Expirada', 'text-tinta-suave', 'bg-neutro'],
        Proposta::RASCUNHO => ['Rascunho', 'text-tinta-suave', 'bg-neutro'],
    ];
@endphp

<div>
    <x-cabecalho titulo="Conversas" />

    <div class="flex flex-col gap-3 px-5">
        @forelse ($propostas as $proposta)
            @php
                $outro = $proposta->contraparte(auth()->user());
                [$rotulo, $cor, $fundo] = $rotulos[$proposta->estado];
                $souRemetente = $proposta->remetente_id === auth()->id();
            @endphp

            <a href="{{ route('negociacao', $proposta) }}" wire:navigate class="cartao flex items-start gap-3.5 p-4">
                <x-avatar :nome="$outro->nome" />
                <div class="flex min-w-0 flex-grow flex-col gap-1">
                    <span class="text-[16px] font-semibold leading-tight">{{ $outro->nome }}</span>
                    <span class="text-[13px] text-tinta-suave">
                        {{ $souRemetente ? 'Você propôs' : 'Propôs a você' }}
                        · versão {{ $proposta->versao_atual }}
                    </span>
                </div>
                <span class="pilula shrink-0 {{ $fundo }} {{ $cor }} text-[12px] font-semibold">{{ $rotulo }}</span>
            </a>
        @empty
            <div class="cartao flex flex-col gap-3 p-5">
                <h3 class="text-[17px] font-semibold">Nenhuma conversa ainda</h3>
                <p class="text-[14px] leading-relaxed text-tinta-suave">
                    As negociações aparecem aqui quando você propõe uma troca ou recebe uma proposta.
                </p>
                <a href="{{ route('inicio') }}" wire:navigate class="botao botao-secundario mt-1 w-full">
                    Procurar ajuda
                </a>
            </div>
        @endforelse
    </div>
</div>
