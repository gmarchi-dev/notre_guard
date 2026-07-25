/**
 * Posição do aparelho no momento da leitura.
 *
 * GPS é lido pontualmente, nunca em polling: rastreamento contínuo drena a
 * bateria do turno inteiro e transforma a ferramenta em vigilância do vigilante,
 * que é o risco número um do projeto.
 */
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
