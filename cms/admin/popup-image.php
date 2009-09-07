<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
require_once '../inc/config.php';
require_once '../inc/mysqli.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$files = $mysqli->fetch_array('SELECT alt, width, height FROM `files` WHERE `path` LIKE \''.$_GET['url'].'\' LIMIT 1');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo($_GET['url']); ?></title>
<style type="text/css"><!--
img {position:absolute;display:block;left:50%;top:50%; margin-left:-<?php echo($files[0]['width']/2); ?>px; margin-top:-<?php echo($files[0]['height']/2); ?>px;}
--></style>
</head>

<body><div><img src="<?php echo($_GET['url']); ?>" alt="<?php echo($files[0]['alt']); ?>" title="<?php echo($files[0]['alt']); ?>" width="<?php echo($files[0]['width']); ?>" height="<?php echo($files[0]['height']); ?>" /></div></body>
</html>
