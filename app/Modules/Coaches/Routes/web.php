<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth', 'verified'], 'prefix' => 'manage'], function () {
    Route::resource('coaches', 'CoachesController')
        ->except('show')
        ->names('coaches');
});
