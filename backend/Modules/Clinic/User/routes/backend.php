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
                'prefix' => '/users',
                'as' => 'users.',
            ],
            function () {
                Route::get('/', 'UserController@index')->name('index');
                Route::get('/data', 'UserController@data')->name('data');
                Route::get('/add', 'UserController@add')->name('add');
                Route::post('/store', 'UserController@store')->name('store');
                Route::get('/edit/{user_id}', 'UserController@edit')->name('edit');
                Route::post('/update/{user_id}', 'UserController@update')->name('update');
                Route::delete('/delete/{user_id}', 'UserController@destroy')->name('delete');
            }
        );
    }
);