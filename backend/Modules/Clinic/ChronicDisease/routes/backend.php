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
         // Chronic Diseases Part
         Route::group(
            [
                'prefix' => '/chronic_diseases',
                'as' => 'chronic_diseases.',
            ],
            function () {
                Route::get('/', 'ChronicDiseasesController@index')->name('index');
                Route::get('/add/{id}', 'ChronicDiseasesController@add')->name('add');
                Route::post('/store', 'ChronicDiseasesController@store')->name('store');
                Route::get('/edit/{disease_id}', 'ChronicDiseasesController@edit')->name('edit');
                Route::post('/update/{disease_id}', 'ChronicDiseasesController@update')->name('update');
                Route::get('/show/{id}', 'ChronicDiseasesController@show')->name('show');
                Route::delete('/{disease_id}', 'ChronicDiseasesController@destroy')->name('destroy');
            }
        );



    }
);