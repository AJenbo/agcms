<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/logon.php';

$kattree = [];

Sajax\Sajax::export(
    [
        'countEmailTo'                      => ['method' => 'GET'],
        'get_mail_size'                     => ['method' => 'GET'],
        'getnykat'                          => ['method' => 'GET'],
        'getSiteTree'                       => ['method' => 'GET'],
        'kat_expand'                        => ['method' => 'GET'],
        'katspath'                          => ['method' => 'GET'],
        'search'                            => ['method' => 'GET'],
        'siteList_expand'                   => ['method' => 'GET'],
        'check_file_names'                  => ['method' => 'GET', 'asynchronous' => false],
        'check_file_paths'                  => ['method' => 'GET', 'asynchronous' => false],
        'get_db_size'                       => ['method' => 'GET', 'asynchronous' => false],
        'get_looping_cats'                  => ['method' => 'GET', 'asynchronous' => false],
        'get_orphan_cats'                   => ['method' => 'GET', 'asynchronous' => false],
        'get_orphan_lists'                  => ['method' => 'GET', 'asynchronous' => false],
        'get_orphan_pages'                  => ['method' => 'GET', 'asynchronous' => false],
        'get_orphan_rows'                   => ['method' => 'GET', 'asynchronous' => false],
        'get_pages_with_mismatch_bindings'  => ['method' => 'GET', 'asynchronous' => false],
        'get_size_of_files'                 => ['method' => 'GET', 'asynchronous' => false],
        'get_subscriptions_with_bad_emails' => ['method' => 'GET', 'asynchronous' => false],
        'bind'                              => ['method' => 'POST'],
        'deleteContact'                     => ['method' => 'POST'],
        'listRemoveRow'                     => ['method' => 'POST'],
        'listSavetRow'                      => ['method' => 'POST'],
        'makeNewList'                       => ['method' => 'POST'],
        'movekat'                           => ['method' => 'POST'],
        'opretSide'                         => ['method' => 'POST'],
        'renamekat'                         => ['method' => 'POST'],
        'saveEmail'                         => ['method' => 'POST'],
        'savekrav'                          => ['method' => 'POST'],
        'saveListOrder'                     => ['method' => 'POST'],
        'save_ny_kat'                       => ['method' => 'POST'],
        'save_ny_maerke'                    => ['method' => 'POST'],
        'sendEmail'                         => ['method' => 'POST'],
        'sletbind'                          => ['method' => 'POST'],
        'sletkat'                           => ['method' => 'POST'],
        'sletkrav'                          => ['method' => 'POST'],
        'sletmaerke'                        => ['method' => 'POST'],
        'sletSide'                          => ['method' => 'POST'],
        'sogogerstat'                       => ['method' => 'POST'],
        'updateContact'                     => ['method' => 'POST'],
        'updateForside'                     => ['method' => 'POST'],
        'updateKat'                         => ['method' => 'POST'],
        'updatemaerke'                      => ['method' => 'POST'],
        'updateSide'                        => ['method' => 'POST'],
        'updateSpecial'                     => ['method' => 'POST'],
        'deleteTempfiles'                   => ['method' => 'POST', 'asynchronous' => false],
        'optimizeTables'                    => ['method' => 'POST', 'asynchronous' => false],
        'removeBadAccessories'              => ['method' => 'POST', 'asynchronous' => false],
        'removeBadBindings'                 => ['method' => 'POST', 'asynchronous' => false],
        'removeBadSubmisions'               => ['method' => 'POST', 'asynchronous' => false],
        'removeNoneExistingFiles'           => ['method' => 'POST', 'asynchronous' => false],
        'sendDelayedEmail'                  => ['method' => 'POST', 'asynchronous' => false],
    ]
);
Sajax\Sajax::handleClientRequest();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<link href="style/style.css" rel="stylesheet" type="text/css" />
<link href="/theme/admin.css" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Administrator menu</title>
<script type="text/javascript"><!--
<?php Sajax\Sajax::showJavascript(); ?>

--></script>
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript" src="javascript/lib/scriptaculous.js"></script>
<script type="text/javascript" src="javascript/lib/protomenu/proto.menu.js"></script>
<link rel="stylesheet" href="style/proto.menu.css" type="text/css" media="screen" />
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="javascript/index.js"></script>
<script type="text/javascript" src="javascript/list.js"></script>
<!-- RTEF -->
<script type="text/javascript" src="rtef/lang/dk.js"></script>
<script type="text/javascript" src="rtef/xhtml.js"></script>
<script type="text/javascript" src="rtef/richtext.js"></script>
</head><body onload="init()"><div id="canvas"><?php
switch (@$_GET['side']) {
    case 'emaillist':
        echo getEmailList();
        break;
    case 'newemail':
        echo getNewEmail();
        break;
    case 'viewemail':
    case 'editemail':
        echo getEmail((int) $_GET['id']);
        break;
    case 'sogogerstat':
        echo getsogogerstat();
        break;
    case 'maerker':
        echo getmaerker();
        break;
    case 'krav':
        echo getkrav();
        break;
    case 'nyside':
        echo getnyside();
        break;
    case 'nykat':
        $temp = getnykat();
        echo $temp['html'];
        break;
    case 'search':
        $temp = search($_GET['text']);
        echo $temp['html'];
        break;
    case 'editkrav':
        echo editkrav((int) $_GET['id']);
        break;
    case 'nykrav':
        echo getnykrav();
        break;
    case 'updatemaerke';
        echo getupdatemaerke((int) $_GET['id']);
        break;
    case 'redigerside';
        echo redigerside((int) $_GET['id']);
        break;
    case 'redigerkat';
        echo redigerkat((int) $_GET['id']);
        break;
    case 'getSiteTree';
        echo getSiteTree();
        break;
    case 'redigerSpecial';
        echo redigerSpecial((int) $_GET['id']);
        break;
    case 'redigerFrontpage';
        echo redigerFrontpage();
        break;
    case 'get_db_error';
        echo get_db_error();
        break;
    case 'listsort';
        echo listsort(intval($_GET['id'] ?? 0));
        break;
    case 'editContact';
        echo editContact((int) $_GET['id']);
        break;
    case 'addressbook';
        echo getaddressbook();
        break;
}
?></div><?php
require 'mainmenu.php';
?></body></html>
