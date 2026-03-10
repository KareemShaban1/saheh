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
        Route::group(
            [
                'prefix' => '/doctors',
                'as' => 'doctors.',
            ],
            function () {
                Route::get('/', 'DoctorController@index')->name('index');
                Route::get('/data', 'DoctorController@data')->name('data');
                Route::get('/add', 'DoctorController@add')->name('add');
                Route::post('/store', 'DoctorController@store')->name('store');
                Route::get('/edit/{doctor_id}', 'DoctorController@edit')->name('edit');
                Route::post('/update/{doctor_id}', 'DoctorController@update')->name('update');
                Route::delete('/delete/{doctor_id}', 'DoctorController@destroy')->name('delete');
            }
        );
    }
);
