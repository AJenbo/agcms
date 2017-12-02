<?php

use PHPUnit\Framework\TestCase;

class functionsTest extends TestCase
{
    /**
     * @covers \encodeUrl
     *
     * @return void
     */
    public function test_encodeUrl(): void
    {
        $this->assertSame('/test/%C3%B8', encodeUrl('/test/ø'));
    }

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

    /**
     * @covers \purifyHTML
     *
     * @return void
     */
    public function test_purifyHTML_alert(): void
    {
        $this->flush_purifyer_cache();
        $this->assertSame('<p>Click me!</p>', purifyHTML('<p onclick="alert(\'Boo!\')">Click me!</p>'));
    }

    /**
     * @covers \purifyHTML
     *
     * @return void
     */
    public function test_purifyHTML_video(): void
    {
        $this->flush_purifyer_cache();
        $html = '<video width="320" height="240" src="video.mp4" controls=""></video>';
        $this->assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \purifyHTML
     *
     * @return void
     */
    public function test_purifyHTML_audio(): void
    {
        $this->flush_purifyer_cache();
        $html = '<audio src="audio.mp3" controls=""></audio>';
        $this->assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \purifyHTML
     *
     * @return void
     */
    public function test_purifyHTML_youtube(): void
    {
        $this->flush_purifyer_cache();
        $html = '<div class="embeddedContent oembed-provider- oembed-provider-youtube" data-oembed="https://www.youtube.com/watch?v=-I4pOBJ3RZQ" data-oembed_provider="youtube"><iframe src="//www.youtube.com/embed/-I4pOBJ3RZQ?wmode=transparent&amp;jqoemcache=mUXUu" allowfullscreen="" scrolling="no" width="425" height="349" frameborder="0"></iframe></div>';
        $this->assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \htmlUrlDecode
     *
     * @return void
     */
    public function test_htmlUrlDecode_decode_url(): void
    {
        $this->assertSame('<a href="/test/ø">', htmlUrlDecode('<a href="/test/%C3%B8">'));
    }

    /**
     * @covers \htmlUrlDecode
     *
     * @return void
     */
    public function test_htmlUrlDecode_decode_html(): void
    {
        $this->assertSame('<a href="/test/ø">', htmlUrlDecode('<a href="/test/&oslash;">'));
    }

    /**
     * @covers \htmlUrlDecode
     *
     * @return void
     */
    public function test_htmlUrlDecode_maintain_special(): void
    {
        $this->assertSame('&quot;%22?test&amp;hi', htmlUrlDecode('&quot;%22?test&amp;hi'));
    }

    /**
     * Clear purifyer cache.
     *
     * @return void
     */
    private function flush_purifyer_cache(): void
    {
        $files = glob(__DIR__ . '/../application/theme/cache/HTMLPurifier/**/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
