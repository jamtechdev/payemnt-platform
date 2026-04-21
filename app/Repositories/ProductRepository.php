<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function findActiveByIdOrFail(int $id): Product
    {
        return Product::query()->active()->with('fields')->findOrFail($id);
    }
}
