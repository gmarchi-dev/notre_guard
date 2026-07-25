<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
    <meta name="theme-color" content="#0b1220">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/notre-guard-192.png">
    <title>Notre Guard — Campo</title>
    @vite(['resources/css/field.css', 'resources/js/field/app.js'])
</head>
<body>
<div class="app" x-data="fieldApp" x-cloak>

    <div class="statusbar">
        <span class="brand">Notre Guard</span>
        <span class="spacer"></span>

        <template x-if="!online">
            <span class="pill offline"><span class="dot"></span> Sem rede</span>
        </template>

        <template x-if="rejected > 0">
            <button type="button" class="pill rejected" style="min-height:auto" @click="openQueue()">
                <span x-text="rejected + ' recusado(s)'"></span>
            </button>
        </template>

        <template x-if="pending > 0">
            <button type="button" class="pill pending" style="min-height:auto" @click="openQueue()">
                <span x-text="syncing ? 'Enviando…' : pending + ' pendente(s)'"></span>
            </button>
        </template>

        <template x-if="online && pending === 0 && rejected === 0 && screen !== 'login' && screen !== 'loading'">
            <button type="button" class="pill clean" style="min-height:auto" @click="openQueue()">
                <span class="dot"></span> Em dia
            </button>
        </template>
    </div>

    <main>
        <template x-if="message">
            <div class="message" :class="message.kind">
                <span x-text="message.text"></span>
                <button type="button" @click="dismissMessage()" aria-label="Fechar aviso">&times;</button>
            </div>
        </template>

        {{-- ======================= LOGIN ======================= --}}
        <template x-if="screen === 'login'">
            <div>
                <h1>Entrar</h1>
                <p class="muted" style="margin:6px 0 18px">Use sua matrícula e senha.</p>

                <form class="card" @submit.prevent="doLogin()">
                    <div class="field">
                        <label for="registration">Matrícula</label>
                        <input id="registration" x-model="credentials.registration" autocomplete="username"
                               inputmode="text" autocapitalize="characters" required>
                    </div>
                    <div class="field">
                        <label for="password">Senha</label>
                        <input id="password" type="password" x-model="credentials.password"
                               autocomplete="current-password" required>
                    </div>
                    <div class="field">
                        <button type="submit" class="primary" :disabled="busy"
                                x-text="busy ? 'Entrando…' : 'Entrar'"></button>
                    </div>
                </form>

                <p class="notice" style="margin-top:16px">
                    Este aplicativo registra presença operacional e rondas.
                    Não é registro de ponto e não substitui a marcação de jornada.
                    A localização é coletada apenas durante a ronda.
                </p>
            </div>
        </template>

        {{-- ======================= INÍCIO ======================= --}}
        <template x-if="screen === 'home'">
            <div>
                <h1 x-text="'Olá, ' + (guardName || 'vigilante')"></h1>
                <p class="muted" x-text="data?.unit?.name ?? 'Carregando unidade…'"></p>

                {{-- sem turno aberto --}}
                <template x-if="!shift">
                    <div style="margin-top:18px">
                        <h2>Assumir posto</h2>
                        <template x-for="post in (data?.posts ?? [])" :key="post.id">
                            <div class="checkpoint" style="margin-bottom:8px">
                                <div class="info">
                                    <div class="code" x-text="post.name"></div>
                                    <div class="name" x-text="post.kind === 'reception' ? 'Portaria/Recepção' : (post.kind === 'mobile' ? 'Móvel' : 'Fixo')"></div>
                                </div>
                                <button type="button" class="primary small" @click="startShift(post.id)">Assumir</button>
                            </div>
                        </template>
                        <template x-if="(data?.posts ?? []).length === 0">
                            <p class="muted">Nenhum posto disponível. Fale com a supervisão.</p>
                        </template>
                    </div>
                </template>

                {{-- turno aberto --}}
                <template x-if="shift">
                    <div>
                        <div class="card" style="margin-top:18px">
                            <h2 style="margin-bottom:6px">Turno em andamento</h2>
                            <p><strong x-text="post?.name ?? 'Posto'"></strong></p>
                            <p class="muted">Desde <span x-text="formatTime(shift.started_at)"></span></p>
                        </div>

                        <div style="margin-top:18px">
                            <h2>Iniciar ronda</h2>
                            <template x-for="route in routes" :key="route.id">
                                <div class="checkpoint" style="margin-bottom:8px">
                                    <div class="info">
                                        <div class="code" x-text="route.name"></div>
                                        <div class="name">
                                            <span x-text="route.checkpoints.length"></span> pontos ·
                                            <span x-text="route.expected_duration_min"></span> min
                                        </div>
                                    </div>
                                    <button type="button" class="primary small" @click="startPatrol(route.id)">Iniciar</button>
                                </div>
                            </template>
                            <template x-if="routes.length === 0">
                                <p class="muted">Nenhum roteiro cadastrado para esta unidade.</p>
                            </template>
                        </div>

                        <div class="card" style="margin-top:18px">
                            <label for="handover">Passagem de serviço</label>
                            <textarea id="handover" x-model="handoverNotes"
                                      placeholder="Pendências para o próximo turno"></textarea>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- ======================= RONDA ======================= --}}
        <template x-if="screen === 'patrol'">
            <div>
                <h1 x-text="route?.name ?? 'Ronda'"></h1>
                <p class="muted">
                    <span x-text="routeCheckpoints.length - remainingCount"></span> de
                    <span x-text="routeCheckpoints.length"></span> pontos ·
                    <span x-text="remainingCount === 0 ? 'roteiro completo' : remainingCount + ' restante(s)'"></span>
                </p>

                <div style="margin-top:16px">
                    <template x-for="item in routeCheckpoints" :key="item.checkpoint_id">
                        <div class="checkpoint" :class="{ done: item.done }">
                            <div class="position" x-text="item.done ? '✓' : item.position"></div>
                            <div class="info">
                                <div class="code" x-text="item.checkpoint.code"></div>
                                <div class="name" x-text="item.checkpoint.name"></div>
                            </div>
                            <template x-if="!item.done">
                                <div style="display:flex; gap:6px">
                                    <button type="button" class="ghost small"
                                            @click="openCheckpointManually(item.checkpoint)">Manual</button>
                                    <button type="button" class="ghost small" @click="skipCheckpoint(item.checkpoint)">Pular</button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- ======================= LEITURA ======================= --}}
        <template x-if="screen === 'scan'">
            <div>
                <h1>Ler ponto</h1>
                <p class="muted">Aponte a câmera para o QR Code do ponto de controle.</p>

                <div class="scanner" style="margin-top:14px">
                    <video x-ref="video" muted playsinline></video>
                    <div class="reticle"></div>
                </div>

                <template x-if="scanError">
                    <div class="message error" style="margin-top:14px">
                        <span x-text="scanError"></span>
                    </div>
                </template>
            </div>
        </template>

        {{-- ======================= CHECKLIST ======================= --}}
        <template x-if="screen === 'checklist'">
            <div>
                <h1 x-text="activeCheckpoint?.code"></h1>
                <p class="muted" x-text="activeCheckpoint?.name"></p>

                <template x-if="activeCheckpoint?.instruction">
                    <div class="card" style="margin-top:14px">
                        <p x-text="activeCheckpoint.instruction"></p>
                    </div>
                </template>

                <template x-if="checklistAnswers.length > 0">
                    <div class="card" style="margin-top:14px">
                        <template x-for="(answer, index) in checklistAnswers" :key="answer.checklist_item_id">
                            <div class="item">
                                <div class="label" x-text="answer.label"></div>
                                <div class="answers">
                                    <button type="button" class="conforming"
                                            :aria-pressed="answer.answer === 'conforming'"
                                            @click="checklistAnswers[index].answer = 'conforming'">Conforme</button>
                                    <button type="button" class="nonconforming"
                                            :aria-pressed="answer.answer === 'nonconforming'"
                                            @click="checklistAnswers[index].answer = 'nonconforming'">Não conf.</button>
                                    <button type="button" class="na"
                                            :aria-pressed="answer.answer === 'not_applicable'"
                                            @click="checklistAnswers[index].answer = 'not_applicable'">N/A</button>
                                </div>
                                <template x-if="answer.answer === 'nonconforming'">
                                    <textarea style="margin-top:10px" x-model="checklistAnswers[index].note"
                                              placeholder="O que você encontrou?"></textarea>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="card" style="margin-top:14px">
                    <label for="checkpoint-photo">
                        Foto <span x-show="photoRequired">(obrigatória: há item não conforme)</span>
                    </label>
                    <input id="checkpoint-photo" type="file" accept="image/*" capture="environment"
                           @change="capturePhoto($event, 'checklistPhoto')">
                    <p class="muted" x-show="checklistPhoto" style="margin-top:8px">Foto anexada.</p>
                </div>
            </div>
        </template>

        {{-- ======================= FILA ======================= --}}
        <template x-if="screen === 'queue'">
            <div>
                <h1>Registros no aparelho</h1>
                <p class="muted">
                    <span x-text="pending"></span> aguardando envio<span x-show="queue.photos > 0">,
                    <span x-text="queue.photos"></span> foto(s)</span><span x-show="rejected > 0">,
                    <span x-text="rejected"></span> recusado(s)</span>.
                </p>

                <template x-if="queue.events.length === 0">
                    <div class="card" style="margin-top:16px">
                        <p>Nada pendente. Tudo que você registrou já está no servidor.</p>
                    </div>
                </template>

                <div style="margin-top:16px">
                    <template x-for="item in queue.events" :key="item.id">
                        <div class="card" style="margin-bottom:10px">
                            <div style="display:flex; gap:10px; align-items:baseline">
                                <strong x-text="queueLabel(item.type)"></strong>
                                <span class="muted" style="margin-left:auto" x-text="formatTime(item.occurred_at)"></span>
                            </div>

                            <p class="muted" style="margin-top:6px">
                                <span x-text="queueStatusLabel(item.status)"></span>
                                <span x-show="item.attempts > 0"> · <span x-text="item.attempts"></span> tentativa(s)</span>
                            </p>

                            <template x-if="item.status === 'rejected'">
                                <div>
                                    <div class="message error" style="margin-top:10px">
                                        <span x-text="item.error || 'O servidor recusou este registro.'"></span>
                                    </div>
                                    <div class="actions row" style="margin-top:10px">
                                        <button type="button" class="ghost small" @click="retry(item.id)">Tentar de novo</button>
                                        <button type="button" class="ghost small" @click="discard(item.id)">Descartar</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <p class="notice" style="margin-top:16px">
                    Registros recusados não sobem sozinhos. Se você não souber o motivo,
                    avise a supervisão antes de descartar.
                </p>
            </div>
        </template>

        {{-- ======================= OCORRÊNCIA ======================= --}}
        <template x-if="screen === 'incident'">
            <div>
                <h1>Registrar ocorrência</h1>

                <div class="card" style="margin-top:14px">
                    <div class="field">
                        <label for="incident-type">Tipo</label>
                        <select id="incident-type" x-model="incident.incident_type_id" @change="applyIncidentDefaults()">
                            <option value="">Selecione…</option>
                            <template x-for="type in (data?.incident_types ?? [])" :key="type.id">
                                <option :value="type.id" x-text="type.label"></option>
                            </template>
                        </select>
                    </div>

                    <div class="field">
                        <label for="incident-severity">Gravidade</label>
                        <select id="incident-severity" x-model="incident.severity">
                            <option value="low">Baixa</option>
                            <option value="medium">Média</option>
                            <option value="high">Alta</option>
                            <option value="critical">Crítica</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="incident-location">Local</label>
                        <input id="incident-location" x-model="incident.location" placeholder="Onde aconteceu">
                    </div>

                    <div class="field">
                        <label for="incident-description">O que aconteceu</label>
                        <textarea id="incident-description" x-model="incident.description"
                                  placeholder="Descreva os fatos, sem opinião"></textarea>
                    </div>

                    <div class="field">
                        <label for="incident-actions">Providências tomadas</label>
                        <textarea id="incident-actions" x-model="incident.actions_taken"
                                  placeholder="O que você fez"></textarea>
                    </div>

                    <div class="field">
                        <label for="incident-photo">Foto</label>
                        <input id="incident-photo" type="file" accept="image/*" capture="environment"
                               @change="capturePhoto($event, 'incidentPhoto')">
                    </div>
                </div>
            </div>
        </template>
    </main>

    {{-- ======================= AÇÕES ======================= --}}
    <footer x-show="screen !== 'login' && screen !== 'loading'">
        <template x-if="screen === 'home' && shift">
            <div class="actions">
                <button type="button" class="ghost" @click="openIncident()">Registrar ocorrência</button>
                <button type="button" class="danger" @click="endShift()">Encerrar turno</button>
            </div>
        </template>

        <template x-if="screen === 'home' && !shift">
            <div class="actions">
                <button type="button" class="ghost" @click="doLogout()">Sair</button>
            </div>
        </template>

        <template x-if="screen === 'patrol'">
            <div class="actions">
                <button type="button" class="primary" @click="openScanner()">Ler QR do ponto</button>
                <div class="actions row">
                    <button type="button" class="ghost" @click="openIncident()">Ocorrência</button>
                    <button type="button" class="ghost" @click="endPatrol()">Encerrar ronda</button>
                </div>
            </div>
        </template>

        <template x-if="screen === 'scan'">
            <div class="actions">
                <button type="button" class="ghost" @click="closeScanner()">Cancelar</button>
            </div>
        </template>

        <template x-if="screen === 'checklist'">
            <div class="actions">
                <button type="button" class="primary"
                        :disabled="busy || (photoRequired && !checklistPhoto)"
                        @click="confirmCheckpoint()"
                        x-text="busy ? 'Registrando…' : 'Confirmar ponto'"></button>
                <button type="button" class="ghost" @click="activeCheckpoint = null; screen = 'patrol'">Voltar</button>
            </div>
        </template>

        <template x-if="screen === 'incident'">
            <div class="actions">
                <button type="button" class="primary" :disabled="busy" @click="submitIncident()"
                        x-text="busy ? 'Registrando…' : 'Registrar ocorrência'"></button>
                <button type="button" class="ghost" @click="screen = patrol ? 'patrol' : 'home'">Cancelar</button>
            </div>
        </template>

        <template x-if="screen === 'queue'">
            <div class="actions">
                <button type="button" class="primary" :disabled="syncing || pending === 0" @click="syncNow()"
                        x-text="syncing ? 'Enviando…' : 'Enviar agora'"></button>
                <button type="button" class="ghost" @click="closeQueue()">Voltar</button>
            </div>
        </template>

        <p class="notice" x-show="pending > 0">
            Os registros ficam salvos no aparelho e sobem sozinhos quando houver rede.
        </p>
    </footer>
</div>

<style>[x-cloak] { display: none !important; }</style>
</body>
</html>
