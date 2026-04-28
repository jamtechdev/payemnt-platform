<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\PersonalAccessTokenController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ConnectArticleController;
use App\Http\Controllers\Admin\ConnectCategoryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\AppResourceController;
use App\Http\Controllers\Admin\SwapOfferController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.platform.dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/forgot-password', [AdminPasswordController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AdminPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AdminPasswordController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AdminPasswordController::class, 'resetPassword'])->name('password.store');
});

Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'session.timeout'])
    ->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'superAdminDashboard'])->middleware('role:super_admin')->name('platform.dashboard');
        Route::get('/customer-service/dashboard', [DashboardController::class, 'customerServiceDashboard'])->middleware('role:customer_service|super_admin')->name('cs.dashboard');
        Route::get('/reports', [DashboardController::class, 'reconciliationDashboard'])->middleware('role:reconciliation_admin|super_admin')->name('reports.dashboard');
        Route::get('/reports/customer-acquisition', [ReportController::class, 'customerAcquisition'])->middleware('role:reconciliation_admin|super_admin')->name('reports.customer-acquisition');
        Route::get('/reports/revenue', [ReportController::class, 'revenueByProduct'])->middleware('role:reconciliation_admin|super_admin')->name('reports.revenue');
        Route::get('/customers', [CustomerController::class, 'index'])->middleware('role:customer_service|super_admin')->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('role:customer_service|super_admin')->name('customers.show');
        Route::post('/customers/export', [CustomerController::class, 'export'])->middleware(['role:customer_service|super_admin', 'permission:customers.export'])->name('customers.export');
        Route::get('/customers/export/{jobId}/download', [CustomerController::class, 'downloadExport'])->middleware(['role:customer_service|super_admin', 'permission:customers.export'])->name('customers.download-export');
        Route::get('/customers/export/expiring', [CustomerController::class, 'exportExpiring'])->middleware(['role:customer_service|super_admin', 'permission:customers.export'])->name('customers.export-expiring');

        Route::prefix('/super-admin')->middleware('role:super_admin')->group(function (): void {
            Route::get('/products', [ProductController::class, 'index'])->name('products.index');
            Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
            Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
            Route::get('/swap-offers', [SwapOfferController::class, 'index'])->name('swap-offers.index');
            Route::get('/swap-offers/{swapOffer}', [SwapOfferController::class, 'show'])->name('swap-offers.show');
            Route::get('/app-resources/task-types', [AppResourceController::class, 'taskTypes'])->name('app-resources.task-types');
            Route::get('/app-resources/occupations', [AppResourceController::class, 'occupations'])->name('app-resources.occupations');
            Route::get('/app-resources/relationships', [AppResourceController::class, 'relationships'])->name('app-resources.relationships');
            Route::get('/connect-categories', [ConnectCategoryController::class, 'index'])->name('connect-categories.index');
            Route::get('/connect-categories/{connectCategory}', [ConnectCategoryController::class, 'show'])->name('connect-categories.show');
            Route::get('/connect-articles', [ConnectArticleController::class, 'index'])->name('connect-articles.index');
            Route::get('/connect-articles/{connectArticle}', [ConnectArticleController::class, 'show'])->name('connect-articles.show');
            Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
            Route::get('/faqs/{faq}', [FaqController::class, 'show'])->name('faqs.show');
            Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

            Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
            Route::get('/partners/create', [PartnerController::class, 'create'])->name('partners.create');
            Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
            Route::get('/partners/{partner}', [PartnerController::class, 'show'])->name('partners.show');
            Route::get('/partners/{partner}/edit', [PartnerController::class, 'edit'])->name('partners.edit');
            Route::patch('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
            Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
            Route::post('/partners/{partner}/toggle-status', [PartnerController::class, 'toggleStatus'])->name('partners.toggle-status');
            Route::post('/partners/{partner}/generate-api-key', [PartnerController::class, 'generateApiKey'])->name('partners.generate-api-key');
            Route::delete('/partners/{partner}/revoke-api-key', [PartnerController::class, 'revokeApiKey'])->name('partners.revoke-api-key');
            Route::post('/partners/{partner}/toggle-product-access', [PartnerController::class, 'toggleProductAccess'])->name('partners.toggle-product-access');
            Route::get('/partners/performance', [ReportController::class, 'partnerPerformance'])->name('partners.performance');
            Route::post('/partners/{id}/restore', [PartnerController::class, 'restore'])->name('partners.restore');

            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/access-control', [UserManagementController::class, 'updateAccessControl'])->name('users.access-control.update');
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
            Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit.show');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::patch('/settings/daily-report', [SettingsController::class, 'updateDailyReport'])->name('settings.daily-report.update');
            Route::get('/api-documentation', fn () => inertia('Admin/SuperAdmin/ApiDocumentation'))->name('api-docs.index');
            Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.index');
            Route::get('/profile/edit', [AdminProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
            Route::post('/personal-access-tokens', [PersonalAccessTokenController::class, 'store'])->name('personal-access-tokens.store');
            Route::delete('/personal-access-tokens/{token}', [PersonalAccessTokenController::class, 'destroy'])->name('personal-access-tokens.destroy');
        });
    });
