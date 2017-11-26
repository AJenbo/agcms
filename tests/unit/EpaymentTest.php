<?php

use AGCMS\Epayment;
use AGCMS\EpaymentAdminService;
use Mockery as M;
use PHPUnit\Framework\TestCase;

class EpaymentTest extends TestCase
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
        $this->epaymentAdminService = M::mock(EpaymentAdminService::class);
    }

    /**
     * Create a payment.
     *
     * @param string $status
     * @param int    $amount
     * @param int    $captured
     *
     * @return Epayment
     */
    private function getPayment(string $status = 'PAYMENT_NEW', int $amount = 100, int $captured = 0): Epayment
    {
        $data = new StdClass();
        $data->transactionid = 1;
        $data->status = $status;
        $data->authamount = $amount;
        $data->capturedamount = $captured;

        return new Epayment($this->epaymentAdminService, $data);
    }

    /**
     * @covers \AGCMS\Epayment::__construct
     *
     * @return void
     */
    public function test_can_instanciate(): void
    {
        $epayment = $this->getPayment();
        $this->assertInstanceOf(Epayment::class, $epayment);
    }

    /**
     * @covers \AGCMS\Epayment::isAuthorized
     *
     * @return void
     */
    public function test_isAuthorized(): void
    {
        $epayment = $this->getPayment();
        $this->assertTrue($epayment->isAuthorized());
    }

    /**
     * @covers \AGCMS\Epayment::getId
     *
     * @return void
     */
    public function test_getId(): void
    {
        $epayment = $this->getPayment();
        $this->assertSame(1, $epayment->getId());
    }

    /**
     * @covers \AGCMS\Epayment::annul
     * @covers \AGCMS\Epayment::isAnnulled
     *
     * @return void
     */
    public function test_annul(): void
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

    /**
     * @covers \AGCMS\Epayment::annul
     * @covers \AGCMS\Epayment::isAnnulled
     *
     * @return void
     */
    public function test_annul_fail(): void
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

    /**
     * @covers \AGCMS\Epayment::__construct
     * @covers \AGCMS\Epayment::isAnnulled
     *
     * @return void
     */
    public function test_confirm_preCancled(): void
    {
        $epayment = $this->getPayment('PAYMENT_DELETED');

        $this->assertTrue($epayment->annul());
        $this->assertTrue($epayment->isAnnulled());
    }

    /**
     * @covers \AGCMS\Epayment::confirm
     * @covers \AGCMS\Epayment::doCapture
     * @covers \AGCMS\Epayment::getAmountCaptured
     *
     * @return void
     */
    public function test_confirm(): void
    {
        $epayment = $this->getPayment();

        $this->epaymentAdminService
            ->shouldReceive('confirm')
            ->with($epayment, 100)
            ->once()
            ->andReturn(true);

        $this->assertTrue($epayment->confirm());
        $this->assertSame(100, $epayment->getAmountCaptured());
    }

    /**
     * @covers \AGCMS\Epayment::confirm
     * @covers \AGCMS\Epayment::doCapture
     * @covers \AGCMS\Epayment::getAmountCaptured
     *
     * @return void
     */
    public function test_confirm_fail(): void
    {
        $epayment = $this->getPayment();

        $this->epaymentAdminService
            ->shouldReceive('confirm')
            ->with($epayment, 100)
            ->once()
            ->andReturn(false);

        $this->assertFalse($epayment->confirm());
        $this->assertSame(0, $epayment->getAmountCaptured());
    }

    /**
     * @covers \AGCMS\Epayment::confirm
     * @covers \AGCMS\Epayment::doCapture
     * @covers \AGCMS\Epayment::getAmountCaptured
     *
     * @return void
     */
    public function test_confirm_overcharge(): void
    {
        $epayment = $this->getPayment();

        $this->assertFalse($epayment->confirm(200));
        $this->assertSame(0, $epayment->getAmountCaptured());
    }

    /**
     * @covers \AGCMS\Epayment::__construct
     * @covers \AGCMS\Epayment::getAmountCaptured
     *
     * @return void
     */
    public function test_confirm_preCaptured(): void
    {
        $epayment = $this->getPayment('PAYMENT_CAPTURED', 100, 100);


        $this->assertTrue($epayment->confirm());
        $this->assertSame(100, $epayment->getAmountCaptured());
    }
}
