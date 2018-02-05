<?php namespace Tests\Unit\Services;

use App\Services\EmailService;
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
     * @covers \App\Services\EmailService::validemail
     *
     * @return void
     */
    public function testValidAddress(): void
    {
        $this->assertTrue($this->emailService->validemail('_An-._E-mail@gmail.com'));
    }

    /**
     * @covers \App\Services\EmailService::validemail
     *
     * @return void
     */
    public function testValideAddressForNonExistingDomain(): void
    {
        $this->assertFalse($this->emailService->validemail('email@test.notadomain'));
    }

    /**
     * @covers \App\Services\EmailService::checkMx
     *
     * @return void
     */
    public function testValidAddressIdnDomain(): void
    {
        $this->assertTrue($this->emailService->validemail('email@sørensen.dk'));
    }
}
