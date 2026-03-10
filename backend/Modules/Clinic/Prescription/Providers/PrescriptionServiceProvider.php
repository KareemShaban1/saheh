<?php

namespace Modules\Clinic\Prescription\Providers;

use Illuminate\Support\ServiceProvider;

class PrescriptionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(PrescriptionRouteServiceProvider::class);


    }
}
