{{--
    Checklist do ponto.

    O grupo de respostas virou um radiogroup de verdade: antes eram três botões
    soltos com aria-pressed, sem ligação com o rótulo do item - o leitor de tela
    anunciava "Conforme, botão" sem dizer de qual item, e nada impedia
    logicamente que os três estivessem marcados ao mesmo tempo.

    O selecionado se distingue por preenchimento E ícone, nunca só por cor.
--}}
<template x-if="screen === 'checklist'">
    <div class="stack">
        <div>
            <h1 tabindex="-1" x-text="activeCheckpoint?.code"></h1>
            <p class="muted mt-2" x-text="activeCheckpoint?.name"></p>
        </div>

        {{--
            Aviso de distância. Aparece assim que o GPS responde, antes de o
            checklist ser preenchido, porque é aí que ainda dá para andar até o
            ponto. O registro nunca é bloqueado - só informado.
        --}}
        <template x-if="checkpointTooFar">
            <div class="banner banner--warn" role="status">
                <div class="banner__body">
                    <span x-text="'O aparelho indica ' + checkpointDistanceLabel + ' deste ponto. Aproxime-se antes de registrar, ou a leitura constará como desvio.'"></span>
                </div>
            </div>
        </template>

        <template x-if="activeCheckpoint?.instruction">
            <div class="card card--accent">
                <p x-text="activeCheckpoint.instruction"></p>
            </div>
        </template>

        <template x-if="checklistAnswers.length > 0">
            <div class="group">
                <template x-for="(answer, index) in checklistAnswers" :key="answer.checklist_item_id">
                    <div class="checklist-item">
                        <div class="checklist-item__label" :id="'ck-' + answer.checklist_item_id"
                             x-text="answer.label"></div>

                        <div class="segmented" role="radiogroup" :aria-labelledby="'ck-' + answer.checklist_item_id">
                            <button type="button" role="radio"
                                    class="segmented__option segmented__option--conforming"
                                    :aria-checked="answer.answer === 'conforming'"
                                    :tabindex="answer.answer === 'conforming' ? 0 : -1"
                                    @click="checklistAnswers[index].answer = 'conforming'"
                                    @keydown.arrow-right.prevent="checklistAnswers[index].answer = 'nonconforming'"
                                    @keydown.arrow-left.prevent="checklistAnswers[index].answer = 'not_applicable'">
                                <svg class="icon icon--sm" aria-hidden="true"><use href="#i-check"/></svg>
                                <span>Conforme</span>
                            </button>

                            <button type="button" role="radio"
                                    class="segmented__option segmented__option--nonconforming"
                                    :aria-checked="answer.answer === 'nonconforming'"
                                    :tabindex="answer.answer === 'nonconforming' ? 0 : -1"
                                    @click="checklistAnswers[index].answer = 'nonconforming'"
                                    @keydown.arrow-right.prevent="checklistAnswers[index].answer = 'not_applicable'"
                                    @keydown.arrow-left.prevent="checklistAnswers[index].answer = 'conforming'">
                                <svg class="icon icon--sm" aria-hidden="true"><use href="#i-alert"/></svg>
                                <span>Não conforme</span>
                            </button>

                            <button type="button" role="radio"
                                    class="segmented__option segmented__option--na"
                                    :aria-checked="answer.answer === 'not_applicable'"
                                    :tabindex="answer.answer === 'not_applicable' ? 0 : -1"
                                    @click="checklistAnswers[index].answer = 'not_applicable'"
                                    @keydown.arrow-right.prevent="checklistAnswers[index].answer = 'conforming'"
                                    @keydown.arrow-left.prevent="checklistAnswers[index].answer = 'nonconforming'">
                                <svg class="icon icon--sm" aria-hidden="true"><use href="#i-minus"/></svg>
                                <span>Não se aplica</span>
                            </button>
                        </div>

                        <template x-if="answer.answer === 'nonconforming'">
                            <div class="field mt-3">
                                <label class="field__label" :for="'note-' + answer.checklist_item_id">
                                    O que você encontrou?
                                </label>
                                <textarea class="field__control" :id="'note-' + answer.checklist_item_id"
                                          x-model="checklistAnswers[index].note"></textarea>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>

        <section class="section">
            <div class="section__head">
                <h2 class="section__title">Foto</h2>
                <span class="section__aside" x-show="photoRequired">obrigatória: há item não conforme</span>
            </div>

            <template x-if="!checklistPhotoUrl">
                <div>
                    <input class="field__file" id="checkpoint-photo" type="file" accept="image/*"
                           capture="environment" @change="capturePhoto($event, 'checklistPhoto')">
                    <label class="btn btn--secondary" for="checkpoint-photo">
                        <svg class="icon" aria-hidden="true"><use href="#i-camera"/></svg> Tirar foto
                    </label>
                </div>
            </template>

            <template x-if="checklistPhotoUrl">
                <div class="photo">
                    <img class="photo__thumb" :src="checklistPhotoUrl" alt="Foto anexada ao ponto">
                    <div class="photo__body">
                        <p class="card__meta">Foto anexada.</p>
                        <button type="button" class="btn btn--secondary btn--md mt-2"
                                @click="clearPhoto('checklistPhoto')">Remover</button>
                    </div>
                </div>
            </template>
        </section>
    </div>
</template>
