<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectArticle extends Model
{
    protected $primaryKey = 'connect_articles_id';

    protected $fillable = [
        'article_code',
        'category_code',
        'title',
        'description',
        'image_url',
        'partner_id',
        'partner_code',
        'status',
        'from_platform',
    ];
}
