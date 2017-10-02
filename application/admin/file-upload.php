<?php

use AGCMS\Render;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';

Sajax::export(['fileExists' => ['method' => 'GET', 'asynchronous' => false, 'uri' => '/admin/file-upload.php']]);
Sajax::handleClientRequest();

Render::sendCacheHeader(Render::getUpdateTime(false));

if (empty($_COOKIE['admin_dir']) || !is_dir(_ROOT_ . $_COOKIE['admin_dir'])) {
    setcookie('admin_dir', '/images');
    $_COOKIE['admin_dir'] = '/images';
}

$maxbyte = min(
    returnBytes(ini_get('post_max_size')),
    returnBytes(ini_get('upload_max_filesize'))
);

Render::output('admin-file-upload', ['javascript' => Sajax::showJavascript(true) . ' var maxbyte = ' . $maxbyte . ';']);
