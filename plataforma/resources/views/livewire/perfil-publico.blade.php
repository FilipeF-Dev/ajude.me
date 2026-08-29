<div>
    <x-cabecalho titulo="Perfil" :voltar="route('inicio')" />

    <div class="flex flex-col gap-4 px-5">
        <div class="cartao flex items-center gap-4 p-5">
            <x-avatar :nome="$usuario->nome" :tamanho="60" />
            <div class="flex min-w-0 flex-col gap-1">
                <span class="text-[18px] font-semibold">{{ $usuario->nome }}</span>
                {{-- 02 §18.1 — antes da proposta, só a região aproximada. --}}
                <span class="text-[14px] text-tinta-suave">
                    {{ $usuario->endereco?->regiaoAproximada() ?? 'Região não informada' }}
                </span>
                <span class="text-[13px] text-tinta-fraca">
                    {{ $prestados }} favores prestados · há {{ $dias }} dia{{ $dias === 1 ? '' : 's' }} na plataforma
                </span>
            </div>
        </div>

        <div class="cartao flex flex-col gap-3 p-5">
            <h3 class="text-[15px] font-semibold">Como ajudante</h3>
            @if ($suficiente)
                <span class="text-[26px] font-bold leading-none">
                    {{ number_format($media, 1, ',', '.') }}
                    <span class="text-[14px] font-medium text-tinta-fraca">/ 7</span>
                </span>
            @else
                <p class="text-[14px] leading-relaxed text-tinta-suave">
                    Ainda não há avaliações suficientes para compor uma média.
                </p>
            @endif

            @foreach ($porProficiencia as $linha)
                <div class="flex items-center justify-between gap-3 border-t border-borda pt-3">
                    <div class="flex min-w-0 flex-col">
                        <span class="text-[14px] font-medium">{{ $linha['proficiencia'] }}</span>
                        {{-- RF-REP-07 — nível declarado ao lado da reputação
                             obtida. Uma coisa não vira a outra (02 §3.3). --}}
                        <span class="text-[13px] text-tinta-suave">
                            Declarado: {{ \App\Models\ProficienciaUsuario::niveis()[$linha['nivel']] }}
                        </span>
                    </div>
                    <span class="text-[15px] font-semibold">{{ number_format($linha['media'], 1, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        @if ($favores->isNotEmpty())
            <h3 class="px-1 text-[15px] font-semibold text-tinta-media">Favores disponíveis</h3>
            <div class="flex flex-col gap-3">
                @foreach ($favores as $favor)
                    <x-cartao-favor :favor="$favor" />
                @endforeach
            </div>
        @endif
    </div>
</div>
