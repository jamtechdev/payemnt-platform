<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Admin\StorePartnerRequest;
use App\Models\Partner;
use App\Models\Product;
use App\Http\Requests\Api\V1\Admin\UpdatePartnerProductAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPartnerController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $partners = Partner::query()->latest()->paginate(min((int) $request->integer('per_page', 20), 100));
        return $this->paginated($partners);
    }

    public function store(StorePartnerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name'].'-'.$validated['partner_code']);

        return $this->success(Partner::query()->create($validated), 201);
    }

    public function show(Partner $partner): JsonResponse
    {
        return $this->success($partner->load('products'));
    }

    public function update(Request $request, Partner $partner): JsonResponse
    {
        $partner->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'in:active,inactive,suspended'],
            'settings' => ['nullable', 'array'],
        ]));

        return $this->success($partner->fresh());
    }

    public function destroy(Partner $partner): JsonResponse
    {
        $partner->delete();
        return $this->success(['message' => 'Partner deleted']);
    }

    public function updateProductAccess(UpdatePartnerProductAccessRequest $request, Partner $partner, Product $product): JsonResponse
    {
        $validated = $request->validated();

        $partner->products()->syncWithoutDetaching([
            $product->id => [
                'is_enabled' => (bool) $validated['is_enabled'],
                'partner_price' => $validated['partner_price'] ?? null,
                'partner_currency' => $validated['partner_currency'] ?? null,
                'cover_duration_days_override' => $validated['cover_duration_days_override'] ?? null,
            ],
        ]);

        $partner->load(['products' => function ($query) use ($product): void {
            $query->where('products.id', $product->id);
        }]);

        return $this->success([
            'message' => 'Partner product access updated successfully.',
            'partner_id' => $partner->partner_code,
            'product_id' => $product->product_code,
            'access' => $partner->products->first()?->pivot,
        ]);
    }
}
