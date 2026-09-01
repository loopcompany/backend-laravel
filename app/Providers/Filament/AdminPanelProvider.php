<?php

namespace App\Providers\Filament;

use App\Http\Middleware\CheckAdminPermission;
use App\Models\Admin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // FilamentAsset::register([
        //     Css::make('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'),
        //     Js::make('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'),
        // ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->assets([
                Css::make(
                    'neshan-sdk-style',
                    'https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.css'
                ),

                // 2) JS خود Neshan SDK
                Js::make(
                    'neshan-sdk-script',
                    'https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.js'
                ),
                Js::make('neshan-picker', asset('assets/js/neshan-picker.js')),
            ])
            ->font('Vazirmatn', asset('assets/css/filament-font.css'))
            ->authGuard('admin')
            ->authPasswordBroker('admins')
            ->profile()
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->brandName('پنل مدیریت')
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'مدیریت کاربران',
                'مدیریت مکان',
                'محتوا',
                'تنظیمات',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class, 
                \App\Filament\Widgets\QuickActionsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                CheckAdminPermission::class . ':access-admin-panel',
            ]);
    }
}
