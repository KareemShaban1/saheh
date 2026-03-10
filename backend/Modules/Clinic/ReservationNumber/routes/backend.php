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
          // Number Of Reservations Part
          Route::group(
            [
                'prefix' => '/reservation_numbers',
                'as' => 'reservation_numbers.',
            ],
            function () {
                Route::get('/', 'ReservationNumberController@index')->name('index');
                Route::get('/data', 'ReservationNumberController@data')->name('data');
                Route::get('/add', 'ReservationNumberController@add')->name('add');
                Route::post('/store', 'ReservationNumberController@store')->name('store');
                Route::get('/edit/{num_of_res}', 'ReservationNumberController@edit')->name('edit');
                Route::post('/update/{num_of_res}', 'ReservationNumberController@update')->name('update');
                Route::delete('/delete/{num_of_res}', 'ReservationNumberController@destroy')->name('destroy');
                Route::get('/trash', 'ReservationNumberController@trash')->name('trash');
                Route::put('/restore/{num_of_res}', 'ReservationNumberController@restore')->name('restore');
                Route::delete('/force_delete/{num_of_res}', 'ReservationNumberController@forceDelete')->name('forceDelete');
            }
        );

    }
);
