<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundWallet extends Model
{
    protected $fillable = [
        'partner_id', 'customer_email', 'bank_name',
        'amount', 'description', 'image_url', 'status', 'date_added',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'date_added' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
