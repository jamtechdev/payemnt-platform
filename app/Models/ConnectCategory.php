<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectCategory extends Model
{
    protected $primaryKey = 'connect_categories_id';

    protected $fillable = [
        'category_code',
        'partner_id',
        'partner_code',
        'name',
        'icon_url',
        'status',
        'from_platform',
    ];
}
