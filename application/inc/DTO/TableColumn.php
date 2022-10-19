<?php

namespace App\DTO;

use App\Enums\ColumnType;

class TableColumn
{
    /**
     * @param array<int, string> $options
     */
    public function __construct(
        public readonly string $title,
        public readonly ColumnType $type,
        public readonly int $sorting,
        public readonly array $options,
    ) {
    }
}
