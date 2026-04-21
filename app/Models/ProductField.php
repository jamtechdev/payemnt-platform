<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductField extends Model
{
    protected $fillable = [
        'product_id',
        'field_key',
        'label',
        'field_type',
        'options',
        'is_required',
        'is_filterable',
        'sort_order',
        'validation_rule',
        'default_value',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'default_value' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('field_type', $type);
    }
}
