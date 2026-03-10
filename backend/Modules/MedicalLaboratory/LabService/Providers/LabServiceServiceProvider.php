<?php

namespace Modules\MedicalLaboratory\LabService\Providers;

use Illuminate\Support\ServiceProvider;

class LabServiceServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        app()->register(LabServiceRouteServiceProvider::class);
    }
}
