<template x-if="screen === 'scan'">
    <div class="stack">
        <div>
            <h1 tabindex="-1">Ler ponto</h1>
            <p class="muted mt-2">Aponte a câmera para o QR Code do ponto de controle.</p>
        </div>

        <div>
            <div class="scanner">
                <video class="scanner__video" x-ref="video" muted playsinline
                       aria-label="Visor da câmera para leitura de QR Code"></video>
                <div class="scanner__reticle" aria-hidden="true"></div>
            </div>

            {{-- Quem não enxerga a imagem precisa saber que a câmera está viva. --}}
            <p class="scanner__status" role="status" x-text="scanStatus"></p>
        </div>

        <template x-if="scanError">
            <div class="banner banner--error" role="alert">
                <div class="banner__body" x-text="scanError"></div>
            </div>
        </template>
    </div>
</template>
