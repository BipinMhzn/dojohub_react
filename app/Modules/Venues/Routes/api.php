<?php


use App\Modules\Venues\Controllers\VenuesController;
use Illuminate\Support\Facades\Route;

Route::get('manage/venues', [VenuesController::class, 'apiIndex'])->middleware(['auth', 'verified']);
