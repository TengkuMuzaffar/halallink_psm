<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\PasswordController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // Added register route
// Password routes
Route::post('/password/forgot', [PasswordController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [PasswordController::class, 'resetPassword']);
Route::post('/password/validate-token', [PasswordController::class, 'validateToken']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Replace the inline function with the controller method
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/password/change', [PasswordController::class, 'changePassword']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [App\Http\Controllers\Api\ProfileController::class, 'getProfile']);
    Route::post('/profile/update', [App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
    Route::post('/profile/password', [App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
    Route::post('/profile/locations', [App\Http\Controllers\Api\ProfileController::class, 'manageLocations']);
    // Admin routes
    Route::middleware('role.company:admin')->group(function () {
        // Dashboard data
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
        
        // Employee management routes with company type checks
        Route::middleware('role.company:admin,admin')->group(function () {
            Route::get('/employees/all', [EmployeeController::class, 'getAllEmployees']);
            // Company routes with middleware
            Route::get('/companies', [CompanyController::class, 'index']);
            Route::get('/companies/stats', [CompanyController::class, 'getStats']);
            Route::post('/companies', [CompanyController::class, 'store']);
            Route::get('/companies/{id}', [CompanyController::class, 'show']);
            Route::put('/companies/{id}', [CompanyController::class, 'update']);
            Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);

            Route::apiResource('poultries', \App\Http\Controllers\Api\PoultryController::class);

        });
        
        // Company-specific routes
        Route::middleware('role.company:admin,broiler')->group(function () {
            Route::get('/employees/broiler', [EmployeeController::class, 'getBroilerEmployees']);
        });
        
        Route::middleware('role.company:admin,slaughterhouse')->group(function () {
            Route::get('/employees/slaughterhouse', [EmployeeController::class, 'getSlaughterhouseEmployees']);
        });
        
        Route::middleware('role.company:admin,SME')->group(function () {
            Route::get('/employees/sme', [EmployeeController::class, 'getSMEEmployees']);
        });
        
        Route::middleware('role.company:admin,logistic')->group(function () {
            Route::get('/employees/logistic', [EmployeeController::class, 'getLogisticEmployees']);
        });
    });
});




