@php
    $eEmail = $tipo === \App\Models\VerificacaoContato::TIPO_EMAIL;
    $esteVerificado = $eEmail ? $emailVerificado : $telefoneVerificado;
    $destino = $eEmail ? $usuario->email : $usuario->telefone;
@endphp

<div>
    <x-cabecalho titulo="Confirmar contato" />

    <p class="px-5 pb-4 text-[14px] leading-relaxed text-tinta-suave">
        Confirmar e-mail e telefone dá a você o selo <strong>Contato verificado</strong>, que
        aparece para quem for decidir uma troca com você.
    </p>

    @if ($temSelo)
        <div class="mx-5 mb-5 flex items-center gap-2.5 rounded-[14px] bg-acento-fraco px-4 py-3">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"
                 class="shrink-0 text-acento">
                <path d="M12 3 5 6v6c0 4.4 3 8.2 7 9 4-.8 7-4.6 7-9V6Z"></path>
                <path d="m9 12 2 2 4-4"></path>
            </svg>
            <span class="text-[14px] leading-snug text-tinta-media">
                <strong class="font-semibold">Contato verificado.</strong>
                Seu e-mail e seu telefone estão confirmados.
            </span>
        </div>
    @endif

    {{-- Os dois contatos, com o estado de cada um. --}}
    <div class="flex gap-2 px-5 pb-5">
        @foreach ([
            [\App\Models\VerificacaoContato::TIPO_TELEFONE, 'Telefone', $telefoneVerificado],
            [\App\Models\VerificacaoContato::TIPO_EMAIL, 'E-mail', $emailVerificado],
        ] as [$valor, $rotulo, $ok])
            <button
                type="button"
                wire:click="escolher('{{ $valor }}')"
                @class([
                    'flex flex-1 flex-col items-start gap-1 rounded-[14px] border bg-superficie p-4 text-left',
                    'border-acento' => $tipo === $valor,
                    'border-borda' => $tipo !== $valor,
                ])
            >
                <span class="text-[15px] font-semibold text-tinta">{{ $rotulo }}</span>
                <span @class([
                    'text-[13px]',
                    'font-semibold text-acento' => $ok,
                    'text-tinta-suave' => ! $ok,
                ])>
                    {{ $ok ? 'Confirmado' : 'Falta confirmar' }}
                </span>
            </button>
        @endforeach
    </div>

    @if ($esteVerificado)
        <p class="px-5 pb-8 text-[14px] leading-relaxed text-tinta-suave">
            Este contato já está confirmado.
        </p>
    @else
        <div class="flex flex-col gap-4 px-5 pb-8">
            <div class="rounded-[14px] border border-borda bg-superficie p-4">
                <p class="text-[13px] text-tinta-suave">Vamos mandar o código para</p>
                <p class="mt-1 text-[16px] font-semibold text-tinta">{{ $destino }}</p>
            </div>

            <button
                type="button"
                wire:click="pedirCodigo"
                wire:loading.attr="disabled"
                class="flex h-14 items-center justify-center rounded-[14px] border border-borda bg-superficie text-[16px] font-semibold text-tinta-media"
            >
                <span wire:loading.remove wire:target="pedirCodigo">Pedir código</span>
                <span wire:loading wire:target="pedirCodigo">Pedindo…</span>
            </button>

            @if ($recado)
                <p class="text-[14px] leading-relaxed text-acento">{{ $recado }}</p>
            @endif

            <form wire:submit="confirmar" class="flex flex-col gap-3">
                <label for="codigo" class="text-[14px] font-semibold text-tinta-media">
                    Código de seis números
                </label>

                <input
                    id="codigo"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    wire:model="codigo"
                    placeholder="000000"
                    class="h-14 rounded-[14px] border border-borda bg-superficie px-4 text-center text-[22px] font-semibold tracking-[0.4em] text-tinta placeholder:text-tinta-fraca"
                />

                @error('codigo')
                    <span class="text-[13px] text-emergencia">{{ $message }}</span>
                @enderror

                <button
                    type="submit"
                    class="flex h-14 items-center justify-center rounded-[14px] bg-acento text-[16px] font-semibold text-white"
                >
                    Confirmar
                </button>
            </form>

            <p class="text-[13px] leading-relaxed text-tinta-suave">
                O código vale por pouco tempo. Se errar várias vezes, peça um novo.
            </p>
        </div>
    @endif
</div>
