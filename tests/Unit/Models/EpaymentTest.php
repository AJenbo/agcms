<?php

namespace Tests\Unit\Models;

use App\Models\Epayment;
use App\Services\EpaymentService;
use Mockery as M;
use Mockery\Expectation;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class EpaymentTest extends TestCase
{
    private EpaymentService $epaymentService;

    /**
     * Initiate the mock.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $epaymentService = M::mock(EpaymentService::class);
        $this->epaymentService = $epaymentService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        M::close();
    }

    /**
     * Create a payment.
     */
    private function getPayment(string $status = 'PAYMENT_NEW', int $amount = 100, int $captured = 0): Epayment
    {
        $data = new StdClass();
        $data->transactionid = 1;
        $data->status = $status;
        $data->authamount = $amount;
        $data->capturedamount = $captured;

        return new Epayment($this->epaymentService, $data);
    }

    /**
     * @covers \App\Models\Epayment::__construct
     */
    public function testCanInstanciate(): void
    {
        $epayment = $this->getPayment();
        $this->assertInstanceOf(Epayment::class, $epayment);
    }

    /**
     * @covers \App\Models\Epayment::isAuthorized
     */
    public function testIsAuthorized(): void
    {
        $epayment = $this->getPayment();
        $this->assertTrue($epayment->isAuthorized());
    }

    /**
     * @covers \App\Models\Epayment::getId
     */
    public function testGetId(): void
    {
        $epayment = $this->getPayment();
        $this->assertSame(1, $epayment->getId());
    }

    /**
     * @covers \App\Models\Epayment::annul
     * @covers \App\Models\Epayment::isAnnulled
     */
    public function testAnnulPayment(): void
    {
        $epayment = $this->getPayment();

        if ($this->epaymentService instanceof MockInterface) {
            $this->mockMethod($this->epaymentService, 'annul', true, [$epayment]);
        }

        $this->assertTrue($epayment->annul());
        $this->assertTrue($epayment->isAnnulled());
    }

    /**
     * @covers \App\Models\Epayment::annul
     * @covers \App\Models\Epayment::isAnnulled
     */
    public function testAnnulPaymentFail(): void
    {
        $epayment = $this->getPayment();

        if ($this->epaymentService instanceof MockInterface) {
            $this->mockMethod($this->epaymentService, 'annul', false, [$epayment]);
        }

        $this->assertFalse($epayment->annul());
        $this->assertFalse($epayment->isAnnulled());
    }

    /**
     * @covers \App\Models\Epayment::__construct
     * @covers \App\Models\Epayment::isAnnulled
     */
    public function testIsAnulled(): void
    {
        $epayment = $this->getPayment('PAYMENT_DELETED');

        $this->assertTrue($epayment->isAnnulled());
    }

    /**
     * @covers \App\Models\Epayment::annul
     */
    public function testAnullAnulled(): void
    {
        $epayment = $this->getPayment('PAYMENT_DELETED');

        $this->assertTrue($epayment->annul());
        $this->assertTrue($epayment->isAnnulled());
    }

    /**
     * @covers \App\Models\Epayment::confirm
     * @covers \App\Models\Epayment::doCapture
     * @covers \App\Models\Epayment::getAmountCaptured
     */
    public function testConfirmAndCheckeCapturedAmount(): void
    {
        $epayment = $this->getPayment();

        if ($this->epaymentService instanceof MockInterface) {
            $this->mockMethod($this->epaymentService, 'confirm', true, [$epayment, 100]);
        }

        $this->assertTrue($epayment->confirm());
        $this->assertSame(100, $epayment->getAmountCaptured());
    }

    /**
     * @covers \App\Models\Epayment::confirm
     * @covers \App\Models\Epayment::doCapture
     * @covers \App\Models\Epayment::getAmountCaptured
     */
    public function testConfirmFail(): void
    {
        $epayment = $this->getPayment();

        if ($this->epaymentService instanceof MockInterface) {
            $this->mockMethod($this->epaymentService, 'confirm', false, [$epayment, 100]);
        }

        $this->assertFalse($epayment->confirm());
        $this->assertSame(0, $epayment->getAmountCaptured());
    }

    /**
     * @param null|mixed[] $with
     */
    protected function mockMethod(
        MockInterface $mockClass,
        string $methodName,
        mixed $return = null,
        ?array $with = [],
        int $times = 1
    ): void {
        $method = $mockClass->shouldReceive($methodName);
        $method->times($times);
        if (null !== $with) {
            $method->withArgs($with);
        }
        $method->andReturn($return);
    }

    /**
     * @covers \App\Models\Epayment::confirm
     * @covers \App\Models\Epayment::doCapture
     * @covers \App\Models\Epayment::getAmountCaptured
     */
    public function testConfirmWithOvercharge(): void
    {
        $epayment = $this->getPayment();

        $this->assertFalse($epayment->confirm(200));
        $this->assertSame(0, $epayment->getAmountCaptured());
    }

    /**
     * @covers \App\Models\Epayment::__construct
     * @covers \App\Models\Epayment::getAmountCaptured
     */
    public function testGetConfirmedAmountAlreadyCaptured(): void
    {
        $epayment = $this->getPayment('PAYMENT_CAPTURED', 100, 100);

        $this->assertSame(100, $epayment->getAmountCaptured());
    }

    /**
     * @covers \App\Models\Epayment::confirm
     * @covers \App\Models\Epayment::doCapture
     * @covers \App\Models\Epayment::getAmountCaptured
     */
    public function testConfirmConfirmed(): void
    {
        $epayment = $this->getPayment('PAYMENT_CAPTURED', 100, 100);

        $this->assertTrue($epayment->confirm());
        $this->assertSame(100, $epayment->getAmountCaptured());
    }
}
