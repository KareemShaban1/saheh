<?php

    namespace Modules\Clinic\Patient\Providers;

use Illuminate\Support\ServiceProvider;

class PatientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

                app()->register(PatientRouteServiceProvider::class);


    }
}
