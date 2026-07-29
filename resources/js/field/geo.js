/**
 * Posição do aparelho no momento da leitura.
 *
 * GPS é lido pontualmente, nunca em polling: rastreamento contínuo drena a
 * bateria do turno inteiro e transforma a ferramenta em vigilância do vigilante,
 * que é o risco número um do projeto.
 */
/**
 * Distância entre duas coordenadas, em metros (Haversine).
 *
 * Mesma fórmula do servidor (App\Support\Geo): o aparelho precisa chegar ao
 * mesmo número para avisar o vigilante ANTES de gravar um desvio que ele nem
 * saberia ter cometido.
 */
export function distanceMeters(lat1, lng1, lat2, lng2) {
    if ([lat1, lng1, lat2, lng2].some((v) => v === null || v === undefined)) {
        return null
    }

    const earthRadius = 6_371_000
    const rad = (deg) => (deg * Math.PI) / 180

    const dLat = rad(lat2 - lat1)
    const dLng = rad(lng2 - lng1)

    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLng / 2) ** 2

    return earthRadius * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}

/** Arredonda para uma precisão que faça sentido a pé. */
export function formatDistance(meters) {
    if (meters === null) return null
    if (meters < 20) return 'aqui'
    if (meters < 1000) return `a ~${Math.round(meters / 10) * 10} m`

    return `a ~${(meters / 1000).toFixed(1)} km`
}

export function currentPosition({ timeout = 8000 } = {}) {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve({ latitude: null, longitude: null, accuracy_m: null })
            return
        }

        navigator.geolocation.getCurrentPosition(
            (position) =>
                resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy_m: Math.round(position.coords.accuracy),
                }),
            // Falha ou negativa do usuário não pode travar o registro: segue sem
            // coordenada e o servidor marca o desvio "sem GPS".
            () => resolve({ latitude: null, longitude: null, accuracy_m: null }),
            { enableHighAccuracy: true, timeout, maximumAge: 15000 },
        )
    })
}
