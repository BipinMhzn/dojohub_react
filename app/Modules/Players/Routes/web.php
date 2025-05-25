<?php

use App\Modules\Players\Controllers\PlayersController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => [
        'auth', 'verified'
    ],
    'prefix' => 'manage'
], function () {
    Route::resource('players', 'PlayersController')->except('update')->names('players');
    Route::post('players/{player}', [PlayersController::class, 'update'])->name('players.update');
});

