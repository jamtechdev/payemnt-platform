<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\AdminPartnerController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\SwapOfferController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\PartnerCustomerController;
use App\Http\Controllers\Api\V1\PartnerProductController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::post('/verify', VerifyController::class)->name('verify');

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
        // Route::post('/customers', [PartnerCustomerController::class, 'store'])->name('customers.store');
        // Route::post('/purchase', [PurchaseController::class, 'store'])->name('purchase.store');
        // Route::get('/partner/products', [ProductController::class, 'index'])->name('partner.products.index');
        // Route::get('/partner/products/{uuid}/fields', [ProductController::class, 'fields'])->name('partner.products.fields');
        Route::post('/partner/products', [PartnerProductController::class, 'store'])->name('partner.products.store');
        Route::put('/partner/products/{product_code}', [PartnerProductController::class, 'update'])->name('partner.products.update');
        Route::delete('/partner/products', [PartnerProductController::class, 'destroyByPartner'])->name('partner.products.destroy-by-partner');
        Route::post('/customers/register', [CustomerController::class, 'store'])->name('partner.customers.store');
        Route::put('/customers/{customer_code}', [CustomerController::class, 'update'])->name('partner.customers.update');
        Route::delete('/customers', [CustomerController::class, 'destroy'])->name('partner.customers.destroy');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('partner.transactions.store');
        Route::delete('/transactions', [TransactionController::class, 'destroy'])->name('partner.transactions.destroy');
        Route::post('/swap-offers', [SwapOfferController::class, 'store'])->name('partner.swap-offers.store');
        Route::delete('/swap-offers', [SwapOfferController::class, 'destroy'])->name('partner.swap-offers.destroy');

        Route::post('/occupations', [LookupController::class, 'occupationStore'])->name('partner.occupations.store');
        Route::delete('/occupations', [LookupController::class, 'occupationDestroy'])->name('partner.occupations.destroy');

        Route::post('/relationships', [LookupController::class, 'relationshipStore'])->name('partner.relationships.store');
        Route::delete('/relationships', [LookupController::class, 'relationshipDestroy'])->name('partner.relationships.destroy');

        Route::post('/task-types', [LookupController::class, 'taskTypeStore'])->name('partner.task-types.store');
        Route::delete('/task-types', [LookupController::class, 'taskTypeDestroy'])->name('partner.task-types.destroy');
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
        Route::get('/products', [AdminProductController::class, 'index'])->middleware('role:super_admin');
        Route::get('/products/{product}', [AdminProductController::class, 'show'])->middleware('role:super_admin');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->middleware('role:super_admin');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->middleware('role:super_admin');
        Route::apiResource('/users', AdminUserController::class)->middleware('role:super_admin');
    });