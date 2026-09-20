<?php

namespace App\Providers;

use App\Models\Business;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer(['admin.layout'], function ($view) {
            $view->with('business', Business::first());
        });
    }
}
