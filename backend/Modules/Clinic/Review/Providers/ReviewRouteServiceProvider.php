<?php

namespace Modules\Clinic\Review\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as BaseRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class ReviewRouteServiceProvider extends BaseRouteServiceProvider
{
    protected $namespace = 'Modules\\Clinic\\Review\\Http\\Controllers';

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