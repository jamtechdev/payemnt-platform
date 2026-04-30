<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Partner extends Model
{
    use HasApiTokens;
    use SoftDeletes;

    private static ?bool $hasPartnerNameColumn = null;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'uuid',
        'partner_code',
        'api_key',
        'name',
        'partner_name',
        'slug',
        'contact_email',
        'email',
        'contact_phone',
        'phone',
        'status',
        'settings',
        'connected_base_url',
        'connected_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'settings'           => 'array',
            'last_seen_at'       => 'datetime',
            'connected_at'       => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Partner $partner): void {
            if (! $partner->uuid) {
                $partner->uuid = (string) Str::uuid();
            }
            $partner->slug = $partner->slug ?: Str::slug($partner->name.'-'.$partner->partner_code);
            if (! $partner->status) {
                $partner->status = 'active';
            }
        });
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: function (?string $value, array $attributes): array {
                $resolved = $value ?? ($attributes['partner_name'] ?? null);
                $hasPartnerNameColumn = self::$hasPartnerNameColumn ??= Schema::hasColumn($this->getTable(), 'partner_name');

                if (! $hasPartnerNameColumn) {
                    return [
                        'name' => $resolved,
                    ];
                }

                return [
                    'name' => $resolved,
                    'partner_name' => $attributes['partner_name'] ?? $resolved,
                ];
            }
        );
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(PartnerApiToken::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'partner_product')
            ->withPivot(['is_enabled', 'partner_price', 'partner_currency', 'cover_duration_days_override', 'rule_overrides'])
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function transactions(): HasMany
    {
        return $this->payments();
    }

    public function hasActiveApiKey(): bool
    {
        if ($this->relationLoaded('tokens')) {
            return $this->tokens->isNotEmpty();
        }

        return $this->tokens()->exists();
    }

    public function generateApiKey(): string
    {
        $this->tokens()->delete();
        $plainTextToken = $this->createToken('partner-api')->plainTextToken;

        $this->forceFill([
            'api_key' => hash('sha256', $plainTextToken),
            'last_seen_at' => now(),
        ])->save();

        return $plainTextToken;
    }
}
