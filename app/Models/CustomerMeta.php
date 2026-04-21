<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMeta extends Model
{
    protected $table = 'customer_meta';

    protected $fillable = [
        'customer_id',
        'meta_key',
        'meta_value',
    ];

    protected function casts(): array
    {
        return [
            'meta_value' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
