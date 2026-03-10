<?php

namespace Modules\Clinic\Doctor\Providers;

use Illuminate\Support\ServiceProvider;

class DoctorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

                app()->register(DoctorRouteServiceProvider::class);


    }
}