<?php

use App\Http\Controllers\Backend\RadiologyCenter\AnnouncementController;
use App\Http\Controllers\Backend\RadiologyCenter\AuthController;
use App\Http\Controllers\Backend\RadiologyCenter\OrganizationInventoryController;
use App\Http\Controllers\Backend\RadiologyCenter\InventoryMovementController;
use App\Http\Controllers\Backend\SubscribeController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;




Route::group(
    [
        'prefix' => LaravelLocalization::setLocale() . '/radiology-center',
        'as' => 'radiologyCenter.',
        'namespace' => 'App\Http\Controllers\Backend\RadiologyCenter',
        'middleware' => [
            'auth:radiology_center',
            'verified',
            'localeCookieRedirect',
            // 'localizationRedirect',
            'localeViewPath'
        ]
    ],
    function () {

        //  // Chat Part
        //  Route::group(
        //     [
        //         'prefix' => '/chats',
        //         'as' => 'chats.',
        //         'controller' => 'ChatController'
        //     ],
        //     function () {
        //         Route::get('/', 'index')->name('index');
        //         Route::get('/patient/{patientId}', 'getChatByPatient')->name('getChatByPatient');
        //     }
        // );

        // Route::group(
        //     [
        //         'prefix' => '/messages',
        //         'as' => 'messages.',
        //         'controller' => 'MessageController'
        //     ],
        //     function () {
        //         Route::get('/', 'index')->name('index');
        //         Route::post('/send-message/{chatId}','store')->name('store');
        //     }
        // );

        Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->name('logs');

        // Dashboard Part
        Route::get('/dashboard', 'DashboardController@index')->name('dashboard');

        // Reviews Part
        Route::get('/reviews', 'ReviewsController@index')->name('reviews.index');
        Route::get('/reviews/data', 'ReviewsController@data')->name('reviews.data');

        Route::group(
            [
                'prefix' => '/events',
                'as' => 'events.',
                'controller' => 'EventController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
                Route::get('/show', 'show')->name('show');
                Route::get('/add', 'add')->name('add');
                Route::delete('/delete/{event_id}', 'destroy')->name('destroy');
                Route::get('/trash', 'trash')->name('trash');
                Route::put('/restore/{event_id}', 'restore')->name('restore');
                Route::delete('/force_delete/{event_id}', 'forceDelete')->name('forceDelete');
            }
        );

        // Analysis Part
        Route::group(
            [
                'prefix' => '/rays',
                'as' => 'rays.',
                'controller' => 'RaysController',
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
                Route::get('/create', 'create')->name('create');
                Route::get('/add/{patient_id}', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{rays_id}', 'edit')->name('edit');
                Route::put('/update/{rays_id}', 'update')->name('update');
                Route::get('/show/{id}', 'show')->name('show');
                Route::delete('/delete/{rays_id}', 'destroy')->name('destroy');
                Route::get('/trash', 'trash')->name('trash');
                Route::put('/restore/{rays_id}', 'restore')->name('restore');
                Route::delete('/force_delete/{rays_id}', 'forceDelete')->name('forceDelete');
            }
        );



        // Patients Part
        Route::group(
            [
                'prefix' => '/patients',
                'as' => 'patients.',
                'controller' => 'PatientController',
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('data', 'data')->name('data');
                Route::get('/trash-data', 'trashData')->name('trash-data');
                Route::get('/show/{id}', 'show')->name('show');
                Route::get('/patient_card/{id}', 'patient_card')->name('patient_card');
                Route::get('/patient_pdf/{id}', 'patientPdf')->name('patient_pdf');
                Route::get('/add', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{id}', 'edit')->name('edit');
                Route::post('/update/{id}', 'update')->name('update');
                Route::delete('/delete/{id}', 'destroy')->name('destroy');
                Route::get('/trash', 'trash')->name('trash');
                Route::put('/restore/{id}', 'restore')->name('restore');
                Route::delete('/force_delete/{id}', 'forceDelete')->name('forceDelete');

                Route::get('/add_patient_code', 'add_patient_code')->name('add_patient_code');
                Route::get('/search', 'search');
                Route::post('/assign', 'assignPatient')->name('assignPatient');
                Route::post('/unassign/{patient_id}', 'unassignPatient')->name('unassignPatient');
            }
        );


        // Rays Part
        Route::group(
            [
                'prefix' => '/rays',
                'as' => 'rays.',
                'controller' => 'RaysController',
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
                Route::get('/add/{id}', 'add')->name('add');
                Route::get('/create', 'create')->name('create');
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


        // User Part
        Route::group(
            [
                'prefix' => '/users',
                'as' => 'users.',
                'controller' => 'UserController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data',  'data')->name('data');
                Route::get('/add', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{user_id}', 'edit')->name('edit');
                Route::post('/update/{user_id}', 'update')->name('update');
                Route::delete('/delete/{user_id}', 'destroy')->name('destroy');
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

        // Fees Part
        Route::group(
            [
                'prefix' => '/fees',
                'as' => 'fees.',
                'controller' => 'FeeController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data', 'data')->name('data');
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


        Route::prefix('/announcements')
        ->name('announcements.')
        ->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::get('/data', [AnnouncementController::class, 'data'])->name('data');
            Route::get('/add', [AnnouncementController::class, 'add'])->name('add');
            Route::post('/store', [AnnouncementController::class, 'store'])->name('store');
            Route::get('/edit/{announcement_id}', [AnnouncementController::class, 'edit'])->name('edit');
            Route::post('/update/{announcement_id}', [AnnouncementController::class, 'update'])->name('update');
            Route::delete('/delete/{announcement_id}', [AnnouncementController::class, 'destroy'])->name('delete');
        });

        Route::prefix('/organization-inventories')
        ->name('organization-inventories.')
        ->group(function () {
            Route::get('/', [OrganizationInventoryController::class, 'index'])->name('index');
            Route::get('/data', [OrganizationInventoryController::class, 'data'])->name('data');
            Route::get('/add', [OrganizationInventoryController::class, 'add'])->name('add');
            Route::post('/store', [OrganizationInventoryController::class, 'store'])->name('store');
            Route::get('/edit/{organization_inventory_id}', [OrganizationInventoryController::class, 'edit'])->name('edit');
            Route::post('/update/{organization_inventory_id}', [OrganizationInventoryController::class, 'update'])->name('update');
            Route::delete('/delete/{organization_inventory_id}', [OrganizationInventoryController::class, 'destroy'])->name('delete');
        });

        Route::prefix('/inventory-movements')
        ->name('inventory-movements.')
        ->group(function () {
            Route::get('/data/{inventoryId}', [InventoryMovementController::class, 'data'])->name('data');
            Route::get('/{inventoryId}', [InventoryMovementController::class, 'index'])->name('index');
            Route::get('/add', [InventoryMovementController::class, 'add'])->name('add');
            Route::post('/store', [InventoryMovementController::class, 'store'])->name('store');
            Route::get('/edit/{inventory_movement_id}', [InventoryMovementController::class, 'edit'])->name('edit');
            Route::post('/update/{inventory_movement_id}', [InventoryMovementController::class, 'update'])->name('update');
            Route::delete('/delete/{inventory_movement_id}', [InventoryMovementController::class, 'destroy'])->name('delete');
        });
    }

);

Route::get('/register-radiology-center', function () {
    return view('backend.dashboards.radiologyCenter.auth.register-radiology-center');
})->name('register-radiology-center');

Route::post('/register-radiology-center', [AuthController::class, 'storeRadiologyCenterTempData'])
    ->name('register-radiology-center');

Route::get('/activate-radiology-center/{token}', [AuthController::class, 'activateRadiologyCenter'])
    ->name('activate-radiology-center');

Route::post('/subscribe', [SubscribeController::class, 'store']);
