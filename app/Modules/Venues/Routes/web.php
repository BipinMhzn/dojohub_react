<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => [
        'auth', 'verified'
    ],
    'prefix' => 'manage'
], function () {
    Route::resource('venues', 'VenuesController')->names('venues')->except('show');
});
