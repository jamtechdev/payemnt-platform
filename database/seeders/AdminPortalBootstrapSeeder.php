<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminPortalBootstrapSeeder extends Seeder
{
    /**
     * Single-source permission list for seeding.
     *
     * @return list<string>
     */
    private function permissions(): array
    {
        return [
            'customers.view_list',
            'customers.search',
            'customers.view_detail',
            'customers.view_submitted_data',
            'customers.view_payment_amount',
            'customers.export',
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.manage_fields',
            'products.manage_partner_access',
            'partners.view',
            'partners.create',
            'partners.edit',
            'partners.delete',
            'partners.view_api_usage',
            'dashboard.customer_overview',
            'dashboard.metrics_overview',
            'dashboard.platform_overview',
            'reports.customer_summary',
            'reports.customer_acquisition',
            'reports.revenue_by_product',
            'reports.partner_performance',
            'reports.export_csv',
            'reports.export_excel',
            'reports.export_pdf',
            'reports.scheduled_reports',
            'users.view',
            'users.create',
            'users.edit',
            'users.deactivate',
            'users.assign_roles',
            'audit_logs.view',
            'settings.system',
            'settings.email',
        ];
    }

    /**
     * Role => permissions map. Use ['*'] for all permissions.
     *
     * @return array<string, array{label:string,permissions:list<string>}>
     */
    private function roles(): array
    {
        return [
            'customer_service' => [
                'label' => 'Customer Service',
                'permissions' => [
                    'dashboard.customer_overview',
                    'customers.view_list',
                    'customers.search',
                    'customers.view_detail',
                    'customers.view_submitted_data',
                    'reports.customer_summary',
                    'reports.export_csv',
                ],
            ],
            'reconciliation_admin' => [
                'label' => 'Reconciliation Admin',
                'permissions' => [
                    'dashboard.metrics_overview',
                    'reports.customer_acquisition',
                    'reports.revenue_by_product',
                    'reports.export_excel',
                    'reports.export_pdf',
                    'reports.scheduled_reports',
                ],
            ],
            'admin' => [
                'label' => 'Admin',
                'permissions' => [
                    'dashboard.platform_overview',
                    'customers.view_list',
                    'customers.search',
                    'customers.view_detail',
                    'customers.view_submitted_data',
                    'customers.view_payment_amount',
                    'customers.export',
                    'products.view',
                    'products.create',
                    'products.edit',
                    'products.manage_fields',
                    'products.manage_partner_access',
                    'partners.view',
                    'partners.create',
                    'partners.edit',
                    'partners.view_api_usage',
                    'reports.customer_summary',
                    'reports.customer_acquisition',
                    'reports.revenue_by_product',
                    'reports.partner_performance',
                    'reports.export_csv',
                    'reports.export_excel',
                    'reports.export_pdf',
                    'reports.scheduled_reports',
                    'users.view',
                    'users.create',
                    'users.edit',
                    'users.deactivate',
                    'users.assign_roles',
                    'audit_logs.view',
                ],
            ],
            'super_admin' => [
                'label' => 'Super Admin',
                'permissions' => ['*'],
            ],
            'partner' => [
                'label' => 'Partner',
                'permissions' => [],
            ],
            'customer' => [
                'label' => 'Customer',
                'permissions' => [],
            ],
        ];
    }

    /**
     * Default password for local/staging seeds (min 12 chars, mixed case, digit, symbol — AUTH-004).
     * Override with ADMIN_SEED_PASSWORD in .env for deployments.
     */
    private function defaultPassword(): string
    {
        return (string) env('ADMIN_SEED_PASSWORD', 'ChangeMe12345');
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->permissions();
        $roles = $this->roles();

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $allPermissions = Permission::query()->pluck('name')->all();
        foreach ($roles as $roleName => $definition) {
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $rolePermissions = $definition['permissions'] ?? [];
            if ($rolePermissions === ['*']) {
                $role->syncPermissions($allPermissions);
                continue;
            }
            $role->syncPermissions($rolePermissions);
        }

        $password = Hash::make($this->defaultPassword());

        $users = [
            ['name' => 'Claire Support', 'email' => 'cs.admin@local.test', 'role' => 'customer_service', 'job_title' => 'Customer Service'],
            ['name' => 'Ray Reconcile', 'email' => 'recon.admin@local.test', 'role' => 'reconciliation_admin', 'job_title' => 'Reconciliation Analyst'],
            ['name' => 'Sam Platform', 'email' => 'super.admin@local.test', 'role' => 'super_admin', 'job_title' => 'Super Administrator'],
            ['name' => 'Alex Admin', 'email' => 'admin@local.test', 'role' => 'admin', 'job_title' => 'Platform Admin'],
            ['name' => 'Swap Circle', 'email' => 'partner.swapcircle@local.test', 'role' => 'partner', 'job_title' => 'Partner Account'],
        ];

        foreach ($users as $payload) {
            $user = User::query()->updateOrCreate(
                ['email' => $payload['email']],
                [
                    'name' => $payload['name'],
                    'slug' => \Illuminate\Support\Str::slug($payload['name']),
                    'password' => $password,
                    'is_active' => true,
                    'status' => 'active',
                ]
            );
            $user->syncRoles([$payload['role']]);

            UserProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['job_title' => $payload['job_title'] ?? null]
            );
        }
    }
}
