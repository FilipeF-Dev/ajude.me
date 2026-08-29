/**
 * Registro do service worker (decisão A1, 05 §15).
 *
 * Só em contexto seguro — HTTPS ou localhost. Falhar aqui não pode derrubar
 * a aplicação: o service worker é melhoria, não requisito.
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Sem service worker a aplicação continua funcionando pela rede.
        });
    });
}
