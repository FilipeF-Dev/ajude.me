<div class="flex flex-1 flex-col px-5 pb-10 pt-[max(40px,env(safe-area-inset-top))]">
    <div class="mb-7 flex flex-col gap-2">
        <h1 class="text-[26px] font-bold leading-tight tracking-[-0.03em]">Criar conta</h1>
        <p class="text-[15px] leading-relaxed text-tinta-suave">
            Leva um minuto. O endereço e as suas habilidades você cadastra depois.
        </p>
    </div>

    <form wire:submit="cadastrar" class="flex flex-col gap-4">
        {{-- RF-ADM-08 e 06 §5.2 — base restrita durante o piloto. --}}
        @if ($exigeConvite)
            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Código do convite</span>
                <input type="text" wire:model="codigoConvite" class="campo"
                       placeholder="AJUD-XXXX" autocapitalize="characters" required>
                <span class="text-[13px] leading-relaxed text-tinta-fraca">
                    Por enquanto o Ajude.me entra por convite. É de propósito: uma rede de troca
                    funciona melhor concentrada num lugar só.
                </span>
                @error('codigoConvite') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
            </label>
        @endif

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

        {{-- 06 R2 — o convite é condicionado à declaração de ao menos uma
             proficiência. Uma rede cheia de quem precisa e vazia de quem
             oferece não é rede de troca. --}}
        <div class="cartao flex flex-col gap-4 p-5">
            <div class="flex flex-col gap-1">
                <span class="text-[16px] font-semibold">O que você sabe fazer?</span>
                <span class="text-[13px] leading-relaxed text-tinta-suave">
                    Aqui todo mundo ajuda e é ajudado. Escolha uma coisa para começar —
                    dá para adicionar outras depois.
                </span>
            </div>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Habilidade</span>
                <select wire:model="proficienciaId" class="campo" required>
                    <option value="">Escolha uma habilidade</option>
                    @foreach ($proficiencias as $categoria => $lista)
                        <optgroup label="{{ $categoria }}">
                            @foreach ($lista as $proficiencia)
                                <option value="{{ $proficiencia->id }}">{{ $proficiencia->nome }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('proficienciaId') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
            </label>

            <fieldset class="flex flex-col gap-2">
                <legend class="pb-2 text-[14px] font-semibold text-tinta-media">Seu nível</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($niveis as $chave => $rotulo)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="nivel" value="{{ $chave }}" class="peer sr-only">
                            <span class="pilula block border border-borda bg-superficie text-tinta-media peer-checked:border-acento peer-checked:bg-acento peer-checked:font-semibold peer-checked:text-white">
                                {{ $rotulo }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </div>

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
