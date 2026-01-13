<?php

namespace App\DTO;

use App\Enums\ColumnType;

class TableColumn
{
    /**
     * @param array<string> $options
     */
    public function __construct(
        public readonly string $title,
        public readonly ColumnType $type,
        public readonly int $sorting,
        public readonly array $options,
    ) {
    }

    public function isText(): bool
    {
        return $this->type === ColumnType::String;
    }

    public function isPrice(): bool
    {
        return in_array($this->type, [ColumnType::Price, ColumnType::SalesPrice, ColumnType::PreviousPrice], true);
    }

    public function isCheckoutPrice(): bool
    {
        return in_array($this->type, [ColumnType::Price, ColumnType::SalesPrice], true);
    }

    public function isPreviousPrice(): bool
    {
        return $this->type === ColumnType::PreviousPrice;
    }

    public function isDiscountPrice(): bool
    {
        return $this->type === ColumnType::SalesPrice;
    }
}
