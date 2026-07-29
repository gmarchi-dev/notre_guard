{{--
    Bottom sheet — substitui confirm() e prompt() nativos.

    Sobe da base porque é onde o polegar está. Foco vai ao título ao abrir e
    volta ao gatilho ao fechar; Tab fica preso dentro; Escape e o botão voltar
    do Android cancelam.
--}}
<template x-if="sheet">
    <div>
        <div class="scrim" @click="sheet.dismissible !== false && resolveSheet(false)"></div>

        <div class="sheet" role="dialog" aria-modal="true" aria-labelledby="sheet-title"
             x-ref="sheet" @keydown.escape.window="sheet.dismissible !== false && resolveSheet(false)"
             @keydown.tab="trapFocus($event, $refs.sheet)">
            <div class="sheet__handle" aria-hidden="true"></div>

            <h2 class="sheet__title" id="sheet-title" tabindex="-1" x-ref="sheetTitle"
                x-text="sheet.title"></h2>

            <template x-if="sheet.text">
                <p class="sheet__text" x-text="sheet.text"></p>
            </template>

            {{-- Variante com campo: a justificativa de ponto pulado é registro
                 de auditoria, e antes era capturada num prompt() de uma linha
                 que nem dizia qual ponto estava sendo pulado. --}}
            <template x-if="sheet.kind === 'text'">
                <div class="sheet__body">
                    <div class="field">
                        <label class="field__label" for="sheet-input" x-text="sheet.label"></label>
                        <textarea class="field__control" id="sheet-input" x-model="sheet.value"
                                  :placeholder="sheet.placeholder ?? ''"></textarea>
                    </div>

                    <template x-if="sheet.reasons?.length">
                        <div class="reasons">
                            <template x-for="reason in sheet.reasons" :key="reason">
                                <button type="button" class="chip chip--reason"
                                        :aria-pressed="sheet.value === reason"
                                        @click="sheet.value = reason"
                                        x-text="reason"></button>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <div class="sheet__actions">
                {{-- Escolha entre vários caminhos (menu do ponto, conflito de
                     ronda). Sem opções, cai no par confirmar/cancelar. --}}
                <template x-if="sheet.options?.length">
                    <div class="sheet__actions">
                        <template x-for="option in sheet.options" :key="option.value">
                            <button type="button"
                                    :class="'btn btn--' + (option.variant ?? 'secondary')"
                                    @click="resolveSheet(option.value)"
                                    x-text="option.label"></button>
                        </template>
                    </div>
                </template>

                <template x-if="!sheet.options?.length">
                    <button type="button"
                            :class="'btn ' + (sheet.destructive ? 'btn--critical' : 'btn--primary')"
                            :disabled="sheet.kind === 'text' && !sheet.value?.trim()"
                            @click="resolveSheet(true)"
                            x-text="sheet.confirmLabel ?? 'Confirmar'"></button>
                </template>

                <button type="button" class="btn btn--secondary" @click="resolveSheet(false)"
                        x-text="sheet.cancelLabel ?? 'Cancelar'"></button>
            </div>
        </div>
    </div>
</template>
