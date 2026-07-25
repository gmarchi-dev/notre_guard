import { db, pendingEvents, pendingCount, rejectedCount } from './db'
import { pushEvents, pushAttachment, ApiError } from './api'

/**
 * Motor de sincronização.
 *
 * Regras que não podem ser afrouxadas:
 * - só remove da fila o que o servidor confirmou por uuid;
 * - falha retentável volta para a fila, nunca é descartada;
 * - eventos sobem em ordem de criação, porque uns referenciam os outros.
 */
let running = false
let timer = null

export const syncState = {
    pending: 0,
    rejected: 0,
    lastSyncAt: null,
    lastError: null,
    running: false,
}

const listeners = new Set()

export function onSyncChange(fn) {
    listeners.add(fn)
    return () => listeners.delete(fn)
}

async function emit() {
    syncState.pending = await pendingCount()
    syncState.rejected = await rejectedCount()
    listeners.forEach((fn) => fn({ ...syncState }))
}

export async function refreshPending() {
    await emit()
}

export async function sync() {
    if (running || !navigator.onLine) {
        return
    }

    running = true
    syncState.running = true
    await emit()

    try {
        await flushEvents()
        await flushBlobs()

        syncState.lastSyncAt = new Date().toISOString()
        syncState.lastError = null
    } catch (error) {
        // Erro de rede não é falha do vigilante: a fila continua intacta e o
        // app tenta de novo sozinho.
        syncState.lastError = error instanceof ApiError ? error.message : 'Sem conexão.'
    } finally {
        running = false
        syncState.running = false
        await emit()
    }
}

async function flushEvents() {
    const batch = await pendingEvents(50)

    if (batch.length === 0) {
        return
    }

    const response = await pushEvents(
        batch.map(({ uuid, type, occurred_at, payload }) => ({ uuid, type, occurred_at, payload })),
    )

    const byUuid = new Map(response.results.map((result) => [result.uuid, result]))

    for (const item of batch) {
        const result = byUuid.get(item.uuid)

        if (!result) {
            continue
        }

        if (result.status === 'accepted' || result.status === 'duplicate') {
            await db.queue.delete(item.id)
            continue
        }

        if (result.retryable) {
            await db.queue.update(item.id, { status: 'retry', attempts: item.attempts + 1 })
            continue
        }

        // Falha permanente: sai da fila, mas fica registrada para a supervisão
        // conseguir investigar o que o aparelho tentou enviar.
        await db.queue.update(item.id, {
            status: 'rejected',
            error: result.message ?? result.code,
            attempts: item.attempts + 1,
        })
    }

    // Se ainda sobrou coisa pendente, continua no próximo ciclo.
    if (batch.length === 50) {
        await flushEvents()
    }
}

async function flushBlobs() {
    const blobs = await db.blobs.where('status').equals('pending').toArray()

    for (const item of blobs) {
        try {
            await pushAttachment(item.uuid, item.blob, item.meta ?? {})
            await db.blobs.delete(item.uuid)
        } catch (error) {
            // 409 = o evento que referencia a foto ainda não subiu. Tenta depois.
            if (error instanceof ApiError && error.status !== 409) {
                await db.blobs.update(item.uuid, { attempts: (item.attempts ?? 0) + 1 })
            }
        }
    }
}

export function startSyncLoop(intervalMs = 30000) {
    stopSyncLoop()

    window.addEventListener('online', sync)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') sync()
    })

    timer = setInterval(sync, intervalMs)
    sync()
}

export function stopSyncLoop() {
    if (timer) clearInterval(timer)
    timer = null
}
