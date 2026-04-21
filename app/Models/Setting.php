<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use JsonException;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();
        if ($row === null || $row->value === null || $row->value === '') {
            return $default;
        }

        try {
            return json_decode($row->value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $row->value;
        }
    }

    public static function setValue(string $key, mixed $value): void
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $encoded],
        );
    }
}
