<?php

namespace App\DTO;

class EmailConfig
{
    public function __construct(
        public readonly string $address,
        public readonly string $password,
        public readonly string $sentBox,
        public readonly string $imapHost,
        public readonly int $imapPort,
        public readonly string $smtpHost,
        public readonly int $smtpPort,
        public readonly bool $smtpAuth,
    ) {
    }
}
