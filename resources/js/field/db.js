import Dexie from 'dexie'

/**
 * Banco local do aparelho.
 *
 * `cache` guarda o pacote baixado no início do turno (roteiros, pontos,
 * checklists). `queue` é a fila de eventos ainda não confirmados pelo servidor —
 * ela é a razão de o app funcionar sem rede, e nada sai dela antes de o servidor
 * confirmar por uuid.
 */
export const db = new Dexie('notre_guard')

db.version(1).stores({
    cache: 'key',
    queue: '++id, uuid, status, created_at',
    blobs: 'uuid, status',
})

/**
 * O Alpine entrega objetos embrulhados em Proxy, e o IndexedDB não consegue
 * cloná-los (DataCloneError). Tudo que entra no banco local passa por aqui.
 */
function plain(value) {
    if (value === null || value === undefined) {
        return value
    }

    return JSON.parse(JSON.stringify(value))
}

export async function putCache(key, value) {
    await db.cache.put({ key, value: plain(value) })
}

export async function getCache(key) {
    const row = await db.cache.get(key)
    return row?.value ?? null
}

export async function clearAll() {
    await Promise.all([db.cache.clear(), db.queue.clear(), db.blobs.clear()])
}

/**
 * Enfileira um evento. O uuid nasce aqui, no aparelho: é o que garante que
 * reenviar o mesmo registro nunca duplica no servidor.
 */
export async function enqueue(type, payload, occurredAt = null) {
    const uuid = crypto.randomUUID()

    await db.queue.add({
        uuid,
        type,
        occurred_at: occurredAt ?? new Date().toISOString(),
        payload: plain(payload),
        status: 'pending',
        attempts: 0,
        created_at: Date.now(),
    })

    return uuid
}

export async function enqueueBlob(blob, meta = {}) {
    const uuid = crypto.randomUUID()
    // O blob vai cru: só os metadados precisam ser normalizados.
    await db.blobs.add({ uuid, blob, meta: plain(meta), status: 'pending', attempts: 0 })
    return uuid
}

export async function pendingEvents(limit = 100) {
    return db.queue
        .where('status')
        .anyOf('pending', 'retry')
        .sortBy('created_at')
        .then((rows) => rows.slice(0, limit))
}

/**
 * Fila completa para a tela de diagnóstico do vigilante: pendentes, em
 * retentativa e rejeitados. Ele precisa conseguir ver o que não subiu.
 */
export async function queueSnapshot() {
    const events = await db.queue.orderBy('created_at').reverse().toArray()
    const blobs = await db.blobs.toArray()

    return {
        events: events.map(({ id, uuid, type, occurred_at, status, attempts, error }) => ({
            id,
            uuid,
            type,
            occurred_at,
            status,
            attempts,
            error,
        })),
        photos: blobs.filter((b) => b.status === 'pending').length,
    }
}

/** Devolve um registro rejeitado para a fila, para tentar de novo. */
export async function retryEvent(id) {
    await db.queue.update(id, { status: 'retry', error: null })
}

export async function discardEvent(id) {
    await db.queue.delete(id)
}

export async function pendingCount() {
    const events = await db.queue.where('status').anyOf('pending', 'retry').count()
    const blobs = await db.blobs.where('status').equals('pending').count()
    return events + blobs
}

/** Rejeitados por falha permanente: não sobem sozinhos, exigem decisão. */
export async function rejectedCount() {
    return db.queue.where('status').equals('rejected').count()
}
