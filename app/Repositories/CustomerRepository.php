<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerRepository
{
    public function create(array $payload): Customer
    {
        return Customer::query()->create($payload);
    }

    public function listForAdmin(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::query()
            ->with(['partner:id,name,partner_code', 'product:id,name,product_code'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->search((string) $search))
            ->when($filters['partner_id'] ?? null, fn ($query, $partnerId) => $query->where('partner_id', $partnerId))
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('product_id', $productId))
            ->when($filters['from_date'] ?? null, fn ($query, $fromDate) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($filters['to_date'] ?? null, fn ($query, $toDate) => $query->whereDate('created_at', '<=', $toDate))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
