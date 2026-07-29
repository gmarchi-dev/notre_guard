{{--
    Ocorrência.

    Era um único cartão com seis campos e dois textarea, sem indicar o que é
    obrigatório — a validação só aparecia depois de enviar. Agora são três
    seções que seguem a ordem da pergunta real: o que houve, onde, e o que você
    fez.
--}}
<template x-if="screen === 'incident'">
    <div class="stack">
        <h1 tabindex="-1">Registrar ocorrência</h1>

        <div class="card">
            <h2 class="section-label">O que aconteceu</h2>

            <div class="field mt-3">
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

        <div class="card">
            <h2 class="section-label">Onde</h2>
            <div class="field mt-3">
                <label class="field__label" for="incident-location">Local</label>
                <input class="field__control" id="incident-location" x-model="incident.location"
                       placeholder="Bloco B, portão dos fundos…">
            </div>
        </div>

        <div class="card">
            <h2 class="section-label">O que você fez</h2>

            <div class="field mt-3">
                <label class="field__label" for="incident-actions">Providências tomadas</label>
                <textarea class="field__control" id="incident-actions" x-model="incident.actions_taken"
                          placeholder="Comunicou pelo rádio, isolou a área…"></textarea>
            </div>

            <div class="field">
                <p class="field__label">Foto</p>

                <template x-if="!incidentPhotoUrl">
                    <div>
                        <input class="field__file" id="incident-photo" type="file" accept="image/*"
                               capture="environment" @change="capturePhoto($event, 'incidentPhoto')">
                        <label class="btn btn--secondary" for="incident-photo">Tirar foto</label>
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
        </div>
    </div>
</template>
