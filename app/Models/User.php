<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function rolesRelation(): BelongsToMany
    {
        return $this->belongsToMany(config('permission.models.role'), config('permission.table_names.model_has_roles'), 'model_id', 'role_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function partnerProfile(): HasOne
    {
        return $this->hasOne(PartnerProfile::class);
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'customer_id');
    }

    public function partnerPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'partner_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function incrementLoginAttempts(): void
    {
        $attempts = $this->login_attempts + 1;
        $this->login_attempts = $attempts;
        if ($attempts >= 5) {
            $this->locked_until = now()->addMinutes(30);
        }
        $this->save();
    }

    public function resetLoginAttempts(): void
    {
        $this->forceFill([
            'login_attempts' => 0,
            'locked_until' => null,
        ])->save();
    }
}
