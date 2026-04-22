<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'web';

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'password',
        'status',
        'is_active',
        'login_attempts',
        'locked_until',
        'last_login_at',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! $user->uuid) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function isLocked(): bool
    {
        if ($this->hasRole('super_admin')) {
            return false;
        }

        return $this->locked_until instanceof Carbon && $this->locked_until->isFuture();
    }

    public function incrementLoginAttempts(): void
    {
        if ($this->hasRole('super_admin')) {
            return;
        }

        $attempts = $this->login_attempts + 1;
        $this->forceFill(['login_attempts' => $attempts]);

        if ($attempts >= 5) {
            $this->forceFill([
                'locked_until' => now()->addMinutes(30),
                'status' => 'suspended',
                'is_active' => false,
            ]);
        }

        $this->save();
    }

    public function resetLoginAttempts(): void
    {
        $this->forceFill([
            'login_attempts' => 0,
            'locked_until' => null,
            'status' => 'active',
            'is_active' => true,
            'last_login_at' => now(),
        ])->save();
    }
}
