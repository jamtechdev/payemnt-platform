<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralUsage extends Model
{
    protected $fillable = ['partner_id', 'referrer_email', 'used_by_email', 'refer_code', 'date_used'];

    protected function casts(): array
    {
        return ['date_used' => 'datetime'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
