<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Return the main view for all routes not matched by other routes
// This allows Vue router to handle client-side routing
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
