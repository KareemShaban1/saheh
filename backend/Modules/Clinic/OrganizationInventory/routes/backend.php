<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale() . '/clinic',
        'as' => 'clinic.',
        'middleware' => [
            'auth:web',
            'verified',
            'localeCookieRedirect',
            // 'localizationRedirect',
            'localeViewPath',
        ],
    ],
    function () {
        Route::prefix('/organization-inventories')
        ->name('organization-inventories.')
        ->group(function () {
            Route::get('/', 'OrganizationInventoryController@index')->name('index');
            Route::get('/data', 'OrganizationInventoryController@data')->name('data');
            Route::get('/add', 'OrganizationInventoryController@add')->name('add');
            Route::post('/store', 'OrganizationInventoryController@store')->name('store');
            Route::get('/edit/{organization_inventory_id}', 'OrganizationInventoryController@edit')->name('edit');
            Route::post('/update/{organization_inventory_id}', 'OrganizationInventoryController@update')->name('update');
            Route::delete('/delete/{organization_inventory_id}', 'OrganizationInventoryController@destroy')->name('delete');
        });

        Route::prefix('/inventory-movements')
        ->name('inventory-movements.')
        ->group(function () {
            Route::get('/data/{inventoryId}', 'InventoryMovementController@data')->name('data');
            Route::get('/{inventoryId}', 'InventoryMovementController@index')->name('index');
            Route::get('/add', 'InventoryMovementController@add')->name('add');
            Route::post('/store', 'InventoryMovementController@store')->name('store');
            Route::get('/edit/{inventory_movement_id}', 'InventoryMovementController@edit')->name('edit');
            Route::post('/update/{inventory_movement_id}', 'InventoryMovementController@update')->name('update');
            Route::delete('/delete/{inventory_movement_id}', 'InventoryMovementController@destroy')->name('delete');
        });

    }
);
