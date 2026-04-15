<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends BaseApiController
{
    #[OA\Get(path: '/api/v1/platform-overview', summary: 'Platform overview', security: [['sanctum' => []]], tags: ['Dashboard'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function platformOverview(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'active_users' => User::query()->where('is_active', true)->count(),
                'customers_total' => Customer::query()->count(),
                'partners_total' => Partner::query()->count(),
                'products_total' => Product::query()->count(),
                'payments_total' => (float) Payment::query()->sum('amount'),
                'recent_audit_logs' => AuditLog::query()->latest()->limit(10)->get(),
            ],
        ]);
    }

    #[OA\Get(path: '/api/v1/customers', summary: 'List customers', security: [['sanctum' => []]], tags: ['Customers'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function customers(Request $request): JsonResponse
    {
        $query = Customer::query()->with(['partner:id,name', 'product:id,name'])->latest('id');

        if ($request->filled('partner_id')) {
            $query->where('partner_id', (int) $request->integer('partner_id'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->integer('product_id'));
        }
        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->where(function ($subQuery) use ($search): void {
                $subQuery->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate((int) $request->integer('per_page', 15)),
        ]);
    }

    #[OA\Get(path: '/api/v1/customers/{uuid}', summary: 'Show customer', security: [['sanctum' => []]], tags: ['Customers'], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function customer(string $uuid): JsonResponse
    {
        $customer = Customer::query()
            ->with(['partner', 'product', 'payments'])
            ->where('uuid', $uuid)
            ->first();

        if (! $customer) {
            return response()->json(['status' => 'error', 'message' => 'Customer not found.'], 404);
        }

        $actor = request()->user();
        $canViewPaymentAmount = (bool) ($actor?->can('customers.view_payment_amount') ?? false);
        if (! $canViewPaymentAmount) {
            $customer->setRelation('payments', $customer->payments->map(function ($payment) {
                $payment->amount = null;

                return $payment;
            }));
        }

        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    #[OA\Get(path: '/api/v1/payments', summary: 'List payments', security: [['sanctum' => []]], tags: ['Payments'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function payments(Request $request): JsonResponse
    {
        $query = Payment::query()->with(['customer:id,uuid,first_name,last_name', 'partner:id,name'])->latest('id');
        if ($request->filled('partner_id')) {
            $query->where('partner_id', (int) $request->integer('partner_id'));
        }
        if ($request->filled('customer_uuid')) {
            $uuid = (string) $request->string('customer_uuid');
            $query->whereHas('customer', fn ($q) => $q->where('uuid', $uuid));
        }
        if ($request->filled('status')) {
            $query->where('payment_status', (string) $request->string('status'));
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate((int) $request->integer('per_page', 20)),
        ]);
    }

    #[OA\Get(path: '/api/v1/payments/{payment}', summary: 'Show payment', security: [['sanctum' => []]], tags: ['Payments'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function payment(Payment $payment): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $payment->load(['customer', 'partner']),
        ]);
    }

    #[OA\Get(path: '/api/v1/reports/customer-acquisition', summary: 'Customer acquisition report', security: [['sanctum' => []]], tags: ['Reports'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function customerAcquisitionReport(Request $request): JsonResponse
    {
        $period = $request->string('period', 'monthly')->toString();
        $format = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-%v',
            default => '%Y-%m',
        };

        $rows = Customer::query()
            ->selectRaw("product_id, DATE_FORMAT(created_at, '{$format}') as bucket, count(*) as total")
            ->groupBy('product_id', 'bucket')
            ->orderBy('bucket')
            ->get();

        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    #[OA\Get(path: '/api/v1/reports/revenue-by-product', summary: 'Revenue by product report', security: [['sanctum' => []]], tags: ['Reports'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function revenueByProductReport(): JsonResponse
    {
        $rows = Payment::query()
            ->selectRaw('users.product_id, sum(amount) as total')
            ->join('users', 'users.id', '=', 'payments.customer_id')
            ->groupBy('users.product_id')
            ->get();

        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    #[OA\Get(path: '/api/v1/products', summary: 'List products', security: [['sanctum' => []]], tags: ['Products'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function products(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => Product::query()->with('fields')->paginate((int) $request->integer('per_page', 15)),
        ]);
    }

    #[OA\Post(path: '/api/v1/products', summary: 'Create product', security: [['sanctum' => []]], tags: ['Products'], responses: [new OA\Response(response: 201, description: 'Created')])]
    public function storeProduct(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($validated) {
            $product = Product::query()->create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'cover_duration_options' => $validated['cover_duration_options'],
            ]);

            foreach ($validated['fields'] ?? [] as $index => $field) {
                $product->fields()->create([
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'options' => $field['options'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            ProductVersion::query()->create([
                'product_id' => $product->id,
                'created_by' => request()->user()?->id,
                'version_number' => 1,
                'snapshot' => [
                    'product' => $product->toArray(),
                    'fields' => $product->fields()->get()->toArray(),
                ],
            ]);

            return $product->load('fields');
        });

        return response()->json(['status' => 'success', 'data' => $product], 201);
    }

    #[OA\Patch(path: '/api/v1/products/{product}', summary: 'Update product', security: [['sanctum' => []]], tags: ['Products'], responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function updateProduct(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $product): void {
            $product->update([
                'name' => $validated['name'] ?? $product->name,
                'slug' => $validated['slug'] ?? (isset($validated['name']) ? Str::slug($validated['name']) : $product->slug),
                'description' => $validated['description'] ?? $product->description,
                'status' => $validated['status'] ?? $product->status,
                'cover_duration_options' => $validated['cover_duration_options'] ?? $product->cover_duration_options,
            ]);

            $nextVersion = ((int) ProductVersion::query()
                ->where('product_id', $product->id)
                ->max('version_number')) + 1;

            ProductVersion::query()->create([
                'product_id' => $product->id,
                'created_by' => request()->user()?->id,
                'version_number' => $nextVersion,
                'snapshot' => [
                    'product' => $product->fresh()->toArray(),
                    'fields' => $product->fields()->get()->toArray(),
                ],
            ]);
        });

        return response()->json(['status' => 'success', 'data' => $product->fresh(['fields'])]);
    }

    #[OA\Delete(path: '/api/v1/products/{product}', summary: 'Delete product', security: [['sanctum' => []]], tags: ['Products'], responses: [new OA\Response(response: 200, description: 'Deleted'), new OA\Response(response: 422, description: 'Validation')])]
    public function deleteProduct(Product $product): JsonResponse
    {
        if ($product->customers()->where('status', 'active')->exists()) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'PRODUCT_HAS_ACTIVE_CUSTOMERS',
                'message' => 'Cannot delete product with active customers.',
            ], 422);
        }

        $product->delete();

        return response()->json(['status' => 'success', 'message' => 'Product deleted.']);
    }

    #[OA\Get(path: '/api/v1/products/{product}/versions', summary: 'Product version history', security: [['sanctum' => []]], tags: ['Products'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function productVersions(Product $product): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => ProductVersion::query()
                ->where('product_id', $product->id)
                ->orderByDesc('version_number')
                ->get(),
        ]);
    }

    #[OA\Get(path: '/api/v1/partners', summary: 'List partners', security: [['sanctum' => []]], tags: ['Partners'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function partners(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => Partner::query()->latest('id')->paginate((int) $request->integer('per_page', 15)),
        ]);
    }

    #[OA\Post(path: '/api/v1/partners', summary: 'Create partner', security: [['sanctum' => []]], tags: ['Partners'], responses: [new OA\Response(response: 201, description: 'Created')])]
    public function storePartner(StorePartnerRequest $request): JsonResponse
    {
        $partner = Partner::query()->create([
            ...$request->validated(),
            'slug' => Str::slug($request->string('name')->toString()),
            'status' => 'active',
        ]);
        $partner->syncRoles(['partner']);

        return response()->json(['status' => 'success', 'data' => $partner], 201);
    }

    #[OA\Patch(path: '/api/v1/partners/{partner}', summary: 'Update partner', security: [['sanctum' => []]], tags: ['Partners'], responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function updatePartner(UpdatePartnerRequest $request, Partner $partner): JsonResponse
    {
        $partner->update($request->validated());

        return response()->json(['status' => 'success', 'data' => $partner->fresh()]);
    }

    #[OA\Delete(path: '/api/v1/partners/{partner}', summary: 'Delete partner', security: [['sanctum' => []]], tags: ['Partners'], responses: [new OA\Response(response: 200, description: 'Deleted'), new OA\Response(response: 422, description: 'Validation')])]
    public function deletePartner(Partner $partner): JsonResponse
    {
        if ($partner->customers()->exists()) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'PARTNER_HAS_CUSTOMERS',
                'message' => 'Cannot delete partner with customer records.',
            ], 422);
        }

        $partner->delete();

        return response()->json(['status' => 'success', 'message' => 'Partner deleted.']);
    }

    #[OA\Patch(path: '/api/v1/partners/{partner}/products/{product}/access', summary: 'Activate or deactivate product access for a partner', security: [['sanctum' => []]], tags: ['Partners'], responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function updatePartnerProductAccess(Request $request, Partner $partner, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $status = $validated['status'];
        $payload = [
            'status' => $status,
            'activated_at' => $status === 'active' ? now() : null,
            'deactivated_at' => $status === 'inactive' ? now() : null,
            'updated_at' => now(),
        ];

        DB::table('partner_products')->updateOrInsert([
            'partner_id' => $partner->id,
            'product_id' => $product->id,
        ], $payload + ['created_at' => now()]);

        AuditLog::record('partner_product_access_updated', $partner, [], [
            'product_id' => $product->id,
            'status' => $status,
        ], $request->user());

        return response()->json([
            'status' => 'success',
            'data' => [
                'partner_id' => $partner->id,
                'product_id' => $product->id,
                'status' => $status,
            ],
        ]);
    }

    #[OA\Get(path: '/api/v1/users', summary: 'List users', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function users(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => User::query()->with(['roles', 'profile'])->paginate((int) $request->integer('per_page', 15)),
        ]);
    }

    #[OA\Post(path: '/api/v1/users', summary: 'Create user', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 201, description: 'Created')])]
    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(Role::query()->pluck('name')->all())],
            'password' => ['nullable', 'string', 'min:12'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? 'ChangeMe12345!'),
            'is_active' => true,
        ]);
        $user->syncRoles([$validated['role']]);
        UserProfile::query()->firstOrCreate(['user_id' => $user->id]);
        AuditLog::record('admin_user_created', $user, [], [
            'assigned_role' => $validated['role'],
        ], $request->user());

        return response()->json(['status' => 'success', 'data' => $user->load('roles', 'profile')], 201);
    }

    #[OA\Patch(path: '/api/v1/users/{user}', summary: 'Update user', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['nullable', Rule::in(Role::query()->pluck('name')->all())],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $user->only(['name', 'email', 'is_active']);
        $user->update($validated);
        if (! empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }
        AuditLog::record('admin_user_updated', $user, $old, [
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'role' => $user->getRoleNames()->first(),
        ], $request->user());

        return response()->json(['status' => 'success', 'data' => $user->fresh(['roles', 'profile'])]);
    }

    #[OA\Delete(path: '/api/v1/users/{user}', summary: 'Delete user', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 200, description: 'Deleted'), new OA\Response(response: 403, description: 'Forbidden')])]
    public function deleteUser(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        if ($actor && (int) $actor->id === (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'SELF_DELETE_FORBIDDEN',
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        $user->delete();

        return response()->json(['status' => 'success', 'message' => 'User deleted.']);
    }

    #[OA\Get(path: '/api/v1/access-control', summary: 'List roles and permissions', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function accessControl(): JsonResponse
    {
        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'roles' => $roles,
                'permissions' => $permissions,
            ],
        ]);
    }

    #[OA\Patch(path: '/api/v1/users/{user}/access-control', summary: 'Assign role and permissions to user', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 200, description: 'Updated'), new OA\Response(response: 403, description: 'Forbidden')])]
    public function updateUserAccessControl(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'role' => ['nullable', Rule::in(Role::query()->pluck('name')->all())],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(Permission::query()->pluck('name')->all())],
        ]);

        if (! $actor->hasRole('super_admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only super admin can manage roles and permissions.',
            ], 403);
        }

        DB::transaction(function () use ($validated, $user): void {
            if (array_key_exists('role', $validated) && ! empty($validated['role'])) {
                $user->syncRoles([$validated['role']]);
            }

            if (array_key_exists('permissions', $validated)) {
                $user->syncPermissions($validated['permissions'] ?? []);
            }
        });
        AuditLog::record('admin_user_access_control_updated', $user, [], [
            'role' => $user->getRoleNames()->first(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ], $actor);

        return response()->json([
            'status' => 'success',
            'data' => $user->fresh(['roles', 'permissions', 'profile']),
        ]);
    }

    #[OA\Get(path: '/api/v1/audit-logs', summary: 'List audit logs', security: [['sanctum' => []]], tags: ['Audit Logs'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function auditLogs(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => AuditLog::query()->latest('id')->paginate((int) $request->integer('per_page', 20)),
        ]);
    }

    #[OA\Get(path: '/api/v1/settings', summary: 'List settings', security: [['sanctum' => []]], tags: ['Settings'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function settings(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => Setting::query()->orderBy('key')->get(),
        ]);
    }

    #[OA\Patch(path: '/api/v1/settings', summary: 'Upsert settings', security: [['sanctum' => []]], tags: ['Settings'], responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::setValue((string) $key, $value);
        }

        return response()->json([
            'status' => 'success',
            'data' => Setting::query()->orderBy('key')->get(),
        ]);
    }
}
