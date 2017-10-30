<?php

use AGCMS\Config;
use AGCMS\Render;

require_once __DIR__ . '/logon.php';

$data = [
    'returnid'   => request()->get('returnid'),
    'bgcolor'    => Config::get('bgcolor'),
    'dirs'       => getRootDirs(),
];
Render::output('admin-explorer', $data);
