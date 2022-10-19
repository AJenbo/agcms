<?php

namespace Tests\Unit\Services;

use App\Services\EpaymentService;
use PHPUnit\Framework\TestCase;

class EpaymentServiceTest extends TestCase
{
    private EpaymentService $epaymentService;

    /**
     * Initiate the mock.
     */
    public function setUp(): void
    {
        $this->epaymentService = new EpaymentService('', '');
    }

    /**
     * @covers \App\Services\EpaymentService::__construct
     */
    public function testCanInstanciate(): void
    {
        $this->assertInstanceOf(EpaymentService::class, $this->epaymentService);
    }
}
