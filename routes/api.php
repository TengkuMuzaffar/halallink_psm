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
Route::post('/register-employee', [AuthController::class, 'registerEmployee']); // New employee registration route
// Route::get('/companies/{formID}', [CompanyController::class, 'getByFormID']); // New route to get company by formID
// Password routes
Route::post('/password/forgot', [PasswordController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [PasswordController::class, 'resetPassword']);
Route::post('/password/validate-token', [PasswordController::class, 'validateToken']);

// Protected routes
// Inside the protected routes middleware group
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
        
        // Employee management routes for company admins
        Route::apiResource('employees', EmployeeController::class);
        Route::patch('/employees/{id}/status', [EmployeeController::class, 'updateStatus']);
        
        // Super admin routes (admin of admin company)
        Route::middleware('role.company:admin,admin')->group(function () {
            // Company routes with middleware
            Route::get('/companies', [CompanyController::class, 'index']);
            Route::get('/companies/all/stats', [CompanyController::class, 'getStats']);
            Route::post('/companies', [CompanyController::class, 'store']);
            Route::get('/companies/{id}', [CompanyController::class, 'show']);
            Route::put('/companies/{id}', [CompanyController::class, 'update']);
            Route::patch('/companies/{id}/status', [CompanyController::class, 'updateStatus']);
            Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);

            Route::apiResource('poultries', \App\Http\Controllers\Api\PoultryController::class);
            
            // Keep this for backward compatibility or admin overview
            Route::get('/employees/all', [EmployeeController::class, 'getAllEmployees']);
        });
        
        // Remove the company-specific employee routes since they're no longer needed
        // Each company admin will only see their own employees through the main employee routes
    });
});




