{{--
    Ocorrência.

    Um formulário contínuo, na ordem da pergunta real: o que houve, onde, o que
    você fez. Antes eram três seções tituladas, e os títulos custavam ~96px numa
    tela que já se chama "Registrar ocorrência" - estrutura demais para seis
    campos, sendo que duas das seções tinham um campo só.
--}}
<template x-if="screen === 'incident'">
    <div class="stack">
        <h1 tabindex="-1">Registrar ocorrência</h1>

        <div class="fieldset">
                {{-- Tipo: seleção em duas etapas por sheet, no lugar da roda
                     nativa com dezessete opções achatadas. A linha mostra a
                     escolha e o grupo a que ela pertence. --}}
                <div class="field">
                    <span class="field__label" id="incident-type-label">
                        Tipo <span class="field__required">- obrigatório</span>
                    </span>

                    <button type="button" class="choice" id="incident-type"
                            @click="pickIncidentType()"
                            aria-labelledby="incident-type-label incident-type-value"
                            :aria-invalid="incidentErrors.incident_type_id ? 'true' : 'false'"
                            aria-describedby="err-type">
                        <span class="choice__body">
                            <span class="choice__title" id="incident-type-value"
                                  x-text="incidentType?.name ?? 'Escolher o tipo'"></span>
                            <span class="choice__meta"
                                  x-text="incidentType?.group ?? 'Cinco grupos'"></span>
                        </span>
                        <span class="choice__chevron"><svg class="icon icon--sm" aria-hidden="true"><use href="#i-forward"/></svg></span>
                    </button>

                    <p class="field__error" id="err-type" x-show="incidentErrors.incident_type_id"
                       x-text="incidentErrors.incident_type_id"></p>
                </div>

                <div class="field">
                    <label class="field__label" for="incident-description">
                        Relato <span class="field__required">- obrigatório</span>
                    </label>
                    <textarea class="field__control" id="incident-description" x-model="incident.description"
                              placeholder="Descreva os fatos, sem opinião"
                              :aria-invalid="incidentErrors.description ? 'true' : 'false'"
                              aria-describedby="err-description"></textarea>
                    <p class="field__error" id="err-description" x-show="incidentErrors.description"
                       x-text="incidentErrors.description"></p>
                </div>

                {{-- A foto pertence ao relato, não ao fim da rolagem: quem
                     acabou de ver a cena fotografa antes de escrever o resto. --}}
                <div class="field">
                    <span class="field__label">Foto</span>

                    <template x-if="!incidentPhotoUrl">
                        <div>
                            <input class="field__file" id="incident-photo" type="file" accept="image/*"
                                   capture="environment" @change="capturePhoto($event, 'incidentPhoto')">
                            <label class="btn btn--secondary" for="incident-photo">
                                <svg class="icon" aria-hidden="true"><use href="#i-camera"/></svg> Tirar foto
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

                {{-- Gravidade é o mesmo tipo de escolha que o checklist faz, e
                     agora tem o mesmo desenho. O tipo já preenche um padrão;
                     isto é o ajuste, não a decisão principal. --}}
                <div class="field">
                    <span class="field__label" id="incident-severity-label">Gravidade</span>

                    <div class="scale" role="radiogroup" aria-labelledby="incident-severity-label">
                        <template x-for="level in severities" :key="level.value">
                            <button type="button" role="radio"
                                    class="scale__option"
                                    :class="'scale__option--' + level.value"
                                    :aria-checked="incident.severity === level.value"
                                    :tabindex="incident.severity === level.value ? 0 : -1"
                                    @click="setSeverity(level.value)"
                                    @keydown.arrow-right.prevent="moveSeverity(1)"
                                    @keydown.arrow-left.prevent="moveSeverity(-1)">
                                <span class="scale__dot" aria-hidden="true"></span>
                                <span x-text="level.label"></span>
                            </button>
                        </template>
                    </div>

                    <p class="field__hint">Alta e crítica avisam a supervisão na hora.</p>
                </div>

                <div class="field">
                    <label class="field__label" for="incident-location">Onde</label>
                    <input class="field__control" id="incident-location" x-model="incident.location"
                           placeholder="Bloco B, portão dos fundos…">
                </div>

                <div class="field">
                    <label class="field__label" for="incident-actions">O que você fez</label>
                    <textarea class="field__control" id="incident-actions" x-model="incident.actions_taken"
                              placeholder="Comunicou pelo rádio, isolou a área…"></textarea>
                </div>
            </div>
    </div>
</template>
