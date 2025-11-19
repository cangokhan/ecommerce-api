<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProductController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Admin only routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('products', ProductController::class);
    });

    // Store only routes
    Route::middleware('role:store')->prefix('store')->group(function () {
        // Store routes will be added here
    });

    // User only routes
    Route::middleware('role:user')->prefix('user')->group(function () {
        // User routes will be added here
    });

    // Admin and Store routes
    Route::middleware('role:admin,store')->prefix('admin-store')->group(function () {
        // Admin and Store routes will be added here
    });
});

