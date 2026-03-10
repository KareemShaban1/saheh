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
         // Medicines Part
         Route::group(
            [
                'prefix' => '/medicines',
                'as' => 'medicines.',
            ],
            function () {
                Route::get('/', 'MedicineController@index')->name('index');
                Route::get('/data', 'MedicineController@data')->name('data');
                Route::get('/add', 'MedicineController@add')->name('add');
                Route::post('/store', 'MedicineController@store')->name('store');
                Route::get('/edit/{medicine_id}', 'MedicineController@edit')->name('edit');
                Route::post('/update/{medicine_id}', 'MedicineController@update')->name('update');
                Route::get('/show/{medicine_id}', 'MedicineController@show')->name('show');
                Route::delete('/delete/{medicine_id}', 'MedicineController@destroy')->name('destroy');
                Route::get('/trash', 'MedicineController@trash')->name('trash');
                Route::put('/restore/{medicine_id}', 'MedicineController@restore')->name('restore');
                Route::delete('/force_delete/{medicine_id}', 'MedicineController@forceDelete')->name('forceDelete');
            }
        );
    }
);