{{--
    Ocorrência.

    Três seções na ordem da pergunta real — o que houve, onde, o que você fez —
    mas sem cartão em volta de cada uma: os campos já são superfície, e a
    moldura extra só apertava a tela.
--}}
<template x-if="screen === 'incident'">
    <div class="stack">
        <h1 tabindex="-1">Registrar ocorrência</h1>

        <section class="section">
            <div class="section__head">
                <h2 class="section__title">O que aconteceu</h2>
            </div>

            <div class="fieldset">
                <div class="field">
                    <label class="field__label" for="incident-type">
                        Tipo <span class="field__required">— obrigatório</span>
                    </label>
                    <select class="field__control" id="incident-type" x-model="incident.incident_type_id"
                            @change="applyIncidentDefaults()"
                            :aria-invalid="incidentErrors.incident_type_id ? 'true' : 'false'"
                            aria-describedby="err-type">
                        <option value="">Selecione…</option>
                        <template x-for="type in (data?.incident_types ?? [])" :key="type.id">
                            <option :value="type.id" x-text="type.label"></option>
                        </template>
                    </select>
                    <p class="field__error" id="err-type" x-show="incidentErrors.incident_type_id"
                       x-text="incidentErrors.incident_type_id"></p>
                </div>

                <div class="field">
                    <label class="field__label" for="incident-description">
                        Relato <span class="field__required">— obrigatório</span>
                    </label>
                    <textarea class="field__control" id="incident-description" x-model="incident.description"
                              placeholder="Descreva os fatos, sem opinião"
                              :aria-invalid="incidentErrors.description ? 'true' : 'false'"
                              aria-describedby="err-description"></textarea>
                    <p class="field__error" id="err-description" x-show="incidentErrors.description"
                       x-text="incidentErrors.description"></p>
                </div>

                <div class="field">
                    <label class="field__label" for="incident-severity">Gravidade</label>
                    <select class="field__control" id="incident-severity" x-model="incident.severity">
                        <option value="low">Baixa</option>
                        <option value="medium">Média</option>
                        <option value="high">Alta</option>
                        <option value="critical">Crítica</option>
                    </select>
                    <p class="field__hint">Alta e crítica avisam a supervisão na hora.</p>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="section__head">
                <h2 class="section__title">Onde</h2>
            </div>

            <input class="field__control" id="incident-location" x-model="incident.location"
                   aria-label="Local da ocorrência"
                   placeholder="Bloco B, portão dos fundos…">
        </section>

        <section class="section">
            <div class="section__head">
                <h2 class="section__title">O que você fez</h2>
            </div>

            <div class="fieldset">
                <textarea class="field__control" id="incident-actions" x-model="incident.actions_taken"
                          aria-label="Providências tomadas"
                          placeholder="Comunicou pelo rádio, isolou a área…"></textarea>

                <template x-if="!incidentPhotoUrl">
                    <div>
                        <input class="field__file" id="incident-photo" type="file" accept="image/*"
                               capture="environment" @change="capturePhoto($event, 'incidentPhoto')">
                        <label class="btn btn--secondary" for="incident-photo">
                            <span aria-hidden="true">◎</span> Tirar foto
                        </label>
                    </div>
                </template>

                <template x-if="incidentPhotoUrl">
                    <div class="photo">
                        <img class="photo__thumb" :src="incidentPhotoUrl" alt="Foto anexada à ocorrência">
                        <div class="photo__body">
                            <p class="card__meta">Foto anexada.</p>
                            <button type="button" class="btn btn--secondary btn--md mt-2"
                                    @click="clearPhoto('incidentPhoto')">Remover</button>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>
</template>
