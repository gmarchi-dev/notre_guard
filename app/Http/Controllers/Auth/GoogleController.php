<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleWorkspaceAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleController extends Controller
{
    public function __construct(private readonly GoogleWorkspaceAuth $google)
    {
        // Enquanto o recurso está desligado as rotas não existem — 404, e não
        // uma tela de erro que revele que o caminho está lá.
        abort_unless($this->google->enabled(), 404);
    }

    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')
            // Reduz o ruído do seletor de contas para quem tem conta pessoal
            // logada no mesmo navegador. Não substitui a validação do domínio.
            ->with(['hd' => $this->google->hostedDomain()])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $this->google->resolve($googleUser);
        } catch (Throwable $e) {
            Log::warning('Login Google recusado', [
                'motivo' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return redirect()
                ->route('filament.admin.auth.login')
                ->withErrors(['email' => $e->getMessage()]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('filament.admin.pages.dashboard'));
    }
}
