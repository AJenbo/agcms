<?php

use AGCMS\EpaymentAdminService;
use AGCMS\Epayment;
use Mockery as M;

class EpaymentTest extends PHPUnit_Framework_TestCase
{
    private $epaymentAdminService;
    private $epayment;

    public function setUp()
    {
        $this->epaymentAdminService = M::mock(EpaymentAdminService::class);
    }

    private function getPayment($status = 'PAYMENT_NEW', $amount = 100, $captured = 0)
    {
        $data = new StdClass();
        $data->transactionid = 1;
        $data->status = $status;
        $data->authamount = $amount;
        $data->capturedamount = $captured;

        return new Epayment($this->epaymentAdminService, $data);
    }

    public function test_can_instanciate()
    {
        $epayment = $this->getPayment();
        $this->assertInstanceOf(Epayment::class, $epayment);
    }

    public function test_isAuthorized()
    {
        $epayment = $this->getPayment();
        $this->assertTrue($epayment->isAuthorized());
    }

    public function test_getId()
    {
        $epayment = $this->getPayment();
        $this->assertEquals(1, $epayment->getId());
    }

    public function test_annul()
    {
        $epayment = $this->getPayment();

        $this->epaymentAdminService
            ->shouldReceive('annul')
            ->with($epayment)
            ->once()
            ->andReturn(true);

        $this->assertTrue($epayment->annul());
        $this->assertTrue($epayment->isAnnulled());
    }

    public function test_annul_fail()
    {
        $epayment = $this->getPayment();

        $this->epaymentAdminService
            ->shouldReceive('annul')
            ->with($epayment)
            ->once()
            ->andReturn(false);

        $this->assertFalse($epayment->annul());
        $this->assertFalse($epayment->isAnnulled());
    }

    public function test_confirm_preCancled()
    {
        $epayment = $this->getPayment('PAYMENT_DELETED');

        $this->assertTrue($epayment->annul());
        $this->assertTrue($epayment->isAnnulled());
    }

    public function test_confirm()
    {
        $epayment = $this->getPayment();

        $this->epaymentAdminService
            ->shouldReceive('confirm')
            ->with($epayment, 100)
            ->once()
            ->andReturn(true);

        $this->assertTrue($epayment->confirm());
        $this->assertEquals(100, $epayment->getAmountCaptured());
    }

    public function test_confirm_fail()
    {
        $epayment = $this->getPayment();

        $this->epaymentAdminService
            ->shouldReceive('confirm')
            ->with($epayment, 100)
            ->once()
            ->andReturn(false);

        $this->assertFalse($epayment->confirm());
        $this->assertEquals(0, $epayment->getAmountCaptured());
    }

    public function test_confirm_overcharge()
    {
        $epayment = $this->getPayment();

        $this->assertFalse($epayment->confirm(200));
        $this->assertEquals(0, $epayment->getAmountCaptured());
    }

    public function test_confirm_preCaptured()
    {
        $epayment = $this->getPayment('PAYMENT_CAPTURED', 100, 100);

        $this->assertTrue($epayment->confirm());
        $this->assertEquals(100, $epayment->getAmountCaptured());
    }
}
