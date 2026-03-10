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
                //  Reservations Slots Part
                Route::group(
                    [
                        'prefix' => '/reservation_slots',
                        'as' => 'reservation_slots.',
                    ],
                    function () {
                        Route::get('/', 'ReservationSlotsController@index')->name('index');
                        Route::get('/data', 'ReservationSlotsController@data')->name('data');
                        Route::get('/add', 'ReservationSlotsController@add')->name('add');
                        Route::post('/store', 'ReservationSlotsController@store')->name('store');
                        Route::get('/edit/{num_of_res}', 'ReservationSlotsController@edit')->name('edit');
                        Route::post('/update/{num_of_res}', 'ReservationSlotsController@update')->name('update');
                        Route::delete('/delete/{num_of_res}', 'ReservationSlotsController@destroy')->name('destroy');
                        Route::get('/trash', 'ReservationSlotsController@trash')->name('trash');
                        Route::put('/restore/{num_of_res}', 'ReservationSlotsController@restore')->name('restore');
                        Route::delete('/force_delete/{num_of_res}', 'ReservationSlotsController@forceDelete')->name('forceDelete');
                    }
                );
    }
);