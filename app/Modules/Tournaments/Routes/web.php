<?php

use App\Modules\Tournaments\Controllers\CategoriesController;
use App\Modules\Tournaments\Controllers\MatchesController;
use App\Modules\Tournaments\Controllers\RegistrationsController;
use App\Modules\Tournaments\Controllers\TournamentsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['auth', 'verified'],
    'prefix' => 'manage',
], function () {

    // Tournament CRUD
    Route::resource('tournaments', 'TournamentsController')->except('update', 'show')->names('tournaments');
    Route::post('tournaments/{tournament}', [TournamentsController::class, 'update'])->name('tournaments.update');

    // Tournament Categories
    Route::group(['prefix' => 'tournaments/{tournament}/categories'], function () {
        Route::get('/', [CategoriesController::class, 'index'])->name('tournaments.categories.index');
        Route::get('/create', [CategoriesController::class, 'create'])->name('tournaments.categories.create');
        Route::post('/', [CategoriesController::class, 'store'])->name('tournaments.categories.store');
        Route::get('/{category}/edit', [CategoriesController::class, 'edit'])->name('tournaments.categories.edit');
        Route::put('/{category}', [CategoriesController::class, 'update'])->name('tournaments.categories.update');
        Route::delete('/{category}', [CategoriesController::class, 'destroy'])->name('tournaments.categories.destroy');

        // Registrations under a category
        Route::get('/{category}/registrations', [RegistrationsController::class, 'index'])->name('tournaments.categories.registrations.index');
        Route::post('/{category}/registrations', [RegistrationsController::class, 'store'])->name('tournaments.categories.registrations.store');
        Route::delete('/{category}/registrations/{registration}', [RegistrationsController::class, 'destroy'])->name('tournaments.categories.registrations.destroy');

        // Matches / Bracket under a category
        Route::get('/{category}/matches', [MatchesController::class, 'index'])->name('tournaments.categories.matches.index');
        Route::post('/{category}/matches/generate', [MatchesController::class, 'generate'])->name('tournaments.categories.matches.generate');
        Route::patch('/{category}/matches/{match}/result', [MatchesController::class, 'updateResult'])->name('tournaments.categories.matches.update-result');
        Route::patch('/{category}/matches/{match}/schedule', [MatchesController::class, 'updateSchedule'])->name('tournaments.categories.matches.update-schedule');
    });
});
