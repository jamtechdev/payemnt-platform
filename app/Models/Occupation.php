<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Occupation extends Model
{
    protected $fillable = ['partner_id', 'name', 'status'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
