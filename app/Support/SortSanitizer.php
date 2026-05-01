<?php

declare(strict_types=1);

namespace App\Support;

class SortSanitizer
{
    /**
     * @param string[] $allowedColumns
     * @return array{column: string, direction: 'asc'|'desc'}
     */
    public static function resolve(string $requestedColumn, string $requestedDirection, array $allowedColumns, string $defaultColumn = 'created_at'): array
    {
        $column = in_array($requestedColumn, $allowedColumns, true) ? $requestedColumn : $defaultColumn;
        $direction = strtolower($requestedDirection) === 'asc' ? 'asc' : 'desc';

        return [
            'column' => $column,
            'direction' => $direction,
        ];
    }
}
