<?php

use App\Modules\Clubs\Controllers\ClubsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => [
        'auth', 'verified'
    ],
    'prefix' => 'manage'
], function () {
    Route::resource('clubs', 'ClubsController')->except('update')->names('clubs');
    Route::post('clubs/{club}', [ClubsController::class, 'update'])->name('clubs.update');
});

