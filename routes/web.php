<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ToyyibPayController;

// Fix: Remove extra slash and add API middleware
Route::get('test/after-payment/{order}', [ToyyibPayController::class, 'createCheckpoints'])
    ->name('test.after.payment');

// Catch-all route for Vue
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
