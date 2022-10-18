<?php

use App\Application;
use App\Exceptions\Exception;
use App\Http\Request;
use App\Services\ConfigService;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Get the current application instance or contained service instance.
 *
 * @template T of object
 *
 * @param class-string<T> $name
 *
 * @return T
 */
function app(string $name = Application::class): object
{
    $app = Application::getInstance();

    return $app->get($name);
}

/**
 * Helper function for getting configurations.
 *
 * @param string $key     The name of the configuration to fetch
 * @param mixed  $default What to return if key does not exists
 *
 * @return mixed Key value
 */
function config(string $key, $default = null)
{
    return ConfigService::get($key, $default);
}

/**
 * Generate redirect response.
 */
function redirect(string $url, int $status = RedirectResponse::HTTP_FOUND): RedirectResponse
{
    if (false === filter_var($url, FILTER_VALIDATE_URL)) {
        $url = app(Request::class)->getSchemeAndHttpHost() . $url;
    }
    $url = (string) new Uri($url); // encode raw utf-8

    return new RedirectResponse($url, $status);
}

/**
 * Get first element from an array that can't be referenced.
 *
 * @param array<mixed> $array
 *
 * @return mixed First element in the array
 */
function first(array $array)
{
    return reset($array);
}

/**
 * Takes a string and changes it to comply with file name restrictions in windows, linux, mac and urls (UTF8)
 * .|"'´`:%=#&\/+?*<>{}-_.
 *
 * @param string $name String to clean
 */
function cleanFileName(string $name): string
{
    $replace = [
        '/[&?\\/:*"<>|%\\s\\-_#\\[\\]@;={}^~\\\\]+/u' => ' ',
        '/^\\s+|\\s+$/u'                              => '', // trim
        '/\\s+/u'                                     => '-',
    ];

    $name = preg_replace(array_keys($replace), $replace, $name);
    if (null === $name) {
        throw new Exception('preg_replace failed');
    }

    return $name;
}

/**
 * Natsort an array.
 *
 * @param array<array<mixed>> $rows      Array to sort
 * @param int|string          $orderBy   Key to sort by
 * @param string              $direction Revers sorting
 *
 * @return array<array<mixed>>
 */
function arrayNatsort(array $rows, $orderBy, string $direction = 'asc'): array
{
    $tempArray = [];
    foreach ($rows as $rowKey => $row) {
        $tempArray[$rowKey] = $row[$orderBy];
    }

    natcasesort($tempArray);

    if (in_array($direction, ['desc', '-'], true)) {
        arsort($tempArray);
    }

    $result = [];
    foreach (array_keys($tempArray) as $rowKey) {
        $result[] = $rows[$rowKey];
    }

    return $result;
}

/**
 * Crope a string to a given max lengt, round by word.
 *
 * @param string $string   String to crope
 * @param int    $length   Crope length
 * @param string $ellipsis String to add at the end, with in the limit
 */
function stringLimit(string $string, int $length = 50, string $ellipsis = '…'): string
{
    if (!$length || mb_strlen($string) <= $length) {
        return $string;
    }

    $length -= mb_strlen($ellipsis);
    $string = mb_substr($string, 0, $length);
    $string = trim($string);
    if (mb_strlen($string) >= $length) {
        $string = preg_replace('/\s+\S+$/u', '', $string);
        if (null === $string) {
            throw new Exception('preg_replace failed');
        }
    }

    return $string . (mb_strlen($string) === $length ? '' : ' ') . $ellipsis;
}

/**
 * Use HTMLPurifier to clean HTML-code, preserves youtube videos.
 *
 * @param string $html Sting to clean
 *
 * @return string Cleaned stirng
 */
function purifyHTML(string $html): string
{
    $config = HTMLPurifier_Config::createDefault();
    /** @var Application */
    $app = app();
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^(https:|http:)?//www.youtube.com/embed/%u');
    $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
    $config->set('Cache.SerializerPath', $app->basePath('/theme/cache/HTMLPurifier'));

    $config->set('HTML.DefinitionID', 'html5-definitions'); // unqiue id

    /** @var ?HTMLPurifier_HTMLDefinition */
    $def = $config->maybeGetRawHTMLDefinition();
    if ($def) {
        $def->addAttribute('div', 'data-oembed_provider', 'Text');
        $def->addAttribute('div', 'data-oembed', 'Text');
        $def->addAttribute('div', 'data-widget', 'Text');
        $def->addAttribute('iframe', 'allowfullscreen', 'Bool');

        // http://developers.whatwg.org/the-video-element.html#the-video-element
        $def->addElement('video', 'Block', 'Flow', 'Common', [
            'controls' => 'Bool',
            'height'   => 'Length',
            'poster'   => 'URI',
            'preload'  => 'Enum#auto,metadata,none',
            'src'      => 'URI',
            'width'    => 'Length',
        ]);
        // http://developers.whatwg.org/the-video-element.html#the-audio-element
        $def->addElement('audio', 'Block', 'Flow', 'Common', [
            'controls' => 'Bool',
            'preload'  => 'Enum#auto,metadata,none',
            'src'      => 'URI',
        ]);
        $def->addElement('source', 'Block', 'Empty', 'Common', ['src' => 'URI', 'type' => 'Text']);
    }
    $purifier = new HTMLPurifier($config);

    $html = $purifier->purify($html);

    $html = htmlUrlDecode($html);

    // remove extra white space
    $html = preg_replace('/\s+/', ' ', $html);
    if (null === $html) {
        throw new Exception('preg_replace failed');
    }

    return trim($html);
}

/**
 * Normalize char encoding.
 *
 * Minimize char encoding to facilitate updating file with search replace
 */
function htmlUrlDecode(string $html): string
{
    // Double encoding url special characters
    $urlSpeciaslChars = '%,",[,],&,?,#,:,/,@,;,=,<,>,{,},|,\,^,`';
    $urlSpeciaslChars = explode(',', $urlSpeciaslChars);
    $urlSpeciaslChars = array_map('rawurlencode', $urlSpeciaslChars);
    $urlDoubleEncoded = array_map('rawurlencode', $urlSpeciaslChars);
    $html = str_replace($urlSpeciaslChars, $urlDoubleEncoded, $html);

    // Decode any url encoded urls
    $html = rawurldecode($html);

    // Double HTML url special characters
    $html = str_replace(
        ['&amp;', '&quot;', '&lt;', '&gt;'],
        ['&amp;amp;', '&amp;quot;', '&amp;lt;', '&amp;gt;'],
        $html
    );

    // Decode all html entities
    return html_entity_decode($html, ENT_QUOTES, 'UTF-8');
}
