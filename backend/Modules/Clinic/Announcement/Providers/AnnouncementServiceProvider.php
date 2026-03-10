<?php

namespace Modules\Clinic\Announcement\Providers;

use Illuminate\Support\ServiceProvider;

class AnnouncementServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        app()->register(AnnouncementRouteServiceProvider::class);


    }
}