<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\AdminPartnerController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PartnerCustomerController;
use App\Http\Controllers\Api\V1\PartnerProductController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        // Lightweight verify endpoint - accepts partner Bearer token
        Route::get('/verify', function (\Illuminate\Http\Request $request) {
            $bearer = $request->bearerToken();
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($bearer);
            if (!$token) {
                return response()->json(['success' => false, 'debug' => 'token_not_found', 'bearer_received' => $bearer ? substr($bearer, 0, 20).'...' : 'none'], 401);
            }
            if ($token->tokenable_type !== \App\Models\Partner::class) {
                return response()->json(['success' => false, 'debug' => 'wrong_tokenable_type', 'type' => $token->tokenable_type], 401);
            }
            $partner = \App\Models\Partner::find($token->tokenable_id);
            if (!$partner) {
                return response()->json(['success' => false, 'debug' => 'partner_not_found'], 401);
            }
            if ($partner->status !== 'active') {
                return response()->json(['success' => false, 'debug' => 'partner_inactive', 'status' => $partner->status], 401);
            }
            return response()->json(['success' => true, 'message' => 'Authenticated.', 'partner' => $partner->name]);
        })->name('verify');

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
        Route::get('/partner/products', [ProductController::class, 'index'])->name('partner.products.index');
        Route::get('/partner/products/{uuid}/fields', [ProductController::class, 'fields'])->name('partner.products.fields');
        Route::post('/partner/products/create', [PartnerProductController::class, 'store'])->name('partner.products.store');
        Route::put('/partner/products/{product_code}', [PartnerProductController::class, 'update'])->name('partner.products.update');
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