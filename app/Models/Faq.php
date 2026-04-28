<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $primaryKey = 'faq_id';

    protected $fillable = [
        'faq_code',
        'question',
        'answer',
        'partner_id',
        'partner_code',
        'status',
        'from_platform',
    ];
}
