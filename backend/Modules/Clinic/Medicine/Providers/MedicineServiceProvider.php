<?php

namespace Modules\Clinic\Medicine\Providers;

use Illuminate\Support\ServiceProvider;

class MedicineServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(MedicineRouteServiceProvider::class);


    }
}
