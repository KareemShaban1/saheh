<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale().'/clinic',
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
        // Events Part
        Route::group(
            [
                'prefix' => '/events',
                'as' => 'events.',
            ],
            function () {
                Route::get('/', 'EventController@index')->name('index');
                Route::get('/data', 'EventController@data')->name('data');
                Route::get('/show', 'EventController@show')->name('show');
                Route::get('/add', 'EventController@add')->name('add');
                Route::delete('/delete/{event_id}', 'EventController@destroy')->name('destroy');
                Route::get('/trash', 'EventController@trash')->name('trash');
                Route::put('/restore/{event_id}', 'EventController@restore')->name('restore');
                Route::delete('/force_delete/{event_id}', 'EventController@forceDelete')->name('forceDelete');
            }
        );
    }
);
