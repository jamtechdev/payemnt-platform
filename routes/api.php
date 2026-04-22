<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\AdminPartnerController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PartnerCustomerController;
use App\Http\Controllers\Api\V1\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        // Lightweight verify endpoint - accepts partner Bearer token
        Route::get('/verify', function () {
            return response()->json(['success' => true, 'message' => 'Authenticated.']);
        })->middleware('auth.partner')->name('verify');

        Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
        Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'throttle:60,1']);
    });

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth.partner', 'throttle:partner_api', 'audit.api'])
    ->group(function (): void {
        Route::post('/customers', [PartnerCustomerController::class, 'store'])->name('customers.store');
        Route::post('/purchase', [PurchaseController::class, 'store'])->name('purchase.store');
    });

Route::prefix('v1/admin')
    ->name('api.v1.admin.')
    ->middleware(['auth:sanctum', 'audit.api'])
    ->group(function (): void {
        Route::get('/customers', [AdminCustomerController::class, 'index'])->middleware('permission:customers.view_list');
        Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->middleware('permission:customers.view_detail');

        Route::get('/analytics/summary', [AdminAnalyticsController::class, 'summary'])->middleware('permission:reports.view');
        Route::get('/analytics/export', [AdminAnalyticsController::class, 'export'])->middleware('permission:reports.export');
        Route::get('/analytics/export/{jobId}/status', [AdminAnalyticsController::class, 'exportStatus'])->middleware('permission:reports.export');
        Route::get('/analytics/export/{jobId}/download', [AdminAnalyticsController::class, 'exportDownload'])->middleware('permission:reports.export');

        Route::apiResource('/partners', AdminPartnerController::class)->middleware('role:super_admin');
        Route::patch('/partners/{partner}/products/{product}/access', [AdminPartnerController::class, 'updateProductAccess'])->middleware('role:super_admin');
        Route::apiResource('/products', AdminProductController::class)->middleware('role:super_admin');
        Route::apiResource('/users', AdminUserController::class)->middleware('role:super_admin');
    });