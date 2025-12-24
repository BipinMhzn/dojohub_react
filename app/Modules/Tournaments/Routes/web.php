<?php

use App\Modules\Tournaments\Controllers\MatchesController;
use App\Modules\Tournaments\Controllers\RegistrationsController;
use App\Modules\Tournaments\Controllers\TournamentsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => [
        'auth', 'verified'
    ],
    'prefix' => 'manage'
], function () {
    Route::resource('tournaments', 'TournamentsController')->except('update', 'show')->names('tournaments');
    Route::post('tournaments/{tournament}', [TournamentsController::class, 'update'])->name('tournaments.update');

    Route::group(['prefix' => 'tournaments/{tournament}'], function () {
       Route::get('registrations', [RegistrationsController::class, 'index'])->name('tournaments.registrations.index');
       Route::post('registrations', [RegistrationsController::class, 'store'])->name('tournaments.registrations.store');
       Route::delete('registrations/{registration}', [RegistrationsController::class, 'destroy'])->name('tournaments.registrations.destroy');

       Route::get('matches', [MatchesController::class, 'index'])->name('tournaments.matches.index');
       Route::post('matches/generate', [MatchesController::class, 'generate'])->name('tournaments.matches.generate');
       Route::patch('matches/{match}/result', [MatchesController::class, 'updateResult'])->name('tournaments.matches.update-result');
       Route::patch('matches/{match}/schedule', [MatchesController::class, 'updateSchedule'])->name('tournaments.matches.update-schedule');

    });
});

