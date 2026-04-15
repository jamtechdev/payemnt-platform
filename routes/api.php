<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        // Auth first sequence (guest APIs)
        Route::middleware('guest')->group(function (): void {
            Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
            Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
            Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
            Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
        });

        // Auth protected APIs
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('auth.logout');
    });

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth.partner', 'throttle:partner_api'])
    ->group(function (): void {
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::post('/partner/customers', [CustomerController::class, 'store'])->name('partner.customers.store');
        Route::get('/partner/customers/{uuid}', [CustomerController::class, 'show'])->name('partner.customers.show');
        Route::patch('/partner/customers/{uuid}/status', [CustomerController::class, 'updateStatus'])->name('partner.customers.update-status');
        Route::post('/partner/customers/{uuid}/payments', [CustomerController::class, 'addPayment'])->name('partner.customers.add-payment');
        Route::get('/partner/products', [ProductController::class, 'index'])->name('partner.products.index');
        Route::get('/partner/products/{uuid}/fields', [ProductController::class, 'fields'])->name('partner.products.fields');
        Route::get('/partner/analytics/usage', [CustomerController::class, 'usageAnalytics'])->name('partner.analytics.usage');
    });

Route::prefix('v1')
    ->name('api.v1.ops.')
    ->middleware(['auth:sanctum'])
    ->group(function (): void {
        Route::get('/platform-overview', [AdminController::class, 'platformOverview'])
            ->middleware('permission:dashboard.platform_overview')
            ->name('platform-overview');

        Route::get('/customers', [AdminController::class, 'customers'])
            ->middleware('permission:customers.view_list')
            ->name('customers.index');
        Route::get('/customers/{uuid}', [AdminController::class, 'customer'])
            ->middleware('permission:customers.view_detail')
            ->name('customers.show');
        Route::get('/payments', [AdminController::class, 'payments'])
            ->middleware('permission:customers.view_payment_amount')
            ->name('payments.index');
        Route::get('/payments/{payment}', [AdminController::class, 'payment'])
            ->middleware('permission:customers.view_payment_amount')
            ->name('payments.show');

        Route::get('/reports/customer-acquisition', [AdminController::class, 'customerAcquisitionReport'])
            ->middleware('permission:reports.customer_acquisition')
            ->name('reports.customer-acquisition');
        Route::get('/reports/revenue-by-product', [AdminController::class, 'revenueByProductReport'])
            ->middleware('permission:reports.revenue_by_product')
            ->name('reports.revenue-by-product');

        Route::get('/products', [AdminController::class, 'products'])->middleware('permission:products.view')->name('products.index');
        Route::post('/products', [AdminController::class, 'storeProduct'])->middleware('permission:products.create')->name('products.store');
        Route::patch('/products/{product}', [AdminController::class, 'updateProduct'])->middleware('permission:products.edit')->name('products.update');
        Route::delete('/products/{product}', [AdminController::class, 'deleteProduct'])->middleware('permission:products.delete')->name('products.delete');
        Route::get('/products/{product}/versions', [AdminController::class, 'productVersions'])->middleware('permission:products.view')->name('products.versions');

        Route::get('/partners', [AdminController::class, 'partners'])->middleware('permission:partners.view')->name('partners.index');
        Route::post('/partners', [AdminController::class, 'storePartner'])->middleware('permission:partners.create')->name('partners.store');
        Route::patch('/partners/{partner}', [AdminController::class, 'updatePartner'])->middleware('permission:partners.edit')->name('partners.update');
        Route::delete('/partners/{partner}', [AdminController::class, 'deletePartner'])->middleware('permission:partners.delete')->name('partners.delete');
        Route::patch('/partners/{partner}/products/{product}/access', [AdminController::class, 'updatePartnerProductAccess'])->middleware('permission:products.manage_partner_access')->name('partners.products.access');

        Route::get('/users', [AdminController::class, 'users'])->middleware('permission:users.view')->name('users.index');
        Route::post('/users', [AdminController::class, 'storeUser'])->middleware('permission:users.create')->name('users.store');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->middleware('permission:users.edit')->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->middleware('permission:users.deactivate')->name('users.delete');
        Route::get('/access-control', [AdminController::class, 'accessControl'])->middleware('permission:users.assign_roles')->name('access-control.index');
        Route::patch('/users/{user}/access-control', [AdminController::class, 'updateUserAccessControl'])->middleware('permission:users.assign_roles')->name('users.access-control.update');

        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->middleware('permission:audit_logs.view')->name('audit-logs.index');
        Route::get('/settings', [AdminController::class, 'settings'])->middleware('permission:settings.system')->name('settings.index');
        Route::patch('/settings', [AdminController::class, 'updateSettings'])->middleware('permission:settings.system')->name('settings.update');
    });
    