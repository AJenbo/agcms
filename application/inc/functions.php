<?php

use AGCMS\Application;
use AGCMS\Config;
use AGCMS\DB;

/**
 * Get database connection.
 *
 * @param DB|null $overwrite
 *
 * @return DB
 */
function db(DB $overwrite = null): DB
{
    static $connection;
    if ($overwrite) {
        $connection = $overwrite;
    } elseif (!$connection) {
        $connection = new DB(
            config('mysql_server'),
            config('mysql_user'),
            config('mysql_password'),
            config('mysql_database')
        );
    }

    return $connection;
}

/**
 * Get the current application instance.
 *
 * @return Application
 */
function app(): Application
{
    return Application::getInstance();
}

/**
 * Helper function for getting configurations.
 *
 * @param string $key     The name of the configuration to fetch
 * @param mixed  $default What to return if key does not exists
 *
 * @return mixed
 */
function config(string $key, $default = null)
{
    return Config::get($key, $default);
}

/**
 * rawurlencode a string, but leave / alone.
 *
 * @param string $url
 *
 * @return string
 */
function encodeUrl(string $url): string
{
    $url = explode('/', $url);
    $url = array_map('rawurlencode', $url);

    return implode('/', $url);
}

/**
 * Get first element from an array that can't be referenced.
 *
 * @param array $array
 *
 * @return mixed
 */
function first(array $array)
{
    return reset($array);
}

/**
 * Generate safe file name.
 *
 * @param string $name String to clean
 *
 * @return string
 */
function clearFileName(string $name): string
{
    $replace = [
        '/[&?\\/:*"<>|%\s-_#\\[\\]@;={}^~\\\\]+/u' => ' ',
        '/^\s+|\s+$/u'                             => '', // trim
        '/\s+/u'                                   => '-',
    ];

    return preg_replace(array_keys($replace), $replace, $name);
}

/**
 * Natsort an array.
 *
 * @param array[] $aryData     Array to sort
 * @param string  $strIndex    Key of unique id
 * @param string  $strSortBy   Key to sort by
 * @param string  $strSortType Revers sorting
 *
 * @return array[]
 */
function arrayNatsort(array $aryData, string $strIndex, string $strSortBy, string $strSortType = 'asc'): array
{
    //loop through the array
    $arySort = [];
    foreach ($aryData as $aryRow) {
        //set up the value in the array
        $arySort[$aryRow[$strIndex]] = $aryRow[$strSortBy];
    }

    //apply the natural sort
    natcasesort($arySort);

    //if the sort type is descending
    if (in_array($strSortType, ['desc', '-'], true)) {
        //reverse the array
        arsort($arySort);
    }

    //loop through the sorted and original data
    $aryResult = [];
    foreach (array_keys($arySort) as $arySortKey) {
        foreach ($aryData as $aryRow) {
            if ($aryRow[$strIndex] == $arySortKey) {
                $aryResult[] = $aryRow;
                break;
            }
        }
    }

    //return the result
    return $aryResult;
}

/**
 * Crope a string to a given max lengt, round by word.
 *
 * @param string $string   String to crope
 * @param int    $length   Crope length
 * @param string $ellipsis String to add at the end, with in the limit
 *
 * @return string
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
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^(https:|http:)?//www.youtube.com/embed/%u');
    $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
    $config->set('Cache.SerializerPath', app()->basePath('/theme/cache/HTMLPurifier'));

    $config->set('HTML.DefinitionID', 'html5-definitions'); // unqiue id
    if ($def = $config->maybeGetRawHTMLDefinition()) {
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

    return trim($html);
}

/**
 * Normalize char encoding.
 *
 * Minimize char encoding to facilitate updating file with search replace
 *
 * @param string $html
 *
 * @return string
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
