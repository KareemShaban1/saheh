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
         // Drugs / Prescription Part
         Route::group(
            [
                'prefix' => '/prescription',
                'as' => 'prescription.',
            ],
            function () {
                Route::get('/', 'PrescriptionController@index')->name('index');
                Route::get('/add/{id}', 'PrescriptionController@add')->name('add');
                Route::post('/store', 'PrescriptionController@store')->name('store');
                Route::get('/edit/{id}', 'PrescriptionController@edit')->name('edit');
                Route::put('/update/{id}', 'PrescriptionController@update')->name('update');
                Route::post('/store_prescription', 'PrescriptionController@storePrescription')->name('storePrescription');
                Route::put('/update_prescription/{id}', 'PrescriptionController@updatePrescription')->name('updatePrescription');
                Route::get('/show/{id}', 'PrescriptionController@show')->name('show');
                Route::get('/arabic_prescription_pdf/{id}', 'PrescriptionController@arabic_prescription_pdf')->name('arabic_prescription_pdf');
                Route::get('/english_prescription_pdf/{id}', 'PrescriptionController@english_prescription_pdf')->name('english_prescription_pdf');
                Route::delete('/drugs/{id}', 'PrescriptionController@deleteDrug')->name('deleteDrug');
            }
        );
    }
);
