<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Share user lengkap (dengan role + profile + warehouses) ke semua view authenticated
        View::composer(['layouts.app', 'dashboard'], function ($view) {
            if (auth()->check()) {
                $view->with('currentUser', auth()->user()->load('role', 'profile', 'warehouses'));
            }
        });
    }
}
