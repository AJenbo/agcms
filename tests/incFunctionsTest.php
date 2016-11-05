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

    public function test_xhtmlEsc()
    {
        $this->assertEquals('&amp;', xhtmlEsc('&'));
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

    public function test_katHTML()
    {
        $GLOBALS['generatedcontent']['activmenu'] = 0;
        $pages = [
            ['id' => 0, 'navn' => 'Page 1', 'varenr' => 'ProdNo', 'for' => 0, 'pris' => 0],
            ['id' => 0, 'navn' => 'Page 1', 'varenr' => 'ProdNo', 'for' => 1, 'pris' => 1],
        ];

        $expected = '<table class="tabel"><thead><tr><td><a href="" onclick="x_getKat(\'0\', \'navn\', inject_html);return false">Titel</a></td><td><a href="" onclick="x_getKat(\'0\', \'for\', inject_html);return false">Før</a></td><td><a href="" onclick="x_getKat(\'0\', \'pris\', inject_html);return false">Pris</a></td><td><a href="" onclick="x_getKat(\'0\', \'varenr\', inject_html);return false">#</a></td></tr></thead><tbody><tr><td><a href="/kat0-Cateogry/side0-Page-1.html">Page 1</a></td><td class="XPris" align="right"></td><td class="Pris" align="right"></td><td align="right" style="font-size:11px">ProdNo</td></tr><tr class="altrow"><td><a href="/kat0-Cateogry/side0-Page-1.html">Page 1</a></td><td class="XPris" align="right">1,-</td><td class="Pris" align="right">1,-</td><td align="right" style="font-size:11px">ProdNo</td></tr></tbody></table>';
        $this->assertEquals($expected, katHTML($pages, 'Cateogry', 0));
    }
}
