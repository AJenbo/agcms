<?php

use PHPUnit\Framework\TestCase;

class functionsTest extends TestCase
{
    /**
     * @covers \clearFileName
     *
     * @return void
     */
    public function test_clearFileName_date(): void
    {
        $this->assertSame('13-04-2016', clearFileName('13/04/2016'));
    }

    /**
     * @covers \clearFileName
     *
     * @return void
     */
    public function test_clearFileName_multiple(): void
    {
        $this->assertSame('my-folder', clearFileName('my\/\/\/folder'));
    }

    /**
     * @covers \clearFileName
     *
     * @return void
     */
    public function test_clearFileName_trim(): void
    {
        $this->assertSame('trimed', clearFileName('trimed#'));
    }

    /**
     * @covers \arrayNatsort
     *
     * @return void
     */
    public function test_arrayNatsort(): void
    {
        $list = [
            ['a' => '1'],
            ['a' => '10'],
            ['a' => '2'],
        ];

        $expected = [
            ['a' => '1'],
            ['a' => '2'],
            ['a' => '10'],
        ];

        $this->assertSame($expected, arrayNatsort($list, 'a', 'a'));
    }

    /**
     * @covers \arrayNatsort
     *
     * @return void
     */
    public function test_arrayNatsort_reverse(): void
    {
        $list = [
            ['a' => '1'],
            ['a' => '10'],
            ['a' => '2'],
        ];

        $expected = [
            ['a' => '10'],
            ['a' => '2'],
            ['a' => '1'],
        ];

        $this->assertSame($expected, arrayNatsort($list, 'a', 'a', 'desc'));
    }

    /**
     * @covers \first
     *
     * @return void
     */
    public function test_first(): void
    {
        $this->assertSame(1, first([1, 2]));
    }

    /**
     * @covers \stringLimit
     *
     * @return void
     */
    public function test_stringLimit(): void
    {
        $this->assertSame('Long tekst …', stringLimit('Long tekst here', 12));
    }

    /**
     * @covers \stringLimit
     *
     * @return void
     */
    public function test_stringLimit_edge_of_word(): void
    {
        $this->assertSame('Long …', stringLimit('Long tekst here', 11));
    }

    /**
     * @covers \stringLimit
     *
     * @return void
     */
    public function test_stringLimit_tiny(): void
    {
        $this->assertSame('Lon…', stringLimit('Long tekst here', 4));
    }

    /**
     * @covers \stringLimit
     *
     * @return void
     */
    public function test_stringLimit_noop(): void
    {
        $this->assertSame('Long tekst here', stringLimit('Long tekst here', 15));
    }
}
