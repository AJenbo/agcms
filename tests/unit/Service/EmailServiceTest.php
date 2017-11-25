<?php

use AGCMS\Service\EmailService;
use PHPUnit\Framework\TestCase;

class EmailServiceTest extends TestCase
{
    private $emailService;

    public function setUp()
    {
        $this->emailService = new EmailService();
    }

    public function test_validemail()
    {
        $this->assertTrue($this->emailService->validemail('_An-._E-mail@gmail.com'));
    }

    public function test_validemail_fake_domain()
    {
        $this->assertFalse($this->emailService->validemail('email@test.notadomain'));
    }

    public function test_validemail_IDN_domain()
    {
        $this->assertTrue($this->emailService->validemail('email@sørensen.dk'));
    }
}
