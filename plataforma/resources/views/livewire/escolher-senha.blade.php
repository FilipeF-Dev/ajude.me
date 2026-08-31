<div class="flex flex-1 flex-col px-5 pb-10 pt-[max(64px,env(safe-area-inset-top))]">
    <div class="flex flex-1 flex-col justify-center gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="text-[28px] font-bold leading-tight tracking-[-0.03em]">Senha nova</h1>
            <p class="text-[15px] leading-relaxed text-tinta-suave">
                Escolha uma senha que você lembre. Quem estiver conectado em outro aparelho vai
                precisar entrar de novo.
            </p>
        </div>

        <form wire:submit="redefinir" class="flex flex-col gap-4">
            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Senha nova</span>
                <input type="password" wire:model="senha" class="campo" autocomplete="new-password" required>
                @error('senha')
                    <span class="text-[13px] text-emergencia">{{ $message }}</span>
                @enderror
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Repita a senha</span>
                <input type="password" wire:model="confirmacao" class="campo" autocomplete="new-password" required>
                @error('confirmacao')
                    <span class="text-[13px] text-emergencia">{{ $message }}</span>
                @enderror
            </label>

            <button type="submit" class="botao botao-principal mt-2 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="redefinir">Trocar a senha</span>
                <span wire:loading wire:target="redefinir">Trocando…</span>
            </button>
        </form>
    </div>

    <p class="mt-8 text-center text-[15px] text-tinta-suave">
        <a href="{{ route('entrar') }}" wire:navigate class="font-semibold text-acento">Voltar para entrar</a>
    </p>
</div>
