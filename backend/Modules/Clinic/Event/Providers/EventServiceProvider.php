<?php

namespace Modules\Clinic\Event\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(EventRouteServiceProvider::class);


    }
}
