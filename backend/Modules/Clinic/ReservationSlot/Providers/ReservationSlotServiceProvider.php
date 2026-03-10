<?php

namespace Modules\Clinic\ReservationSlot\Providers;

use Illuminate\Support\ServiceProvider;

class ReservationSlotServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

                app()->register(ReservationSlotRouteServiceProvider::class);


    }
}