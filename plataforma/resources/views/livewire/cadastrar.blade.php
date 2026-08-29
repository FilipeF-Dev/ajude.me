<div class="flex flex-1 flex-col px-5 pb-10 pt-[max(40px,env(safe-area-inset-top))]">
    <div class="mb-7 flex flex-col gap-2">
        <h1 class="text-[26px] font-bold leading-tight tracking-[-0.03em]">Criar conta</h1>
        <p class="text-[15px] leading-relaxed text-tinta-suave">
            Leva um minuto. O endereço e as suas habilidades você cadastra depois.
        </p>
    </div>

    <form wire:submit="cadastrar" class="flex flex-col gap-4">
        <label class="flex flex-col gap-2">
            <span class="text-[14px] font-semibold text-tinta-media">Nome completo</span>
            <input type="text" wire:model="nome" class="campo" autocomplete="name" required>
            @error('nome') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
        </label>

        <label class="flex flex-col gap-2">
            <span class="text-[14px] font-semibold text-tinta-media">E-mail</span>
            <input type="email" wire:model="email" class="campo" autocomplete="email"
                   inputmode="email" autocapitalize="none" required>
            @error('email') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
        </label>

        <label class="flex flex-col gap-2">
            <span class="text-[14px] font-semibold text-tinta-media">Celular</span>
            <input type="tel" wire:model="telefone" class="campo" autocomplete="tel"
                   inputmode="tel" placeholder="(11) 98842-0000" required>
            @error('telefone') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
        </label>

        <label class="flex flex-col gap-2">
            <span class="text-[14px] font-semibold text-tinta-media">CPF</span>
            <input type="text" wire:model="cpf" class="campo" inputmode="numeric"
                   placeholder="000.000.000-00" required>
            {{-- RF-CAD-10 — o CPF não muda depois. Dizer isso antes evita a
                 correção que a plataforma não pode fazer. --}}
            <span class="text-[13px] text-tinta-fraca">Não poderá ser alterado depois.</span>
            @error('cpf') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
        </label>

        <label class="flex flex-col gap-2">
            <span class="text-[14px] font-semibold text-tinta-media">Senha</span>
            <input type="password" wire:model="senha" class="campo" autocomplete="new-password" required>
            <span class="text-[13px] text-tinta-fraca">Ao menos 8 caracteres.</span>
            @error('senha') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
        </label>

        <label class="flex flex-col gap-2">
            <span class="text-[14px] font-semibold text-tinta-media">Repita a senha</span>
            <input type="password" wire:model="senha_confirmation" class="campo"
                   autocomplete="new-password" required>
        </label>

        <button type="submit" class="botao botao-principal mt-3 w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="cadastrar">Criar conta</span>
            <span wire:loading wire:target="cadastrar">Criando…</span>
        </button>
    </form>

    <p class="mt-7 text-center text-[15px] text-tinta-suave">
        Já tem conta?
        <a href="{{ route('entrar') }}" wire:navigate class="font-semibold text-acento">Entrar</a>
    </p>
</div>
