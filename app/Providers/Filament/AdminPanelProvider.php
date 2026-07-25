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
            // Sino no topo do painel. O polling é a via mais simples que
            // funciona sem WebSockets; 30s é suficiente para o uso aqui.
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->colors([
                'primary' => Color::Blue,
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
            ]);
    }
}
