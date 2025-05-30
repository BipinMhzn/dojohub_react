<?php

use App\Modules\Tournaments\Controllers\TournamentsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => [
        'auth', 'verified'
    ],
    'prefix' => 'manage'
], function () {
    Route::resource('tournaments', 'TournamentsController')->except('update')->names('tournaments');
    Route::post('tournaments/{tournament}', [TournamentsController::class, 'update'])->name('tournaments.update');

    Route::resource('tournaments/{tournament}/registrations', 'RegistrationsController')->except('show')->names('tournaments.registrations');
});

