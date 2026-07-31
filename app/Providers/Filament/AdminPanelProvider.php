<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AdherenceChart;
use App\Filament\Widgets\IncidentsByHourChart;
use App\Filament\Widgets\IncidentsByTypeChart;
use App\Filament\Widgets\OperationOverview;
use App\Filament\Widgets\RecurrenceChart;
use Filament\Navigation\NavigationGroup;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Notre Guard')
            // Marca do colégio no topo, com o nome do sistema ao lado: o painel
            // é uma entre várias aplicações da instituição.
            ->brandLogo(fn () => view('filament.marca', ['nome' => 'Notre Guard']))
            ->favicon('/icons/notre-guard.svg')
            // Sino no topo do painel. O polling é a via mais simples que
            // funciona sem WebSockets; 30s é suficiente para o uso aqui.
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            // Navy institucional (navy-900 do docs/design-system.md). O
            // Filament gera a escala inteira a partir dele, então botões,
            // badges e navegação já nascem na identidade do colégio.
            ->colors([
                'primary' => Color::hex('#013d53'),
                'danger' => Color::hex('#b3423a'),
                'warning' => Color::hex('#b8873a'),
                'success' => Color::hex('#2f7a52'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Operação'),
                NavigationGroup::make('Cadastros'),
                NavigationGroup::make('Configuração'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Sem discoverWidgets: a ordem e a composição do painel são
            // decisão explícita, não resultado de varredura de diretório.
            ->widgets([
                OperationOverview::class,
                AdherenceChart::class,
                IncidentsByHourChart::class,
                RecurrenceChart::class,
                IncidentsByTypeChart::class,
            ])
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
            ])
            // Render hook em vez de subclasse da página de login: o botão é um
            // acréscimo, e trocar a página inteira do Filament por causa dele
            // criaria dívida a cada upgrade.
            //
            // A condição fica dentro da closure, avaliada a cada render, para
            // que virar o flag no .env baste - sem republicar nada.
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.identidade'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => config('google.enabled')
                    ? view('auth.google-button')->render()
                    : '',
            );
    }
}
