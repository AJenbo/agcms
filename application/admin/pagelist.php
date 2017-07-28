<?php

use AGCMS\Render;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';

$kattree = [];

Sajax::export([
    'katspath'   => ['method' => 'GET'],
    'kat_expand' => ['method' => 'GET'],
]);
Sajax::handleClientRequest();

$data = [
    'javascript' => Sajax::showJavascript(true),
    'categories' => getCategoryRootStructure(true),
    'input' => 'pages',
    'includePages' => true,
];
Render::output('admin-pagelist', $data);
