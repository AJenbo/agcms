<?php

require_once __DIR__ . '/logon.php';

//TODO run countEmailTo() onload

$kattree = [];

Sajax\Sajax::export(
    [
        'katspath'        => ['method' => 'GET'],
        'siteList_expand' => ['method' => 'GET'],
        'kat_expand'      => ['method' => 'GET'],
        'getSiteTree'     => ['method' => 'GET'],
    ]
);
Sajax\Sajax::handleClientRequest();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style/style.css" rel="stylesheet" type="text/css" />
<link href="/theme/admin.css" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Liste af sider</title>
<script type="text/javascript"><!--
<?php Sajax\Sajax::showJavascript(); ?>

--></script>
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.3.0/prototype.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/scriptaculous.js"></script>
<script type="text/javascript" src="javascript/lib/protomenu/proto.menu.js"></script>
<link rel="stylesheet" href="style/proto.menu.css" type="text/css" media="screen" />
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="javascript/index.js"></script>
</head>
<body onload="init()">
<img id="loading" src="images/loading.gif" width="16" height="16" alt="Processing" />
<?php echo getSiteTree(); ?>
</body>
</html>
