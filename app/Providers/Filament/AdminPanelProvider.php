<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Http\Middleware\SetAdminLocale;
use App\Support\BrandPalette;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
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
            ->brandName('MOFA')
            ->brandLogo(asset('images/brand/mofa-header-logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('favicon.ico'))
            ->darkMode(false)
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            // Self-hosted through the theme. Filament wraps the family in quotes,
            // so the Arabic fallback is spliced into the same declaration.
            ->font("Jost', 'Tajawal", provider: LocalFontProvider::class)
            ->serifFont("Cormorant Garamond', 'Amiri", provider: LocalFontProvider::class)
            ->colors([
                'primary' => BrandPalette::brown(),
                'gray' => BrandPalette::gray(),
                'warning' => BrandPalette::terracotta(),
                'success' => BrandPalette::olive(),
                'info' => BrandPalette::mauve(),
                'danger' => Color::Red,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('7xl')
            ->userMenuItems([
                // One tap to the other dashboard language.
                MenuItem::make()
                    ->label(fn () => app()->getLocale() === 'ar' ? 'English' : 'العربية')
                    ->icon('heroicon-o-language')
                    ->url(fn () => route('filament.admin.locale', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar'])),
            ])
            ->routes(function () {
                Route::get('locale/{locale}', function (Request $request, string $locale) {
                    abort_unless(in_array($locale, SetAdminLocale::LOCALES, true), 404);
                    $request->session()->put(SetAdminLocale::SESSION_KEY, $locale);

                    return redirect()->back(fallback: route('filament.admin.pages.dashboard'));
                })->name('locale');
            })
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
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
            // Persistent, so Livewire requests inside the panel keep the same language.
            ->middleware([SetAdminLocale::class], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
