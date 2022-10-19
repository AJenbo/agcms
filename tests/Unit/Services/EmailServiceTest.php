<?php

namespace Tests\Unit\Services;

use App\Services\EmailService;
use PHPUnit\Framework\TestCase;

class EmailServiceTest extends TestCase
{
    private EmailService $emailService;

    /**
     * Initiate the mock.
     */
    protected function setUp(): void
    {
        $this->emailService = new EmailService();
    }

    /**
     * @covers \App\Services\EmailService::validemail
     */
    public function testValidAddress(): void
    {
        static::assertTrue($this->emailService->validemail('_An-._E-mail@gmail.com'));
    }

    /**
     * @covers \App\Services\EmailService::checkMx
     */
    public function testValidAddressIdnDomain(): void
    {
        static::assertTrue($this->emailService->validemail('email@sørensen.dk'));
    }
}
