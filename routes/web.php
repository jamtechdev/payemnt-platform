<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::get('/forgot-password', [AdminPasswordController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AdminPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AdminPasswordController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AdminPasswordController::class, 'resetPassword'])->name('password.store');
});
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'session.timeout'])
    ->group(function (): void {
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');

        Route::get('/cs/dashboard', [DashboardController::class, 'customerServiceDashboard'])
            ->middleware('permission:dashboard.customer_overview')
            ->name('cs.dashboard');
        Route::get('/reports/dashboard', [DashboardController::class, 'reconciliationDashboard'])
            ->middleware('permission:reports.customer_acquisition')
            ->name('reports.dashboard');
        Route::get('/platform/dashboard', [DashboardController::class, 'superAdminDashboard'])
            ->middleware('permission:dashboard.platform_overview')
            ->name('platform.dashboard');

        Route::middleware('permission:customers.view_list')->group(function (): void {
            Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
            Route::get('/customers/{uuid}', [CustomerController::class, 'show'])->name('customers.show');
            Route::post('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
            Route::get('/customers/export/{jobId}', [CustomerController::class, 'downloadExport'])->name('customers.download-export');
        });

        Route::get('/reports/customer-acquisition', [ReportController::class, 'customerAcquisition'])
            ->middleware('permission:reports.customer_acquisition')
            ->name('reports.customer-acquisition');
        Route::get('/reports/revenue', [ReportController::class, 'revenueByProduct'])
            ->middleware('permission:reports.revenue_by_product')
            ->name('reports.revenue');
        Route::post('/reports/export', [ReportController::class, 'exportReport'])
            ->name('reports.export');
        Route::get('/reports/export/{jobId}', [ReportController::class, 'downloadExport'])->name('reports.download-export');

        Route::middleware('permission:dashboard.platform_overview')->group(function (): void {
            Route::get('/reports/partner-performance', [ReportController::class, 'partnerPerformance'])
                ->middleware('permission:reports.partner_performance')
                ->name('reports.partner-performance');

            Route::get('products', [ProductController::class, 'index'])
                ->middleware('permission:products.view')
                ->name('products.index');
            Route::get('products/create', [ProductController::class, 'create'])
                ->middleware('permission:products.create')
                ->name('products.create');
            Route::post('products', [ProductController::class, 'store'])
                ->middleware('permission:products.create')
                ->name('products.store');
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])
                ->middleware('permission:products.edit')
                ->name('products.edit');
            Route::patch('products/{product}', [ProductController::class, 'update'])
                ->middleware('permission:products.edit')
                ->name('products.update');
            Route::delete('products/{product}', [ProductController::class, 'destroy'])
                ->middleware('permission:products.delete')
                ->name('products.destroy');
            Route::post('products/{product}/toggle-partner-access', [ProductController::class, 'togglePartnerAccess'])
                ->middleware('permission:products.manage_partner_access')
                ->name('products.toggle-partner-access');
            Route::get('products/{product}/versions', [ProductController::class, 'versions'])
                ->middleware('permission:products.view')
                ->name('products.versions');

            Route::get('partners', [PartnerController::class, 'index'])
                ->middleware('permission:partners.view')
                ->name('partners.index');
            Route::get('/partners/create', [PartnerController::class, 'create'])->middleware('permission:partners.create')->name('partners.create');
            Route::post('partners', [PartnerController::class, 'store'])
                ->middleware('permission:partners.create')
                ->name('partners.store');
            Route::get('partners/{partner}', [PartnerController::class, 'show'])
                ->middleware('permission:partners.view')
                ->name('partners.show');
            Route::get('partners/{partner}/edit', [PartnerController::class, 'edit'])
                ->middleware('permission:partners.edit')
                ->name('partners.edit');
            Route::patch('partners/{partner}', [PartnerController::class, 'update'])
                ->middleware('permission:partners.edit')
                ->name('partners.update');
            Route::delete('partners/{partner}', [PartnerController::class, 'destroy'])
                ->middleware('permission:partners.delete')
                ->name('partners.destroy');
            Route::post('partners/{partner}/toggle-status', [PartnerController::class, 'toggleStatus'])
                ->middleware('permission:partners.edit')
                ->name('partners.toggle-status');
            Route::post('partners/{partner}/generate-api-key', [PartnerController::class, 'generateApiKey'])
                ->middleware('permission:partners.edit')
                ->name('partners.generate-api-key');
            Route::delete('partners/{partner}/revoke-api-key', [PartnerController::class, 'revokeApiKey'])
                ->middleware('permission:partners.edit')
                ->name('partners.revoke-api-key');
            Route::post('partners/{partner}/toggle-product-access', [PartnerController::class, 'toggleProductAccess'])
                ->middleware('permission:partners.edit')
                ->name('partners.toggle-product-access');
            Route::get('api-documentation', function () {
                return Inertia::render('Admin/SuperAdmin/ApiDocumentation');
            })->middleware('permission:dashboard.platform_overview')
                ->name('api-documentation');

            Route::get('users', [UserManagementController::class, 'index'])
                ->middleware('permission:users.view')
                ->name('users.index');
            Route::post('users', [UserManagementController::class, 'store'])
                ->middleware('permission:users.create')
                ->name('users.store');
            Route::patch('users/{user}', [UserManagementController::class, 'update'])
                ->middleware('permission:users.edit')
                ->name('users.update');
            Route::delete('users/{user}', [UserManagementController::class, 'destroy'])
                ->middleware('permission:users.deactivate')
                ->name('users.destroy');
            Route::patch('users/{user}/access-control', [UserManagementController::class, 'updateAccessControl'])
                ->middleware(['permission:users.assign_roles', 'role:admin|super_admin'])
                ->name('users.access-control.update');
            Route::post('users/{user}/deactivate', [UserManagementController::class, 'deactivate'])
                ->middleware('permission:users.deactivate')
                ->name('users.deactivate');

            Route::get('audit-logs', [AuditLogController::class, 'index'])
                ->middleware('permission:audit_logs.view')
                ->name('audit-logs.index');
            Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])
                ->middleware('permission:audit_logs.view')
                ->name('audit-logs.show');

            Route::get('settings', [SettingsController::class, 'index'])
                ->middleware('permission:settings.system')
                ->name('settings.index');
            Route::patch('settings/email', [SettingsController::class, 'updateEmail'])
                ->middleware('permission:settings.email')
                ->name('settings.update-email');
            Route::patch('settings/daily-report', [SettingsController::class, 'updateDailyReport'])
                ->middleware('permission:settings.system')
                ->name('settings.update-daily-report');
        });
    });
