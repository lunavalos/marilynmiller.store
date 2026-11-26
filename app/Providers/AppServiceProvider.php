<?php

namespace App\Providers;

use App\Livewire\MySubscriptionPlan;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Jeffgreco13\FilamentBreezy\Livewire\SanctumTokens;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword;
use Jeffgreco13\FilamentBreezy\Pages\TwoFactorPage;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        Livewire::component('personal_info', PersonalInfo::class);
        Livewire::component('update_password', UpdatePassword::class);
        Livewire::component('two_factor_authentication', TwoFactorAuthentication::class);
        Livewire::component('sanctum_tokens', SanctumTokens::class);
        Livewire::component('two-factor-page', TwoFactorPage::class);
        Livewire::component('my_subscription_plan', MySubscriptionPlan::class);
    }
}
