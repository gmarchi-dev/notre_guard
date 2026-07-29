/**
 * Cliente da API de campo.
 *
 * Toda função aqui pode falhar por falta de rede — isso é o normal, não a
 * exceção. Quem chama trata o erro mantendo o registro na fila local.
 */
const TOKEN_KEY = 'ng.token'
const DEVICE_KEY = 'ng.device_id'

export function deviceId() {
    let id = localStorage.getItem(DEVICE_KEY)

    if (!id) {
        id = crypto.randomUUID()
        localStorage.setItem(DEVICE_KEY, id)
    }

    return id
}

export function token() {
    return localStorage.getItem(TOKEN_KEY)
}

export function setToken(value) {
    if (value) {
        localStorage.setItem(TOKEN_KEY, value)
    } else {
        localStorage.removeItem(TOKEN_KEY)
    }
}

async function request(path, options = {}) {
    const response = await fetch(`/api/v1/${path}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            'X-Device-Id': deviceId(),
            ...(token() ? { Authorization: `Bearer ${token()}` } : {}),
            ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            ...(options.headers ?? {}),
        },
    })

    if (response.status === 401) {
        setToken(null)
        throw new ApiError('Sessão expirada. Faça login novamente.', 401)
    }

    const data = await response.json().catch(() => ({}))

    if (!response.ok) {
        throw new ApiError(data.message ?? 'Falha na comunicação.', response.status, data)
    }

    return data
}

export class ApiError extends Error {
    constructor(message, status, data = {}) {
        super(message)
        this.status = status
        this.data = data
    }
}

export function login(registration, password) {
    return request('auth/login', {
        method: 'POST',
        body: JSON.stringify({
            registration,
            password,
            device_id: deviceId(),
            device_name: navigator.userAgent.slice(0, 120),
        }),
    })
}

export function logout() {
    return request('auth/logout', { method: 'POST' })
}

export function bootstrap() {
    return request('bootstrap')
}

/**
 * Pânico vai direto, fora da fila. Timeout curto de propósito: se o servidor
 * não responder rápido, o app não pode ficar esperando — enfileira e segue.
 */
export function sendPanic(payload, timeoutMs = 6000) {
    return request('panic', {
        method: 'POST',
        body: JSON.stringify(payload),
        signal: AbortSignal.timeout(timeoutMs),
    })
}

export function panicStatus(uuid) {
    return request(`panic/${uuid}`)
}

export function pushEvents(events) {
    return request('sync/events', {
        method: 'POST',
        body: JSON.stringify({
            client_sent_at: new Date().toISOString(),
            events,
        }),
    })
}

export function pushAttachment(uuid, blob, meta = {}) {
    const form = new FormData()
    form.append('file', blob, meta.filename ?? `${uuid}.jpg`)

    if (meta.captured_at) form.append('captured_at', meta.captured_at)
    if (meta.latitude != null) form.append('latitude', meta.latitude)
    if (meta.longitude != null) form.append('longitude', meta.longitude)

    return request(`sync/attachments/${uuid}`, { method: 'POST', body: form })
}
