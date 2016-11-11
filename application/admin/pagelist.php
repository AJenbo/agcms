<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/logon.php';

//TODO run countEmailTo() onload

$kattree = [];

SAJAX::export(
    [
        'katspath'        => ['method' => 'GET'],
        'siteList_expand' => ['method' => 'GET'],
        'kat_expand'      => ['method' => 'GET'],
        'getSiteTree'     => ['method' => 'GET'],
    ]
);
SAJAX::handleClientRequest();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style/style.css" rel="stylesheet" type="text/css" />
<link href="/theme/admin.css" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Liste af sider</title>
<script type="text/javascript"><!--
<?php SAJAX::showJavascript(); ?>

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
</head>
<body onload="init()">
<img id="loading" src="images/loading.gif" width="16" height="16" alt="Processing" />
<?php echo getSiteTree(); ?>
</body>
</html>
