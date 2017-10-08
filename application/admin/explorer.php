<?php

use AGCMS\Config;
use AGCMS\Render;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';

Sajax::export([
    'listdirs'     => ['method' => 'GET'],
    'searchfiles'  => ['method' => 'GET'],
    'showfiles'    => ['method' => 'GET'],
    'deletefile'   => ['method' => 'POST'],
    'deletefolder' => ['method' => 'POST'],
    'edit_alt'     => ['method' => 'POST'],
    'makedir'      => ['method' => 'POST'],
    'renamefile'   => ['method' => 'POST'],
]);
Sajax::handleClientRequest();

$data = [
    'returnid'   => request()->get('returnid'),
    'javascript' => Sajax::showJavascript(true),
    'bgcolor'    => Config::get('bgcolor'),
    'dirs'       => getRootDirs(),
];
Render::output('admin-explorer', $data);
