<?php
/**
 * Handle SEO frindly urls
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

$url = urldecode($_SERVER['REQUEST_URI']);

// If url !utf-8 make it utf-8 and try again
$encoding = mb_detect_encoding($url, 'UTF-8, ISO-8859-1');
if ($encoding !== 'UTF-8') {
    // Windows-1252 is a superset of iso-8859-1
    if (!$encoding || $encoding == 'ISO-8859-1') {
        $encoding = 'windows-1252';
    }
    $url = mb_convert_encoding($url, 'UTF-8', $encoding);
    redirect($url, 301);
}

session_start();
Render::sendCacheHeader();
Render::doRouting($url);

header('Status: 200', true, 200);
header('HTTP/1.1 200 OK', true, 200);
Render::outputPage();
