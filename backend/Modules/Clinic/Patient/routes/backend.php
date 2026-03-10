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
                'prefix' => '/patients',
                'as' => 'patients.',
            ],
            function () {
                Route::get('/', 'PatientController@index')->name('index');
                Route::get('/data', 'PatientController@data')->name('data');
                Route::get('/add', 'PatientController@add')->name('add');
                Route::post('/store', 'PatientController@store')->name('store');
                Route::get('/edit/{patient_id}', 'PatientController@edit')->name('edit');
                Route::post('/update/{patient_id}', 'PatientController@update')->name('update');
                Route::delete('/delete/{patient_id}', 'PatientController@destroy')->name('destroy');
                Route::get('/trash', 'PatientController@trash')->name('trash');
                Route::get('/trash-data', 'PatientController@trashData')->name('trash-data');
                Route::put('/restore/{patient_id}', 'PatientController@restore')->name('restore');
                Route::delete('/force_delete/{patient_id}', 'PatientController@forceDelete')->name('forceDelete');
                Route::get('/add_patient_code', 'PatientController@add_patient_code')->name('add_patient_code');
                Route::get('/patient_card/{id}', 'PatientController@patient_card')->name('patient_card');
                Route::get('/patient_pdf/{id}', 'PatientController@patientPdf')->name('patient_pdf');
                Route::get('/search', 'PatientController@search')->name('search');
                Route::post('/assign', 'PatientController@assignPatient')->name('assignPatient');
                Route::post('/unassign/{patient_id}', 'PatientController@unassignPatient')->name('unassignPatient');
                Route::get('/show/{patient_id}', 'PatientController@show')->name('show');
            }
        );
    }
);