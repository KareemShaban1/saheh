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
              // Glasses Distance Part
              Route::group(
                [
                    'prefix' => '/glasses_distance',
                    'as' => 'glasses_distance.',
                ],
                function () {
                    Route::get('/', 'GlassesDistanceController@index')->name('index');
                    Route::get('/data', 'GlassesDistanceController@data')->name('data');
                    Route::get('/add/{id}', 'GlassesDistanceController@add')->name('add');
                    Route::post('/store', 'GlassesDistanceController@store')->name('store');
                    Route::get('/edit/{disease_id}', 'GlassesDistanceController@edit')->name('edit');
                    Route::post('/update/{disease_id}', 'GlassesDistanceController@update')->name('update');
                    Route::get('/glasses_pdf/{glasses_distance_id}', 'GlassesDistanceController@glasses_distance_pdf')->name('glasses_distance_pdf');

                }
            );
    }
);
