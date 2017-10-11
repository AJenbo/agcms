<?php

use AGCMS\Render;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';

Sajax::export([
    'katspath'       => ['method' => 'GET'],
    'expandCategory' => ['method' => 'GET'],
]);
Sajax::handleClientRequest();

$data = [
    'javascript' => Sajax::showJavascript(true),
    'siteTree' => getSiteTreeData('pages', request()->cookies->get('activekat', -1)),
];
Render::output('admin-pagelist', $data);
