<?php

namespace Modules\Clinic\ChronicDisease\Providers;

use Illuminate\Support\ServiceProvider;

class ChronicDiseaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(ChronicDiseaseRouteServiceProvider::class);


    }
}
