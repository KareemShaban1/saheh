<?php

namespace App\Providers;

use App\Models\BaseModel;
use App\Observers\BaseModelObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        // remove "data" wrapper from json resource response
        JsonResource::withoutWrapping();

        BaseModel::observe(BaseModelObserver::class);


        Relation::morphMap([
            // 'clinic' => Clinic::class,
            // 'medical_laboratory' => MedicalLaboratory::class,
            // 'radiology_center' => RadiologyCenter::class,
        ]);


        DB::whenQueryingForLongerThan(300, function ($connection, $event) {
            Log::channel('slow_queries')->warning('⚠️ Slow Query Detected', [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'time_ms' => $event->time,
                'connection' => $connection->getName(),
                'trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
                    ->take(8) // لتقليل حجم الـ trace
                    ->map(fn($trace) => Arr::only($trace, ['file', 'line', 'function']))
                    ->toArray(),
            ]);
        });

    }
}