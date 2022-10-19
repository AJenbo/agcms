<?php

namespace App\DTO;

class InvoiceFilter
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $year,
        public readonly int $month,
        public readonly string $department,
        public readonly string $status,
        public readonly string $name,
        public readonly string $tlf,
        public readonly string $email,
        public readonly ?string $momssats,
        public readonly string $clerk,
    ) {
    }
}
