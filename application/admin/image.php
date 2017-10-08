<?php

use AGCMS\Render;

require_once __DIR__ . '/logon.php';

$path = request()->get('path');
if (mb_substr($path, 0, 7) !== '/files/' && mb_substr($path, 0, 8) !== '/images/') {
    throw new Exception(_('File manipulation not allowed outside user folders'));
}

Render::sendCacheHeader(filemtime(_ROOT_ . $path));
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
