<?php

use App\Http\Controllers\Backend\Admin\AreaController;
use App\Http\Controllers\Backend\Admin\CityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PerformanceLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('laravel_files.welcome');
// });

Route::get('cities/by-governorate', [CityController::class, 'getCitiesByGovernorate'])
    ->name('cities.by-governorate');

Route::get('areas/by-city', [AreaController::class, 'getAreasByCity'])
    ->name('areas.by-city');

Route::get('/', [HomeController::class, 'index']);
Route::get('/clinics', [HomeController::class, 'clinics'])
    ->name('clinics');
Route::get('/medical-laboratories', [HomeController::class, 'medicalLaboratories'])
    ->name('medical-laboratories');
Route::get('/radiology-centers', [HomeController::class, 'radiologyCenters'])
    ->name('radiology-centers');


// Detail pages routes
Route::get('/clinic/{id}', [HomeController::class, 'clinicDetail'])->name('clinic.detail');
Route::get('/doctor/{id}', [HomeController::class, 'doctorDetail'])->name('doctor.detail');
Route::get('/medical-laboratory/{id}', [HomeController::class, 'medicalLaboratoryDetail'])->name('medical-laboratory.detail');
Route::get('/radiology-center/{id}', [HomeController::class, 'radiologyCenterDetail'])->name('radiology-center.detail');


Route::post('/broadcasting/auth', function (Request $request) {
    foreach (['web', 'medical_laboratory', 'radiology_center'] as $guard) {
        if (Auth::guard($guard)->check()) {
            return Broadcast::auth($request->setUserResolver(function () use ($guard) {
                return Auth::guard($guard)->user();
            }));
        }
    }

    return response('Unauthorized.', 403);
});

Route::post('/api/performance-log', function (Illuminate\Http\Request $request) {
    Log::channel('performance')->info('Slow page detected', $request->all());
    return response()->json(['message' => 'Logged']);
});

Route::get('/performance-monitor', [PerformanceLogController::class, 'index'])
    ->name('performance.monitor');

// Documentation website (password-protected)
Route::prefix('docs')->name('docs.')->middleware('web')->group(function () {
    Route::get('login', [App\Http\Controllers\DocumentationController::class, 'login'])->name('login');
    Route::post('login', [App\Http\Controllers\DocumentationController::class, 'login'])->name('login');
    Route::post('logout', [App\Http\Controllers\DocumentationController::class, 'logout'])->name('logout')->middleware('docs.password');
    Route::get('/', [App\Http\Controllers\DocumentationController::class, 'index'])->name('index')->middleware('docs.password');
    Route::get('/{file}', [App\Http\Controllers\DocumentationController::class, 'show'])->name('show')->middleware('docs.password')->where('file', '[a-zA-Z0-9_\-\.]+');
});

require __DIR__ . '/clinic.php';

require __DIR__ . '/medicalLaboratory.php';

require __DIR__ . '/radiologyCenter.php';

require __DIR__ . '/patient.php';

require __DIR__ . '/admin.php';