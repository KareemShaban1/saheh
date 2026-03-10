<?php

namespace Modules\Clinic\Reservation\Providers;

use Illuminate\Support\ServiceProvider;

    class ReservationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(ReservationRouteServiceProvider::class);


    }
}