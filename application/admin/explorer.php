<?php

use AGCMS\Config;
use AGCMS\Render;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';

/*
$mode
0 = exploere
1 = filemove

$return
rtef = inserthtml
thb = returnid.value, returnid+'thb'.src, thb limit.
icon = returnid.value, returnid+'thb'.src, 16x16 limit.
*/

//load path from cookie, else default to /images
if (empty($_COOKIE['admin_dir']) || !is_dir(_ROOT_ . @$_COOKIE['admin_dir'])) {
    @setcookie('admin_dir', '/images');
    @$_COOKIE['admin_dir'] = '/images';
}

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

if (@$_COOKIE['qpath'] || @$_COOKIE['qalt'] || @$_COOKIE['qtype']) {
    $showfiles = searchfiles(@$_COOKIE['qpath'], @$_COOKIE['qalt'], @$_COOKIE['qtype']);
} else {
    $showfiles = showfiles($_COOKIE['admin_dir'] ?? '');
}

$data = [
    'rte'        => $_GET['rte'] ?? '',
    'returnid'   => $_GET['returnid'] ?? '',
    'javascript' => Sajax::showJavascript(true).$showfiles['javascript'],
    'bgcolor'    => Config::get('bgcolor'),
    'hasSearch'  => (bool) ($_COOKIE['qpath'] ?? $_COOKIE['qalt'] ?? $_COOKIE['qtype'] ?? false),
    'filesHtml'  => $showfiles['html'],
    'qpath'      => $_COOKIE['qpath'] ?? '',
    'qalt'       => $_COOKIE['qalt'] ?? '',
    'qtype'      => $_COOKIE['qtype'] ?? '',
    'dirs'       => getRootDirs(),
];
echo Render::render('admin-explorer', $data);
