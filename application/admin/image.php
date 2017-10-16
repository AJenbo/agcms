<?php

use AGCMS\Render;

require_once __DIR__ . '/logon.php';

$path = request()->get('path');
if ('/files/' !== mb_substr($path, 0, 7) && '/images/' !== mb_substr($path, 0, 8)) {
    throw new Exception(_('File manipulation not allowed outside user folders'));
}

$timestamp = filemtime(_ROOT_ . $path);
Render::sendCacheHeader($timestamp);
header('Cache-Control: max-age=2592000');
header('ETag: "' . $timestamp . '"');
$timeZone = date_default_timezone_get();
date_default_timezone_set('GMT');
$expires = mb_substr(date('r', time() + 60 * 60 * 24 * 30), 0, -5) . 'GMT';
$lastModified = mb_substr(date('r', $timestamp), 0, -5) . 'GMT';
date_default_timezone_set($timeZone);
header('Expires: ' . $expires);
header('Last-Modified: ' . $lastModified);

generateImage(
    _ROOT_ . $path,
    request()->get('cropX', 0),
    request()->get('cropY', 0),
    request()->get('cropW', 0),
    request()->get('cropH', 0),
    request()->get('maxW', 0),
    request()->get('maxH', 0),
    request()->get('flip', 0),
    request()->get('rotate', 0)
);
