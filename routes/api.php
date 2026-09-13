<?php

use App\Http\Controllers\TripayCallbackController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/callback', TripayCallbackController::class)->name('tripay.callback');
