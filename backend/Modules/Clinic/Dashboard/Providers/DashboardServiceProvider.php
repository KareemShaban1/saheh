<?php

namespace Modules\Clinic\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(DashboardRouteServiceProvider::class);


    }
}