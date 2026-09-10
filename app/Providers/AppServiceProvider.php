<?php

namespace App\Providers;

use App\Models\PriceTier;
use App\Observers\PriceTierObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        PriceTier::observe(PriceTierObserver::class);
    }
}
