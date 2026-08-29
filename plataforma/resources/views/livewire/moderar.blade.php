<div class="cartao flex flex-col gap-3 p-5">
    <h3 class="text-[15px] font-semibold">Se algo não estiver certo</h3>

    @if ($recado)
        <div class="rounded-[14px] bg-acento-fraco px-4 py-3 text-[14px] leading-snug text-tinta-media">
            {{ $recado }}
        </div>
    @endif

    @error('moderacao')
        <div class="aviso">{{ $message }}</div>
    @enderror

    @if ($denunciando)
        <div class="flex flex-col gap-3">
            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">O que aconteceu?</span>
                <textarea wire:model="motivo" class="campo"
                          placeholder="Descreva o que motivou a denúncia."></textarea>
            </label>

            <p class="text-[13px] leading-relaxed text-tinta-fraca">
                A denúncia vai para a Operação analisar.
                Ela <strong class="font-semibold">não bloqueia</strong> a pessoa —
                se você também quiser deixar de ver os favores dela, bloqueie separadamente.
            </p>

            <button type="button" wire:click="denunciar" class="botao botao-principal w-full">
                Enviar denúncia
            </button>
            <button type="button" wire:click="fecharDenuncia" class="botao botao-secundario w-full">
                Voltar
            </button>
        </div>
    @else
        <button type="button" wire:click="abrirDenuncia" class="botao botao-secundario w-full">
            Denunciar {{ $alvo->nome }}
        </button>

        @if ($bloqueado)
            <button type="button" wire:click="desbloquear" class="botao botao-secundario w-full">
                Desfazer bloqueio
            </button>
        @else
            <button type="button" wire:click="bloquear" class="botao botao-secundario w-full">
                Bloquear {{ $alvo->nome }}
            </button>

            {{-- RF-BLO-05 e RF-BLO-06 — dito antes, porque muda a decisão. --}}
            <p class="text-[13px] leading-relaxed text-tinta-fraca">
                Bloquear oculta os perfis dos dois lados e impede novas propostas.
                Não é avaliação negativa e não avisa a outra pessoa.
                E <strong class="font-semibold">não cancela atendimento já combinado</strong>
                nem invalida tickets já trocados.
            </p>
        @endif
    @endif
</div>
