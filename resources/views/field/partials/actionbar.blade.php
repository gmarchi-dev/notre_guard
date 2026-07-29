{{--
    Rodapé de ações.

    x-if e não x-show: com x-show o rodapé existia no DOM e era tabulável até na
    tela de login.

    A emergência fica numa faixa própria, separada por borda das ações da tela —
    antes o botão de encerrar turno aparecia logo abaixo dela, mesmo vermelho e
    mesma largura, a 4px de distância.
--}}
<template x-if="screen !== 'boot' && screen !== 'login'">
    <footer class="actionbar">
        <div class="actionbar__emergency">
            <button type="button" class="btn btn--emergency" @click="askPanic()">
                <span aria-hidden="true">⚠</span> Emergência
            </button>
        </div>

        <template x-if="screen === 'home' && shift">
            <div class="actionbar__actions">
                <button type="button" class="btn btn--secondary" @click="openIncident()">
                    Registrar ocorrência
                </button>
                <button type="button" class="btn btn--critical" @click="endShift()">
                    Encerrar turno
                </button>
            </div>
        </template>

        <template x-if="screen === 'home' && !shift">
            <div class="actionbar__actions">
                <button type="button" class="btn btn--ghost" @click="doLogout()">Sair</button>
            </div>
        </template>

        <template x-if="screen === 'patrol'">
            <div class="actionbar__actions">
                {{-- A ação nomeia o alvo: sozinha, essa mudança elimina boa
                     parte da necessidade de consultar a lista. --}}
                <button type="button" class="btn btn--primary" @click="openScanner()">
                    <span x-text="nextCheckpoint ? 'Ler QR' : 'Ler QR do ponto'"></span>
                    <span class="btn__hint" x-show="nextCheckpoint" x-text="nextCheckpoint?.code"></span>
                </button>
                <div class="actionbar__actions actionbar__actions--row">
                    <button type="button" class="btn btn--secondary" @click="openIncident()">Ocorrência</button>
                    <button type="button" class="btn btn--secondary" @click="endPatrol()">Encerrar ronda</button>
                </div>
            </div>
        </template>

        <template x-if="screen === 'scan'">
            <div class="actionbar__actions">
                <button type="button" class="btn btn--secondary" @click="closeScanner()">Voltar</button>
            </div>
        </template>

        <template x-if="screen === 'checklist'">
            <div class="actionbar__actions">
                <button type="button" class="btn btn--primary"
                        :disabled="photoRequired && !checklistPhoto"
                        :aria-disabled="busy"
                        @click="confirmCheckpoint()"
                        x-text="busy ? 'Registrando…' : 'Confirmar ponto'"></button>
                <button type="button" class="btn btn--secondary" @click="leaveChecklist()">Voltar</button>
            </div>
        </template>

        <template x-if="screen === 'incident'">
            <div class="actionbar__actions">
                <button type="button" class="btn btn--primary" :aria-disabled="busy"
                        @click="submitIncident()"
                        x-text="busy ? 'Registrando…' : 'Registrar ocorrência'"></button>
                <button type="button" class="btn btn--secondary" @click="cancelIncident()">Cancelar</button>
            </div>
        </template>

        <template x-if="screen === 'queue'">
            <div class="actionbar__actions">
                <button type="button" class="btn btn--primary" :aria-disabled="syncing || pending === 0"
                        @click="syncNow()"
                        x-text="syncing ? 'Enviando…' : 'Enviar agora'"></button>
                <button type="button" class="btn btn--secondary" @click="goBack()">Voltar</button>
            </div>
        </template>
    </footer>
</template>
