<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductField extends Model
{
    use HasAuditLog;

    protected $fillable = [
        'product_id',
        'name',
        'label',
        'type',
        'options',
        'is_required',
        'sort_order',
        'validation_rules',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'validation_rules' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function getOptionsAttribute($value): array
    {
        if ($value === null) {
            return [];
        }

        return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
    }
}
