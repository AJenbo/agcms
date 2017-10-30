<?php

use AGCMS\Render;

require_once __DIR__ . '/logon.php';

$data = [
    'siteTree'   => getSiteTreeData('pages', request()->cookies->get('activekat', -1)),
];
Render::output('admin-pagelist', $data);
