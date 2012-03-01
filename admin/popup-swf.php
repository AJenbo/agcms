<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
require_once '../inc/mysqli.php';
require_once '../inc/config.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$files = $mysqli->fetch_array('SELECT aspect, width, height FROM `files` WHERE `path` LIKE \''.$_GET['url'].'\' LIMIT 1');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php
echo $_GET['url'];
?></title>
<style type="text/css"><!--
* {
	margin:0;
}
--></style>
</head>

<body><object width="<?php echo $files[0]['width']; ?>" height="<?php echo $files[0]['height']; ?>" id="flash" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" align="middle">
<param name="allowScriptAccess" value="sameDomain" />
<param name="movie" value="<?php echo $_GET['url']; ?>" />
<param name="allowFullScreen" value="true" />
<param name="quality" value="high" />
<param name="bgcolor" value="#" />
<embed src="<?php echo $_GET['url']; ?>" width="<?php echo $files[0]['width']; ?>" height="<?php echo $files[0]['height']; ?>" bgcolor="#" name="flash" quality="high" align="middle" allowscriptaccess="sameDomain" allowfullscreen="true" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object><script src="/ieupdate.js" type="text/javascript"></script></body>
</html>
