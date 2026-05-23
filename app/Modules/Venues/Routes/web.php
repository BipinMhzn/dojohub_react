<?php

use App\Modules\Venues\Controllers\VenueMatController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth', 'verified'], 'prefix' => 'manage'], function () {
    Route::resource('venues', 'VenuesController')->names('venues');

    Route::prefix('venues/{venue}/mats')->name('venues.mats.')->group(function () {
        Route::get('/', [VenueMatController::class, 'index'])->name('index');
        Route::post('/', 'VenueMatController@store')->name('store');
        Route::put('/{mat}', 'VenueMatController@update')->name('update');
        Route::delete('/{mat}', 'VenueMatController@destroy')->name('destroy');
    });
});
