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
                'prefix' => '/announcements',
                'as' => 'announcements.',
            ],
            function () {
                Route::get('/', 'AnnouncementController@index')->name('index');
                Route::get('/data', 'AnnouncementController@data')->name('data');
                Route::get('/add', 'AnnouncementController@add')->name('add');
                Route::post('/store', 'AnnouncementController@store')->name('store');
                Route::get('/edit/{announcement_id}', 'AnnouncementController@edit')->name('edit');
                Route::post('/update/{announcement_id}', 'AnnouncementController@update')->name('update');
                Route::delete('/delete/{announcement_id}', 'AnnouncementController@destroy')->name('delete');
            }
        );
    }
);