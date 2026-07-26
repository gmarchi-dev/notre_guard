{{--
    Botão de login Google, injetado na tela do Filament por render hook.
    Só é renderizado quando GOOGLE_AUTH_ENABLED=true.
--}}
<div class="fi-google-auth">
    <div class="fi-google-auth-divider">
        <span>ou</span>
    </div>

    <a href="{{ route('auth.google.redirect') }}" class="fi-google-auth-btn">
        <svg viewBox="0 0 18 18" aria-hidden="true" width="18" height="18">
            <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.71-1.57 2.68-3.89 2.68-6.62z"/>
            <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.84.86-3.04.86-2.34 0-4.32-1.58-5.03-3.7H.96v2.34A9 9 0 0 0 9 18z"/>
            <path fill="#FBBC05" d="M3.97 10.72a5.41 5.41 0 0 1 0-3.44V4.96H.96a9 9 0 0 0 0 8.1l3.01-2.34z"/>
            <path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.59C13.46.9 11.43 0 9 0A9 9 0 0 0 .96 4.96l3.01 2.34C4.68 5.16 6.66 3.58 9 3.58z"/>
        </svg>
        Entrar com Google
    </a>

    <p class="fi-google-auth-hint">
        Use sua conta &commat;{{ config('google.hosted_domain') }}
    </p>
</div>

<style>
    .fi-google-auth { margin-top: 1.5rem; }

    .fi-google-auth-divider {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1rem;
        color: var(--gray-500);
        font-size: .8125rem;
    }

    .fi-google-auth-divider::before,
    .fi-google-auth-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--gray-200);
    }

    .fi-google-auth-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .625rem;
        width: 100%;
        min-height: 2.75rem;
        padding: 0 1rem;
        border: 1px solid var(--gray-300);
        border-radius: .5rem;
        background: #fff;
        color: #1f2937;
        font-size: .9375rem;
        font-weight: 600;
        text-decoration: none;
    }

    .fi-google-auth-btn:hover { background: var(--gray-50); }

    .fi-google-auth-hint {
        margin-top: .625rem;
        text-align: center;
        font-size: .8125rem;
        color: var(--gray-500);
    }

    .dark .fi-google-auth-divider::before,
    .dark .fi-google-auth-divider::after { background: var(--gray-700); }

    .dark .fi-google-auth-btn {
        background: var(--gray-800);
        border-color: var(--gray-700);
        color: var(--gray-100);
    }

    .dark .fi-google-auth-btn:hover { background: var(--gray-700); }
</style>
