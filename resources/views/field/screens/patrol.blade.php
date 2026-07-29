{{--
    Ronda.

    O próximo ponto sai da lista e vira um cartão no topo, com o código em
    corpo grande e o nome completo quebrando em até três linhas — antes o nome
    era truncado com reticências, ou seja, o dado que diz aonde ir era o
    primeiro a se perder.

    O trilho abaixo continua listando todos os pontos porque ronda real não é
    sempre sequencial: o vigilante precisa poder ir a um ponto fora de ordem.
--}}
<template x-if="screen === 'patrol'">
    <div class="stack">
        <div>
            <h1 tabindex="-1" x-text="route?.name ?? 'Ronda'"></h1>

            <div class="progress mt-3">
                <div class="progress__track" role="img" :aria-label="progressLabel()">
                    <template x-for="item in routeCheckpoints" :key="'seg-' + item.checkpoint_id">
                        <span class="progress__segment"
                              :data-state="item.done ? 'done' : (item.checkpoint_id === nextCheckpoint?.id ? 'current' : 'todo')"></span>
                    </template>
                </div>
                <span class="progress__count" x-text="(routeCheckpoints.length - remainingCount) + '/' + routeCheckpoints.length"></span>
            </div>
        </div>

        <template x-if="nextCheckpoint">
            <div class="now">
                <span class="now__position" x-text="nextPosition"></span>
                <div class="now__body">
                    <p class="now__eyebrow">Próximo ponto</p>
                    <p class="now__code" x-text="nextCheckpoint.code"></p>
                    <p class="now__name" x-text="nextCheckpoint.name"></p>
                    <template x-if="nextCheckpoint.instruction">
                        <p class="now__instruction" x-text="nextCheckpoint.instruction"></p>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="!nextCheckpoint">
            <div class="now now--done">
                <span class="now__position" aria-hidden="true">✓</span>
                <div class="now__body">
                    <p class="now__eyebrow">Roteiro completo</p>
                    <p class="card__title mt-2">Todos os pontos foram registrados. Encerre a ronda.</p>
                </div>
            </div>
        </template>

        <div>
            <h2 class="section-label">Pontos do roteiro</h2>
            <div class="spine mt-3">
                <template x-for="item in routeCheckpoints" :key="item.checkpoint_id">
                    <button type="button"
                            class="spine__item"
                            :data-state="item.done ? 'done' : (item.skipped ? 'skipped' : 'todo')"
                            :aria-current="item.checkpoint_id === nextCheckpoint?.id ? 'step' : false"
                            @click="openCheckpointMenu(item)">
                        <span class="spine__marker" x-text="item.done ? '✓' : item.position"></span>
                        <span class="spine__body">
                            <span class="spine__code" x-text="item.checkpoint.code"></span>
                            <span class="spine__name" x-text="item.checkpoint.name"></span>
                        </span>
                        <span class="choice__chevron" aria-hidden="true" x-show="!item.done">⋯</span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
