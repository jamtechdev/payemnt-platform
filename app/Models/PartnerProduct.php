<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PartnerProduct extends Pivot
{
    protected $table = 'partner_product';

    protected $casts = [
        'is_enabled'  => 'boolean',
        'base_price'  => 'decimal:2',
        'guide_price' => 'decimal:2',
        'rule_overrides' => 'array',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
