<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Partner extends User
{
    use HasAuditLog, HasApiTokens;
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
        'api_key_last_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'api_key_last_generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('partner_role', function (Builder $query): void {
            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'partner'));
        });

        static::creating(function (Partner $partner): void {
            if (! $partner->uuid) {
                $partner->uuid = (string) Str::uuid();
            }
            $partner->slug = Str::slug($partner->name.'-'.$partner->uuid);
            if (! $partner->password) {
                $partner->password = bcrypt(Str::random(24));
            }
            if (! $partner->status) {
                $partner->status = 'active';
            }
            $partner->is_active = $partner->status === 'active';
            // Initialize login attempts
            $partner->login_attempts = 0;
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
            ->withPivot(['status', 'partner_price', 'partner_currency', 'activated_at', 'deactivated_at'])
            ->withTimestamps();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(PartnerProfile::class, 'user_id');
    }

    public function getMorphClass(): string
    {
        return User::class;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_active', true);
    }

    public function generateApiKey(string $name = 'API Access'): string
    {
        // Revoke existing tokens
        $this->tokens()->delete();
        
        // Generate new token
        $token = $this->createToken($name);
        
        // Update last generated timestamp
        $this->update(['api_key_last_generated_at' => now()]);
        
        return $token->plainTextToken;
    }

    public function hasActiveApiKey(): bool
    {
        return $this->tokens()->where('name', 'API Access')->exists();
    }

    public function getApiKeyStatusAttribute(): string
    {
        return $this->hasActiveApiKey() ? 'active' : 'inactive';
    }

    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
        
        // Lock account after 5 failed attempts (BRD AUTH-005)
        if ($this->login_attempts >= 5) {
            $this->update([
                'locked_until' => now()->addMinutes(30), // Lock for 30 minutes
                'is_active' => false
            ]);
        }
    }

    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now()
        ]);
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function getPartnerIdAttribute(): string
    {
        return 'PARTNER_' . str_pad((string)$this->id, 3, '0', STR_PAD_LEFT);
    }

    public function unlockAccount(): void
    {
        if ($this->locked_until && $this->locked_until->isPast()) {
            $this->update([
                'locked_until' => null,
                'login_attempts' => 0,
                'is_active' => $this->status === 'active'
            ]);
        }
    }

}
