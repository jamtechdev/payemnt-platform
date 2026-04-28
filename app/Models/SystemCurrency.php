<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemCurrency extends Model
{
    protected $fillable = ['partner_id', 'name', 'code', 'symbol', 'margin', 'admin_rate', 'status'];

    protected function casts(): array
    {
        return [
            'margin'     => 'decimal:2',
            'admin_rate' => 'decimal:2',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
