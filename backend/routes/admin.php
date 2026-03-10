<?php

use App\Http\Controllers\Backend\Admin\AnnouncementController;
use App\Http\Controllers\Backend\Admin\AreaController;
use App\Http\Controllers\Backend\Admin\CityController;
use App\Http\Controllers\Backend\Admin\ClinicController;
use App\Http\Controllers\Backend\Admin\DashboardController;
use App\Http\Controllers\Backend\Admin\GovernorateController;
use App\Http\Controllers\Backend\Admin\MedicalLaboratoryController;
use App\Http\Controllers\Backend\Admin\MedicalLaboratoryTempDataController;
use App\Http\Controllers\Backend\Admin\RadiologyCenterController;
use App\Http\Controllers\Backend\Admin\specialtyController;
use App\Http\Controllers\Backend\Admin\ClinicTempDataController;
use App\Http\Controllers\Backend\Admin\NotificationController;
use App\Http\Controllers\Backend\Admin\RadiologyCenterTempDataController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\Admin\ReviewsController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;







Route::group(
    [
        'prefix' => LaravelLocalization::setLocale() . '/admin',
        'as' => 'admin.',
        'namespace' => 'App\Http\Controllers\Backend\Admin',
        'middleware' => [
            'tenant',
            'auth:admin',
            'verified',
            'localeCookieRedirect',
            'localizationRedirect',
            'localeViewPath'
        ]
    ],
    function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');


        Route::get('/notifications/read/{id}', [NotificationController::class, 'markAsReadAndRedirect'])->name('notifications.read');


        Route::group(
            [
                'prefix' => '/backups',
                'as' => 'backups.',
                'controller' => 'BackupController',
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::get('/download/{file_name}', 'download')->name('download');
                Route::get('delete/{file_name}', 'delete')->name('delete');
            }
        );

        Route::get('specialties/data', [specialtyController::class, 'data'])
            ->name('specialties.data');
        Route::get('specialties', [specialtyController::class, 'index'])
            ->name('specialties.index');
        Route::post('specialties/store', [specialtyController::class, 'store'])
            ->name('specialties.store');
        Route::post('specialties/update/{id}', [specialtyController::class, 'update'])
            ->name('specialties.update');

        Route::get('governorates/data', [GovernorateController::class, 'data'])
            ->name('governorates.data');
        Route::get('governorates', [GovernorateController::class, 'index'])
            ->name('governorates.index');
        Route::post('governorates/store', [GovernorateController::class, 'store'])
            ->name('governorates.store');
        Route::post('governorates/update/{id}', [GovernorateController::class, 'update'])
            ->name('governorates.update');
        Route::delete('governorates/delete/{id}', [GovernorateController::class, 'destroy'])
            ->name('governorates.delete');

        Route::get('cities/data', [CityController::class, 'data'])
            ->name('cities.data');
        Route::get('cities', [CityController::class, 'index'])
            ->name('cities.index');
        Route::post('cities/store', [CityController::class, 'store'])
            ->name('cities.store');
        Route::post('cities/update/{id}', [CityController::class, 'update'])
            ->name('cities.update');
        Route::get('cities/by-governorate', [CityController::class, 'getCitiesByGovernorate'])
            ->name('cities.by-governorate');

        Route::get('areas/data', [AreaController::class, 'data'])
            ->name('areas.data');
        Route::get('areas', [AreaController::class, 'index'])
            ->name('areas.index');
        Route::post('areas/store', [AreaController::class, 'store'])
            ->name('areas.store');
        Route::get('areas/edit/{id}', [AreaController::class, 'edit'])
            ->name('areas.edit');
        Route::post('areas/update/{id}', [AreaController::class, 'update'])
            ->name('areas.update');
        Route::delete('areas/delete/{id}', [AreaController::class, 'destroy'])
            ->name('areas.delete');
        Route::get('areas/by-city', [AreaController::class, 'getAreasByCity'])
            ->name('areas.by-city');


        Route::get('clinics/data', action: [ClinicController::class, 'data'])
            ->name('clinics.data');
        Route::get('clinics', [ClinicController::class, 'index'])
            ->name('clinics.index');
        Route::post('clinics/update-status', [ClinicController::class, 'updateStatus'])
            ->name('clinics.update-status');

        Route::get('medical-laboratories/data', [MedicalLaboratoryController::class, 'data'])
            ->name('medical-laboratories.data');
        Route::get('medical-laboratories', [MedicalLaboratoryController::class, 'index'])
            ->name('medical-laboratories.index');
        Route::post('medical-laboratories/update-status', [MedicalLaboratoryController::class, 'updateStatus'])
            ->name('medical-laboratories.update-status');


        Route::get('radiology-centers/data', [RadiologyCenterController::class, 'data'])
            ->name('radiology-centers.data');
        Route::get('radiology-centers', [RadiologyCenterController::class, 'index'])
            ->name('radiology-centers.index');
        Route::post('radiology-centers/update-status', [RadiologyCenterController::class, 'updateStatus'])
            ->name('radiology-centers.update-status');


        Route::get('reviews/data', [ReviewsController::class, 'data'])
            ->name('reviews.data');
        Route::get('reviews', [ReviewsController::class, 'index'])
            ->name('reviews.index');
        Route::post('reviews/store', [ReviewsController::class, 'store'])
            ->name('reviews.store');
        Route::post('reviews/update/{id}', [ReviewsController::class, 'update'])
            ->name('reviews.update');
        Route::delete('reviews/delete/{id}', [ReviewsController::class, 'destroy'])
            ->name('reviews.delete');
        Route::get('reviews/{id}', 'ReviewsController@show')->name('reviews.show');
        Route::put('reviews/{id}', 'ReviewsController@update')->name('reviews.update');
        Route::post('reviews/update-status', [ReviewsController::class, 'updateStatus'])
            ->name('reviews.update-status');

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
            }
        );


        // Roles Part
        Route::group(
            [
                'prefix' => '/roles',
                'as' => 'roles.',
                'controller' => 'RoleController'
            ],
            function () {
                Route::get('/', 'index')->name('index');
                Route::get('/data',  'data')->name('data');
                Route::get('/add', 'add')->name('add');
                Route::post('/store', 'store')->name('store');
                Route::get('/edit/{role_id}', 'edit')->name('edit');
                Route::post('/update/{role_id}', 'update')->name('update');
            }
        );

        Route::prefix('/clinic-temp-data')
            ->name('clinic-temp-data.')
            ->group(function () {
                Route::get('/pendingClinics', [ClinicTempDataController::class, 'pendingClinics'])->name('pendingClinics');
                Route::get('/approveClinic/{batchId}', [ClinicTempDataController::class, 'approveClinic'])->name('approveClinic');
                Route::delete('/destroyClinic/{batchId}', [ClinicTempDataController::class, 'destroyClinic'])->name('destroyClinic');
            });

        Route::prefix('/medical-laboratories-temp-data')
            ->name('medical-laboratory-temp-data.')
            ->group(function () {
                Route::get('/pendingMedicalLaboratories', [MedicalLaboratoryTempDataController::class, 'pendingMedicalLaboratories'])->name('pendingMedicalLaboratories');
                Route::get('/approveMedicalLaboratory/{batchId}', [MedicalLaboratoryTempDataController::class, 'approveMedicalLaboratory'])->name('approveMedicalLaboratory');
                Route::delete('/destroyMedicalLaboratory/{batchId}', [MedicalLaboratoryTempDataController::class, 'destroyMedicalLaboratory'])->name('destroyMedicalLaboratory');
            });

        Route::prefix('/radiology-centers-temp-data')
            ->name('radiology-center-temp-data.')
            ->group(function () {
                Route::get('/pendingRadiologyCenters', [RadiologyCenterTempDataController::class, 'pendingRadiologyCenters'])->name('pendingRadiologyCenters');
                Route::get('/approveRadiologyCenter/{batchId}', [RadiologyCenterTempDataController::class, 'approveRadiologyCenter'])->name('approveRadiologyCenter');
                Route::delete('/destroyRadiologyCenter/{batchId}', [RadiologyCenterTempDataController::class, 'destroyRadiologyCenter'])->name('destroyRadiologyCenter');
            });


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
    }
);
