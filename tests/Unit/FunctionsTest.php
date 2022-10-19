<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    /**
     * @covers \cleanFileName
     */
    public function testCleanFileNameDate(): void
    {
        static::assertSame('13-04-2016', cleanFileName('13/04/2016'));
    }

    /**
     * @covers \cleanFileName
     */
    public function testCleanFileNameSequenceOfBadChars(): void
    {
        static::assertSame('my-folder', cleanFileName('my\/\/\/folder'));
    }

    /**
     * @covers \cleanFileName
     */
    public function testCleanFileNameTrim(): void
    {
        static::assertSame('trimed', cleanFileName('trimed#'));
    }

    /**
     * @covers \arrayNatsort
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

        static::assertSame($expected, arrayNatsort($list, 'a'));
    }

    /**
     * @covers \arrayNatsort
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

        static::assertSame($expected, arrayNatsort($list, 'a', 'desc'));
    }

    /**
     * @covers \first
     */
    public function testFirst(): void
    {
        static::assertSame(1, first([1, 2]));
    }

    /**
     * @covers \stringLimit
     */
    public function testStringLimit(): void
    {
        static::assertSame('Long tekst …', stringLimit('Long tekst here', 12));
    }

    /**
     * @covers \stringLimit
     */
    public function testStringLimitRdgeOfWord(): void
    {
        static::assertSame('Long …', stringLimit('Long tekst here', 11));
    }

    /**
     * @covers \stringLimit
     */
    public function testStringLimitTiny(): void
    {
        static::assertSame('Lon…', stringLimit('Long tekst here', 4));
    }

    /**
     * @covers \stringLimit
     */
    public function testStringLimitNoop(): void
    {
        static::assertSame('Long tekst here', stringLimit('Long tekst here', 15));
    }

    /**
     * @covers \purifyHTML
     */
    public function testPurifyHTMLAlert(): void
    {
        $this->flushPurifyerCache();
        static::assertSame('<p>Click me!</p>', purifyHTML('<p onclick="alert(\'Boo!\')">Click me!</p>'));
    }

    /**
     * @covers \purifyHTML
     */
    public function testPurifyHTMLVideo(): void
    {
        $this->flushPurifyerCache();
        $html = '<video width="320" height="240" src="video.mp4" controls=""></video>';
        static::assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \purifyHTML
     */
    public function testPurifyHTMLAudio(): void
    {
        $this->flushPurifyerCache();
        $html = '<audio src="audio.mp3" controls=""></audio>';
        static::assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \purifyHTML
     */
    public function testPurifyHTMLYoutube(): void
    {
        $this->flushPurifyerCache();
        $html = '<div class="embeddedContent oembed-provider- oembed-provider-youtube" data-oembed="https://www.youtube.com/watch?v=-I4pOBJ3RZQ" data-oembed_provider="youtube"><iframe src="//www.youtube.com/embed/-I4pOBJ3RZQ?wmode=transparent&amp;jqoemcache=mUXUu" allowfullscreen="" scrolling="no" width="425" height="349" frameborder="0"></iframe></div>';
        static::assertSame($html, purifyHTML($html));
    }

    /**
     * @covers \htmlUrlDecode
     */
    public function testHtmlUrlDecodeDecodeUrl(): void
    {
        static::assertSame('<a href="/test/ø">', htmlUrlDecode('<a href="/test/%C3%B8">'));
    }

    /**
     * @covers \htmlUrlDecode
     */
    public function testHtmlUrlDecodeDecodeHtml(): void
    {
        static::assertSame('<a href="/test/ø">', htmlUrlDecode('<a href="/test/&oslash;">'));
    }

    /**
     * @covers \htmlUrlDecode
     */
    public function testHtmlUrlDecodeMaintainSpecial(): void
    {
        static::assertSame('&quot;%22?test&amp;hi', htmlUrlDecode('&quot;%22?test&amp;hi'));
    }

    /**
     * Clear purifyer cache.
     */
    private function flushPurifyerCache(): void
    {
        $files = glob(__DIR__ . '/../application/theme/cache/HTMLPurifier/**/*');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }
}
