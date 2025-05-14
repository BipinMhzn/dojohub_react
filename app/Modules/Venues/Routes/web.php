<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => [
        'auth', 'verified'
    ],
    'prefix' => 'manage'
], function () {
    Route::apiResource('venues', 'VenuesController')->names('venues')->except('show');
});
