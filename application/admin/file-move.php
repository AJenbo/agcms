<?php

use AGCMS\Render;

require_once __DIR__ . '/logon.php';

$path = request()->get('path');
if ('/files/' !== mb_substr($path, 0, 7) && '/images/' !== mb_substr($path, 0, 8)) {
    throw new Exception(_('File manipulation not allowed outside user folders'));
}

$data = [
    'id' => request()->get('id'),
    'path' => $path,
    'dirs' => getRootDirs(),
];
Render::output('admin-file-move', $data);
