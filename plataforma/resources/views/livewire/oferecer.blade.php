<div>
    <x-cabecalho titulo="Oferecer ajuda" :voltar="route('inicio')" />

    <form wire:submit="publicar" class="flex flex-col gap-4 px-5">

        <div class="cartao flex flex-col gap-4 p-5">
            <h3 class="text-[16px] font-semibold">O que você sabe fazer</h3>

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
                {{-- 02 §3.3 — proficiência não é reputação, e uma não altera
                     a outra. Dizer isso aqui evita a inflação do declarado. --}}
                <span class="text-[13px] leading-relaxed text-tinta-fraca">
                    O nível é o que você declara. Quem recebe a ajuda avalia separado, e uma coisa
                    não muda a outra.
                </span>
            </fieldset>
        </div>

        <div class="cartao flex flex-col gap-4 p-5">
            <h3 class="text-[16px] font-semibold">A ajuda</h3>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Título</span>
                <input type="text" wire:model="titulo" class="campo"
                       placeholder="Ex.: Troco resistência de chuveiro" required>
                @error('titulo') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">O que você faz</span>
                <textarea wire:model="descricao" class="campo"
                          placeholder="Descreva o que está incluído e o que não está." required></textarea>
                @error('descricao') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Tempo estimado (minutos)</span>
                <input type="number" wire:model="tempoEstimadoMinutos" class="campo"
                       inputmode="numeric" min="10" max="600" step="5" required>
                @error('tempoEstimadoMinutos') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="cartao flex flex-col gap-4 p-5">
            <h3 class="text-[16px] font-semibold">Quando e onde</h3>

            <fieldset class="flex flex-col gap-2">
                <legend class="pb-2 text-[14px] font-semibold text-tinta-media">Modalidade</legend>
                <div class="flex gap-2">
                    @foreach (['presencial' => 'Presencial', 'remoto' => 'Remoto'] as $chave => $rotulo)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" wire:model.live="modalidade" value="{{ $chave }}" class="peer sr-only">
                            <span class="pilula block border border-borda bg-superficie text-center text-tinta-media peer-checked:border-acento peer-checked:bg-acento peer-checked:font-semibold peer-checked:text-white">
                                {{ $rotulo }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            @if ($modalidade === 'presencial')
                <label class="flex flex-col gap-2">
                    <span class="text-[14px] font-semibold text-tinta-media">Atende até quantos km</span>
                    <input type="number" wire:model="areaAtendimentoKm" class="campo"
                           inputmode="numeric" min="1" max="200">
                </label>
            @endif

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Antecedência mínima (dias)</span>
                <input type="number" wire:model="antecedenciaMinimaDias" class="campo"
                       inputmode="numeric" min="0" max="60" required>
            </label>
        </div>

        <div class="cartao flex flex-col gap-4 p-5">
            <h3 class="text-[16px] font-semibold">Os tickets</h3>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Quantos vai emitir</span>
                <input type="number" wire:model="quantidade" class="campo"
                       inputmode="numeric" min="1" max="20" required>
                {{-- 02 §5.1 — o limite é de circulação simultânea, não
                     vitalício: consumido libera vaga. --}}
                <span class="text-[13px] leading-relaxed text-tinta-fraca">
                    É quantos podem circular ao mesmo tempo. Quando um é usado, abre vaga para outro.
                </span>
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Validade de cada ticket (dias)</span>
                <input type="number" wire:model="validadeTicketDias" class="campo"
                       inputmode="numeric" min="1" max="365" required>
                @error('validadeTicketDias') <span class="text-[13px] text-emergencia">{{ $message }}</span> @enderror
            </label>

            <label class="flex items-start gap-3 py-1">
                <input type="checkbox" wire:model="trocavel"
                       class="mt-0.5 h-5 w-5 shrink-0 rounded border-borda-forte accent-[var(--color-acento)]">
                <span class="flex flex-col gap-1">
                    <span class="text-[14px] font-medium">Estes tickets podem ser trocados</span>
                    <span class="text-[13px] leading-relaxed text-tinta-fraca">
                        Quem receber pode passar adiante numa troca. Sem isso, o ticket só serve a
                        quem o recebeu primeiro.
                    </span>
                </span>
            </label>

            <label class="flex flex-col gap-2">
                <span class="text-[14px] font-semibold text-tinta-media">Observações (opcional)</span>
                <textarea wire:model="condicoes" class="campo"
                          placeholder="Ex.: levo as ferramentas, o material é por sua conta."></textarea>
            </label>
        </div>

        {{-- RF-FAV-10 e P5 — a condição congela na primeira emissão. A regra é
             dura e irreversível; esconder isso seria armar quem publica. --}}
        <div class="aviso">
            Ao publicar, estas condições ficam <strong class="font-semibold">congeladas</strong>.
            Quem adquirir um ticket vai contar com exatamente o que está escrito aqui. Para
            condições diferentes, você cria outro favor.
        </div>

        <button type="submit" class="botao botao-principal w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="publicar">Publicar e emitir tickets</span>
            <span wire:loading wire:target="publicar">Publicando…</span>
        </button>
    </form>
</div>
