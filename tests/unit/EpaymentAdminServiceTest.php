<?php namespace AGCMS\Tests;

use AGCMS\EpaymentAdminService;
use PHPUnit\Framework\TestCase;

class EpaymentAdminServiceTest extends TestCase
{
    /** @var EpaymentAdminService */
    private $epaymentAdminService;

    /**
     * Initiate the mock.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->epaymentAdminService = new EpaymentAdminService('', '');
    }

    /**
     * @covers \AGCMS\EpaymentAdminService::__construct
     *
     * @return void
     */
    public function testCanInstanciate(): void
    {
        $this->assertInstanceOf(EpaymentAdminService::class, $this->epaymentAdminService);
    }
}
