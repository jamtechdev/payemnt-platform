<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Customer extends User
{
    use HasAuditLog;
    protected $table = 'users';
    protected $fillable = [
        'name',
        'slug',
        'uuid',
        'email',
        'phone',
        'status',
        'password',
        'last_login_at',
        'is_active',
        'login_attempts',
        'locked_until',
        'metadata',
        'partner_id',
        'product_id',
        'first_name',
        'last_name',
        'cover_start_date',
        'cover_duration_months',
        'cover_end_date',
        'customer_since',
        'submitted_data',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'cover_start_date' => 'date',
            'cover_end_date' => 'date',
            'customer_since' => 'date',
            'submitted_data' => 'array',
        ]);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('customer_role', function (Builder $query): void {
            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'customer'));
        });

        static::creating(function (Customer $customer): void {
            $customer->uuid = $customer->uuid ?? (string) Str::uuid();
            $customer->name = trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
            $customer->slug = Str::slug($customer->name.'-'.$customer->uuid);
            $customer->status = $customer->status ?? 'active';
            if (! $customer->password) {
                $customer->password = Hash::make(Str::random(24));
            }
            $start = Carbon::parse($customer->cover_start_date);
            $customer->cover_end_date = $start->copy()->addMonths((int) $customer->cover_duration_months)->toDateString();
            $customer->customer_since = $customer->customer_since ?? now()->toDateString();
            $customer->is_active = $customer->status === 'active';

            $exists = self::query()
                ->where('partner_id', $customer->partner_id)
                ->where('email', $customer->email)
                ->exists();
            $metadata = $customer->metadata ?? [];
            $metadata['is_returning_customer'] = $exists;
            $customer->metadata = $metadata;
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

    public function anonymize(): void
    {
        $this->forceFill([
            'first_name' => 'ANONYMIZED',
            'last_name' => 'USER-'.$this->id,
            'email' => 'anon-'.$this->uuid.'@example.invalid',
            'name' => 'ANONYMIZED USER',
            'phone' => null,
            'submitted_data' => [],
        ])->save();
    }

    public function getMorphClass(): string
    {
        return User::class;
    }
}
