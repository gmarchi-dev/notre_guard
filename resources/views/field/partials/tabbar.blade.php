{{--
    Barra de abas: navegação.

    Some nos subfluxos (leitura de QR e checklist), onde o retorno é a seta do
    cabeçalho. Sair de um checklist pela metade tem de ser deliberado.
--}}
<nav class="tabbar" aria-label="Navegação principal">
    <template x-for="tab in tabs" :key="tab.id">
        <button type="button"
                class="tabbar__item"
                :aria-current="screen === tab.id ? 'page' : false"
                :aria-disabled="! tabEnabled(tab) ? 'true' : 'false'"
                @click="openTab(tab)">
            <span class="tabbar__glyph" aria-hidden="true" x-text="tab.glyph"></span>
            <span class="tabbar__label" x-text="tab.label"></span>

            <template x-if="tab.id === 'queue' && (pending > 0 || rejected > 0)">
                <span class="tabbar__badge"
                      :class="rejected > 0 && 'tabbar__badge--alert'"
                      x-text="rejected > 0 ? rejected : pending"></span>
            </template>
        </button>
    </template>
</nav>
