/**
 * Service worker do Ajude.me.
 *
 * Faz o mínimo defensável e nada além: guarda o que já foi baixado e serve
 * do cache quando a rede falha. Não guarda resposta de página autenticada —
 * um aparelho compartilhado devolveria a carteira de outra pessoa.
 *
 * A resiliência das telas de segurança (check-in, check-out, acionamento de
 * emergência) exige tratamento deliberado e ainda não foi construída
 * (05 §15.2). Isto aqui não a substitui.
 */
const VERSAO = 'ajudeme-v1';

const ESSENCIAIS = ['/icone.svg', '/icone-mascara.svg'];

self.addEventListener('install', (evento) => {
    evento.waitUntil(
        caches.open(VERSAO).then((cache) => cache.addAll(ESSENCIAIS)).then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches
            .keys()
            .then((chaves) => Promise.all(chaves.filter((c) => c !== VERSAO).map((c) => caches.delete(c))))
            .then(() => self.clients.claim()),
    );
});

/**
 * Tocar a notificação traz a pessoa de volta para dentro do aplicativo, e
 * não para uma aba nova. Se já houver janela aberta, é ela que é focada.
 */
self.addEventListener('notificationclick', (evento) => {
    evento.notification.close();

    const destino = evento.notification.data?.url ?? '/';

    evento.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((janelas) => {
            for (const janela of janelas) {
                if ('focus' in janela) {
                    janela.navigate?.(destino);
                    return janela.focus();
                }
            }

            return self.clients.openWindow(destino);
        }),
    );
});

self.addEventListener('fetch', (evento) => {
    const requisicao = evento.request;

    if (requisicao.method !== 'GET') {
        return;
    }

    const url = new URL(requisicao.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Estático versionado pelo Vite: cache primeiro, porque o nome do arquivo
    // muda a cada build e nunca serve conteúdo velho por engano.
    const ehEstatico = url.pathname.startsWith('/build/') || ESSENCIAIS.includes(url.pathname);

    if (ehEstatico) {
        evento.respondWith(
            caches.match(requisicao).then(
                (guardado) =>
                    guardado ||
                    fetch(requisicao).then((resposta) => {
                        const copia = resposta.clone();
                        caches.open(VERSAO).then((cache) => cache.put(requisicao, copia));
                        return resposta;
                    }),
            ),
        );

        return;
    }

    // Páginas: rede primeiro, sem guardar. O conteúdo é pessoal, e servir do
    // cache mostraria a carteira de quem usou o aparelho antes.
    if (requisicao.mode === 'navigate') {
        evento.respondWith(fetch(requisicao).catch(() => caches.match('/icone.svg')));
    }
});
