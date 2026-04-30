<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    private static ?bool $hasProductNameColumn = null;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'uuid',
        'product_code',
        'partner_id',
        'partner_code',
        'country',
        'name',
        'product_name',
        'slug',
        'description',
        'price',
        'base_price',
        'guide_price',
        'guide_price_set_by',
        'image',
        'cover_duration_mode',
        'cover_duration_type',
        'default_cover_duration_days',
        'cover_duration_options',
        'status',
        'settings',
        'api_schema',
    ];

    protected function casts(): array
    {
        return [
            'cover_duration_options' => 'array',
            'default_cover_duration_days' => 'integer',
            'settings' => 'array',
            'api_schema' => 'array',
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

    protected function name(): Attribute
    {
        return Attribute::make(
            set: function (?string $value, array $attributes): array {
                $resolved = $value ?? ($attributes['product_name'] ?? null);
                $hasProductNameColumn = self::$hasProductNameColumn ??= Schema::hasColumn($this->getTable(), 'product_name');

                if (! $hasProductNameColumn) {
                    return [
                        'name' => $resolved,
                    ];
                }

                return [
                    'name' => $resolved,
                    'product_name' => $attributes['product_name'] ?? $resolved,
                ];
            }
        );
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ProductField::class)->orderBy('sort_order');
    }

    public function partnerDirect(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function guidePriceCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guide_price_set_by');
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
