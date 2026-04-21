<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'customer_code',
        'partner_id',
        'product_id',
        'external_customer_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'age',
        'gender',
        'address',
        'email',
        'phone',
        'status',
        'cover_start_date',
        'cover_duration',
        'start_date',
        'cover_duration_days',
        'cover_end_date',
        'customer_since',
        'last_payment_date',
        'customer_data',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'date_of_birth' => 'date',
            'cover_start_date' => 'date',
            'cover_end_date' => 'date',
            'customer_since' => 'date',
            'last_payment_date' => 'datetime',
            'customer_data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            if (! $customer->uuid) {
                $customer->uuid = (string) Str::uuid();
            }
            if (! $customer->customer_code) {
                $customer->customer_code = 'CUST_'.str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT);
            }
            $customer->status = $customer->status ?: 'active';
            $start = Carbon::parse($customer->start_date);
            $customer->cover_end_date = $start->copy()->addDays((int) $customer->cover_duration_days)->toDateString();
            $customer->customer_since = $customer->customer_since ?? now()->toDateString();
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function meta(): HasMany
    {
        return $this->hasMany(CustomerMeta::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->first_name.' '.$this->last_name),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereBetween('cover_end_date', [now()->toDateString(), now()->addDays(30)->toDateString()]);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereDate('cover_end_date', '<', now()->toDateString());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term): void {
            $builder->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
