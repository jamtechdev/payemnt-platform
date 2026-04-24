<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'product_code',
        'partner_id',
        'partner_code',
        'country',
        'name',
        'slug',
        'description',
        'price',
        'image',
        'cover_duration_mode',
        'cover_duration_type',
        'default_cover_duration_days',
        'cover_duration_options',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'cover_duration_options' => 'array',
            'default_cover_duration_days' => 'integer',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (! $product->uuid) {
                $product->uuid = (string) Str::uuid();
            }
        });
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ProductField::class)->orderBy('sort_order');
    }

    public function partnerDirect(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'partner_product')
            ->withPivot(['is_enabled', 'cover_duration_days_override', 'rule_overrides'])
            ->withTimestamps();
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
