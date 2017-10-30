<?php

use AGCMS\Render;

require_once __DIR__ . '/logon.php';

Render::sendCacheHeader(Render::getUpdateTime(false));

$maxbyte = min(
    returnBytes(ini_get('post_max_size')),
    returnBytes(ini_get('upload_max_filesize'))
);

Render::output(
    'admin-file-upload',
    [
        'maxbyte' => $maxbyte,
        'activeDir' => request()->get('path'),
    ]
);
