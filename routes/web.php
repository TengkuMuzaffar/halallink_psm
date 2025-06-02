<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ToyyibPayController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\AWBController;
use App\Http\Controllers\ReportPDFController;
use App\Http\Controllers\InvoiceController;
// Fix: Remove extra slash and add API middleware
Route::get('test/after-payment/{order}', [ToyyibPayController::class, 'createCheckpoints'])
    ->name('test.after.payment');
Route::get('test/deliveries', [App\Http\Controllers\Api\DeliveryController::class, 'index'])
    ->name('test.deliveries');
Route::get('test/deliveries/executes', [App\Http\Controllers\Api\ExecuteDeliveriesController::class, 'index'])
    ->name('test.executes');
Route::get('/awb/{cart}', [AWBController::class, 'generate'])->name('awb.generate');
Route::get('/report-pdf/{reportValidity}', [ReportPDFController::class, 'generate'])->name('report.pdf.generate');
Route::get('/invoice/{order}', [InvoiceController::class, 'generate'])->name('invoice.generate');
// Add 'test' prefix to order routes
Route::prefix('test')->group(function () {
    Route::get('/orders/stats', [OrderController::class, 'getStats']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
});

// Catch-all route for Vue
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
