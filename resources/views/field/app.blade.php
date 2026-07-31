<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    {{-- Sem maximum-scale: bloquear zoom viola a WCAG 1.4.4, e este aplicativo
         é operado por adultos com pouca luz. Todo campo tem fonte >= 16px, que
         é o que impede o iOS de dar zoom sozinho ao focar. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0b1120" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/notre-guard-192.png">
    <title>Notre Guard - Campo</title>

    {{-- Bloqueante e antes do @vite de propósito: o Alpine carrega como módulo
         diferido, tarde demais. Um flash de tela branca na madrugada custa a
         visão noturna do vigilante por vários segundos. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('ng.theme');
                if (t === 'light' || t === 'dark' || t === 'night') {
                    document.documentElement.dataset.theme = t;
                }
            } catch (e) {
                /* Armazenamento bloqueado: segue a preferência do sistema. */
            }
        })();
    </script>

    @vite(['resources/css/field.css', 'resources/js/field/app.js'])
</head>
<body>
@include('field.partials.icons')

<div class="app" x-data="fieldApp" x-cloak>

    @include('field.partials.appbar')

    <main class="view" id="view" x-ref="view" tabindex="-1">
        @include('field.partials.banners')

        @include('field.screens.boot')
        @include('field.screens.login')
        @include('field.screens.home')
        @include('field.screens.patrol')
        @include('field.screens.scan')
        @include('field.screens.checklist')
        @include('field.screens.incident')
        @include('field.screens.queue')
    </main>

    @include('field.partials.actionbar')

    {{-- Sem dock (boot e login) a pilha se ancora na janela. Os dois pontos de
         montagem são mutuamente exclusivos: nunca há duas regiões aria-live. --}}
    <template x-if="!showDock()">
        @include('field.partials.toasts', ['floating' => true])
    </template>

    @include('field.partials.sheet')
    @include('field.partials.panic')
</div>
</body>
</html>
