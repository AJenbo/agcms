<?php

use Sajax\Sajax;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

Sajax::export(['listdirs' => ['method' => 'GET']]);
Sajax::handleClientRequest();

$path = request()->get('path');
if (mb_substr($path, 0, 7) !== '/files/' && mb_substr($path, 0, 8) !== '/images/') {
    throw new Exception(_('File manipulation not allowed outside user folders'));
}

$data = [
    'javascript' => Sajax::showJavascript(true),
    'id' => request()->get('id'),
    'path' => $path,
    'dirs' => getRootDirs(),
];
Render::output('admin-file-move', $data);
