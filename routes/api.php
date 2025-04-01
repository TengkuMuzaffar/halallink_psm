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

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // Added register route
Route::post('/register-employee', [AuthController::class, 'registerEmployee']); // New employee registration route
Route::get('/companies/form/{formID}', [CompanyController::class, 'getByFormID']);

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
    
    // Email verification routes
    Route::get('/email/verification-status', [EmailVerificationController::class, 'checkVerificationStatus']);
    Route::post('/email/send-verification', [EmailVerificationController::class, 'sendVerificationEmail']);
    
    // Profile routes
    Route::get('/profile', [App\Http\Controllers\Api\ProfileController::class, 'getProfile']);
    Route::post('/profile/update', [App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
    Route::post('/profile/password', [App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
    Route::post('/profile/locations', [App\Http\Controllers\Api\ProfileController::class, 'manageLocations']);
    // Poultry routes
    Route::apiResource('poultries', \App\Http\Controllers\Api\PoultryController::class);
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
    // Admin role only routes - regardless of company type
    Route::middleware('role:admin')->group(function () {
        // Employee management routes
        Route::get('/employees/all', [EmployeeController::class, 'getAllEmployees']);
        Route::apiResource('employees', EmployeeController::class);
        Route::patch('/employees/{id}/status', [EmployeeController::class, 'updateStatus']);
    });
    
  
});




