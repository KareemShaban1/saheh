<?php

namespace Modules\Clinic\ReservationNumber\Providers;

use Illuminate\Support\ServiceProvider;

class ReservationNumberServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(ReservationNumberRouteServiceProvider::class);


    }
}