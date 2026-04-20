<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductField;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            'customers.create',
            'customers.search',
            'customers.view_detail',
            'customers.edit',
            'customers.delete',
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
                    'customers.create',
                    'customers.search',
                    'customers.view_detail',
                    'customers.edit',
                    'customers.delete',
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

        $this->seedBeneficiaryProducts();
    }

    private function seedBeneficiaryProducts(): void
    {
        $products = [
            [
                'name' => 'Nigerian Beneficiary Community Product',
                'slug' => 'nigerian-beneficiary-community-product',
                'description' => 'Community policy for Nigerian beneficiaries.',
                'partner_currency' => 'NGN',
            ],
            [
                'name' => 'Ghana Beneficiary Community Product',
                'slug' => 'ghana-beneficiary-community-product',
                'description' => 'Community policy for Ghanaian beneficiaries.',
                'partner_currency' => 'GHS',
            ],
        ];

        $fieldDefinitions = [
            ['name' => 'beneficiary_first_name', 'label' => 'Beneficiary First Name', 'type' => 'text', 'is_required' => true, 'options' => null],
            ['name' => 'beneficiary_surname', 'label' => 'Beneficiary Surname', 'type' => 'text', 'is_required' => true, 'options' => null],
            ['name' => 'beneficiary_date_of_birth', 'label' => 'Beneficiary Date of Birth', 'type' => 'date', 'is_required' => true, 'options' => null],
            ['name' => 'beneficiary_age', 'label' => 'Beneficiary Age', 'type' => 'number', 'is_required' => true, 'options' => null],
            ['name' => 'beneficiary_gender', 'label' => 'Beneficiary Gender', 'type' => 'dropdown', 'is_required' => true, 'options' => ['male', 'female', 'other']],
            ['name' => 'beneficiary_address', 'label' => 'Beneficiary Address', 'type' => 'textarea', 'is_required' => true, 'options' => null],
            ['name' => 'cover_start_date', 'label' => 'Cover Start Date', 'type' => 'date', 'is_required' => true, 'options' => null],
            ['name' => 'cover_duration', 'label' => 'Cover Duration', 'type' => 'dropdown', 'is_required' => true, 'options' => ['monthly', 'annual']],
            ['name' => 'cover_duration_months', 'label' => 'Cover Duration Months', 'type' => 'number', 'is_required' => false, 'options' => null],
            ['name' => 'first_name', 'label' => 'Customer First Name', 'type' => 'text', 'is_required' => true, 'options' => null],
            ['name' => 'last_name', 'label' => 'Customer Last Name', 'type' => 'text', 'is_required' => true, 'options' => null],
            ['name' => 'email', 'label' => 'Customer Email', 'type' => 'email', 'is_required' => true, 'options' => null],
        ];

        /** @var Partner|null $swapCircle */
        $swapCircle = Partner::query()->where('email', 'partner.swapcircle@local.test')->first();

        foreach ($products as $definition) {
            $product = Product::query()->firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'status' => 'active',
                    'cover_duration_options' => [1, 12],
                ]
            );
            $product->update([
                'name' => $definition['name'],
                'description' => $definition['description'],
                'status' => 'active',
                'cover_duration_options' => [1, 12],
            ]);

            foreach ($fieldDefinitions as $index => $field) {
                ProductField::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'name' => $field['name'],
                    ],
                    [
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'is_required' => $field['is_required'],
                        'options' => $field['options'],
                        'sort_order' => $index,
                        'validation_rules' => null,
                    ]
                );
            }

            if ($swapCircle) {
                DB::table('partner_products')->updateOrInsert(
                    [
                        'partner_id' => $swapCircle->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'status' => 'active',
                        'partner_price' => null,
                        'partner_currency' => $definition['partner_currency'],
                        'activated_at' => now(),
                        'deactivated_at' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
