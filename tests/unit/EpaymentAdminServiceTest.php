<?php

use AGCMS\EpaymentAdminService;

class EpaymentAdminServiceTest extends PHPUnit_Framework_TestCase
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