import Alpine from 'alpinejs'
import { db, putCache, getCache, clearAll, enqueue, enqueueBlob } from './db'
import * as api from './api'
import { sync, startSyncLoop, onSyncChange, refreshPending } from './sync'
import { QrScanner, confirmFeedback } from './scanner'
import { currentPosition } from './geo'

function fieldApp() {
    return {
        // --- estado ---
        screen: 'loading', // loading | login | home | patrol | scan | checklist | incident
        online: navigator.onLine,
        pending: 0,
        syncing: false,
        message: null,
        busy: false,

        credentials: { registration: '', password: '' },
        data: null, // pacote do bootstrap
        guardName: '',

        shift: null, // { uuid, post_id, started_at }
        patrol: null, // { uuid, route_id, scanned: [checkpoint_id] }

        scanner: null,
        scanError: null,

        activeCheckpoint: null,
        checklistAnswers: [],
        checklistPhoto: null,

        incident: { incident_type_id: '', severity: 'medium', classification: 'prevention', description: '', location: '', actions_taken: '' },
        incidentPhoto: null,

        handoverNotes: '',

        // --- ciclo de vida ---
        async init() {
            window.addEventListener('online', () => (this.online = true))
            window.addEventListener('offline', () => (this.online = false))

            onSyncChange((state) => {
                this.pending = state.pending
                this.syncing = state.running
            })

            this.data = await getCache('bootstrap')
            this.shift = await getCache('shift')
            this.patrol = await getCache('patrol')

            if (!api.token()) {
                this.screen = 'login'
                return
            }

            this.guardName = this.data?.guard?.name ?? ''
            this.screen = this.shift ? 'home' : 'home'

            startSyncLoop()
            await refreshPending()
            this.refreshData()
        },

        // --- autenticação ---
        async doLogin() {
            this.busy = true
            this.message = null

            try {
                const result = await api.login(this.credentials.registration, this.credentials.password)
                api.setToken(result.token)
                this.guardName = result.guard.name

                if (result.guard.refresher_expired) {
                    this.message = { kind: 'warn', text: 'Sua reciclagem está vencida. Avise a supervisão.' }
                }

                await this.refreshData({ force: true })
                this.credentials.password = ''
                this.screen = 'home'
                startSyncLoop()
            } catch (error) {
                this.message = { kind: 'error', text: error.message }
            } finally {
                this.busy = false
            }
        },

        async doLogout() {
            if (this.pending > 0 && !confirm(`Há ${this.pending} registro(s) não sincronizado(s). Sair mesmo assim?`)) {
                return
            }

            try {
                await api.logout?.()
            } catch {
                // Sair local é o que importa; o token expira sozinho no servidor.
            }

            api.setToken(null)
            await clearAll()
            this.data = this.shift = this.patrol = null
            this.screen = 'login'
        },

        /** Baixa o pacote do turno. Sem rede, segue com o que já está em cache. */
        async refreshData({ force = false } = {}) {
            if (!navigator.onLine && !force) return

            try {
                const payload = await api.bootstrap()
                await putCache('bootstrap', payload)
                this.data = payload
                this.guardName = payload.guard.name

                // O servidor é a verdade sobre turno aberto: se o aparelho foi
                // trocado no meio do turno, o novo já assume o turno em curso.
                if (payload.open_shift && !this.shift) {
                    this.shift = {
                        uuid: payload.open_shift.uuid,
                        post_id: payload.open_shift.post_id,
                        started_at: payload.open_shift.started_at,
                    }
                    await putCache('shift', this.shift)
                }
            } catch (error) {
                if (!this.data) {
                    this.message = { kind: 'error', text: error.message }
                }
            }
        },

        // --- turno ---
        get post() {
            return this.data?.posts?.find((p) => p.id === this.shift?.post_id) ?? null
        },

        get routes() {
            return this.data?.routes ?? []
        },

        async startShift(postId) {
            const position = await currentPosition()

            const uuid = await enqueue('shift.start', { post_id: postId, ...position })

            this.shift = { uuid, post_id: postId, started_at: new Date().toISOString() }
            await putCache('shift', this.shift)
            await refreshPending()
            sync()

            this.message = { kind: 'ok', text: 'Posto assumido.' }
        },

        async endShift() {
            if (this.patrol) {
                this.message = { kind: 'error', text: 'Encerre a ronda em andamento antes de fechar o turno.' }
                return
            }

            await enqueue('shift.end', { shift_uuid: this.shift.uuid, handover_notes: this.handoverNotes || null })

            this.shift = null
            this.handoverNotes = ''
            await putCache('shift', null)
            await refreshPending()
            sync()

            this.message = { kind: 'ok', text: 'Turno encerrado.' }
        },

        // --- ronda ---
        async startPatrol(routeId) {
            const uuid = await enqueue('patrol.start', {
                shift_uuid: this.shift.uuid,
                patrol_route_id: routeId,
            })

            this.patrol = { uuid, route_id: routeId, scanned: [] }
            await putCache('patrol', this.patrol)
            await refreshPending()
            sync()

            this.screen = 'patrol'
        },

        get route() {
            return this.routes.find((r) => r.id === this.patrol?.route_id) ?? null
        },

        /** Pontos do roteiro, na ordem, já com o estado de leitura. */
        get routeCheckpoints() {
            if (!this.route) return []

            return [...this.route.checkpoints]
                .sort((a, b) => a.position - b.position)
                .map((item) => {
                    const checkpoint = this.data.checkpoints.find((c) => c.id === item.checkpoint_id)

                    return {
                        ...item,
                        checkpoint,
                        done: this.patrol.scanned.includes(item.checkpoint_id),
                    }
                })
                .filter((item) => item.checkpoint)
        },

        get remainingCount() {
            return this.routeCheckpoints.filter((item) => !item.done).length
        },

        async endPatrol() {
            if (this.remainingCount > 0 && !confirm(`Faltam ${this.remainingCount} ponto(s). Encerrar mesmo assim?`)) {
                return
            }

            await enqueue('patrol.end', { patrol_uuid: this.patrol.uuid })

            this.patrol = null
            await putCache('patrol', null)
            await refreshPending()
            sync()

            this.screen = 'home'
            this.message = { kind: 'ok', text: 'Ronda encerrada.' }
        },

        // --- leitura de ponto ---
        async openScanner() {
            this.screen = 'scan'
            this.scanError = null

            await this.$nextTick()

            this.scanner = new QrScanner(this.$refs.video, (value) => this.handleScan(value))

            try {
                await this.scanner.start()
            } catch {
                this.scanError = 'Não foi possível abrir a câmera. Use a lista de pontos para registrar manualmente.'
            }
        },

        closeScanner() {
            this.scanner?.stop()
            this.scanner = null

            if (this.screen === 'scan') {
                this.screen = 'patrol'
            }
        },

        handleScan(value) {
            const token = value.startsWith('CP:') ? value.slice(3) : value
            const checkpoint = this.data.checkpoints.find((c) => c.qr_token === token)

            if (!checkpoint) {
                this.scanError = 'Este QR Code não pertence a esta unidade.'
                this.scanner?.tick()
                return
            }

            confirmFeedback()
            this.closeScanner()
            this.openCheckpoint(checkpoint, 'qr')
        },

        /** Registro manual: o ponto existe mas o QR está danificado ou coberto. */
        openCheckpointManually(checkpoint) {
            this.openCheckpoint(checkpoint, 'manual')
        },

        openCheckpoint(checkpoint, method) {
            this.activeCheckpoint = { ...checkpoint, method }
            this.checklistPhoto = null
            this.checklistAnswers = (checkpoint.checklist ?? []).map((item) => ({
                checklist_item_id: item.id,
                label: item.label,
                answer: 'conforming',
                note: '',
            }))

            this.screen = 'checklist'
        },

        async confirmCheckpoint({ skipped = false, justification = null } = {}) {
            this.busy = true

            try {
                const position = await currentPosition()
                const attachments = []

                if (this.checklistPhoto) {
                    attachments.push(await enqueueBlob(this.checklistPhoto, {
                        captured_at: new Date().toISOString(),
                        ...position,
                    }))
                }

                await enqueue('patrol.scan', {
                    patrol_uuid: this.patrol.uuid,
                    checkpoint_id: this.activeCheckpoint.id,
                    method: skipped ? 'manual' : this.activeCheckpoint.method,
                    outcome: skipped ? 'skipped' : 'scanned',
                    justification,
                    ...position,
                    checklist: skipped
                        ? []
                        : this.checklistAnswers.map((answer) => ({
                              uuid: crypto.randomUUID(),
                              checklist_item_id: answer.checklist_item_id,
                              answer: answer.answer,
                              note: answer.note || null,
                          })),
                    attachments,
                })

                if (!skipped) {
                    this.patrol.scanned = [...new Set([...this.patrol.scanned, this.activeCheckpoint.id])]
                    await putCache('patrol', this.patrol)
                }

                this.activeCheckpoint = null
                this.checklistPhoto = null
                this.screen = 'patrol'
                await refreshPending()
                sync()
            } finally {
                this.busy = false
            }
        },

        async skipCheckpoint(checkpoint) {
            const justification = prompt('Por que este ponto não foi realizado?')

            if (!justification?.trim()) {
                return
            }

            this.activeCheckpoint = { ...checkpoint, method: 'manual' }
            await this.confirmCheckpoint({ skipped: true, justification: justification.trim() })
        },

        get hasNonConforming() {
            return this.checklistAnswers.some((answer) => answer.answer === 'nonconforming')
        },

        get photoRequired() {
            return this.checklistAnswers.some(
                (answer) =>
                    answer.answer === 'nonconforming' &&
                    (this.activeCheckpoint?.checklist ?? []).find(
                        (item) => item.id === answer.checklist_item_id,
                    )?.photo_required_when_nonconforming,
            )
        },

        // --- ocorrência ---
        openIncident() {
            this.incident = {
                incident_type_id: '',
                severity: 'medium',
                classification: 'prevention',
                description: '',
                location: '',
                actions_taken: '',
            }
            this.incidentPhoto = null
            this.screen = 'incident'
        },

        applyIncidentDefaults() {
            const type = this.data.incident_types.find((t) => t.id === Number(this.incident.incident_type_id))

            if (type?.default_severity) this.incident.severity = type.default_severity
            if (type?.default_classification) this.incident.classification = type.default_classification
        },

        async submitIncident() {
            if (!this.incident.incident_type_id || !this.incident.description.trim()) {
                this.message = { kind: 'error', text: 'Informe o tipo e o relato da ocorrência.' }
                return
            }

            this.busy = true

            try {
                const position = await currentPosition()
                const attachments = []

                if (this.incidentPhoto) {
                    attachments.push(await enqueueBlob(this.incidentPhoto, {
                        captured_at: new Date().toISOString(),
                        ...position,
                    }))
                }

                await enqueue('incident.report', {
                    shift_uuid: this.shift?.uuid ?? null,
                    patrol_uuid: this.patrol?.uuid ?? null,
                    checkpoint_id: this.activeCheckpoint?.id ?? null,
                    incident_type_id: Number(this.incident.incident_type_id),
                    severity: this.incident.severity,
                    classification: this.incident.classification,
                    description: this.incident.description.trim(),
                    location: this.incident.location || null,
                    actions_taken: this.incident.actions_taken || null,
                    ...position,
                    attachments,
                })

                await refreshPending()
                sync()

                this.screen = this.patrol ? 'patrol' : 'home'
                this.message = { kind: 'ok', text: 'Ocorrência registrada. Ela sobe assim que houver rede.' }
            } finally {
                this.busy = false
            }
        },

        // --- utilidades ---
        capturePhoto(event, target) {
            const file = event.target.files?.[0]

            if (file) {
                this[target] = file
            }
        },

        dismissMessage() {
            this.message = null
        },

        syncNow() {
            sync()
        },

        formatTime(value) {
            if (!value) return '—'
            return new Date(value).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
        },
    }
}

window.Alpine = Alpine
Alpine.data('fieldApp', fieldApp)
Alpine.start()

// Service worker: casca offline. Sem ele o app não abre no subsolo.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Sem SW o app ainda funciona online; a fila local continua valendo.
        })
    })
}

export { db }
