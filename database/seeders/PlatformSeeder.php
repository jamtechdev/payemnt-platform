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
                'name' => 'Swap Circle',
                'slug' => Str::slug('Swap Circle'),
                'contact_email' => 'api@swapcircle.local',
                'status' => 'active',
            ]
        );

        $requiredProductCodes = ['NIGERIA_BENEFICIARY_COMMUNITY', 'GHANA_BENEFICIARY_COMMUNITY'];
        Product::withTrashed()
            ->whereNotIn('product_code', $requiredProductCodes)
            ->get()
            ->each(function (Product $product): void {
                $product->forceDelete();
            });

        $nigeriaProduct = Product::withTrashed()->updateOrCreate(
            ['product_code' => 'NIGERIA_BENEFICIARY_COMMUNITY'],
            [
                'name' => 'Nigerian Beneficiary Community Product',
                'slug' => Str::slug('Nigerian Beneficiary Community Product'),
                'country' => 'NG',
                'cover_duration_mode' => 'custom',
                'cover_duration_type' => 'custom',
                'default_cover_duration_days' => 30,
                'cover_duration_options' => [30, 365],
                'status' => 'active',
            ]
        );
        if ($nigeriaProduct->trashed()) {
            $nigeriaProduct->restore();
        }

        $ghanaProduct = Product::withTrashed()->updateOrCreate(
            ['product_code' => 'GHANA_BENEFICIARY_COMMUNITY'],
            [
                'name' => 'Ghana Beneficiary Community Product',
                'slug' => Str::slug('Ghana Beneficiary Community Product'),
                'country' => 'GH',
                'cover_duration_mode' => 'custom',
                'cover_duration_type' => 'custom',
                'default_cover_duration_days' => 30,
                'cover_duration_options' => [30, 365],
                'status' => 'active',
            ]
        );
        if ($ghanaProduct->trashed()) {
            $ghanaProduct->restore();
        }

        foreach ([$nigeriaProduct, $ghanaProduct] as $product) {
            $product->fields()->delete();
            $product->fields()->createMany([
                ['field_key' => 'beneficiary_first_name', 'label' => 'Beneficiary First Name', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1],
                ['field_key' => 'beneficiary_surname', 'label' => 'Beneficiary Surname', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 2],
                ['field_key' => 'beneficiary_date_of_birth', 'label' => 'Beneficiary Date of Birth', 'field_type' => 'date', 'is_required' => true, 'sort_order' => 3],
                ['field_key' => 'beneficiary_age', 'label' => 'Beneficiary Age (Auto)', 'field_type' => 'number', 'is_required' => false, 'sort_order' => 4],
                ['field_key' => 'beneficiary_gender', 'label' => 'Beneficiary Gender', 'field_type' => 'dropdown', 'is_required' => true, 'options' => ['male', 'female', 'other'], 'sort_order' => 5],
                ['field_key' => 'beneficiary_address', 'label' => 'Beneficiary Address', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 6],
                ['field_key' => 'cover_start_date', 'label' => 'Cover Start Date', 'field_type' => 'date', 'is_required' => true, 'sort_order' => 7],
                ['field_key' => 'cover_duration', 'label' => 'Cover Duration', 'field_type' => 'dropdown', 'is_required' => true, 'options' => ['monthly', 'annual'], 'sort_order' => 8],
            ]);
        }

        $partner->products()->sync([
            $nigeriaProduct->id => ['is_enabled' => true, 'partner_price' => 100.00, 'partner_currency' => 'NGN', 'cover_duration_days_override' => 30],
            $ghanaProduct->id => ['is_enabled' => true, 'partner_price' => 25.00, 'partner_currency' => 'GHS', 'cover_duration_days_override' => 30],
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
