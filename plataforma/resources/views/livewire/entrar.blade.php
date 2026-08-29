<div class="flex flex-1 flex-col px-5 pb-10 pt-[max(64px,env(safe-area-inset-top))]">
    <div class="flex flex-1 flex-col justify-center gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="text-[28px] font-bold leading-tight tracking-[-0.03em]">Ajude.me</h1>
            <p class="text-[15px] leading-relaxed text-tinta-suave">
                Aqui a sua habilidade é a moeda. Você ajuda alguém, alguém ajuda você.
            </p>
        </div>

        <form wire:submit="entrar" class="flex flex-col gap-4">
            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">E-mail</span>
                <input type="email" wire:model="email" class="campo" autocomplete="email"
                       inputmode="email" autocapitalize="none" required>
                @error('email')
                    <span class="text-[13px] text-emergencia">{{ $message }}</span>
                @enderror
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Senha</span>
                <input type="password" wire:model="senha" class="campo" autocomplete="current-password" required>
                @error('senha')
                    <span class="text-[13px] text-emergencia">{{ $message }}</span>
                @enderror
            </label>

            <label class="flex items-center gap-3 py-1">
                <input type="checkbox" wire:model="lembrar"
                       class="h-5 w-5 rounded border-borda-forte accent-[var(--color-acento)]">
                <span class="text-[14px] text-tinta-media">Continuar conectado neste aparelho</span>
            </label>

            <button type="submit" class="botao botao-principal mt-2 w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="entrar">Entrar</span>
                <span wire:loading wire:target="entrar">Entrando…</span>
            </button>
        </form>
    </div>

    <p class="mt-8 text-center text-[15px] text-tinta-suave">
        Ainda não tem conta?
        <a href="{{ route('cadastrar') }}" wire:navigate class="font-semibold text-acento">Criar conta</a>
    </p>
</div>
