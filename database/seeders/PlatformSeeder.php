<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = array_values(array_unique(array_merge(
            config('admin_portal.permissions'),
            $this->permissionNamesFromRoles(),
        )));

        foreach ($permissionNames as $permissionName) {
            Permission::query()->firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $allPermissions = Permission::query()->pluck('name')->all();
        $rolesConfig = config('admin_portal.roles');

        foreach (['super_admin'] as $roleName) {
            if (! isset($rolesConfig[$roleName])) {
                continue;
            }
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $rolePermissions = $rolesConfig[$roleName]['permissions'] ?? [];
            $role->syncPermissions($rolePermissions === ['*'] ? $allPermissions : $rolePermissions);
        }

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'super.admin@platform.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('ChangeMe@12345'),
                'status' => 'active',
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        $partner = Partner::query()->updateOrCreate(
            ['partner_code' => 'SWAP_CIRCLE'],
            [
                'name' => 'Swap',
                'partner_name' => 'Swap',
                'slug' => Str::slug('Swap Circle'),
                'contact_email' => 'integrations@swap.example',
                'contact_phone' => '+2348000000000',
                'company_name' => 'Swap',
                'website_url' => 'https://swap.example',
                'status' => 'active',
            ]
        );

        Product::withTrashed()
            ->where('product_code', '!=', 'INSURETECH_SWAP_PROTECT')
            ->get()
            ->each(function (Product $product): void {
                $product->forceDelete();
            });

        $product = Product::withTrashed()->updateOrCreate(
            ['product_code' => 'INSURETECH_SWAP_PROTECT'],
            [
                'name' => 'InsureTech Swap Protect',
                'product_name' => 'InsureTech Swap Protect',
                'slug' => Str::slug('InsureTech Swap Protect'),
                'description' => 'Single partner-integrated product for Swap acquisition and policy submission.',
                'cover_duration_mode' => 'custom',
                'cover_duration_type' => 'custom',
                'default_cover_duration_days' => 30,
                'cover_duration_options' => [30, 90, 365],
                'status' => 'active',
                'guide_price' => 100.00,
            ]
        );
        if ($product->trashed()) {
            $product->restore();
        }

        $product->fields()->delete();
        $product->fields()->createMany([
            ['field_key' => 'customer_name', 'label' => 'Customer Name', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1],
            ['field_key' => 'customer_email', 'label' => 'Customer Email', 'field_type' => 'email', 'is_required' => true, 'sort_order' => 2],
            ['field_key' => 'phone', 'label' => 'Phone Number', 'field_type' => 'phone', 'is_required' => false, 'sort_order' => 3],
            ['field_key' => 'cover_duration', 'label' => 'Cover Duration', 'field_type' => 'dropdown', 'is_required' => true, 'options' => ['30_days', '90_days', '365_days'], 'sort_order' => 4],
            ['field_key' => 'id_type', 'label' => 'KYC ID Type', 'field_type' => 'dropdown', 'is_required' => false, 'options' => ['national_id', 'passport', 'drivers_license'], 'sort_order' => 5],
            ['field_key' => 'id_number', 'label' => 'KYC ID Number', 'field_type' => 'text', 'is_required' => false, 'sort_order' => 6],
        ]);

        $partner->products()->sync([
            $product->id => ['is_enabled' => true, 'partner_price' => 100.00, 'partner_currency' => 'USD', 'cover_duration_days_override' => 30],
        ]);
    }

    /**
     * @return list<string>
     */
    private function permissionNamesFromRoles(): array
    {
        $names = [];
        foreach (config('admin_portal.roles') as $meta) {
            $perms = $meta['permissions'] ?? [];
            if ($perms !== ['*']) {
                foreach ($perms as $p) {
                    $names[] = $p;
                }
            }
        }

        return $names;
    }
}
