<?php

namespace Modules\Clinic\GlassesDistance\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class GlassesDistanceRouteServiceProvider extends BaseRouteServiceProvider
{
    protected $namespace = 'Modules\\Clinic\\GlassesDistance\\Http\\Controllers';

    public function map()
    {
        $this->mapApiRoutes();
        $this->mapBackendRoutes();
    }

    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace . '\\Api')
            ->group(__DIR__ . '/../routes/api.php');
    }

    protected function mapBackendRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace . '\\Backend')
            ->group(__DIR__ . '/../routes/backend.php');
    }
}
