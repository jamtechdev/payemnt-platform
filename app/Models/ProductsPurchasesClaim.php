<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductsPurchasesClaim extends Model
{
    protected $fillable = [
        'partner_id', 'customer_email', 'product_code',
        'date', 'description', 'acknowledged', 'date_added',
    ];

    protected function casts(): array
    {
        return [
            'date'       => 'date',
            'date_added' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
