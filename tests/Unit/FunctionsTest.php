<?php namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    /**
     * @covers \clearFileName
     *
     * @return void
     */
    public function testClearFileNameDate(): void
    {
        $this->assertSame('13-04-2016', clearFileName('13/04/2016'));
    }

    /**
     * @covers \clearFileName
     *
     * @return void
     */
    public function testClearFileNameSequenceOfBadChars(): void
    {
        $this->assertSame('my-folder', clearFileName('my\/\/\/folder'));
    }

    /**
     * @covers \clearFileName
     *
     * @return void
     */
    public function testClearFileNameTrim(): void
    {
        $this->assertSame('trimed', clearFileName('trimed#'));
    }

    /**
     * @covers \arrayNatsort
     *
     * @return void
     */
    public function testArrayNatsort(): void
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

        $this->assertSame($expected, arrayNatsort($list, 'a'));
    }

    /**
     * @covers \arrayNatsort
     *
     * @return void
     */
    public function testArrayNatsortReverse(): void
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

        $this->assertSame($expected, arrayNatsort($list, 'a', 'desc'));
    }

    /**
     * @covers \first
     *
     * @return void
     */
    public function testFirst(): void
    {
        $this->assertSame(1, first([1, 2]));
    }

    /**
     * @covers \stringLimit
     *
     * @return void
     */
    public function testStringLimit(): void
    {
        $this->assertSame('Long tekst …', stringLimit('Long tekst here', 12));
    }

    /**
     * @covers \stringLimit
     *
     * @return void
     */
    public function testStringLimitRdgeOfWord(): void
    {
        $this->assertSame('Long …', stringLimit('Long tekst here', 11));
    }

    /**
     * @covers \stringLimit
     *
     * @return void
     */
    public function testStringLimitTiny(): void
    {
        $this->assertSame('Lon…', stringLimit('Long tekst here', 4));
    }

    /**
     * @covers \stringLimit
     *
     * @return void
     */
    public function testStringLimitNoop(): void
    {
        $this->assertSame('Long tekst here', stringLimit('Long tekst here', 15));
    }

    /**
     * @covers \purifyHTML
     *
     * @return void
     */
    public function testPurifyHTMLAlert(): void
    {
        $this->flushPurifyerCache();
        $this->assertSame('<p>Click me!</p>', purifyHTML('<p onclick="alert(\'Boo!\')">Click me!</p>'));
    }

    /**
     * @covers \purifyHTML
     *
     * @return void
     */
    public function testPurifyHTMLVideo(): void
    {
        $this->flushPurifyerCache();
        $html = '<video width="320" height="240" src="video.mp4" controls=""></video>';
        $this->assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \purifyHTML
     *
     * @return void
     */
    public function testPurifyHTMLAudio(): void
    {
        $this->flushPurifyerCache();
        $html = '<audio src="audio.mp3" controls=""></audio>';
        $this->assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \purifyHTML
     *
     * @return void
     */
    public function testPurifyHTMLYoutube(): void
    {
        $this->flushPurifyerCache();
        $html = '<div class="embeddedContent oembed-provider- oembed-provider-youtube" data-oembed="https://www.youtube.com/watch?v=-I4pOBJ3RZQ" data-oembed_provider="youtube"><iframe src="//www.youtube.com/embed/-I4pOBJ3RZQ?wmode=transparent&amp;jqoemcache=mUXUu" allowfullscreen="" scrolling="no" width="425" height="349" frameborder="0"></iframe></div>';
        $this->assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \htmlUrlDecode
     *
     * @return void
     */
    public function testHtmlUrlDecodeDecodeUrl(): void
    {
        $this->assertSame('<a href="/test/ø">', htmlUrlDecode('<a href="/test/%C3%B8">'));
    }

    /**
     * @covers \htmlUrlDecode
     *
     * @return void
     */
    public function testHtmlUrlDecodeDecodeHtml(): void
    {
        $this->assertSame('<a href="/test/ø">', htmlUrlDecode('<a href="/test/&oslash;">'));
    }

    /**
     * @covers \htmlUrlDecode
     *
     * @return void
     */
    public function testHtmlUrlDecodeMaintainSpecial(): void
    {
        $this->assertSame('&quot;%22?test&amp;hi', htmlUrlDecode('&quot;%22?test&amp;hi'));
    }

    /**
     * Clear purifyer cache.
     *
     * @return void
     */
    private function flushPurifyerCache(): void
    {
        $files = glob(__DIR__ . '/../application/theme/cache/HTMLPurifier/**/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
