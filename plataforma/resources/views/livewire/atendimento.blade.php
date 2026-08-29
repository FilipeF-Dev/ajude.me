@php
    use App\Models\ManifestacaoRealizacao;
    use App\Models\RegistroComparecimento;
    use App\Models\Solicitacao;

    $estados = [
        Solicitacao::SOLICITADA => ['Aguardando confirmação', 'text-aviso-tinta', 'bg-aviso-fundo'],
        Solicitacao::AGENDADA => ['Agendado', 'text-acento', 'bg-acento-fraco'],
        Solicitacao::AGUARDANDO_CONFIRMACAO => ['Aconteceu?', 'text-aviso-tinta', 'bg-aviso-fundo'],
        Solicitacao::EM_ANALISE => ['Em análise', 'text-aviso-tinta', 'bg-aviso-fundo'],
        Solicitacao::REALIZADA => ['Realizado', 'text-acento', 'bg-acento-fraco'],
        Solicitacao::ENCERRADA => ['Encerrado', 'text-tinta-suave', 'bg-neutro'],
        Solicitacao::RECUSADA => ['Recusado', 'text-tinta-suave', 'bg-neutro'],
        Solicitacao::NAO_REALIZADA => ['Não realizado', 'text-tinta-suave', 'bg-neutro'],
        Solicitacao::INCONCLUSIVA => ['Inconclusivo', 'text-tinta-suave', 'bg-neutro'],
        Solicitacao::CANCELADA_AJUDANTE => ['Cancelado pelo ajudante', 'text-tinta-suave', 'bg-neutro'],
        Solicitacao::CANCELADA_SOLICITANTE => ['Cancelado pelo solicitante', 'text-tinta-suave', 'bg-neutro'],
    ];

    [$rotuloEstado, $corEstado, $fundoEstado] = $estados[$solicitacao->estado];
@endphp

<div>
    <x-cabecalho titulo="O atendimento" :voltar="route('carteira')" />

    <div class="flex flex-col gap-4 px-5">

        <div class="cartao flex flex-col gap-3.5 p-5">
            <div class="flex items-start justify-between gap-3">
                <span class="text-[17px] font-semibold leading-tight">{{ $favor->titulo }}</span>
                <span class="pilula shrink-0 {{ $fundoEstado }} {{ $corEstado }} text-[12px] font-semibold">
                    {{ $rotuloEstado }}
                </span>
            </div>

            <div class="flex items-center gap-3">
                <x-avatar :nome="$contraparte->nome" :tamanho="40" />
                <div class="flex flex-col">
                    <span class="text-[15px] font-medium">{{ $contraparte->nome }}</span>
                    <span class="text-[13px] text-tinta-suave">
                        {{ $papel === Solicitacao::PARTE_AJUDANTE ? 'Você atende' : 'Você recebe' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2 border-t border-borda pt-3.5">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-tinta-suave)"
                     stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>
                </svg>
                <span class="text-[14px]">
                    {{ $solicitacao->inicio_previsto->translatedFormat('D, d/m') }} ·
                    {{ $solicitacao->inicio_previsto->format('H:i') }}–{{ $solicitacao->fim_previsto->format('H:i') }}
                </span>
            </div>
        </div>

        @error('acao')
            <div class="aviso">{{ $message }}</div>
        @enderror

        {{-- RF-SOL-07 e 02 §18.1 — telefone e endereço são liberados no
             agendamento, e só às partes. Antes disso, nem existem na tela. --}}
        @if ($liberouContato)
            <div class="cartao flex flex-col gap-3 p-5">
                <h3 class="text-[16px] font-semibold">Contato liberado</h3>
                <p class="text-[13px] leading-relaxed text-tinta-fraca">
                    Disponível porque o atendimento foi agendado. Volta a ficar oculto no
                    encerramento.
                </p>

                <a href="tel:{{ $contraparte->telefone }}" class="botao botao-secundario w-full">
                    Ligar para {{ $contraparte->nome }}
                </a>

                @if ($ehPresencial && $contraparte->endereco)
                    <div class="flex flex-col gap-1 border-t border-borda pt-3">
                        <span class="text-[13px] text-tinta-suave">Endereço do atendimento</span>
                        <span class="text-[14px] leading-relaxed">
                            {{ $contraparte->endereco->logradouro }}, {{ $contraparte->endereco->numero }}
                            @if ($contraparte->endereco->complemento)
                                — {{ $contraparte->endereco->complemento }}
                            @endif
                            <br>
                            {{ $contraparte->endereco->bairro }}, {{ $contraparte->endereco->municipio }}/{{ $contraparte->endereco->uf }}
                            @if ($contraparte->endereco->referencia)
                                <br><span class="text-tinta-suave">{{ $contraparte->endereco->referencia }}</span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        @endif

        {{-- RF-SEG-05 a RF-SEG-07 — cada parte registra por si. É o encontro
             das duas marcações que constitui a evidência; uma pessoa marcando
             pelas duas não prova comparecimento nenhum (02 §8.1). --}}
        @if ($ehPresencial && in_array($solicitacao->estado, [Solicitacao::AGENDADA, Solicitacao::AGUARDANDO_CONFIRMACAO], true))
            <div class="cartao flex flex-col gap-3 p-5">
                <h3 class="text-[16px] font-semibold">Comparecimento</h3>
                <p class="text-[13px] leading-relaxed text-tinta-fraca">
                    Cada um registra o seu. É a evidência independente do que foi combinado —
                    vale mesmo se depois as versões divergirem.
                </p>

                @if (! $registrouInicio)
                    <button type="button" wire:click="registrarInicio" class="botao botao-principal w-full">
                        Cheguei — registrar início
                    </button>
                @elseif (! $registrouFim)
                    <button type="button" wire:click="registrarFim" class="botao botao-secundario w-full">
                        Terminamos — registrar fim
                    </button>
                @endif

                @if ($registros->isNotEmpty())
                    <div class="flex flex-col gap-2 border-t border-borda pt-3">
                        @foreach ($registros as $registro)
                            <div class="flex items-center justify-between gap-3 text-[13px]">
                                <span class="text-tinta-suave">
                                    {{ $registro->parte === Solicitacao::PARTE_AJUDANTE ? 'Ajudante' : 'Solicitante' }}
                                    · {{ $registro->tipo === RegistroComparecimento::INICIO ? 'início' : 'fim' }}
                                </span>
                                <span class="font-medium">{{ $registro->momento->format('d/m H:i') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- RF-SEG-08 — o acionamento vive entre o início e o fim do
             atendimento: é a janela em que as duas pessoas estão no mesmo
             lugar.

             Um link `tel:` e não uma API: em aparelho com dados ruins, quem
             entrega confiabilidade é a rede de voz da operadora, que opera
             onde os dados já falharam (05 §15.2). --}}
        @if ($emCurso)
            <div class="cartao flex flex-col gap-3 p-5"
                 style="border-color: #F0C9C6;">
                <h3 class="text-[16px] font-semibold" style="color: var(--color-emergencia);">
                    Emergência
                </h3>
                <p class="text-[13px] leading-relaxed text-tinta-media">
                    Se você não estiver se sentindo seguro, ligue. A chamada sai mesmo sem internet.
                </p>
                <a href="tel:190" class="botao w-full"
                   style="background-color: var(--color-emergencia); color: #fff;">
                    Ligar para a emergência (190)
                </a>
            </div>
        @endif

        {{-- RF-SOL-09 e RF-SOL-11 — o silêncio confirma; só a contestação
             ativa impede o encerramento (02 §8.4). --}}
        @if ($solicitacao->estado === Solicitacao::AGUARDANDO_CONFIRMACAO)
            <div class="cartao flex flex-col gap-3 p-5">
                <h3 class="text-[16px] font-semibold">O favor aconteceu?</h3>

                @if ($minhaManifestacao)
                    <p class="text-[14px] leading-relaxed text-tinta-media">
                        Você respondeu
                        <strong class="font-semibold">
                            {{ $minhaManifestacao->tipo === ManifestacaoRealizacao::CONFIRMA ? 'que sim' : 'que não' }}</strong>.
                        Não é possível mudar — a apuração cabe à Operação.
                    </p>
                @else
                    <p class="text-[13px] leading-relaxed text-tinta-fraca">
                        Se você não responder até
                        {{ $solicitacao->janela_contestacao_ate->format('d/m \à\s H:i') }},
                        o favor é dado como realizado. Só a contestação impede isso.
                    </p>

                    <button type="button" wire:click="confirmar" class="botao botao-principal w-full">
                        Sim, aconteceu
                    </button>
                    <button type="button" wire:click="contestar" class="botao botao-secundario w-full">
                        Não aconteceu
                    </button>
                    <p class="text-[13px] leading-relaxed text-tinta-fraca">
                        Contestar abre uma análise da Operação e não exige justificativa agora.
                    </p>
                @endif
            </div>
        @endif

        @if ($solicitacao->estado === Solicitacao::REALIZADA)
            <a href="{{ route('avaliar', $solicitacao) }}" wire:navigate class="botao botao-principal w-full">
                Avaliar
            </a>
        @endif

        {{-- RF-CAN-07 — o solicitante cancela a qualquer momento, e o sistema
             não bloqueia. Bloquear não faz ninguém comparecer. --}}
        @if (in_array($solicitacao->estado, [Solicitacao::SOLICITADA, Solicitacao::AGENDADA], true))
            <livewire:cancelar-atendimento :solicitacao="$solicitacao" :key="'cancelar-'.$solicitacao->id" />
        @endif
    </div>
</div>
