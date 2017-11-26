<?php

use AGCMS\Service\EmailService;
use PHPUnit\Framework\TestCase;

class EmailServiceTest extends TestCase
{
    /** @var EmailService */
    private $emailService;

    /**
     * Initiate the mock.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->emailService = new EmailService();
    }

    /**
     * @return void
     */
    public function test_validemail(): void
    {
        $this->assertTrue($this->emailService->validemail('_An-._E-mail@gmail.com'));
    }

    /**
     * @return void
     */
    public function test_validemail_fake_domain(): void
    {
        $this->assertFalse($this->emailService->validemail('email@test.notadomain'));
    }

    /**
     * @return void
     */
    public function test_validemail_IDN_domain(): void
    {
        $this->assertTrue($this->emailService->validemail('email@sørensen.dk'));
    }
}
