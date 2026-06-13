<?php

use App\Modules\Tournaments\Controllers\MatchesController;
use App\Modules\Tournaments\Controllers\RegistrationCoachesController;
use App\Modules\Tournaments\Controllers\RegistrationPaymentsController;
use App\Modules\Tournaments\Controllers\RegistrationsController;
use App\Modules\Tournaments\Controllers\ResultsController;
use App\Modules\Tournaments\Controllers\TournamentsController;
use App\Modules\Tournaments\Controllers\WeighInsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['auth', 'verified'],
    'prefix' => 'manage',
], function () {

    // Tournament CRUD
    Route::resource('tournaments', 'TournamentsController')->except('update', 'show')->names('tournaments');
    Route::get('tournaments/{tournament}', [TournamentsController::class, 'show'])->name('tournaments.show');
    Route::post('tournaments/{tournament}', [TournamentsController::class, 'update'])->name('tournaments.update');
    Route::post('tournaments/{tournament}/cancel', [TournamentsController::class, 'cancel'])->name('tournaments.cancel');

    // Nested under a category (categories CRUD lives in TournamentCategories module)
    Route::group(['prefix' => 'tournaments/{tournament}/categories'], function () {
        // Registrations under a category
        Route::get('/{category}/registrations', [RegistrationsController::class, 'index'])->name('tournaments.categories.registrations.index');
        Route::post('/{category}/registrations', [RegistrationsController::class, 'store'])->name('tournaments.categories.registrations.store');
        Route::delete('/{category}/registrations/{registration}', [RegistrationsController::class, 'destroy'])->name('tournaments.categories.registrations.destroy');
        Route::post('/{category}/registrations/{registration}/withdraw', [RegistrationsController::class, 'withdraw'])->name('tournaments.categories.registrations.withdraw');

        // Coaches assigned to a registration
        Route::post('/{category}/registrations/{registration}/coaches', [RegistrationCoachesController::class, 'store'])->name('tournaments.categories.registrations.coaches.store');
        Route::delete('/{category}/registrations/{registration}/coaches/{coach}', [RegistrationCoachesController::class, 'destroy'])->name('tournaments.categories.registrations.coaches.destroy');

        // Payments recorded against a registration
        Route::post('/{category}/registrations/{registration}/payments', [RegistrationPaymentsController::class, 'store'])->name('tournaments.categories.registrations.payments.store');
        Route::delete('/{category}/registrations/{registration}/payments/{payment}', [RegistrationPaymentsController::class, 'destroy'])->name('tournaments.categories.registrations.payments.destroy');

        // Matches / Bracket under a category
        Route::get('/{category}/matches', [MatchesController::class, 'index'])->name('tournaments.categories.matches.index');
        Route::post('/{category}/matches/generate', [MatchesController::class, 'generate'])->name('tournaments.categories.matches.generate');
        Route::patch('/{category}/matches/{match}/result', [MatchesController::class, 'updateResult'])->name('tournaments.categories.matches.update-result');
        Route::patch('/{category}/matches/{match}/schedule', [MatchesController::class, 'updateSchedule'])->name('tournaments.categories.matches.update-schedule');

        // Weigh-ins under a category (recorded inline from the registrations page)
        Route::post('/{category}/weigh-ins', [WeighInsController::class, 'store'])->name('tournaments.categories.weigh-ins.store');

        // Results under a category
        Route::get('/{category}/results', [ResultsController::class, 'index'])->name('tournaments.categories.results.index');
    });
});
