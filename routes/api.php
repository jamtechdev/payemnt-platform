<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\AdminPartnerController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminTransactionController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PartnerApiGuideController;
use App\Http\Controllers\Api\V1\ProductDistributionController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\VerifyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::post('/verify', VerifyController::class)->name('verify');
        Route::get('/partner/guide', PartnerApiGuideController::class)->name('partner.guide');

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
        Route::get('/partner/products', [ProductController::class, 'index'])->name('partner.products.index');
        Route::get('/partner/products/{uuid}/fields', [ProductController::class, 'fields'])->name('partner.products.fields');
        Route::get('/partner/products/{uuid}/schema', [ProductController::class, 'schema'])->name('partner.products.schema');
        Route::get('/partner/products', [PartnerProductController::class, 'index'])->name('partner.products.index');
        Route::post('/partner/products', [PartnerProductController::class, 'store'])->name('partner.products.store');
        Route::put('/partner/products/{product_code}', [PartnerProductController::class, 'update'])->name('partner.products.update');
        Route::delete('/partner/products', [PartnerProductController::class, 'destroyByPartner'])->name('partner.products.destroy-by-partner');
        Route::post('/customers/register', [CustomerController::class, 'store'])->name('partner.customers.store');
        Route::put('/customers/{customer_code}', [CustomerController::class, 'update'])->name('partner.customers.update');
        Route::delete('/customers', [CustomerController::class, 'destroy'])->name('partner.customers.destroy');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('partner.transactions.store');
        Route::delete('/transactions', [TransactionController::class, 'destroy'])->name('partner.transactions.destroy');
        Route::get('/products/{product_code}/fields', [ProductDistributionController::class, 'getProductFields'])->name('partner.distribution.fields');
        Route::post('/products/{product_code}/submit', [ProductDistributionController::class, 'submit'])->name('partner.distribution.submit');
        Route::post('/products/{product_code}/transactions/{transaction_number}/kyc', [ProductDistributionController::class, 'submitKyc'])->name('partner.distribution.kyc');
        Route::put('/products/{product_code}/transactions/{transaction_number}', [ProductDistributionController::class, 'updatePolicy'])->name('partner.distribution.update');
        Route::post('/products/{product_code}/transactions/{transaction_number}/cancel', [ProductDistributionController::class, 'cancelPolicy'])->name('partner.distribution.cancel');
        Route::post('/products/{product_code}/transactions/{transaction_number}/callback', [ProductDistributionController::class, 'webhookCallback'])
            ->middleware('verify.webhook.signature')
            ->name('partner.distribution.callback');
        Route::get('/verify-token', [ProductDistributionController::class, 'verifyToken'])->name('partner.distribution.verify-token');
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
        Route::post('/products', [AdminProductController::class, 'store'])->middleware('role:super_admin');
        Route::get('/products/{product}', [AdminProductController::class, 'show'])->middleware('role:super_admin');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->middleware('role:super_admin');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->middleware('role:super_admin');
        Route::get('/transactions', [AdminTransactionController::class, 'index'])->middleware('permission:reports.view');
        Route::patch('/transactions/{transaction}', [AdminTransactionController::class, 'update'])->middleware('permission:reports.view');
        Route::post('/transactions/{transaction}/suspend', [AdminTransactionController::class, 'suspend'])->middleware('permission:reports.view');
        Route::post('/transactions/{transaction}/notes', [AdminTransactionController::class, 'addNote'])->middleware('permission:reports.view');
        Route::apiResource('/users', AdminUserController::class)->middleware('role:super_admin');
    });
