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
                'prefix' => '/reviews',
                'as' => 'reviews.',
            ],
            function () {
                Route::get('/', 'ReviewsController@index')->name('index');
                Route::get('/data', 'ReviewsController@data')->name('data');
            }
        );
    }
);