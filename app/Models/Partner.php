<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Partner extends User
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
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('partner_role', function (Builder $query): void {
            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'partner'));
        });

        static::creating(function (Partner $partner): void {
            if (! $partner->slug) {
                $partner->slug = Str::slug($partner->name);
            }
            if (! $partner->password) {
                $partner->password = bcrypt(Str::random(24));
            }
            if (! $partner->status) {
                $partner->status = 'active';
            }
            $partner->is_active = $partner->status === 'active';
        });
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'partner_products')
            ->withPivot(['status', 'activated_at', 'deactivated_at'])
            ->withTimestamps();
    }

    public function getMorphClass(): string
    {
        return User::class;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

}
