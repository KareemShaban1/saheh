<?php

namespace Modules\MedicalLaboratory\LabServiceCategory\Providers;

use Illuminate\Support\ServiceProvider;

class LabServiceCategoryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        app()->register(LabServiceCategoryRouteServiceProvider::class);
    }
}
