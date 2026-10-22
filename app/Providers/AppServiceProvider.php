<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Livewire\Checkout;
use App\Livewire\OrderPage;
use Illuminate\Support\ServiceProvider;
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
        Livewire::component('checkout', Checkout::class);
        Livewire::component('order-page', OrderPage::class);

        // Livewire's follow-up requests keep the page's language.
        Livewire::addPersistentMiddleware([SetLocale::class]);
    }
}
