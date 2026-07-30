{{--
    Conjunto de ícones.

    Substitui os glifos de texto (⌂ ◎ ✎ ↑ ⚠ ‹ ›). Aqueles dependiam da fonte do
    sistema: mudavam de desenho, de peso e de largura entre Android e iOS, e o
    pior - não tinham caixa previsível. O "⚠" do botão de emergência media
    19x22 e sentava alto dentro do círculo, que era a origem do desalinhamento.

    Sprite único com <symbol>, referenciado por <use>. O SVG é inline no HTML
    (não um arquivo externo) porque a casca offline já é uma requisição só, e
    <use href> entre documentos não funciona sem rede.

    Traço de 2px em grade de 24, extremidades arredondadas: um só peso visual
    para o app inteiro.
--}}
<svg class="icons" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <g id="i-base" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round"></g>
    </defs>

    {{-- navegação --}}
    <symbol id="i-home" viewBox="0 0 24 24">
        <path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-4v-6H8v6H4a1 1 0 0 1-1-1z"/>
    </symbol>

    <symbol id="i-patrol" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="8"/>
        <circle cx="12" cy="12" r="3"/>
        <path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
    </symbol>

    <symbol id="i-incident" viewBox="0 0 24 24">
        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9"/>
        <path d="M14 3v5a1 1 0 0 0 1 1h5"/>
        <path d="M9 13h6M9 17h4"/>
    </symbol>

    <symbol id="i-queue" viewBox="0 0 24 24">
        <path d="M12 19V5"/>
        <path d="m6 11 6-6 6 6"/>
        <path d="M4 21h16"/>
    </symbol>

    {{-- emergência --}}
    <symbol id="i-sos" viewBox="0 0 24 24">
        <path d="M12 3.5 2.6 19a1.3 1.3 0 0 0 1.1 2h16.6a1.3 1.3 0 0 0 1.1-2z"/>
        <path d="M12 9.5v4.5"/>
        <path d="M12 17.6h.01"/>
    </symbol>

    {{-- navegação secundária --}}
    <symbol id="i-back" viewBox="0 0 24 24">
        <path d="m14.5 5-7 7 7 7"/>
    </symbol>

    <symbol id="i-forward" viewBox="0 0 24 24">
        <path d="m9.5 5 7 7-7 7"/>
    </symbol>

    <symbol id="i-more" viewBox="0 0 24 24">
        <circle cx="5" cy="12" r="1.4"/>
        <circle cx="12" cy="12" r="1.4"/>
        <circle cx="19" cy="12" r="1.4"/>
    </symbol>

    <symbol id="i-check" viewBox="0 0 24 24">
        <path d="m4.5 12.5 5 5 10-11"/>
    </symbol>

    <symbol id="i-close" viewBox="0 0 24 24">
        <path d="M6 6 18 18M18 6 6 18"/>
    </symbol>

    <symbol id="i-alert" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 7.5v5.5"/>
        <path d="M12 16.5h.01"/>
    </symbol>

    <symbol id="i-minus" viewBox="0 0 24 24">
        <path d="M6 12h12"/>
    </symbol>

    <symbol id="i-refresh" viewBox="0 0 24 24">
        <path d="M20 11a8 8 0 1 0-.7 4.3"/>
        <path d="M20 5v6h-6"/>
    </symbol>

    <symbol id="i-play" viewBox="0 0 24 24">
        <path d="M8 5.5v13l11-6.5z"/>
    </symbol>

    {{-- ações --}}
    <symbol id="i-qr" viewBox="0 0 24 24">
        <rect x="3" y="3" width="7" height="7" rx="1.5"/>
        <rect x="14" y="3" width="7" height="7" rx="1.5"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5"/>
        <path d="M14 14h3v3M20 14v.01M14 20h.01M17 20h4"/>
    </symbol>

    <symbol id="i-camera" viewBox="0 0 24 24">
        <path d="M4 8h3l1.5-2.5h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
        <circle cx="12" cy="13.5" r="3.5"/>
    </symbol>

    <symbol id="i-pin" viewBox="0 0 24 24">
        <path d="M12 21s7-6.3 7-11a7 7 0 1 0-14 0c0 4.7 7 11 7 11z"/>
        <circle cx="12" cy="10" r="2.5"/>
    </symbol>

    {{-- aparência --}}
    <symbol id="i-theme-system" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="8.5"/>
        <path d="M12 3.5a8.5 8.5 0 0 1 0 17z" fill="currentColor" stroke="none"/>
    </symbol>

    <symbol id="i-theme-light" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="4.5"/>
        <path d="M12 2v2.5M12 19.5V22M4.2 4.2l1.8 1.8M18 18l1.8 1.8M2 12h2.5M19.5 12H22M4.2 19.8 6 18M18 6l1.8-1.8"/>
    </symbol>

    <symbol id="i-theme-dark" viewBox="0 0 24 24">
        <path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"/>
    </symbol>

    <symbol id="i-theme-night" viewBox="0 0 24 24">
        <path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z" fill="currentColor" stroke="none"/>
    </symbol>
</svg>
