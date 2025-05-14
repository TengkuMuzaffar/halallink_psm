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
use App\Http\Controllers\Api\OrderController; // Add this line

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // Added register route
Route::post('/register-employee', [AuthController::class, 'registerEmployee']); // New employee registration route
Route::get('/companies/form/{formID}', [CompanyController::class, 'getByFormID']);

// Public poultry routes - only index/read is public
Route::get('/poultries', [PoultryController::class, 'index']);
Route::get('/poultries/{poultry}', [PoultryController::class, 'show']);

// Payment routes
Route::get('/payment/status', [ToyyibPayController::class, 'paymentStatus'])->name('payment.status');
Route::post('/payment/callback', [ToyyibPayController::class, 'callBack'])->name('payment.callback');

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
    Route::get('/payment/verify', [PaymentController::class, 'verifyPayment']);
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
    // If 'admin' company type should be the only one, it remains as is.
    // If it needs to be admin OR another_type, it would be: Route::middleware('role.company:both,admin,another_type')
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
    
    // Broiler company type routes
    Route::middleware('role.company:both,broiler')->group(function () {
        // Specific routes should come before wildcard routes
        Route::get('/items/stats', [ItemController::class, 'getItemStats']);
        Route::get('/items/company/locations', [ItemController::class, 'getCompanyLocations']);
        Route::get('/items/slaughterhouse/locations', [ItemController::class, 'getSlaughterhouseLocations']);
        
        // General item routes
        Route::get('/items', [ItemController::class, 'index']);
        Route::post('/items', [ItemController::class, 'store']);
        Route::get('/items/{id}', [ItemController::class, 'show']);
        Route::post('/items/{id}', [ItemController::class, 'update']);
        Route::delete('/items/{id}', [ItemController::class, 'destroy']);
    });

 
    Route::middleware('role.company:both,sme,broiler')->group(function () {
        Route::get('/orders/stats', [OrderController::class, 'getStats']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::put('/orders/{order}', [OrderController::class, 'update']);

    });


    // SME company type routes
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
        
        // Delivery routes
        Route::get('/deliveries/trips', [App\Http\Controllers\Api\DeliveryController::class, 'index']);
        Route::get('/deliveries/created', [App\Http\Controllers\Api\DeliveryController::class, 'getCreatedDeliveries']);
        Route::get('/deliveries/stats', [App\Http\Controllers\Api\DeliveryController::class, 'getDeliveryStats']);
        Route::get('/deliveries/{deliveryID}', [App\Http\Controllers\Api\DeliveryController::class, 'getDeliveryDetails']);
        Route::post('/deliveries/create', [App\Http\Controllers\Api\DeliveryController::class, 'createDelivery']);
        Route::post('/deliveries/assign', [App\Http\Controllers\Api\DeliveryController::class, 'assignDelivery']);
        Route::put('/deliveries/{deliveryID}/status', [App\Http\Controllers\Api\DeliveryController::class, 'updateDeliveryStatus']);
        
        // Add the new route for execution deliveries
        Route::get('/deliveries/get/execution', [App\Http\Controllers\Api\ExecuteDeliveriesController::class, 'index']);
        
        // Add new route for starting a delivery
        Route::post('/deliveries/{deliveryID}/start', [App\Http\Controllers\Api\ExecuteDeliveriesController::class, 'startDelivery']);
        
        // Add new route for QR code processing
        Route::post('/qrcode/process', [App\Http\Controllers\Api\QRcodeController::class, 'processQRCode']);
        // Verification routes
        Route::get('/verifications', [App\Http\Controllers\Api\VerifyController::class, 'index']);
        Route::get('/verifications/{verifyID}', [App\Http\Controllers\Api\VerifyController::class, 'show']);
        Route::post('/verifications/{verifyID}', [App\Http\Controllers\Api\VerifyController::class, 'update']);
        Route::post('/deliveries/{deliveryID}/complete-verification', [App\Http\Controllers\Api\VerifyController::class, 'completeVerification']);
            
       
       
        // QR code processing route - support both GET and POST
        Route::match(['get', 'post'], '/qrcode/process/{locationID}/{companyID}', [App\Http\Controllers\Api\QRcodeController::class, 'processQRCode']);
        // Route::get('/qrcode/process/{orderID}/{locationID}', [App\Http\Controllers\Api\QRcodeController::class, 'processQRCode']);
        // Location routes for delivery
        Route::get('/locations', [App\Http\Controllers\Api\LocationController::class, 'index']);
        Route::get('/users/get/drivers', [App\Http\Controllers\Api\DeliveryController::class, 'getDrivers']);
        Route::get('/deliveries/get/vehicles', [App\Http\Controllers\Api\DeliveryController::class, 'getVehicles']);
    });



    // Admin role only routes - regardless of company type
    Route::middleware('role:admin')->group(function () {
        // Employee management routes
        Route::get('/employees/all/stats', [EmployeeController::class, 'getAllEmployeeStats']);
        Route::apiResource('employees', EmployeeController::class);
        Route::patch('/employees/{id}/status', [EmployeeController::class, 'updateStatus']);
    });
}); // Ensure this is the closing bracket for Route::middleware('auth:sanctum')->group








