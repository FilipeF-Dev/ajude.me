@php use App\Models\Proposta; @endphp

<div>
    <x-cabecalho :titulo="$contraparte->nome" :voltar="route('conversas')" />

    <div class="flex flex-col gap-4 px-5">
        <div class="cartao flex flex-col gap-4 p-5">
            <div class="flex flex-col gap-2">
                <span class="text-[13px] text-tinta-suave">
                    {{ $souRemetente ? 'Você pediu' : $contraparte->nome.' pediu' }}
                </span>
                @foreach ($solicitados as $item)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[15px] font-medium">{{ $item->ticket->favor->titulo }}</span>
                        <x-descritor :favor="$item->ticket->favor" />
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-2 border-t border-borda pt-4">
                <span class="text-[13px] text-tinta-suave">Em troca de</span>
                @forelse ($contrapartida as $item)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[15px] font-medium">{{ $item->ticket->favor->titulo }}</span>
                        <x-descritor :favor="$item->ticket->favor" />
                    </div>
                @empty
                    <span class="text-[14px] text-tinta-suave">Nada — é um pedido sem contrapartida.</span>
                @endforelse
            </div>

            @if ($proposta->estaAtiva())
                <span class="text-[13px] text-tinta-fraca">
                    Versão {{ $proposta->versao_atual }} · vale até
                    {{ $proposta->expira_em->format('d/m/Y') }}
                </span>
            @endif
        </div>

        @error('acao')
            <div class="aviso">{{ $message }}</div>
        @enderror

        @if ($proposta->estaAtiva())
            <div class="flex flex-col gap-3">
                @if ($podeAceitar)
                    <button type="button" wire:click="aceitar" class="botao botao-principal w-full">
                        Aceitar a troca
                    </button>
                @else
                    {{-- RF-NEG-09 — quem revisou por último aguarda a decisão
                         da outra parte. --}}
                    <p class="text-center text-[14px] leading-relaxed text-tinta-suave">
                        Aguardando a resposta de {{ $contraparte->nome }}.
                    </p>
                @endif

                @if ($souRemetente)
                    <button type="button" wire:click="retirar" class="botao botao-secundario w-full">
                        Retirar proposta
                    </button>
                @else
                    <button type="button" wire:click="recusar" class="botao botao-secundario w-full">
                        Recusar
                    </button>
                @endif
            </div>
        @else
            <div class="cartao p-5 text-[14px] text-tinta-media">
                Esta proposta está {{ $proposta->estado === Proposta::ACEITA ? 'aceita' : 'encerrada' }}.
                @if ($proposta->estado === Proposta::ACEITA)
                    Os tickets já estão nas carteiras novas.
                @endif
            </div>
        @endif

        <div class="cartao flex flex-col gap-4 p-5">
            <h3 class="text-[16px] font-semibold">Conversa</h3>

            <div class="flex flex-col gap-3">
                @forelse ($mensagens as $msg)
                    @php $minha = $msg->autor_id === auth()->id(); @endphp
                    <div class="flex flex-col gap-1 {{ $minha ? 'items-end' : 'items-start' }}">
                        <span class="max-w-[80%] rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed
                                     {{ $minha ? 'bg-acento text-white' : 'bg-neutro text-tinta' }}">
                            {{ $msg->texto }}
                        </span>
                        <span class="text-[12px] text-tinta-fraca">{{ $msg->enviada_em->format('d/m H:i') }}</span>
                    </div>
                @empty
                    <p class="text-[14px] text-tinta-suave">Nenhuma mensagem ainda.</p>
                @endforelse
            </div>

            @if ($proposta->estaAtiva())
                <form wire:submit="enviarMensagem" class="flex items-end gap-2">
                    <input type="text" wire:model="mensagem" class="campo flex-grow"
                           placeholder="Escreva uma mensagem" aria-label="Mensagem">
                    <button type="submit" class="botao botao-principal shrink-0 px-5" aria-label="Enviar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m4 12 16-8-6 16-2.5-6.5L4 12Z"/>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
