<?php
//access
//0:ny, ingen ratigheder.
//1:supper admin.
//2:admin.
//3:klader.
//4:gaest, ikke gemme.

require_once $_SERVER['DOCUMENT_ROOT'].'/inc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/mysqli.php';

session_start();

if(empty($_SESSION['_user']) && !empty($_POST['username'])) {
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	$GLOBALS['_user'] = $mysqli->fetch_array("SELECT `id`, `fullname`, `name`, `access`, `password` FROM `users` WHERE `access` != '0' AND `name` = '".addcslashes($_POST['username'], "'")."' LIMIT 1");
	$GLOBALS['_user'] = @$GLOBALS['_user'][0];
	$_SESSION['_user'] = $GLOBALS['_user'];
} elseif(!empty($_SESSION['_user'])) {
	$GLOBALS['_user'] = $_SESSION['_user'];
}

if(empty($GLOBALS['_user']['password']) || ($GLOBALS['_user']['password'] != crypt(mb_strtolower(@$_POST['password'], 'UTF-8'), $GLOBALS['_user']['password']))) {
	unset($GLOBALS['_user']);
	unset($_SESSION['_user']);
	header('HTTP/1.0 401 Unauthorized');
	die('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Login</title>
</head>
<body>
<form action="" method="post" style="text-align:right; width:250px; margin:auto;">
    Bruger:
    <input name="username" />
    <br />
    Adgangskode:
    <input type="password" name="password" />
    <br />
    <input type="submit" value="Log ind" />
</form>
</body>
</html>');
}
?>