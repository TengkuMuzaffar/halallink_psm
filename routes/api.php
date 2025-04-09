<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\PoultryController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\ToyyibPayController;
use App\Http\Controllers\Api\PaymentController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // Added register route
Route::post('/register-employee', [AuthController::class, 'registerEmployee']); // New employee registration route
Route::get('/companies/form/{formID}', [CompanyController::class, 'getByFormID']);

// Public poultry routes - only index/read is public
Route::get('/poultries', [PoultryController::class, 'index']);
Route::get('/poultries/{poultry}', [PoultryController::class, 'show']);



Route::get('/payment/status', [ToyyibPayController::class, 'paymentStatus'])->name('payment.status');
Route::post('/payment/callback', [ToyyibPayController::class, 'callBack'])->name('payment.callback');
// Payment routes
Route::get('/payment/verify', [PaymentController::class, 'verifyPayment']);

// Password routes
Route::post('/password/forgot', [PasswordController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [PasswordController::class, 'resetPassword']);
Route::post('/password/validate-token', [PasswordController::class, 'validateToken']);

// Email verification public route
Route::post('/email/verify', [EmailVerificationController::class, 'verifyEmail']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Common routes for all authenticated users
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/password/change', [PasswordController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // ToyyibPay routes
    Route::post('/payment/create', [\App\Http\Controllers\Api\ToyyibPayController::class, 'createBill'])->name('payment.create');
   
   
    
    // Email verification routes
    Route::get('/email/verification-status', [EmailVerificationController::class, 'checkVerificationStatus']);
    Route::post('/email/send-verification', [EmailVerificationController::class, 'sendVerificationEmail']);
    
    // Profile routes
    Route::get('/profile', [App\Http\Controllers\Api\ProfileController::class, 'getProfile']);
    Route::post('/profile/update', [App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
    Route::post('/profile/password', [App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
    Route::post('/profile/locations', [App\Http\Controllers\Api\ProfileController::class, 'manageLocations']);
    
    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    // Admin company type routes - accessible by both admin and employee roles
    Route::middleware('role.company:both,admin')->group(function () {
        // Company routes
        Route::get('/companies', [CompanyController::class, 'index']);
        Route::get('/companies/all/stats', [CompanyController::class, 'getStats']);
        Route::post('/companies', [CompanyController::class, 'store']);
        Route::get('/companies/{id}', [CompanyController::class, 'show']);
        Route::put('/companies/{id}', [CompanyController::class, 'update']);
        Route::patch('/companies/{id}/status', [CompanyController::class, 'updateStatus']);
        Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
        
        // Protected poultry routes - only admin company can create, update, delete
        Route::post('/poultries', [PoultryController::class, 'store']);
        Route::put('/poultries/{poultry}', [PoultryController::class, 'update']);
        Route::patch('/poultries/{poultry}', [PoultryController::class, 'update']);
        Route::delete('/poultries/{poultry}', [PoultryController::class, 'destroy']);
        // Add this to your existing poultry routes
        Route::get('/poultries/all/stats', [PoultryController::class, 'getStats']);
    });
    
    Route::middleware('role.company:both,broiler')->group(function () {
        // Specific routes should come before wildcard routes
        Route::get('/items/stats', [ItemController::class, 'getItemStats']);
        Route::get('/items/locations', [ItemController::class, 'getCompanyLocations']);
        
        // General item routes
        Route::get('/items', [ItemController::class, 'index']);
        Route::post('/items', [ItemController::class, 'store']);
        Route::get('/items/{id}', [ItemController::class, 'show']);
        Route::post('/items/{id}', [ItemController::class, 'update']);
        Route::delete('/items/{id}', [ItemController::class, 'destroy']);
    });

    Route::middleware('role.company:both,sme')->group(function () {
        // Add these routes if they don't exist
        Route::get('/marketplace/items', [MarketplaceController::class, 'getItems']);
        Route::get('/marketplace/items/poultry-types', [MarketplaceController::class, 'getPoultryTypes']);
        
        // Cart routes
        Route::get('/cart/items', [App\Http\Controllers\Api\CartController::class, 'getCartItems']);
        Route::post('/cart/add', [App\Http\Controllers\Api\CartController::class, 'addToCart']);
        Route::put('/cart/update', [App\Http\Controllers\Api\CartController::class, 'updateCartItem']);
        Route::delete('/cart/remove/{cartID}', [App\Http\Controllers\Api\CartController::class, 'removeCartItem']);
        Route::delete('/cart/clear', [App\Http\Controllers\Api\CartController::class, 'clearCart']);
    });
    
    Route::middleware('role.company:both,logistic')->group(function () {
        // Vehicle routes
        Route::get('/vehicles/companies', [App\Http\Controllers\Api\VehicleController::class, 'getCompanies']);
        Route::apiResource('vehicles', App\Http\Controllers\Api\VehicleController::class);
    });



    // Admin role only routes - regardless of company type
    Route::middleware('role:admin')->group(function () {
        // Employee management routes
        Route::get('/employees/all/stats', [EmployeeController::class, 'getAllEmployeeStats']);
        Route::apiResource('employees', EmployeeController::class);
        Route::patch('/employees/{id}/status', [EmployeeController::class, 'updateStatus']);
    });
});





