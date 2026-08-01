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
                <span class="now__position"><svg class="icon icon--lg" aria-hidden="true"><use href="#i-play"/></svg></span>
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
                            <span class="choice__chevron"><svg class="icon icon--sm" aria-hidden="true"><use href="#i-forward"/></svg></span>
                        </button>
                    </template>

                    <template x-if="(data?.posts ?? []).length === 0">
                        <div class="empty">
                            <span class="empty__icon">
                                <svg class="icon" aria-hidden="true"><use href="#i-home"/></svg>
                            </span>
                            <p class="empty__title">Nenhum posto para esta unidade</p>
                            <p class="empty__text">
                                O cadastro de postos é feito pela supervisão. Se você deveria
                                assumir um posto agora, avise pelo rádio.
                            </p>
                            <button type="button" class="btn btn--secondary btn--md"
                                    @click="refreshData({ force: true })">Atualizar dados</button>
                        </div>
                    </template>
                </div>
            </section>
        </template>

        <template x-if="shift">
            <div class="stack">
                {{-- Lado a lado, não empilhados: dois fatos curtos custavam
                     114px como linhas, e a coluna inteira tinha a mesma
                     largura, sem nenhuma quebra horizontal. --}}
                <div class="facts">
                    <div class="fact">
                        <span class="fact__label">Posto</span>
                        <span class="fact__value" x-text="post?.name ?? '—'"></span>
                    </div>
                    <div class="fact">
                        <span class="fact__label">Em serviço desde</span>
                        <time class="fact__value fact__value--time" x-text="formatTime(shift.started_at)"></time>
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
                                    <span class="choice__chevron"><svg class="icon icon--sm" aria-hidden="true"><use href="#i-forward"/></svg></span>
                                </button>
                            </template>

                            <template x-if="routes.length === 0">
                                <div class="empty">
                                    <span class="empty__icon">
                                        <svg class="icon" aria-hidden="true"><use href="#i-patrol"/></svg>
                                    </span>
                                    <p class="empty__title">Nenhum roteiro nesta unidade</p>
                                    <p class="empty__text">
                                        Você pode seguir registrando ocorrências normalmente.
                                        A ronda depende de um roteiro cadastrado pela supervisão.
                                    </p>
                                    <button type="button" class="btn btn--secondary btn--md"
                                            @click="refreshData({ force: true })">Atualizar dados</button>
                                </div>
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

        {{-- Ações secundárias vivem no conteúdo: o dock guarda só a principal. --}}
        <template x-if="shift">
            <button type="button" class="btn btn--critical" @click="endShift()">
                Encerrar turno
            </button>
        </template>

        {{-- Sempre visível, inclusive em serviço. Quando só aparecia sem turno,
             quem entrasse com a matrícula errada precisava encerrar um turno que
             não era dele para conseguir sair - e aquilo ia para o RDO. --}}
        <button type="button" class="btn btn--ghost" @click="doLogout()">Sair</button>
    </div>
</template>
