<?php

class EpaymentAdminServiceTest extends TestCase
{
    private $epaymentAdminService;

    public function setUp()
    {
        $this->epaymentAdminService = new EpaymentAdminService('', '', '');
    }

    public function test_can_instanciate()
    {
        $this->assertInstanceOf(EpaymentAdminService::class, $this->epaymentAdminService);
    }

    public function test_getId()
    {
        $this->assertEquals(0, $this->epaymentAdminService->getId());
    }

    public function test_isAnnulled()
    {
        $this->assertFalse($this->epaymentAdminService->isAnnulled());
    }

    public function test_hasError()
    {
        $this->assertFalse($this->epaymentAdminService->hasError());
    }

    public function test_isAuthorized()
    {
        $this->assertFalse($this->epaymentAdminService->isAuthorized());
    }

    public function test_getAmountCaptured()
    {
        $this->assertEquals(0, $this->epaymentAdminService->getAmountCaptured());
    }

    public function test_annul()
    {
        $this->assertFalse($this->epaymentAdminService->annul());
    }

    public function test_confirm()
    {
        $this->assertFalse($this->epaymentAdminService->confirm());
    }
}
