<?php

namespace App\Providers\Filament;

use App\Filament\Portaria\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Painel do computador da portaria: controle de chaves e nada mais.
 *
 * Existe como painel separado, e não como um recorte do administrativo, porque
 * o vigilante precisa entrar aqui - e o painel administrativo tem a operação
 * inteira das duas unidades. Separar é a diferença entre dar uma tela e dar
 * acesso ao sistema.
 */
class PortariaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portaria')
            ->path('portaria')
            ->login(Login::class)
            ->brandName('Notre Guard · Portaria')
            ->brandLogo(fn () => view('filament.marca', ['nome' => 'Portaria']))
            ->favicon('/icons/notre-guard.svg')
            // A mesma paleta institucional do painel administrativo e do app
            // de campo. A portaria usava Slate, o que a deixava visualmente
            // órfã dentro do próprio sistema.
            ->colors([
                'primary' => Color::hex('#013d53'),
                'danger' => Color::hex('#b3423a'),
                'warning' => Color::hex('#b8873a'),
                'success' => Color::hex('#2f7a52'),
            ])
            ->discoverResources(
                in: app_path('Filament/Portaria/Resources'),
                for: 'App\Filament\Portaria\Resources',
            )
            ->pages([])
            ->widgets([])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.identidade'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.portaria.styles'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
