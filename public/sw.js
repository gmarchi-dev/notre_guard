/**
 * Service worker do Notre Guard — Campo.
 *
 * Só a casca do app é cacheada. Dados nunca: eles vivem no IndexedDB, sob
 * controle da fila de sincronização. Requisições à API jamais são servidas do
 * cache, porque uma resposta velha de /bootstrap faria o vigilante rondar com um
 * roteiro desatualizado.
 */
const CACHE = 'notre-guard-shell-v1'
const SHELL = ['/campo', '/manifest.webmanifest']

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(SHELL))
            .then(() => self.skipWaiting()),
    )
})

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    )
})

self.addEventListener('fetch', (event) => {
    const { request } = event

    if (request.method !== 'GET') {
        return
    }

    const url = new URL(request.url)

    if (url.origin !== self.location.origin || url.pathname.startsWith('/api/')) {
        return
    }

    // Navegação: rede primeiro, cache como rede de segurança. Assim uma versão
    // nova do app chega assim que houver sinal, mas o subsolo continua abrindo.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const copy = response.clone()
                    caches.open(CACHE).then((cache) => cache.put('/campo', copy))
                    return response
                })
                .catch(() => caches.match('/campo')),
        )
        return
    }

    // Assets versionados pelo Vite: cache primeiro, porque o nome muda a cada build.
    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached
            }

            return fetch(request).then((response) => {
                if (response.ok && (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/'))) {
                    const copy = response.clone()
                    caches.open(CACHE).then((cache) => cache.put(request, copy))
                }

                return response
            })
        }),
    )
})
