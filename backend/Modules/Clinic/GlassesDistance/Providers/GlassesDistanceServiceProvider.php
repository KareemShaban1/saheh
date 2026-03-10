<?php

namespace Modules\Clinic\GlassesDistance\Providers;

use Illuminate\Support\ServiceProvider;

class GlassesDistanceServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(GlassesDistanceRouteServiceProvider::class);


    }
}