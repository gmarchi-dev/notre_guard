{{--
    Tela inicial.

    As ações primárias saíram de dentro do conteúdo (onde eram botões de 44px
    encostados à direita) e viraram linhas de escolha de 64px, com a linha
    inteira tocável. A ação de encerrar e a de ocorrência ficam no rodapé, ao
    alcance do polegar.
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
            <div>
                <h2 class="section-label">Assumir posto</h2>
                <div class="mt-3">
                    <template x-for="post in (data?.posts ?? [])" :key="post.id">
                        <button type="button" class="choice" @click="startShift(post.id)" :aria-disabled="busy">
                            <span class="choice__body">
                                <span class="choice__title" x-text="post.name"></span>
                                <span class="choice__meta" x-text="postKindLabel(post.kind)"></span>
                            </span>
                            <span class="choice__chevron" aria-hidden="true">›</span>
                        </button>
                    </template>
                    <template x-if="(data?.posts ?? []).length === 0">
                        <p class="muted">Nenhum posto disponível. Fale com a supervisão.</p>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="shift">
            <div class="stack">
                <div class="card">
                    <h2 class="section-label">Turno em andamento</h2>
                    <p class="card__title mt-2" x-text="post?.name ?? 'Posto'"></p>
                    <p class="card__meta">Desde <time x-text="formatTime(shift.started_at)"></time></p>
                </div>

                <template x-if="!patrol">
                    <div>
                        <h2 class="section-label">Iniciar ronda</h2>
                        <div class="mt-3">
                            <template x-for="route in routes" :key="route.id">
                                <button type="button" class="choice" @click="startPatrol(route.id)" :aria-disabled="busy">
                                    <span class="choice__body">
                                        <span class="choice__title" x-text="route.name"></span>
                                        <span class="choice__meta numeric"
                                              x-text="route.checkpoints.length + ' pontos · ' + route.expected_duration_min + ' min'"></span>
                                    </span>
                                    <span class="choice__chevron" aria-hidden="true">›</span>
                                </button>
                            </template>
                            <template x-if="routes.length === 0">
                                <p class="muted">Nenhum roteiro cadastrado para esta unidade.</p>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="card">
                    <label class="field__label" for="handover">Passagem de serviço</label>
                    <textarea class="field__control" id="handover" x-model="handoverNotes"
                              @input.debounce.600ms="persistHandover()"
                              placeholder="Pendências para o próximo turno"></textarea>
                    <p class="field__hint">Salvo no aparelho enquanto você digita. Entra no encerramento do turno.</p>
                </div>
            </div>
        </template>

        {{-- A fila deixa de ser alcançável só pela pastilha da barra de topo. --}}
        <button type="button" class="choice choice--quiet" @click="openQueue()">
            <span class="choice__body">
                <span class="choice__title">Registros no aparelho</span>
                <span class="choice__meta" x-text="syncChip().label"></span>
            </span>
            <span class="choice__chevron" aria-hidden="true">›</span>
        </button>
    </div>
</template>
