<?php

use AGCMS\Config;
use AGCMS\Render;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';

Sajax::export(['saveImage' => ['method' => 'POST']]);
Sajax::handleClientRequest();

$path = request()->get('path');
if (mb_substr($path, 0, 7) !== '/files/' && mb_substr($path, 0, 8) !== '/images/') {
    throw new Exception(_('File manipulation not allowed outside user folders'));
}

$imagesize = getimagesize(_ROOT_ . $path);
$mode = request()->get('mode');
$fileName = '';

if ($mode == 'thb') {
    $pathinfo = pathinfo($path);
    $fileName = $pathinfo['filename'] . '-thb';
}

$data = [
    'textWidth' => Config::get('text_width'),
    'thumbWidth' => Config::get('thumb_width'),
    'thumbHeight' => Config::get('thumb_height'),
    'width' => (int) $imagesize[0],
    'height' => (int) $imagesize[1],
    'id' => (int) request()->get('id'),
    'mode' => $mode,
    'fileName' => $fileName,
    'path' => $path,
] + getBasicAdminTemplateData();
Render::output('admin-image-edit', $data);
