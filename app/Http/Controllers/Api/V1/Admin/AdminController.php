<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerProductAccessRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Http\Requests\Admin\UpdateUserAccessControlRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
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
        return $this->success([
            'active_users' => User::query()->where('is_active', true)->count(),
            'customers_total' => Customer::query()->count(),
            'partners_total' => Partner::query()->count(),
            'products_total' => Product::query()->count(),
            'payments_total' => (float) Payment::query()->sum('amount'),
            'recent_audit_logs' => AuditLog::query()->latest()->limit(10)->get(),
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

        return $this->paginated($query->paginate((int) $request->integer('per_page', 15)));
    }

    #[OA\Get(
        path: '/api/v1/customers/{uuid}',
        summary: 'Show customer',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function customer(string $uuid): JsonResponse
    {
        $customer = Customer::query()
            ->with(['partner', 'product', 'payments'])
            ->where('uuid', $uuid)
            ->first();

        if (! $customer) {
            return $this->error('CUSTOMER_NOT_FOUND', 'Customer not found.', status: 404);
        }

        $actor = request()->user();
        $canViewPaymentAmount = (bool) ($actor?->can('customers.view_payment_amount') ?? false);
        if (! $canViewPaymentAmount) {
            $customer->setRelation('payments', $customer->payments->map(function ($payment) {
                $payment->amount = null;

                return $payment;
            }));
        }

        return $this->success($customer);
    }

    #[OA\Post(
        path: '/api/v1/customers/admin',
        summary: 'Create customer',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['partner_id', 'product_id', 'first_name', 'last_name', 'email', 'cover_start_date', 'cover_duration_months'],
                properties: [
                    new OA\Property(property: 'partner_id', type: 'integer'),
                    new OA\Property(property: 'product_id', type: 'integer'),
                    new OA\Property(property: 'first_name', type: 'string'),
                    new OA\Property(property: 'last_name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                    new OA\Property(property: 'cover_start_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'cover_duration_months', type: 'integer'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'expired', 'cancelled']),
                    new OA\Property(property: 'submitted_data', type: 'object', additionalProperties: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function storeCustomer(StoreCustomerRequest $request): JsonResponse
    {
        $this->ensureAdminActor($request);

        $customer = Customer::query()->create([
            ...$request->validated(),
            'submitted_data' => $request->validated('submitted_data', []),
        ]);
        $customer->syncRoles(['customer']);

        return $this->success($customer->fresh(['partner', 'product']), 201);
    }

    #[OA\Patch(
        path: '/api/v1/customers/{uuid}',
        summary: 'Update customer',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'partner_id', type: 'integer'),
                    new OA\Property(property: 'product_id', type: 'integer'),
                    new OA\Property(property: 'first_name', type: 'string'),
                    new OA\Property(property: 'last_name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                    new OA\Property(property: 'cover_start_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'cover_duration_months', type: 'integer'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'expired', 'cancelled']),
                    new OA\Property(property: 'submitted_data', type: 'object', additionalProperties: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Updated'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function updateCustomer(UpdateCustomerRequest $request, string $uuid): JsonResponse
    {
        $this->ensureAdminActor($request);

        $customer = Customer::query()->where('uuid', $uuid)->first();
        if (! $customer) {
            return $this->error('CUSTOMER_NOT_FOUND', 'Customer not found.', status: 404);
        }

        $customer->update($request->validated());

        return $this->success($customer->fresh(['partner', 'product']));
    }

    #[OA\Delete(
        path: '/api/v1/customers/{uuid}',
        summary: 'Delete customer',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Deleted'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function deleteCustomer(Request $request, string $uuid): JsonResponse
    {
        $this->ensureAdminActor($request);

        $customer = Customer::query()->where('uuid', $uuid)->first();
        if (! $customer) {
            return $this->error('CUSTOMER_NOT_FOUND', 'Customer not found.', status: 404);
        }

        $customer->delete();

        return $this->success(['message' => 'Customer deleted.']);
    }

    #[OA\Get(
        path: '/api/v1/payments',
        summary: 'List payments',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'partner_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'customer_uuid', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
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

        return $this->paginated($query->paginate((int) $request->integer('per_page', 20)));
    }

    #[OA\Get(
        path: '/api/v1/payments/{payment}',
        summary: 'Show payment',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [new OA\Parameter(name: 'payment', in: 'path', required: true, description: 'Payment database id', schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function payment(Payment $payment): JsonResponse
    {
        $payment->load(['customer', 'partner']);

        return $this->success([
            'id' => $payment->id,
            'payment_uuid' => $payment->uuid,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'payment_date' => optional($payment->payment_date)->toIso8601String(),
            'transaction_reference' => $payment->transaction_reference,
            'payment_status' => $payment->payment_status,
            'customer_uuid' => $payment->customer?->uuid,
            'partner_id' => $payment->partner_id,
        ]);
    }

    #[OA\Get(path: '/api/v1/customers/expiring', summary: 'List customers with covers expiring within 30 days', security: [['sanctum' => []]], tags: ['Customers'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function expiringCustomers(Request $request): JsonResponse
    {
        $query = Customer::query()
            ->expiringSoon()
            ->with(['partner:id,name,uuid', 'product:id,name,uuid']);

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->integer('partner_id'));
        }

        $customers = $query->get()->map(fn (Customer $c) => [
            'uuid' => $c->uuid,
            'customer_id' => 'CUST_'.str_pad((string) $c->id, 6, '0', STR_PAD_LEFT),
            'full_name' => $c->full_name,
            'email' => $c->email,
            'phone' => $c->phone,
            'cover_end_date' => optional($c->cover_end_date)->toDateString(),
            'days_remaining' => (int) now()->diffInDays($c->cover_end_date, false),
            'partner_name' => $c->partner?->name,
            'product_name' => $c->product?->name,
        ]);

        return $this->success($customers);
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

        $query = Customer::query()
            ->selectRaw("product_id, partner_id, DATE_FORMAT(created_at, '{$format}') as bucket, count(*) as total")
            ->groupBy('product_id', 'partner_id', 'bucket')
            ->orderBy('bucket');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->toString());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->toString());
        }
        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->integer('partner_id'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        return $this->success($query->get());
    }

    #[OA\Get(path: '/api/v1/reports/revenue-by-product', summary: 'Revenue by product report', security: [['sanctum' => []]], tags: ['Reports'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function revenueByProductReport(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->selectRaw('customers.product_id, SUM(payments.amount) as total_revenue, COUNT(payments.id) as payment_count')
            ->join('users as customers', 'customers.id', '=', 'payments.customer_id')
            ->whereExists(function ($q): void {
                $q->selectRaw('1')
                    ->from('model_has_roles')
                    ->whereColumn('model_has_roles.model_id', 'customers.id')
                    ->where('model_has_roles.model_type', User::class)
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('roles.name', 'customer');
            });

        if ($request->filled('date_from')) {
            $query->whereDate('payments.payment_date', '>=', $request->string('date_from')->toString());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payments.payment_date', '<=', $request->string('date_to')->toString());
        }
        if ($request->filled('period') && ! $request->filled('date_from')) {
            $period = $request->string('period')->toString();
            $query->when($period === 'daily', fn ($q) => $q->whereDate('payments.payment_date', today()))
                ->when($period === 'weekly', fn ($q) => $q->whereBetween('payments.payment_date', [now()->startOfWeek(), now()->endOfWeek()]))
                ->when($period === 'monthly', fn ($q) => $q->whereMonth('payments.payment_date', now()->month)->whereYear('payments.payment_date', now()->year));
        }

        return $this->success($query->groupBy('customers.product_id')->get());
    }

    #[OA\Get(path: '/api/v1/products', summary: 'List products', security: [['sanctum' => []]], tags: ['Products'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function products(Request $request): JsonResponse
    {
        $actor = $request->user();
        $canViewPartnerPricing = $this->canViewPartnerPricing($actor);
        $paginator = Product::query()->with(['fields', 'partners'])->paginate((int) $request->integer('per_page', 15));
        $paginator->getCollection()->transform(function (Product $product) use ($canViewPartnerPricing) {
            $serialized = $product->toArray();
            $serialized['partners'] = collect($serialized['partners'] ?? [])->map(function (array $partner) use ($canViewPartnerPricing): array {
                if (! $canViewPartnerPricing && isset($partner['pivot']) && is_array($partner['pivot'])) {
                    $partner['pivot']['partner_price'] = null;
                    $partner['pivot']['partner_currency'] = null;
                }

                return $partner;
            })->all();

            return $serialized;
        });

        return $this->paginated($paginator);
    }

    #[OA\Post(
        path: '/api/v1/products',
        summary: 'Create product',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'status', 'cover_duration_options'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
                    new OA\Property(property: 'cover_duration_options', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(
                        property: 'fields',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'label', type: 'string'),
                                new OA\Property(property: 'type', type: 'string'),
                                new OA\Property(property: 'is_required', type: 'boolean'),
                                new OA\Property(property: 'options', type: 'array', items: new OA\Items(type: 'string')),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function storeProduct(StoreProductRequest $request): JsonResponse
    {
        $this->ensureAdminActor($request);

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

        return $this->success($product, 201);
    }

    #[OA\Patch(
        path: '/api/v1/products/{product}',
        summary: 'Update product',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
                    new OA\Property(property: 'cover_duration_options', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function updateProduct(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->ensureAdminActor($request);

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

        return $this->success($product->fresh(['fields']));
    }

    #[OA\Delete(path: '/api/v1/products/{product}', summary: 'Delete product', security: [['sanctum' => []]], tags: ['Products'], responses: [new OA\Response(response: 200, description: 'Deleted'), new OA\Response(response: 422, description: 'Validation')])]
    public function deleteProduct(Request $request, Product $product): JsonResponse
    {
        $this->ensureAdminActor($request);

        if ($product->customers()->where('status', 'active')->exists()) {
            return $this->error('PRODUCT_HAS_ACTIVE_CUSTOMERS', 'Cannot delete product with active customers.', status: 422);
        }

        $product->delete();

        return $this->success(['message' => 'Product deleted.']);
    }

    #[OA\Get(path: '/api/v1/products/{product}/versions', summary: 'Product version history', security: [['sanctum' => []]], tags: ['Products'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function productVersions(Product $product): JsonResponse
    {
        return $this->success(ProductVersion::query()
            ->where('product_id', $product->id)
            ->orderByDesc('version_number')
            ->get());
    }

    #[OA\Get(path: '/api/v1/partners', summary: 'List partners', security: [['sanctum' => []]], tags: ['Partners'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function partners(Request $request): JsonResponse
    {
        return $this->paginated(Partner::query()->latest('id')->paginate((int) $request->integer('per_page', 15)));
    }

    #[OA\Post(
        path: '/api/v1/partners',
        summary: 'Create partner',
        security: [['sanctum' => []]],
        tags: ['Partners'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function storePartner(StorePartnerRequest $request): JsonResponse
    {
        $this->ensureAdminActor($request);

        $partner = Partner::query()->create([
            ...$request->validated(),
            'slug' => Str::slug($request->string('name')->toString()),
            'status' => 'active',
        ]);
        $partner->syncRoles(['partner']);

        return $this->success($partner, 201);
    }

    #[OA\Patch(
        path: '/api/v1/partners/{partner}',
        summary: 'Update partner',
        security: [['sanctum' => []]],
        tags: ['Partners'],
        parameters: [new OA\Parameter(name: 'partner', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function updatePartner(UpdatePartnerRequest $request, Partner $partner): JsonResponse
    {
        $this->ensureAdminActor($request);

        $partner->update($request->validated());

        return $this->success($partner->fresh());
    }

    #[OA\Delete(path: '/api/v1/partners/{partner}', summary: 'Delete partner', security: [['sanctum' => []]], tags: ['Partners'], responses: [new OA\Response(response: 200, description: 'Deleted'), new OA\Response(response: 422, description: 'Validation')])]
    public function deletePartner(Request $request, Partner $partner): JsonResponse
    {
        $this->ensureAdminActor($request);

        if ($partner->customers()->exists()) {
            return $this->error('PARTNER_HAS_CUSTOMERS', 'Cannot delete partner with customer records.', status: 422);
        }

        $partner->delete();

        return $this->success(['message' => 'Partner deleted.']);
    }

    #[OA\Patch(
        path: '/api/v1/partners/{partner}/products/{product}/access',
        summary: 'Activate or deactivate product access for a partner',
        security: [['sanctum' => []]],
        tags: ['Partners'],
        parameters: [
            new OA\Parameter(name: 'partner', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'product', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
                    new OA\Property(property: 'partner_price', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'partner_currency', type: 'string', nullable: true, example: 'NGN'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function updatePartnerProductAccess(UpdatePartnerProductAccessRequest $request, Partner $partner, Product $product): JsonResponse
    {
        $this->ensureAdminActor($request);

        $validated = $request->validated();

        $status = $validated['status'];
        $payload = [
            'status' => $status,
            'partner_price' => $validated['partner_price'] ?? null,
            'partner_currency' => $validated['partner_currency'] ?? null,
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

        return $this->success([
            'partner_id' => $partner->id,
            'partner_uuid' => $partner->uuid,
            'product_id' => $product->id,
            'product_uuid' => $product->uuid,
            'status' => $status,
            'partner_price' => $payload['partner_price'],
            'partner_currency' => $payload['partner_currency'],
        ]);
    }

    #[OA\Get(path: '/api/v1/users', summary: 'List users', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function users(Request $request): JsonResponse
    {
        return $this->paginated(User::query()->with(['roles', 'profile'])->paginate((int) $request->integer('per_page', 15)));
    }

    #[OA\Post(
        path: '/api/v1/users',
        summary: 'Create user',
        security: [['sanctum' => []]],
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'role'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'role', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function storeUser(StoreUserRequest $request): JsonResponse
    {
        $this->ensureAdminActor($request);

        $actor = $request->user();
        $validated = $request->validated();
        if ($this->isAdminButNotSuperAdmin($actor) && ($validated['role'] ?? null) === 'super_admin') {
            return $this->error('ROLE_ASSIGNMENT_FORBIDDEN', 'Admins cannot assign the super_admin role.', status: 403);
        }

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

        return $this->success($user->load('roles', 'profile'), 201);
    }

    #[OA\Patch(
        path: '/api/v1/users/{user}',
        summary: 'Update user',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'role', type: 'string'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function updateUser(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->ensureAdminActor($request);

        $actor = $request->user();
        if (! $this->canManageUser($actor, $user)) {
            return $this->error('USER_UPDATE_FORBIDDEN', 'You are not allowed to update this user.', status: 403);
        }

        $validated = $request->validated();
        if ($this->isAdminButNotSuperAdmin($actor) && ($validated['role'] ?? null) === 'super_admin') {
            return $this->error('ROLE_ASSIGNMENT_FORBIDDEN', 'Admins cannot assign the super_admin role.', status: 403);
        }

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

        return $this->success($user->fresh(['roles', 'profile']));
    }

    #[OA\Delete(path: '/api/v1/users/{user}', summary: 'Delete user', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 200, description: 'Deleted'), new OA\Response(response: 403, description: 'Forbidden')])]
    public function deleteUser(Request $request, User $user): JsonResponse
    {
        $this->ensureAdminActor($request);

        $actor = $request->user();
        if (! $this->canManageUser($actor, $user)) {
            return $this->error('USER_DELETE_FORBIDDEN', 'You are not allowed to delete this user.', status: 403);
        }

        $user->delete();

        return $this->success(['message' => 'User deleted.']);
    }

    #[OA\Get(path: '/api/v1/access-control', summary: 'List roles and permissions', security: [['sanctum' => []]], tags: ['Users'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function accessControl(): JsonResponse
    {
        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('name')->get();
        $permissionNames = collect(config('admin_portal.permissions', []))
            ->filter(fn ($permission): bool => is_string($permission) && $permission !== '')
            ->values();
        $matrix = $permissionNames->map(function (string $permission) use ($roles): array {
            $allowed = [];
            foreach ($roles as $role) {
                $allowed[$role->name] = $role->permissions->pluck('name')->contains($permission);
            }

            return [
                'permission' => $permission,
                'function' => str_replace(['.', '_'], ' ', $permission),
                'allowed' => $allowed,
            ];
        })->values();

        return $this->success([
            'roles' => $roles,
            'permissions' => $permissions,
            'permission_matrix' => [
                'roles' => $roles->map(fn (Role $role): array => [
                    'name' => $role->name,
                    'label' => (string) data_get(config('admin_portal.roles'), "{$role->name}.label", str_replace('_', ' ', $role->name)),
                ])->values(),
                'rows' => $matrix,
            ],
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/users/{user}/access-control',
        summary: 'Assign role to user',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role'],
                properties: [new OA\Property(property: 'role', type: 'string')]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Updated'), new OA\Response(response: 403, description: 'Forbidden')]
    )]
    public function updateUserAccessControl(UpdateUserAccessControlRequest $request, User $user): JsonResponse
    {
        $this->ensureAdminActor($request);

        $actor = $request->user();
        if (! $this->canManageUser($actor, $user)) {
            return $this->error('ACCESS_CONTROL_FORBIDDEN', 'You are not allowed to change access control for this user.', status: 403);
        }

        $validated = $request->validated();
        if ($this->isAdminButNotSuperAdmin($actor) && ($validated['role'] ?? null) === 'super_admin') {
            return $this->error('ROLE_ASSIGNMENT_FORBIDDEN', 'Admins cannot assign the super_admin role.', status: 403);
        }

        DB::transaction(function () use ($validated, $user): void {
            $user->syncRoles([$validated['role']]);
            $user->syncPermissions([]);
        });
        AuditLog::record('admin_user_access_control_updated', $user, [], [
            'role' => $user->getRoleNames()->first(),
            'permissions' => $user->getPermissionsViaRoles()->pluck('name')->unique()->values()->all(),
        ], $actor);

        return $this->success($user->fresh(['roles', 'permissions', 'profile']));
    }

    #[OA\Get(path: '/api/v1/audit-logs', summary: 'List audit logs', security: [['sanctum' => []]], tags: ['Audit Logs'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function auditLogs(Request $request): JsonResponse
    {
        return $this->paginated(AuditLog::query()->latest('id')->paginate((int) $request->integer('per_page', 20)));
    }

    #[OA\Get(path: '/api/v1/settings', summary: 'List settings', security: [['sanctum' => []]], tags: ['Settings'], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function settings(): JsonResponse
    {
        return $this->success(Setting::query()->orderBy('key')->get());
    }

    #[OA\Patch(
        path: '/api/v1/settings',
        summary: 'Upsert settings',
        security: [['sanctum' => []]],
        tags: ['Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['settings'],
                properties: [new OA\Property(property: 'settings', type: 'object', additionalProperties: true)]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        $this->ensureAdminActor($request);

        $validated = $request->validated();

        foreach ($validated['settings'] as $key => $value) {
            Setting::setValue((string) $key, $value);
        }

        return $this->success(Setting::query()->orderBy('key')->get());
    }

    private function ensureAdminActor(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);
    }

    private function canManageUser(?User $actor, User $target): bool
    {
        if (! $actor) {
            return false;
        }
        if ((int) $actor->id === (int) $target->id) {
            return false;
        }

        if ($actor->hasRole('super_admin')) {
            return true;
        }

        if ($actor->hasRole('admin')) {
            return ! $target->hasRole('super_admin');
        }

        return false;
    }

    private function isAdminButNotSuperAdmin(?User $actor): bool
    {
        return (bool) ($actor?->hasRole('admin') && ! $actor?->hasRole('super_admin'));
    }

    private function canViewPartnerPricing(?User $actor): bool
    {
        return (bool) $actor?->hasAnyRole(['partner', 'super_admin', 'reconciliation_admin']);
    }
}
