<?php
/*
ini_set('display_errors', 1);
error_reporting(-1);
/**/

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';

require_once '../inc/sajax.php';
require_once '../inc/config.php';
require_once '../inc/mysqli.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

$sajax_request_type = 'POST';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo(_('Users and Groups')); ?></title>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<link href="style/mainmenu.css" rel="stylesheet" type="text/css" />
<link href="style/style.css" rel="stylesheet" type="text/css" />
</head>
<body onload="$('loading').style.visibility = 'hidden';">
<div id="canvas"><div id="headline"><?php echo(_('Users and Groups')); ?></div><table id="addressbook"><thead><tr><td></td><td><?php echo(_('Name')); ?></td></tr></thead><tbody><?php

$users = $mysqli->fetch_array("SELECT * FROM `users` ORDER BY `fullname` ASC");

foreach($users as $key => $user) {
	echo('<tr');
	if($key % 2)
		echo(' class="altrow"');
	echo('><td>');
	if($_SESSION['_user']['access'] == 1)
		echo(' <img src="images/cross.png" alt="X" title="'._('Delete').'">');
	echo('</td><td><a href="user.php?id='.$user['id'].'">'.$user['fullname'].'</a></td></tr>');
}

?></tbody></table></div><?php
require 'mainmenu.php';
?>
</body>
</html>
