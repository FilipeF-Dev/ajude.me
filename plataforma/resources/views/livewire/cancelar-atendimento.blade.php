<div>
    @if (! $confirmando)
        <button type="button" wire:click="abrir" class="botao botao-secundario w-full">
            Cancelar o atendimento
        </button>
    @else
        <div class="cartao flex flex-col gap-3 p-5">
            <h3 class="text-[16px] font-semibold">Cancelar o atendimento</h3>

            {{-- RF-CAN-16 — a resposta precisa existir antes de confirmar. --}}
            @if ($geraOcorrencia)
                <div class="aviso">
                    @if ($ehAjudante)
                        O cancelamento pelo ajudante registra uma ocorrência no seu histórico,
                        em qualquer prazo. Quem se comprometeu a atender responde por desmarcar.
                    @else
                        Faltam menos de 24 horas: este cancelamento registra uma ocorrência no
                        seu histórico. Você continua podendo cancelar.
                    @endif
                </div>
            @else
                <p class="text-[14px] leading-relaxed text-tinta-media">
                    Com esta antecedência, o cancelamento não gera ocorrência nem penalidade.
                    O ticket volta para a carteira.
                </p>
            @endif

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">
                    Justificativa {{ $ehAjudante ? '' : '(opcional)' }}
                </span>
                <textarea wire:model="justificativa" class="campo"
                          placeholder="O que aconteceu?"></textarea>
                @error('cancelamento')
                    <span class="text-[13px] text-emergencia">{{ $message }}</span>
                @enderror
            </label>

            <button type="button" wire:click="cancelar" class="botao w-full"
                    style="background-color: var(--color-emergencia); color: #fff;">
                Confirmar cancelamento
            </button>
            <button type="button" wire:click="fechar" class="botao botao-secundario w-full">
                Voltar
            </button>
        </div>
    @endif
</div>
