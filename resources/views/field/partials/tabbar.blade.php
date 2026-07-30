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
            {{-- O indicador é uma pastilha ATRÁS do ícone, não um traço solto
                 acima: o traço vivia fora de fluxo, com margem negativa, e não
                 tinha relação nenhuma com o alvo de toque que ele marcava. --}}
            <span class="tabbar__icon">
                <svg class="icon" aria-hidden="true"><use :href="'#i-' + tab.id"/></svg>

                <template x-if="tab.id === 'queue' && (pending > 0 || rejected > 0)">
                    <span class="tabbar__badge"
                          :class="rejected > 0 && 'tabbar__badge--alert'"
                          x-text="rejected > 0 ? rejected : pending"></span>
                </template>
            </span>

            <span class="tabbar__label" x-text="tab.label"></span>
        </button>
    </template>
</nav>
