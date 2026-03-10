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
          // Reservations Part
          Route::group(
            [
                'prefix' => '/reservations',
                'as' => 'reservations.',
            ],
            function () {
                Route::get('/', 'ReservationController@index')->name('index');
                Route::get('data', 'ReservationController@data')->name('data');
                Route::get('/report', 'ReservationController@todayReservationReport')->name('today_reservation_report');
                Route::get('/today_reservations', 'ReservationController@todayReservations')->name('today_reservations');
                Route::get('/show/{id}', 'ReservationController@show')->name('show');
                Route::get('/status/{id}/{status}', 'ReservationController@reservationStatus')->name('reservation_status');
                Route::get('/payment/{id}/{payment}', 'ReservationController@paymentStatus')->name('payment_status');
                Route::get('/add/{id}/', 'ReservationController@add')->name('add');
                Route::post('/store', 'ReservationController@store')->name('store');
                Route::get('/edit/{id}', 'ReservationController@edit')->name('edit');
                Route::post('/update/{id}', 'ReservationController@update')->name('update');
                Route::delete('/delete/{id}', 'ReservationController@destroy')->name('destroy');
                Route::get('/trash', 'ReservationController@trash')->name('trash');
                Route::get('/trash-data', 'ReservationController@trashData')->name('trash-data');
                Route::put('/restore/{id}', 'ReservationController@restore')->name('restore');
                Route::delete('/force_delete/{id}', 'ReservationController@forceDelete')->name('forceDelete');
                Route::get('/get_res_slot_number_add', 'ReservationController@getResNumberOrSlotAdd');
                Route::get('/get_res_slot_number_edit', 'ReservationController@getResNumberOrSlotEdit');
                Route::get('/available_slots_numbers', 'ReservationController@getAvailableSlotsNumbersForSwap')->name('available_slots_numbers');
                Route::post('/swap_slot_number/{id}', 'ReservationController@swapSlotOrNumber')->name('swap_slot_number');
                Route::get('/editChronicDisease/{reservation_id}', 'ReservationController@editChronicDisease')->name('editChronicDisease');
                Route::put('/updateChronicDisease/{reservation_id}', 'ReservationController@updateChronicDisease')->name('updateChronicDisease');
                Route::get('/get_doctor_services/{doctor_id}', 'ReservationController@getDoctorServices')->name('getDoctorServices');
                Route::delete('/delete_service_fee/{id}', 'ReservationController@deleteService')->name('deleteService');
            }
        );
    }
);