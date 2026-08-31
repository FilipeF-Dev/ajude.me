<div class="flex flex-1 flex-col px-5 pb-10 pt-[max(64px,env(safe-area-inset-top))]">
    <div class="flex flex-1 flex-col justify-center gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="text-[28px] font-bold leading-tight tracking-[-0.03em]">Recuperar acesso</h1>
            <p class="text-[15px] leading-relaxed text-tinta-suave">
                Diga o e-mail da sua conta e mandamos um link para você escolher uma senha nova.
            </p>
        </div>

        @if ($pedido)
            {{-- A mesma frase para e-mail que existe e e-mail que não existe:
                 responder diferente transformaria este formulário em consulta
                 de cadastro (NF-SEG-05). --}}
            <div class="flex flex-col gap-3 rounded-[14px] bg-acento-fraco px-4 py-4">
                <p class="text-[15px] font-semibold text-tinta">Se houver conta com esse e-mail, o link já saiu.</p>
                <p class="text-[14px] leading-relaxed text-tinta-media">
                    Dê uma olhada na caixa de entrada. O link vale por pouco tempo.
                </p>
            </div>
        @else
            <form wire:submit="pedir" class="flex flex-col gap-4">
                <label class="flex flex-col gap-2">
                    <span class="text-[14px] font-semibold text-tinta-media">E-mail</span>
                    <input type="email" wire:model="email" class="campo" autocomplete="email"
                           inputmode="email" autocapitalize="none" required>
                    @error('email')
                        <span class="text-[13px] text-emergencia">{{ $message }}</span>
                    @enderror
                </label>

                <button type="submit" class="botao botao-principal mt-2 w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="pedir">Mandar link</span>
                    <span wire:loading wire:target="pedir">Mandando…</span>
                </button>
            </form>
        @endif
    </div>

    <p class="mt-8 text-center text-[15px] text-tinta-suave">
        Lembrou a senha?
        <a href="{{ route('entrar') }}" wire:navigate class="font-semibold text-acento">Entrar</a>
    </p>
</div>
