<?php

class EpaymentAdminServiceTest extends TestCase
{
    private $epaymentAdminService;

    public function setUp()
    {
        $this->epaymentAdminService = new EpaymentAdminService('', '');
    }

    public function test_can_instanciate()
    {
        $this->assertInstanceOf(EpaymentAdminService::class, $this->epaymentAdminService);
    }
}
