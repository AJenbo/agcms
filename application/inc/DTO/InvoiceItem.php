<?php

namespace App\DTO;

class InvoiceItem
{
    public function __construct(
        public readonly int $quantity,
        public readonly string $title,
        public readonly float $value,
    ) {
    }
}
