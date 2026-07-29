<template x-if="screen === 'login'">
    <div class="stack">
        <div>
            <h1 tabindex="-1">Entrar</h1>
            <p class="muted mt-2">Use sua matrícula e senha.</p>
        </div>

        <form class="card" @submit.prevent="doLogin()">
            <div class="field">
                <label class="field__label" for="registration">Matrícula</label>
                <input class="field__control" id="registration" x-model="credentials.registration"
                       autocomplete="username" inputmode="text" autocapitalize="characters" required>
            </div>

            <div class="field">
                <label class="field__label" for="password">Senha</label>
                <input class="field__control" id="password" type="password" x-model="credentials.password"
                       autocomplete="current-password" required>
            </div>

            <div class="field">
                <button type="submit" class="btn btn--primary" :aria-disabled="busy"
                        x-text="busy ? 'Entrando…' : 'Entrar'"></button>
            </div>
        </form>

        <p class="notice">
            Este aplicativo registra presença operacional e rondas.
            Não é registro de ponto e não substitui a marcação de jornada.
            A localização é coletada apenas durante a ronda.
        </p>
    </div>
</template>
