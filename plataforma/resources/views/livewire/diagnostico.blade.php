<div>
    <x-cabecalho titulo="Diagnóstico do aparelho" :voltar="route('inicio')" />

    <div class="flex flex-col gap-4 px-5">
        <p class="text-[14px] leading-relaxed text-tinta-suave">
            Verificação da decisão A1 (<code>05 §15.3</code>): a web entrega, neste aparelho, o que a
            segurança presencial exige? Cada linha é medida aqui, não presumida.
        </p>

        <div id="resultado" class="flex flex-col gap-3">
            <div class="cartao p-5 text-[14px] text-tinta-suave">Medindo…</div>
        </div>

        <div class="cartao flex flex-col gap-3 p-5">
            <h3 class="text-[16px] font-semibold">Provas que exigem um toque</h3>
            <p class="text-[13px] leading-relaxed text-tinta-fraca">
                Permissão e discagem só podem ser pedidas a partir de um gesto seu. O navegador
                recusa se a página tentar sozinha.
            </p>

            <button type="button" id="pedir-notificacao" class="botao botao-secundario w-full">
                Pedir permissão de notificação
            </button>

            <button type="button" id="testar-notificacao" class="botao botao-secundario w-full">
                Disparar notificação pelo service worker
            </button>

            {{-- 05 §15.2 — a ação mais confiável em aparelho com dados ruins
                 é ligar. Quem entrega confiabilidade aqui é a rede de voz da
                 operadora, que opera onde os dados já falharam. --}}
            <a href="tel:190" class="botao w-full"
               style="background-color: var(--color-emergencia); color: #fff;">
                Testar discagem de emergência (190)
            </a>
            <p class="text-[13px] leading-relaxed text-tinta-fraca">
                Abre o discador com o número preenchido. <strong class="font-semibold">Não liga
                sozinho</strong> — confira que o número aparece e cancele.
            </p>
        </div>

        <div class="cartao flex flex-col gap-2 p-5">
            <h3 class="text-[16px] font-semibold">Como ler</h3>
            <p class="text-[13px] leading-relaxed text-tinta-suave">
                Se notificação e discagem funcionarem, A1 se sustenta e o PWA basta. Se a
                notificação não chegar com o aplicativo fechado, ou a discagem não abrir, o
                aplicativo nativo deixa de ser luxo e vira requisito.
            </p>
        </div>
    </div>

    @script
    <script>
        const linhas = [];

        const registrar = (grupo, item, valor, veredito) =>
            linhas.push({ grupo, item, valor: String(valor), veredito });

        const SIM = 'sim', NAO = 'nao', TALVEZ = 'talvez';

        async function medir() {
            // --- Instalação -------------------------------------------------
            const standalone = window.matchMedia('(display-mode: standalone)').matches
                || window.navigator.standalone === true;

            registrar('Instalação', 'Rodando instalado (standalone)', standalone ? 'sim' : 'não (aba do navegador)',
                standalone ? SIM : TALVEZ);
            registrar('Instalação', 'Contexto seguro', window.isSecureContext ? 'sim' : 'não',
                window.isSecureContext ? SIM : NAO);

            try {
                const r = await fetch('/manifesto.webmanifest');
                const m = await r.json();
                const png = (m.icons || []).filter((i) => i.type === 'image/png');
                registrar('Instalação', 'Manifesto com ícones PNG', `${png.length} ícone(s)`,
                    png.length >= 2 ? SIM : NAO);
            } catch (e) {
                registrar('Instalação', 'Manifesto', 'não carregou', NAO);
            }

            // --- Service worker ---------------------------------------------
            const temSW = 'serviceWorker' in navigator;
            registrar('Service worker', 'Suportado', temSW ? 'sim' : 'não', temSW ? SIM : NAO);

            if (temSW) {
                const reg = await navigator.serviceWorker.getRegistration();
                registrar('Service worker', 'Registrado', reg ? 'sim' : 'não', reg ? SIM : NAO);
                registrar('Service worker', 'Ativo', reg?.active ? 'sim' : 'não', reg?.active ? SIM : NAO);
                registrar('Service worker', 'Sincronização em segundo plano',
                    reg && 'sync' in reg ? 'sim' : 'não', reg && 'sync' in reg ? SIM : TALVEZ);
                registrar('Service worker', 'Sincronização periódica',
                    reg && 'periodicSync' in reg ? 'sim' : 'não', reg && 'periodicSync' in reg ? SIM : TALVEZ);
            }

            // --- Notificação (o primeiro dos dois riscos de A1) -------------
            const temNotif = 'Notification' in window;
            registrar('Notificação', 'API disponível', temNotif ? 'sim' : 'não', temNotif ? SIM : NAO);
            registrar('Notificação', 'Permissão', temNotif ? Notification.permission : '—',
                temNotif && Notification.permission === 'granted' ? SIM
                    : temNotif && Notification.permission === 'denied' ? NAO : TALVEZ);
            registrar('Notificação', 'Push (com o app fechado)', 'PushManager' in window ? 'sim' : 'não',
                'PushManager' in window ? SIM : NAO);

            // --- Emergência (o segundo risco de A1) -------------------------
            // Não existe API: o que vale é o discador abrir a partir de um
            // link `tel:`. A prova é o botão acima, tocado por uma pessoa.
            registrar('Emergência', 'Link tel: (verificar no botão acima)', 'toque para provar', TALVEZ);
            registrar('Emergência', 'Vibração', 'vibrate' in navigator ? 'sim' : 'não',
                'vibrate' in navigator ? SIM : TALVEZ);
            registrar('Emergência', 'Manter a tela ligada (wake lock)',
                'wakeLock' in navigator ? 'sim' : 'não', 'wakeLock' in navigator ? SIM : TALVEZ);

            // --- Localização (área de atendimento, RF-SOL-02) ---------------
            registrar('Localização', 'API disponível', 'geolocation' in navigator ? 'sim' : 'não',
                'geolocation' in navigator ? SIM : NAO);

            if (navigator.permissions) {
                try {
                    const p = await navigator.permissions.query({ name: 'geolocation' });
                    registrar('Localização', 'Permissão', p.state, p.state === 'granted' ? SIM : TALVEZ);
                } catch (e) { /* nem todo navegador consulta esta permissão */ }
            }

            // --- Armazenamento e rede ---------------------------------------
            try {
                const est = await navigator.storage?.estimate?.();
                registrar('Armazenamento', 'Cota',
                    est ? `${Math.round(est.quota / 1048576)} MB` : 'não informado', est ? SIM : TALVEZ);
            } catch (e) { /* ignora */ }

            registrar('Armazenamento', 'IndexedDB', 'indexedDB' in window ? 'sim' : 'não',
                'indexedDB' in window ? SIM : NAO);

            const con = navigator.connection;
            if (con) {
                registrar('Rede', 'Tipo estimado', con.effectiveType ?? '—', TALVEZ);
                registrar('Rede', 'Economia de dados', con.saveData ? 'ligada' : 'desligada',
                    con.saveData ? TALVEZ : SIM);
            }

            // --- Aparelho ---------------------------------------------------
            registrar('Aparelho', 'Memória', navigator.deviceMemory ? `${navigator.deviceMemory} GB` : '—', TALVEZ);
            registrar('Aparelho', 'Núcleos', navigator.hardwareConcurrency ?? '—', TALVEZ);
            registrar('Aparelho', 'Tela', `${screen.width}×${screen.height} @${devicePixelRatio}x`, TALVEZ);
            registrar('Aparelho', 'Navegador', (navigator.userAgentData?.brands ?? [])
                .map((b) => `${b.brand} ${b.version}`).join(', ') || navigator.userAgent.slice(0, 80), TALVEZ);

            // --- Desempenho: quanto custou chegar até aqui ------------------
            const nav = performance.getEntriesByType('navigation')[0];
            if (nav) {
                registrar('Desempenho', 'Carregamento da página', `${Math.round(nav.duration)} ms`,
                    nav.duration < 3000 ? SIM : nav.duration < 6000 ? TALVEZ : NAO);
            }

            // Exposto para leitura externa: é uma tela de diagnóstico, e o
            // resultado precisa poder ser lido por quem está depurando o
            // aparelho por cabo, sem depender de rolagem e captura de tela.
            window.diagnostico = linhas;

            desenhar();
        }

        function desenhar() {
            const cores = {
                sim: ['bg-acento-fraco', 'text-acento', '✓'],
                nao: ['bg-[#FBECEB]', 'text-emergencia', '✕'],
                talvez: ['bg-aviso-fundo', 'text-aviso-tinta', '•'],
            };

            const grupos = [...new Set(linhas.map((l) => l.grupo))];

            document.getElementById('resultado').innerHTML = grupos.map((g) => `
                <div class="cartao flex flex-col gap-3 p-5">
                    <h3 class="text-[16px] font-semibold">${g}</h3>
                    ${linhas.filter((l) => l.grupo === g).map((l) => {
                        const [fundo, cor, sinal] = cores[l.veredito];
                        return `
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-[14px] text-tinta-media">${l.item}</span>
                                <span class="pilula shrink-0 ${fundo} ${cor} text-[12px] font-semibold">
                                    ${sinal} ${l.valor}
                                </span>
                            </div>`;
                    }).join('')}
                </div>`).join('');
        }

        document.getElementById('pedir-notificacao').addEventListener('click', async () => {
            if (!('Notification' in window)) { return; }

            const resultado = await Notification.requestPermission();
            alert(`Permissão de notificação: ${resultado}`);
            linhas.length = 0;
            medir();
        });

        document.getElementById('testar-notificacao').addEventListener('click', async () => {
            const reg = await navigator.serviceWorker?.getRegistration();

            if (!reg) { alert('Sem service worker registrado.'); return; }
            if (Notification.permission !== 'granted') { alert('Permissão ainda não concedida.'); return; }

            // Pelo service worker, e não por `new Notification()`: é assim que
            // ela chega com o aplicativo fechado, que é o que A1 questiona.
            await reg.showNotification('Ajude.me', {
                body: 'Se você está lendo isto, a notificação chegou.',
                icon: '/icone-192.png',
                badge: '/icone-192.png',
                tag: 'diagnostico',
            });
        });

        medir();
    </script>
    @endscript
</div>
