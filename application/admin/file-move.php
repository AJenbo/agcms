<?php

use Sajax\Sajax;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

Sajax::export(['listdirs' => ['method' => 'GET']]);
Sajax::handleClientRequest();

$pathinfo = pathinfo($_GET['path']);

$data = [
    'javascript' => Sajax::showJavascript(true),
    'id' => $_GET['id'] ?? 0,
    'path' => $_GET['path'] ?? '',
    'dirs' => getRootDirs(),
    'move' => true,
];
Render::output('admin-file-move', $data);
