<template x-if="screen === 'queue'">
    <div class="stack">
        <div>
            <h1 tabindex="-1">Registros no aparelho</h1>
            <p class="muted" x-text="queueSummary()"></p>
        </div>

        {{-- Vazio aqui é a BOA notícia, e o desenho tem de tranquilizar em vez
             de parecer falta. --}}
        <template x-if="queue.events.length === 0">
            <div class="empty empty--ok">
                <span class="empty__icon">
                    <svg class="icon" aria-hidden="true"><use href="#i-check-circle"/></svg>
                </span>
                <p class="empty__title">Nada pendente</p>
                <p class="empty__text">
                    Tudo o que você registrou já está no servidor. Pode seguir a ronda
                    tranquilo — se a rede cair, os registros ficam aqui até subirem.
                </p>
            </div>
        </template>

        {{-- Um agrupamento com fios internos, e não um cartão por registro. --}}
        <template x-if="queue.events.length > 0">
            <div class="group">
                <template x-for="item in queue.events" :key="item.id">
                    <div class="row row--stacked">
                        <div class="cluster">
                            <strong x-text="queueLabel(item.type)"></strong>
                            <time class="card__meta numeric" x-text="formatTime(item.occurred_at)"></time>
                        </div>

                        <p class="card__meta">
                            <span x-text="queueStatusLabel(item.status)"></span>
                            <span x-show="item.attempts > 0" class="numeric">
                                · <span x-text="item.attempts"></span> tentativa(s)
                            </span>
                        </p>

                        <template x-if="item.status === 'rejected'">
                            <div>
                                <div class="banner banner--error" role="alert">
                                    <div class="banner__body"
                                         x-text="item.error || 'O servidor recusou este registro.'"></div>
                                </div>

                                <div class="actionslot--row mt-3">
                                    <button type="button" class="btn btn--secondary btn--md"
                                            @click="retry(item.id)">Tentar de novo</button>
                                    <button type="button" class="btn btn--critical btn--md"
                                            @click="discard(item.id)">Descartar</button>
                                </div>
                            </div>
                        </template>
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
