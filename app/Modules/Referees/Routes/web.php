<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth', 'verified'], 'prefix' => 'manage'], function () {
    Route::resource('referees', 'RefereesController')
        ->except('show')
        ->names('referees');
});
