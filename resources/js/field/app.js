import Alpine from 'alpinejs'
import {
    db,
    putCache,
    getCache,
    clearAll,
    enqueue,
    enqueueBlob,
    queueSnapshot,
    retryEvent,
    discardEvent,
} from './db'
import * as api from './api'
import { sync, startSyncLoop, onSyncChange, refreshPending } from './sync'
import { QrScanner, confirmFeedback } from './scanner'
import { currentPosition } from './geo'

/** Telas que empilham histórico — ver navigate() e o tratamento de popstate. */
const SCREENS = ['boot', 'login', 'home', 'patrol', 'scan', 'checklist', 'incident', 'queue']

const REASONS = ['Área interditada', 'Sem acesso / chave', 'Risco no local']

function fieldApp() {
    return {
        // ------------------------------------------------------------ estado
        screen: 'boot',
        bootError: null,
        online: navigator.onLine,
        pending: 0,
        rejected: 0,
        syncing: false,
        queue: { events: [], photos: 0 },
        busy: false,

        toasts: [],
        sheet: null,

        credentials: { registration: '', password: '' },
        data: null,
        dataAt: null,
        guardName: '',

        shift: null,
        patrol: null,

        scanner: null,
        scanError: null,
        scanStatus: 'Abrindo a câmera…',

        activeCheckpoint: null,
        checklistAnswers: [],
        checklistPhoto: null,
        checklistPhotoUrl: null,

        incident: {
            incident_type_id: '',
            severity: 'medium',
            classification: 'prevention',
            description: '',
            location: '',
            actions_taken: '',
        },
        incidentErrors: {},
        incidentPhoto: null,
        incidentPhotoUrl: null,

        panic: { confirming: false, sending: false, state: null, at: null },

        handoverNotes: '',
        exitArmed: false,

        // ------------------------------------------------------ ciclo de vida
        async init() {
            window.addEventListener('online', () => {
                this.online = true
                this.refreshData()
            })
            window.addEventListener('offline', () => (this.online = false))

            onSyncChange((state) => {
                this.pending = state.pending
                this.rejected = state.rejected
                this.syncing = state.running

                if (this.screen === 'queue') {
                    this.loadQueue()
                }
            })

            window.addEventListener('popstate', (event) => this.onPopState(event))

            await this.boot()
        },

        /**
         * Leitura do estado local. Antes isto rodava solto: se o IndexedDB
         * falhasse ou travasse, o aplicativo ficava numa tela em branco para
         * sempre, porque o estado inicial não tinha tela correspondente.
         */
        async boot() {
            this.bootError = null

            try {
                const timeout = new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('O armazenamento do aparelho não respondeu.')), 6000),
                )

                const load = (async () => {
                    this.data = await getCache('bootstrap')
                    this.dataAt = await getCache('bootstrap_at')
                    this.shift = await getCache('shift')
                    this.patrol = await getCache('patrol')
                    this.handoverNotes = (await getCache('handover')) ?? ''
                })()

                await Promise.race([load, timeout])
            } catch (error) {
                this.bootError = error.message
                return
            }

            if (!api.token()) {
                this.navigate('login', { replace: true })
                return
            }

            this.guardName = this.data?.guard?.name ?? ''

            // Ronda em andamento volta a ser visível. Antes o estado era
            // restaurado do cache mas a tela ia para 'home' de qualquer jeito.
            this.navigate(this.patrol ? 'patrol' : 'home', { replace: true })

            startSyncLoop()
            await refreshPending()
            this.refreshData()
        },

        retryBoot() {
            this.boot()
        },

        async hardReset() {
            const ok = await this.ask({
                title: 'Entrar de novo?',
                text: 'Isso apaga o que ainda não subiu para o servidor. Só faça se não houver outra saída.',
                confirmLabel: 'Apagar e entrar de novo',
                destructive: true,
            })

            if (!ok) return

            api.setToken(null)
            await clearAll()
            this.data = this.shift = this.patrol = null
            this.bootError = null
            this.navigate('login', { replace: true })
        },

        // -------------------------------------------------------- navegação
        /**
         * Ponto único de troca de tela. Empilha histórico para que o botão
         * voltar do Android navegue dentro do aplicativo em vez de fechá-lo no
         * meio de uma ronda.
         */
        navigate(screen, { replace = false } = {}) {
            if (!SCREENS.includes(screen)) return

            const state = { screen }

            if (replace) {
                history.replaceState(state, '')
            } else if (this.screen !== screen) {
                history.pushState(state, '')
            }

            this.screen = screen
            this.exitArmed = false

            this.$nextTick(() => {
                if (this.$refs.view) this.$refs.view.scrollTop = 0
                this.focusHeading()
            })
        },

        goBack() {
            history.back()
        },

        onPopState(event) {
            // Camada aberta: voltar fecha a camada, não a tela. É a convenção
            // do Android.
            if (this.panic.confirming) {
                this.cancelPanic()
                history.pushState({ screen: this.screen }, '')
                return
            }

            if (this.sheet) {
                this.resolveSheet(false)
                history.pushState({ screen: this.screen }, '')
                return
            }

            if (this.scanner) {
                this.closeScanner()
                return
            }

            const target = event.state?.screen

            if (target && SCREENS.includes(target)) {
                this.screen = target
                this.$nextTick(() => this.focusHeading())
                return
            }

            // Raiz do histórico com turno aberto: sair sem querer no meio de um
            // turno é caro, então o primeiro toque só avisa.
            if (this.shift && !this.exitArmed) {
                this.exitArmed = true
                this.toast('Toque em voltar de novo para sair do aplicativo.', 'warn', 2500)
                history.pushState({ screen: this.screen }, '')
                setTimeout(() => (this.exitArmed = false), 2500)
            }
        },

        focusHeading() {
            const heading = this.$root?.querySelector('main h1[tabindex="-1"]')
            heading?.focus({ preventScroll: true })
        },

        // ------------------------------------------------------------ toasts
        toast(text, kind = 'ok', ttl = null) {
            const id = crypto.randomUUID()
            this.toasts = [...this.toasts.slice(-1), { id, text, kind }]

            // Sucesso some sozinho; erro e alerta ficam até serem fechados.
            const life = ttl ?? (kind === 'ok' ? 4000 : null)

            if (life) {
                setTimeout(() => this.dismissToast(id), life)
            }
        },

        dismissToast(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id)
        },

        // ------------------------------------------------------------ sheets
        /**
         * Substituem confirm() e prompt(). Devolvem Promise para que o fluxo de
         * chamada continue linear.
         */
        ask(options) {
            return new Promise((resolve) => {
                this.sheet = { kind: 'confirm', ...options, resolve }
                this.openLayer()
            })
        },

        askText(options) {
            return new Promise((resolve) => {
                this.sheet = { kind: 'text', value: '', ...options, resolve }
                this.openLayer()
            })
        },

        choose(options) {
            return new Promise((resolve) => {
                this.sheet = { kind: 'choice', ...options, resolve }
                this.openLayer()
            })
        },

        openLayer() {
            this.$nextTick(() => this.$refs.sheetTitle?.focus({ preventScroll: true }))
        },

        resolveSheet(value) {
            if (!this.sheet) return

            const { resolve, kind } = this.sheet
            const trigger = document.activeElement

            let result = value

            if (kind === 'text') {
                result = value ? (this.sheet.value ?? '').trim() : null
            }

            this.sheet = null
            resolve(result)

            // Foco volta a quem abriu.
            this.$nextTick(() => {
                if (trigger && document.contains(trigger)) return
                this.focusHeading()
            })
        },

        /** Mantém o Tab dentro da camada aberta. */
        trapFocus(event, container) {
            if (!container) return

            const focusable = [...container.querySelectorAll(
                'button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
            )].filter((el) => el.offsetParent !== null)

            if (focusable.length === 0) return

            const first = focusable[0]
            const last = focusable[focusable.length - 1]

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault()
                last.focus()
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault()
                first.focus()
            }
        },

        // ------------------------------------------------------ autenticação
        async doLogin() {
            if (this.busy) return

            this.busy = true

            try {
                const result = await api.login(this.credentials.registration, this.credentials.password)
                api.setToken(result.token)
                this.guardName = result.guard.name

                if (result.guard.refresher_expired) {
                    this.toast('Sua reciclagem está vencida. Avise a supervisão.', 'warn')
                }

                await this.refreshData({ force: true })
                this.credentials.password = ''
                this.navigate('home', { replace: true })
                startSyncLoop()
            } catch (error) {
                this.toast(error.message, 'error')
            } finally {
                this.busy = false
            }
        },

        async doLogout() {
            if (this.pending > 0) {
                const ok = await this.ask({
                    title: 'Sair com registros pendentes?',
                    text: `Há ${this.pending} registro(s) que ainda não subiram. Sair apaga tudo o que está no aparelho.`,
                    confirmLabel: 'Sair mesmo assim',
                    destructive: true,
                })

                if (!ok) return
            }

            try {
                await api.logout?.()
            } catch {
                // Sair local é o que importa; o token expira sozinho no servidor.
            }

            api.setToken(null)
            await clearAll()
            this.data = this.shift = this.patrol = null
            this.handoverNotes = ''
            this.navigate('login', { replace: true })
        },

        /** Baixa o pacote do turno. Sem rede, segue com o que já está em cache. */
        async refreshData({ force = false } = {}) {
            if (!navigator.onLine && !force) return

            try {
                const payload = await api.bootstrap()
                const now = new Date().toISOString()

                await putCache('bootstrap', payload)
                await putCache('bootstrap_at', now)

                this.data = payload
                this.dataAt = now
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
                // Sem dado nenhum é erro de verdade. Com dado velho, o aplicativo
                // segue funcionando — mas o vigilante precisa SABER que está
                // rondando com um roteiro desatualizado. Antes isso era engolido.
                if (!this.data) {
                    this.toast(error.message, 'error')
                }
            }
        },

        get dataStale() {
            if (!this.data || !this.dataAt) return false

            return Date.now() - new Date(this.dataAt).getTime() > 6 * 60 * 60 * 1000
        },

        dataAgeLabel() {
            if (!this.dataAt) return 'há muito tempo'

            const hours = Math.floor((Date.now() - new Date(this.dataAt).getTime()) / 3600000)

            if (hours < 1) return 'há menos de uma hora'
            if (hours < 24) return `há ${hours} h`

            return `há ${Math.floor(hours / 24)} dia(s)`
        },

        // ------------------------------------------------------------- turno
        get post() {
            return this.data?.posts?.find((p) => p.id === this.shift?.post_id) ?? null
        },

        get routes() {
            return this.data?.routes ?? []
        },

        postKindLabel(kind) {
            return { reception: 'Portaria/Recepção', mobile: 'Móvel' }[kind] ?? 'Fixo'
        },

        contextLine() {
            if (!this.shift) return ''

            return `${this.post?.name ?? 'Posto'} · desde ${this.formatTime(this.shift.started_at)}`
        },

        async startShift(postId) {
            if (this.busy) return

            this.busy = true

            try {
                const position = await currentPosition()
                const uuid = await enqueue('shift.start', { post_id: postId, ...position })

                this.shift = { uuid, post_id: postId, started_at: new Date().toISOString() }
                await putCache('shift', this.shift)
                await refreshPending()
                sync()

                this.toast('Posto assumido.')
            } catch (error) {
                this.toast('Não foi possível assumir o posto: ' + error.message, 'error')
            } finally {
                this.busy = false
            }
        },

        async endShift() {
            if (this.patrol) {
                this.toast('Encerre a ronda em andamento antes de fechar o turno.', 'error')
                return
            }

            const ok = await this.ask({
                title: 'Encerrar o turno?',
                text: 'A passagem de serviço que você escreveu vai junto.',
                confirmLabel: 'Encerrar turno',
                destructive: true,
            })

            if (!ok) return

            await enqueue('shift.end', {
                shift_uuid: this.shift.uuid,
                handover_notes: this.handoverNotes || null,
            })

            this.shift = null
            this.handoverNotes = ''
            await putCache('shift', null)
            await putCache('handover', '')
            await refreshPending()
            sync()

            this.toast('Turno encerrado.')
        },

        /** A passagem de serviço sobrevive a um recarregamento do aplicativo. */
        persistHandover() {
            putCache('handover', this.handoverNotes)
        },

        // ------------------------------------------------------------- ronda
        async startPatrol(routeId) {
            if (this.busy) return

            // Antes, iniciar uma ronda com outra em andamento sobrescrevia a
            // anterior sem encerrá-la — o servidor ficava com uma ronda órfã.
            if (this.patrol) {
                const choice = await this.choose({
                    title: 'Já existe uma ronda em andamento',
                    text: `${this.route?.name ?? 'Roteiro'} — ${this.routeCheckpoints.length - this.remainingCount} de ${this.routeCheckpoints.length} pontos.`,
                    options: [
                        { label: 'Retomar a ronda atual', value: 'resume', variant: 'primary' },
                        { label: 'Encerrar a atual e iniciar esta', value: 'switch', variant: 'critical' },
                    ],
                })

                if (choice === 'resume') {
                    this.navigate('patrol')
                    return
                }

                if (choice !== 'switch') return

                await this.finishPatrol()
            }

            this.busy = true

            try {
                const uuid = await enqueue('patrol.start', {
                    shift_uuid: this.shift.uuid,
                    patrol_route_id: routeId,
                })

                this.patrol = { uuid, route_id: routeId, scanned: [], skipped: [] }
                await putCache('patrol', this.patrol)
                await refreshPending()
                sync()

                this.navigate('patrol')
            } catch (error) {
                this.toast('Não foi possível iniciar a ronda: ' + error.message, 'error')
            } finally {
                this.busy = false
            }
        },

        resumePatrol() {
            this.navigate('patrol')
        },

        get route() {
            return this.routes.find((r) => r.id === this.patrol?.route_id) ?? null
        },

        /** Pontos do roteiro, na ordem, já com o estado de leitura. */
        get routeCheckpoints() {
            if (!this.route || !this.patrol) return []

            const skipped = this.patrol.skipped ?? []

            return [...this.route.checkpoints]
                .sort((a, b) => a.position - b.position)
                .map((item) => {
                    const checkpoint = this.data.checkpoints.find((c) => c.id === item.checkpoint_id)

                    return {
                        ...item,
                        checkpoint,
                        done: this.patrol.scanned.includes(item.checkpoint_id),
                        skipped: skipped.includes(item.checkpoint_id),
                    }
                })
                .filter((item) => item.checkpoint)
        },

        get remainingCount() {
            return this.routeCheckpoints.filter((item) => !item.done).length
        },

        /** O primeiro ponto ainda não registrado. É o que o cartão do topo mostra. */
        get nextItem() {
            return this.routeCheckpoints.find((item) => !item.done && !item.skipped) ?? null
        },

        get nextCheckpoint() {
            return this.nextItem?.checkpoint ?? null
        },

        get nextPosition() {
            return this.nextItem?.position ?? ''
        },

        progressLabel() {
            const done = this.routeCheckpoints.length - this.remainingCount

            return `${done} de ${this.routeCheckpoints.length} pontos registrados`
        },

        async endPatrol() {
            if (this.remainingCount > 0) {
                const ok = await this.ask({
                    title: 'Encerrar com pontos faltando?',
                    text: `Faltam ${this.remainingCount} ponto(s) deste roteiro.`,
                    confirmLabel: 'Encerrar mesmo assim',
                    destructive: true,
                })

                if (!ok) return
            }

            await this.finishPatrol()
            this.navigate('home')
            this.toast('Ronda encerrada.')
        },

        async finishPatrol() {
            await enqueue('patrol.end', { patrol_uuid: this.patrol.uuid })

            this.patrol = null
            await putCache('patrol', null)
            await refreshPending()
            sync()
        },

        // --------------------------------------------------- leitura de ponto
        async openScanner() {
            this.navigate('scan')
            this.scanError = null
            this.scanStatus = 'Abrindo a câmera…'

            await this.$nextTick()

            this.scanner = new QrScanner(this.$refs.video, (value) => this.handleScan(value))

            try {
                await this.scanner.start()
                this.scanStatus = 'Procurando QR Code…'
            } catch {
                this.scanner = null
                this.scanStatus = 'Câmera indisponível.'
                this.scanError = 'Não foi possível abrir a câmera. Toque no ponto na lista para registrar manualmente.'
            }
        },

        closeScanner() {
            this.scanner?.stop()
            this.scanner = null

            if (this.screen === 'scan') {
                this.navigate('patrol')
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

        /** Toque numa linha do trilho: registrar manualmente ou pular. */
        async openCheckpointMenu(item) {
            if (item.done) {
                this.toast(`${item.checkpoint.code} já foi registrado nesta ronda.`, 'warn', 3000)
                return
            }

            const choice = await this.choose({
                title: `${item.checkpoint.code} — ${item.checkpoint.name}`,
                text: 'O QR Code é o registro preferencial. Use o manual só quando a etiqueta estiver danificada ou coberta.',
                options: [
                    { label: 'Registrar manualmente', value: 'manual', variant: 'primary' },
                    { label: 'Pular este ponto', value: 'skip', variant: 'critical' },
                ],
                cancelLabel: 'Voltar',
            })

            if (choice === 'manual') {
                this.openCheckpoint(item.checkpoint, 'manual')
            } else if (choice === 'skip') {
                this.skipCheckpoint(item.checkpoint)
            }
        },

        openCheckpoint(checkpoint, method) {
            this.activeCheckpoint = { ...checkpoint, method }
            this.clearPhoto('checklistPhoto')
            this.checklistAnswers = (checkpoint.checklist ?? []).map((item) => ({
                checklist_item_id: item.id,
                label: item.label,
                answer: 'conforming',
                note: '',
            }))

            this.navigate('checklist')
        },

        leaveChecklist() {
            this.activeCheckpoint = null
            this.clearPhoto('checklistPhoto')
            this.navigate('patrol')
        },

        async confirmCheckpoint({ skipped = false, justification = null } = {}) {
            if (this.busy) return

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

                if (skipped) {
                    this.patrol.skipped = [
                        ...new Set([...(this.patrol.skipped ?? []), this.activeCheckpoint.id]),
                    ]
                } else {
                    this.patrol.scanned = [
                        ...new Set([...this.patrol.scanned, this.activeCheckpoint.id]),
                    ]
                }

                await putCache('patrol', this.patrol)

                const code = this.activeCheckpoint.code

                this.activeCheckpoint = null
                this.clearPhoto('checklistPhoto')
                this.navigate('patrol')
                await refreshPending()
                sync()

                this.toast(skipped ? `${code} marcado como não realizado.` : `${code} registrado.`)
            } catch (error) {
                // O registro falhou: fica na tela, com a foto preservada, para o
                // vigilante tentar de novo. Antes o erro sumia em silêncio.
                this.toast('Não foi possível registrar o ponto: ' + error.message, 'error')
            } finally {
                this.busy = false
            }
        },

        async skipCheckpoint(checkpoint) {
            // Justificativa é registro de auditoria. Antes vinha de um prompt()
            // de uma linha que nem dizia qual ponto estava sendo pulado.
            const justification = await this.askText({
                title: `Pular ${checkpoint.code}`,
                text: checkpoint.name,
                label: 'Por que este ponto não foi realizado?',
                placeholder: 'Descreva o motivo',
                reasons: REASONS,
                confirmLabel: 'Registrar como não realizado',
                destructive: true,
            })

            // Cancelar precisa ser cancelar: gravar "pulado" sem justificativa
            // seria um furo de auditoria.
            if (!justification) return

            this.activeCheckpoint = { ...checkpoint, method: 'manual' }
            await this.confirmCheckpoint({ skipped: true, justification })
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

        // --------------------------------------------------------- ocorrência
        openIncident() {
            this.incident = {
                incident_type_id: '',
                severity: 'medium',
                classification: 'prevention',
                description: '',
                location: '',
                actions_taken: '',
            }
            this.incidentErrors = {}
            this.clearPhoto('incidentPhoto')
            this.navigate('incident')
        },

        cancelIncident() {
            this.clearPhoto('incidentPhoto')
            this.navigate(this.patrol ? 'patrol' : 'home')
        },

        applyIncidentDefaults() {
            const type = this.data.incident_types.find((t) => t.id === Number(this.incident.incident_type_id))

            if (type?.default_severity) this.incident.severity = type.default_severity
            if (type?.default_classification) this.incident.classification = type.default_classification

            delete this.incidentErrors.incident_type_id
        },

        async submitIncident() {
            if (this.busy) return

            this.incidentErrors = {}

            if (!this.incident.incident_type_id) {
                this.incidentErrors.incident_type_id = 'Escolha o tipo da ocorrência.'
            }

            if (!this.incident.description.trim()) {
                this.incidentErrors.description = 'Descreva o que aconteceu.'
            }

            if (Object.keys(this.incidentErrors).length > 0) {
                this.toast('Faltam campos obrigatórios.', 'error')
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

                this.clearPhoto('incidentPhoto')
                this.navigate(this.patrol ? 'patrol' : 'home')
                this.toast('Ocorrência registrada. Ela sobe assim que houver rede.')
            } catch (error) {
                this.toast('Não foi possível registrar a ocorrência: ' + error.message, 'error')
            } finally {
                this.busy = false
            }
        },

        // ------------------------------------------------------------- pânico
        askPanic() {
            this.panic.confirming = true
            history.pushState({ screen: this.screen }, '')
            this.$nextTick(() => this.$refs.panicTitle?.focus({ preventScroll: true }))
        },

        cancelPanic() {
            this.panic.confirming = false
        },

        async firePanic() {
            if (this.panic.sending) return

            this.panic.sending = true

            const uuid = crypto.randomUUID()
            const occurredAt = new Date().toISOString()

            // GPS com prazo curto: se demorar, envia sem coordenada. Alerta sem
            // localização chega; alerta que não chega não serve para nada.
            const position = await currentPosition({ timeout: 4000 })
            const payload = { uuid, occurred_at: occurredAt, ...position }

            let delivered = false

            try {
                await api.sendPanic(payload)
                delivered = true
            } catch {
                // Sem rede ou servidor lento: entra na fila com o MESMO uuid,
                // então a entrega tardia não cria um segundo alerta.
                await enqueue(
                    'panic.alert',
                    { shift_uuid: this.shift?.uuid ?? null, ...position },
                    occurredAt,
                    uuid,
                )
                await refreshPending()
                sync()
            }

            this.panic = {
                confirming: false,
                sending: false,
                state: delivered ? 'delivered' : 'queued',
                at: occurredAt,
            }

            // Vibração longa: confirma o acionamento sem exigir que ele leia.
            navigator.vibrate?.([200, 100, 200, 100, 200])
        },

        dismissPanicState() {
            this.panic.state = null
        },

        // --------------------------------------------------------------- fila
        async openQueue() {
            this.navigate('queue')
            await this.loadQueue()
        },

        async loadQueue() {
            this.queue = await queueSnapshot()
        },

        queueSummary() {
            const parts = [`${this.pending} aguardando envio`]

            if (this.queue.photos > 0) parts.push(`${this.queue.photos} foto(s)`)
            if (this.rejected > 0) parts.push(`${this.rejected} recusado(s)`)

            return parts.join(' · ') + '.'
        },

        syncChip() {
            if (this.rejected > 0) {
                return { variant: 'chip--rejected', label: `${this.rejected} recusado(s)` }
            }

            if (!this.online) {
                return { variant: 'chip--offline', label: 'Sem rede' }
            }

            if (this.syncing) {
                return { variant: 'chip--pending', label: 'Enviando…' }
            }

            if (this.pending > 0) {
                return { variant: 'chip--pending', label: `${this.pending} pendente(s)` }
            }

            return { variant: 'chip--clean', label: 'Em dia' }
        },

        async retry(id) {
            await retryEvent(id)
            await this.loadQueue()
            sync()
        },

        async discard(id) {
            const ok = await this.ask({
                title: 'Descartar este registro?',
                text: 'Ele não será enviado e não poderá ser recuperado.',
                confirmLabel: 'Descartar',
                destructive: true,
            })

            if (!ok) return

            await discardEvent(id)
            await this.loadQueue()
            await refreshPending()
        },

        queueLabel(type) {
            return {
                'shift.start': 'Assunção de posto',
                'shift.end': 'Encerramento de turno',
                'patrol.start': 'Início de ronda',
                'patrol.scan': 'Leitura de ponto',
                'patrol.end': 'Encerramento de ronda',
                'incident.report': 'Ocorrência',
                'panic.alert': 'Acionamento de emergência',
            }[type] ?? type
        },

        queueStatusLabel(status) {
            return {
                pending: 'Aguardando envio',
                retry: 'Tentando de novo',
                rejected: 'Recusado pelo servidor',
            }[status] ?? status
        },

        // --------------------------------------------------------- utilidades
        capturePhoto(event, target) {
            const file = event.target.files?.[0]

            if (!file) return

            this.clearPhoto(target)
            this[target] = file
            this[target + 'Url'] = URL.createObjectURL(file)
        },

        /** Revoga a URL do preview: sem isto o blob vaza a cada troca de foto. */
        clearPhoto(target) {
            const url = this[target + 'Url']

            if (url) URL.revokeObjectURL(url)

            this[target] = null
            this[target + 'Url'] = null
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
