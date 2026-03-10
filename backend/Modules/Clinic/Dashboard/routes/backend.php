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
                // Dashboard Part
                Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
                
                // Vue Dashboard (parallel to Blade)
                Route::get('/vue/dashboard', function () {
                    return view('backend.dashboards.clinic.vue.dashboard');
                })->name('vue.dashboard');

    }
);
