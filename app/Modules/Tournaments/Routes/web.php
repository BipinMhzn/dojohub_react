<?php

use App\Modules\Tournaments\Controllers\MatchesController;
use App\Modules\Tournaments\Controllers\RegistrationsController;
use App\Modules\Tournaments\Controllers\ResultsController;
use App\Modules\Tournaments\Controllers\TournamentsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['auth', 'verified'],
    'prefix' => 'manage',
], function () {

    // Tournament CRUD
    Route::resource('tournaments', 'TournamentsController')->except('update', 'show')->names('tournaments');
    Route::get('tournaments/{tournament}', [TournamentsController::class, 'show'])->name('tournaments.show');
    Route::post('tournaments/{tournament}', [TournamentsController::class, 'update'])->name('tournaments.update');

    // Nested under a category (categories CRUD lives in TournamentCategories module)
    Route::group(['prefix' => 'tournaments/{tournament}/categories'], function () {
        // Registrations under a category
        Route::get('/{category}/registrations', [RegistrationsController::class, 'index'])->name('tournaments.categories.registrations.index');
        Route::post('/{category}/registrations', [RegistrationsController::class, 'store'])->name('tournaments.categories.registrations.store');
        Route::delete('/{category}/registrations/{registration}', [RegistrationsController::class, 'destroy'])->name('tournaments.categories.registrations.destroy');

        // Matches / Bracket under a category
        Route::get('/{category}/matches', [MatchesController::class, 'index'])->name('tournaments.categories.matches.index');
        Route::post('/{category}/matches/generate', [MatchesController::class, 'generate'])->name('tournaments.categories.matches.generate');
        Route::patch('/{category}/matches/{match}/result', [MatchesController::class, 'updateResult'])->name('tournaments.categories.matches.update-result');
        Route::patch('/{category}/matches/{match}/schedule', [MatchesController::class, 'updateSchedule'])->name('tournaments.categories.matches.update-schedule');

        // Results under a category
        Route::get('/{category}/results', [ResultsController::class, 'index'])->name('tournaments.categories.results.index');
    });
});
