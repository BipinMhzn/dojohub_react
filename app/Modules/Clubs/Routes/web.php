<?php

use App\Modules\Clubs\Controllers\ClubPlayersController;
use App\Modules\Clubs\Controllers\ClubsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => [
        'auth', 'verified',
    ],
    'prefix' => 'manage',
], function () {
    Route::resource('clubs', 'ClubsController')->except('update')->names('clubs');
    Route::post('clubs/{club}', [ClubsController::class, 'update'])->name('clubs.update');

    Route::prefix('clubs/{club}/players')->name('clubs.players.')->group(function () {
        Route::post('/', [ClubPlayersController::class, 'store'])->name('store');
        Route::put('/{membership}', [ClubPlayersController::class, 'update'])->name('update');
        Route::patch('/{membership}', [ClubPlayersController::class, 'patch'])->name('patch');
    });
});
