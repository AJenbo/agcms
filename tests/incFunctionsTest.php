<?php

class incFunctionsTest extends TestCase
{
    public function test_validemail()
    {
        $this->assertTrue(validemail('_An-._E-mail@gmail.com'));
    }

    public function test_validemail_fake_domain()
    {
        $this->assertFalse(validemail('email@test.notadomain'));
    }

    public function test_validemail_IDN_domain()
    {
        $this->assertTrue(validemail('email@sørensen.dk'));
    }

    public function test_clearFileName_date()
    {
        $this->assertEquals('13-04-2016', clearFileName('13/04/2016'));
    }

    public function test_clearFileName_multiple()
    {
        $this->assertEquals('my-folder', clearFileName('my\/\/\/folder'));
    }

    public function test_clearFileName_trim()
    {
        $this->assertEquals('trimed', clearFileName('trimed#'));
    }

    public function test_arrayNatsort()
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

    public function test_arrayNatsort_reverse()
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

    public function test_first()
    {
        $this->assertEquals(1, first([1, 2]));
    }

    public function test_stringLimit()
    {
        $this->assertEquals('Long tekst …', stringLimit('Long tekst here', 12));
    }

    public function test_stringLimit_edge_of_word()
    {
        $this->assertEquals('Long …', stringLimit('Long tekst here', 11));
    }

    public function test_stringLimit_tiny()
    {
        $this->assertEquals('Lon…', stringLimit('Long tekst here', 4));
    }

    public function test_stringLimit_noop()
    {
        $this->assertEquals('Long tekst here', stringLimit('Long tekst here', 15));
    }
}
