<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\MemberRegister;
use App\Filament\Pages\SessionsCalendar;
use App\Filament\Resources\BookingResource;
use App\Filament\Resources\CourseResource;
use App\Filament\Resources\LiveEventResource;
use App\Filament\Resources\SessionResource;
use App\Filament\Resources\VideoResource;
use App\Filament\Widgets\SubscriberDashboardWidget;
use App\Filament\Widgets\SubscriberOverviewWidget;
use App\Livewire\MySubscriptionPlan;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;

class MemberPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('member')
            ->path('member')
            ->login()
            ->registration(MemberRegister::class)
            ->brandLogo(asset('logo.svg'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('favicon.png'))
            ->colors([
                'primary' => Color::hex('#3CA7A1'),
            ])
            ->resources([
                CourseResource::class,
                VideoResource::class,
                LiveEventResource::class,
                SessionResource::class,
                BookingResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                SessionsCalendar::class,
            ])
            ->navigationGroups([
                'Operations',
                'E-Learning',
            ])
            ->widgets([
                SubscriberOverviewWidget::class,
                SubscriberDashboardWidget::class,
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
            ])
            ->userMenuItems([
                'account' => MenuItem::make()
                    ->label('My profile')
                    ->url(fn (): string => MyProfilePage::getUrl()),
            ])
            ->plugin(
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: false,
                        shouldRegisterNavigation: false,
                        hasAvatars: true,
                        slug: 'my-profile'
                    )
                    ->myProfileComponents([
                        'my_subscription_plan' => MySubscriptionPlan::class,
                    ])
                    ->enableTwoFactorAuthentication(
                        force: false,
                    )
            );
    }
}
