{{--
    Tela inicial.

    Antes eram quatro contêineres com borda empilhados, mais uma caixa por item
    de lista. Agora cada seção é um agrupamento único com fios internos: uma
    superfície contínua no lugar de vários blocos.
--}}
<template x-if="screen === 'home'">
    <div class="stack">
        <div>
            <h1 tabindex="-1" x-text="'Olá, ' + (guardName || 'vigilante')"></h1>
            <p class="muted" x-text="data?.unit?.name ?? 'Carregando unidade…'"></p>
        </div>

        {{-- Ronda em andamento: antes ficava invisível ao reabrir o aplicativo,
             e iniciar outra sobrescrevia a anterior sem encerrá-la. --}}
        <template x-if="patrol">
            <button type="button" class="now" @click="resumePatrol()">
                <span class="now__position" aria-hidden="true">▸</span>
                <span class="now__body">
                    <span class="now__eyebrow">Ronda em andamento</span>
                    <span class="card__title" x-text="route?.name ?? 'Roteiro'"></span>
                    <span class="card__meta numeric"
                          x-text="(routeCheckpoints.length - remainingCount) + ' de ' + routeCheckpoints.length + ' pontos · toque para retomar'"></span>
                </span>
            </button>
        </template>

        <template x-if="!shift">
            <section class="section">
                <div class="section__head">
                    <h2 class="section__title">Assumir posto</h2>
                </div>

                <div class="group">
                    <template x-for="post in (data?.posts ?? [])" :key="post.id">
                        <button type="button" class="choice" @click="startShift(post.id)"
                                :aria-disabled="busy">
                            <span class="choice__body">
                                <span class="choice__title" x-text="post.name"></span>
                                <span class="choice__meta" x-text="postKindLabel(post.kind)"></span>
                            </span>
                            <span class="choice__chevron" aria-hidden="true">›</span>
                        </button>
                    </template>

                    <template x-if="(data?.posts ?? []).length === 0">
                        <p class="row muted">Nenhum posto disponível. Fale com a supervisão.</p>
                    </template>
                </div>
            </section>
        </template>

        <template x-if="shift">
            <div class="stack">
                {{-- Duas informações não precisam de um cartão inteiro. --}}
                <div class="group">
                    <div class="row">
                        <span class="row__label">Posto</span>
                        <span class="row__value" x-text="post?.name ?? '—'"></span>
                    </div>
                    <div class="row">
                        <span class="row__label">Em serviço desde</span>
                        <time class="row__value numeric" x-text="formatTime(shift.started_at)"></time>
                    </div>
                </div>

                <template x-if="!patrol">
                    <section class="section">
                        <div class="section__head">
                            <h2 class="section__title">Iniciar ronda</h2>
                        </div>

                        <div class="group">
                            <template x-for="route in routes" :key="route.id">
                                <button type="button" class="choice" @click="startPatrol(route.id)"
                                        :aria-disabled="busy">
                                    <span class="choice__body">
                                        <span class="choice__title" x-text="route.name"></span>
                                        <span class="choice__meta numeric"
                                              x-text="route.checkpoints.length + ' pontos · ' + route.expected_duration_min + ' min'"></span>
                                    </span>
                                    <span class="choice__chevron" aria-hidden="true">›</span>
                                </button>
                            </template>

                            <template x-if="routes.length === 0">
                                <p class="row muted">Nenhum roteiro cadastrado para esta unidade.</p>
                            </template>
                        </div>
                    </section>
                </template>

                <section class="section">
                    <div class="section__head">
                        <h2 class="section__title">Passagem de serviço</h2>
                        <span class="section__aside">salva no aparelho</span>
                    </div>

                    <textarea class="field__control" id="handover" x-model="handoverNotes"
                              @input.debounce.600ms="persistHandover()"
                              aria-label="Passagem de serviço"
                              placeholder="Pendências para o próximo turno"></textarea>
                </section>
            </div>
        </template>

        {{-- Aparência. O modo noturno não é "escuro mais escuro": ele apaga os
             preenchimentos claros, que são o que de fato ilumina o rosto de
             quem ronda às duas da manhã. --}}
        <section class="section">
            <div class="section__head">
                <h2 class="section__title" id="theme-label">Aparência</h2>
            </div>

            <div class="segmented" role="radiogroup" aria-labelledby="theme-label">
                <template x-for="option in themes" :key="option.value">
                    <button type="button" role="radio" class="segmented__option"
                            :class="'segmented__option--theme-' + option.value"
                            :aria-checked="theme === option.value"
                            :tabindex="theme === option.value ? 0 : -1"
                            @click="setTheme(option.value)"
                            @keydown.arrow-right.prevent="moveTheme(1)"
                            @keydown.arrow-left.prevent="moveTheme(-1)">
                        <span class="segmented__mark" aria-hidden="true" x-text="option.mark"></span>
                        <span x-text="option.label"></span>
                    </button>
                </template>
            </div>

            <p class="field__hint">
                No modo noturno o botão de emergência continua vermelho cheio — é a
                única coisa que não escurece.
            </p>
        </section>

        {{-- Ações secundárias vivem no conteúdo: o dock guarda só a principal. --}}
        <template x-if="shift">
            <button type="button" class="btn btn--critical" @click="endShift()">
                Encerrar turno
            </button>
        </template>

        <template x-if="!shift">
            <button type="button" class="btn btn--ghost" @click="doLogout()">Sair</button>
        </template>
    </div>
</template>
