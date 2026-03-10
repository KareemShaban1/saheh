<?php

namespace Modules\Clinic\OrganizationInventory\Providers;

use Illuminate\Support\ServiceProvider;

class OrganizationInventoryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

            app()->register(OrganizationInventoryRouteServiceProvider::class);


    }
}
