<?php

use App\Modules\TournamentCategories\Controllers\CategoryTransitionController;
use App\Modules\TournamentCategories\Controllers\TournamentCategoriesController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['auth', 'verified'],
    'prefix' => 'manage',
], function () {
    Route::group(['prefix' => 'tournaments/{tournament}/categories'], function () {
        Route::get('/', [TournamentCategoriesController::class, 'index'])->name('tournaments.categories.index');
        Route::get('/options', [TournamentCategoriesController::class, 'options'])->name('tournaments.categories.options');
        Route::get('/create', [TournamentCategoriesController::class, 'create'])->name('tournaments.categories.create');
        Route::post('/', [TournamentCategoriesController::class, 'store'])->name('tournaments.categories.store');
        Route::get('/{category}/edit', [TournamentCategoriesController::class, 'edit'])->name('tournaments.categories.edit');
        Route::put('/{category}', [TournamentCategoriesController::class, 'update'])->name('tournaments.categories.update');
        Route::delete('/{category}', [TournamentCategoriesController::class, 'destroy'])->name('tournaments.categories.destroy');
        Route::post('/{category}/transition', [CategoryTransitionController::class, 'transition'])->name('tournaments.categories.transition');
    });
});
