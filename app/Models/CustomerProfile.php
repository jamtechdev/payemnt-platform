<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'partner_user_id',
        'product_id',
        'first_name',
        'last_name',
        'cover_start_date',
        'cover_duration_months',
        'cover_end_date',
        'customer_since',
        'submitted_data',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'cover_start_date' => 'date',
            'cover_end_date' => 'date',
            'customer_since' => 'date',
            'submitted_data' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
