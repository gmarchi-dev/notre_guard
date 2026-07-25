import jsQR from 'jsqr'

/**
 * Leitor de QR Code.
 *
 * Usa BarcodeDetector quando existe (Chrome/Android — muito mais rápido e com
 * menos bateria) e cai para jsQR sobre canvas no resto, principalmente iOS.
 */
export class QrScanner {
    constructor(video, onResult) {
        this.video = video
        this.onResult = onResult
        this.stream = null
        this.raf = null
        this.detector = null
        this.canvas = document.createElement('canvas')
        this.context = this.canvas.getContext('2d', { willReadFrequently: true })
    }

    async start() {
        this.stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
        })

        this.video.srcObject = this.stream
        this.video.setAttribute('playsinline', 'true')
        await this.video.play()

        if ('BarcodeDetector' in window) {
            const formats = await window.BarcodeDetector.getSupportedFormats()

            if (formats.includes('qr_code')) {
                this.detector = new window.BarcodeDetector({ formats: ['qr_code'] })
            }
        }

        this.tick()
    }

    async tick() {
        if (!this.stream) return

        try {
            const value = this.detector ? await this.detectNative() : this.detectFallback()

            if (value) {
                this.onResult(value)
                return
            }
        } catch {
            // Quadro ruim (foco, luz) não é erro: tenta o próximo.
        }

        this.raf = requestAnimationFrame(() => this.tick())
    }

    async detectNative() {
        const [first] = await this.detector.detect(this.video)
        return first?.rawValue ?? null
    }

    detectFallback() {
        const { videoWidth: width, videoHeight: height } = this.video

        if (!width || !height) return null

        this.canvas.width = width
        this.canvas.height = height
        this.context.drawImage(this.video, 0, 0, width, height)

        const image = this.context.getImageData(0, 0, width, height)
        const result = jsQR(image.data, width, height, { inversionAttempts: 'dontInvert' })

        return result?.data ?? null
    }

    stop() {
        if (this.raf) cancelAnimationFrame(this.raf)
        this.stream?.getTracks().forEach((track) => track.stop())
        this.stream = null
        this.raf = null
    }
}

/**
 * Feedback de leitura: vibração e bipe curto. O vigilante muitas vezes está de
 * luva e sem olhar direto para a tela.
 */
export function confirmFeedback() {
    navigator.vibrate?.(120)

    try {
        const audio = new (window.AudioContext || window.webkitAudioContext)()
        const oscillator = audio.createOscillator()
        const gain = audio.createGain()

        oscillator.frequency.value = 880
        gain.gain.value = 0.08
        oscillator.connect(gain).connect(audio.destination)
        oscillator.start()
        oscillator.stop(audio.currentTime + 0.12)
    } catch {
        // Sem áudio disponível — a vibração já basta.
    }
}
