<template x-if="screen === 'queue'">
    <div class="stack">
        <div>
            <h1 tabindex="-1">Registros no aparelho</h1>
            <p class="muted mt-2" x-text="queueSummary()"></p>
        </div>

        <template x-if="queue.events.length === 0">
            <div class="card">
                <p>Nada pendente. Tudo que você registrou já está no servidor.</p>
            </div>
        </template>

        <template x-for="item in queue.events" :key="item.id">
            <div class="card">
                <div class="cluster">
                    <strong x-text="queueLabel(item.type)"></strong>
                    <time class="card__meta flex-1 text-center" x-text="formatTime(item.occurred_at)"></time>
                </div>

                <p class="card__meta mt-2">
                    <span x-text="queueStatusLabel(item.status)"></span>
                    <span x-show="item.attempts > 0" class="numeric">
                        · <span x-text="item.attempts"></span> tentativa(s)
                    </span>
                </p>

                <template x-if="item.status === 'rejected'">
                    <div>
                        <div class="banner banner--error mt-3" role="alert">
                            <div class="banner__body"
                                 x-text="item.error || 'O servidor recusou este registro.'"></div>
                        </div>

                        <div class="actionbar__actions actionbar__actions--row mt-3">
                            <button type="button" class="btn btn--secondary" @click="retry(item.id)">
                                Tentar de novo
                            </button>
                            <button type="button" class="btn btn--critical" @click="discard(item.id)">
                                Descartar
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <p class="notice">
            Registros recusados não sobem sozinhos. Se você não souber o motivo,
            avise a supervisão antes de descartar.
        </p>
    </div>
</template>
