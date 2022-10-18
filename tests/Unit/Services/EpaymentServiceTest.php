<?php

namespace Tests\Unit\Services;

use App\Services\EpaymentService;
use PHPUnit\Framework\TestCase;

class EpaymentServiceTest extends TestCase
{
    /** @var EpaymentService */
    private $epaymentService;

    /**
     * Initiate the mock.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->epaymentService = new EpaymentService('', '');
    }

    /**
     * @covers \App\Services\EpaymentService::__construct
     *
     * @return void
     */
    public function testCanInstanciate(): void
    {
        $this->assertInstanceOf(EpaymentService::class, $this->epaymentService);
    }
}
