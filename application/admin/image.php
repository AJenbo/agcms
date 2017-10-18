<?php

use AGCMS\Render;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/logon.php';

$path = request()->get('path');
if ('/files/' !== mb_substr($path, 0, 7) && '/images/' !== mb_substr($path, 0, 8)) {
    throw new Exception(_('File manipulation not allowed outside user folders'));
}

$oneMonth = 2592000;
$timestamp = filemtime(_ROOT_ . $path);

$response = new Response();
$response->setPublic();

$lastModified = DateTime::createFromFormat('U', (string) $timestamp);
$response->setLastModified($lastModified);
$response->setEtag((string) $timestamp);
$response->setMaxAge($oneMonth);
$expires = DateTime::createFromFormat('U', (string) (time() + $oneMonth));
$response->setExpires($expires);

if ($response->isNotModified(request())) {
    $response->send();
    exit;
}
$response->sendHeaders();

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
