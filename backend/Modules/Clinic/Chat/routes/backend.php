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
                'prefix' => '/chats',
                'as' => 'chats.',
            ],
            function () {
                    Route::get('/', 'ChatController@index')->name('index');
                    Route::get('/patient/{patientId}', 'ChatController@getChatByPatient')->name('getChatByPatient');
            }

            
        );

        Route::group(
            [
                'prefix' => '/messages',
                'as' => 'messages.',
                'controller' => 'MessageController'
            ],
            function () {
                Route::post('/send-message/{chatId}', 'MessageController@store')->name('store');            }
        );
    }
);