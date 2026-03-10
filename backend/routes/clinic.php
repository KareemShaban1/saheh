<?php

use App\Http\Controllers\Backend\Clinic\NotificationController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\Clinic\AuthController as ClinicAuthController;
use App\Http\Controllers\Backend\ClinicController;
use App\Http\Controllers\Backend\SubscribeController;
use App\Http\Controllers\Backend\Clinic\OrganizationInventoryController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use App\Http\Controllers\Backend\Clinic\InventoryMovementController;
use App\Models\ToothRecord;

// Route::post('/backup', 'App\Http\Controllers\Backend\BackupController@create')->name('backup.create');


Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'as' => 'clinic.',
        'namespace' => 'App\Http\Controllers\Backend\Clinic',
        'middleware' => [
            'auth:web',
            'verified',
            'localeCookieRedirect',
            // 'localizationRedirect',
            'localeViewPath',
        ]
    ],
    function () {

        // Vue Dashboard (parallel to Blade) - Must be before clinic prefix
        Route::get('clinic/vue/dashboard', function () {
            return view('backend.dashboards.clinic.vue.dashboard');
        })->name('vue.dashboard');

        Route::prefix('clinic')->group(function () {

        Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->name('logs');


        Route::get('/notifications/read/{id}', [NotificationController::class, 'markAsReadAndRedirect'])->name('notifications.read');





        Route::group(
            [
                'prefix' => '/reservations_options',
                'as' => 'reservations_options.',
                'controller' => 'ReservationsControllers\ReservationOptionsController',
            ],
            function () {
                Route::post('/status/{id}', 'reservationStatus')->name('reservation_status');
                Route::get('/payment/{id}/{payment}', 'paymentStatus')->name('payment_status');
                Route::get('/acceptance/{id}/{status}', 'ReservationAcceptance')->name('reservation_acceptance');
            }
        );

        // Online Reservations Part
        Route::group(
            [
                'prefix' => '/online_reservations',
                'as' => 'online_reservations.',
                'controller' => 'ReservationsControllers\OnlineReservationController',
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
                Route::get('/show/{id}', 'show')->name('show');
                Route::get('/status/{id}/{status}', 'reservation_status')->name('reservation_status');
                Route::get('/payment/{id}/{payment}', 'payment_status')->name('payment_status');
                Route::get('/add/{id}/', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{id}', 'edit')->name('edit');
                Route::post('/update/{id}', 'update')->name('update');
                Route::delete('/delete', 'destroy')->name('destroy');
                Route::get('/trash', 'trash')->name('trash');
                Route::put('/restore/{id}', 'restore')->name('restore');
                Route::delete('/force_delete/{id}', 'forceDelete')->name('forceDelete');
            }
        );






        // Rays Part
        Route::group(
            [
                'prefix' => '/rays',
                'as' => 'rays.',
                'controller' => 'ReservationsControllers\RaysController',
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
                Route::get('/add/{id}', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{ray_id}', 'edit')->name('edit');
                Route::post('/update/{ray_id}', 'update')->name('update');
                Route::get('/show/{id}', 'show')->name('show');
                Route::delete('/delete/{ray_id}', 'destroy')->name('destroy');
                Route::get('/trash', 'trash')->name('trash');
                Route::put('/restore/{ray_id}', 'restore')->name('restore');
                Route::delete('/force_delete/{ray_id}', 'forceDelete')->name('forceDelete');
            }
        );

        // Analysis Part
        Route::group(
            [
                'prefix' => '/analysis',
                'as' => 'analysis.',
                'controller' => 'ReservationsControllers\MedicalAnalysisController',
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
                Route::get('/add/{id}', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{medical_analysis_id}', 'edit')->name('edit');
                Route::post('/update/{medical_analysis_id}', 'update')->name('update');
                Route::get('/show/{id}', 'show')->name('show');
                Route::delete('/delete/{medical_analysis_id}', 'destroy')->name('destroy');
                Route::get('/trash', 'trash')->name('trash');
                Route::put('/restore/{medical_analysis_id}', 'restore')->name('restore');
                Route::delete('/force_delete/{medical_analysis_id}', 'forceDelete')->name('forceDelete');
            }
        );

        // Fees Part
        Route::group(
            [
                'prefix' => '/fees',
                'as' => 'fees.',
                'controller' => 'ReservationsControllers\FeeController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
            }
        );





        // Settings Part
        Route::group(
            [
                'prefix' => '/settings',
                'as' => 'settings.'
            ],
            function () {
                // settings
                Route::get('/', 'SettingsController@index')->name('index');

                Route::get('/clinic_settings', 'SettingsController@clinicSettings')
                    ->name('clinicSettings.index');
                Route::post('/clinic_settings', 'SettingsController@updateClinicSettings')->name('clinicSettings.update');

                Route::get('/zoom_settings', 'SettingsController@zoomSettings')->name('zoomSettings.index');
                Route::post('/zoom_settings', 'SettingsController@updateZoomSettings')->name('zoomSettings.update');


                Route::get('/reservation_settings', 'SettingsController@reservationSettings')->name('reservationSettings.index');
                Route::post('/reservation_settings', 'SettingsController@updateReservationSettings')->name('reservationSettings.update');
            }
        );


        // Reservation Control Part
        Route::group(
            [
                'prefix' => '/system_control',
                'as' => 'system_control.',
                'controller' => 'ReservationsControllers\SystemControlController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::post('/update', 'update')->name('update');
            }
        );


        // Roles Part
        Route::group(
            [
                'prefix' => '/roles',
                'as' => 'roles.',
                'controller' => 'RolesPermissionsController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/permissions', 'permissions')->name('permissions');
                Route::get('/data',  'data')->name('data');
                Route::get('/add', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{role_id}', 'edit')->name('edit');
                Route::post('/update/{role_id}', 'update')->name('update');
            }
        );

        // Service Fee Part
        Route::group(
            [
                'prefix' => '/service_fee',
                'as' => 'Services.',
                'controller' => 'ServiceController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
                Route::get('/add', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{service_fee_id}', 'edit')->name('edit');
                Route::post('/update/{service_fee_id}', 'update')->name('update');
                Route::delete('/delete/{service_fee_id}', 'destroy')->name('destroy');
            }
        );

        Route::group(
            [
                'prefix' => '/type',
                'as' => 'type.',
                'controller' => 'TypeController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
                Route::get('/add', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{type_id}', 'edit')->name('edit');
                Route::post('/update/{type_id}', 'update')->name('update');
                Route::delete('/delete/{type_id}', 'destroy')->name('destroy');
            }
        );


        Route::group(
            [
                'prefix' => '/tooth-record',
                'as' => 'tooth-record.',
                'controller' => 'ToothRecordController'
            ],function () {
                Route::get('show/{patient}', 'show')->name('show');
                Route::post('store/{patient}',  'save')->name('store');
                Route::post('delete/{patient}', 'delete')->name('delete');
            }
        );




    });

});

Route::get('/register-clinic', function () {
    return view('backend.dashboards.clinic.auth.register-clinic');
})->name('register-clinic');

Route::post('/register-clinic', [ClinicAuthController::class, 'storeClinicTempData'])->name('register-clinic');

Route::get('/activate-clinic/{token}', [ClinicAuthController::class, 'activate'])->name('activate-clinic');
Route::post('/subscribe', [SubscribeController::class, 'store']);
