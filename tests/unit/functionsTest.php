<?php

use PHPUnit\Framework\TestCase;

class functionsTest extends TestCase
{
    /**
     * @return void
     */
    public function test_clearFileName_date(): void
    {
        $this->assertEquals('13-04-2016', clearFileName('13/04/2016'));
    }

    /**
     * @return void
     */
    public function test_clearFileName_multiple(): void
    {
        $this->assertEquals('my-folder', clearFileName('my\/\/\/folder'));
    }

    /**
     * @return void
     */
    public function test_clearFileName_trim(): void
    {
        $this->assertEquals('trimed', clearFileName('trimed#'));
    }

    /**
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

        $this->assertEquals($expected, arrayNatsort($list, 'a', 'a'));
    }

    /**
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

        $this->assertEquals($expected, arrayNatsort($list, 'a', 'a', 'desc'));
    }

    /**
     * @return void
     */
    public function test_first(): void
    {
        $this->assertEquals(1, first([1, 2]));
    }

    /**
     * @return void
     */
    public function test_stringLimit(): void
    {
        $this->assertEquals('Long tekst …', stringLimit('Long tekst here', 12));
    }

    /**
     * @return void
     */
    public function test_stringLimit_edge_of_word(): void
    {
        $this->assertEquals('Long …', stringLimit('Long tekst here', 11));
    }

    /**
     * @return void
     */
    public function test_stringLimit_tiny(): void
    {
        $this->assertEquals('Lon…', stringLimit('Long tekst here', 4));
    }

    /**
     * @return void
     */
    public function test_stringLimit_noop(): void
    {
        $this->assertEquals('Long tekst here', stringLimit('Long tekst here', 15));
    }
}
