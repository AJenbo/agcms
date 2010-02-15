<?php
//access
//0:ny, ingen ratigheder.
//1:supper admin.
//2:admin.
//3:klader.
//4:gaest, ikke gemme.

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/inc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/mysqli.php';

session_start();

if(empty($_SESSION['_user']) && !empty($_POST['username'])) {
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

	$_SESSION['_user'] = $mysqli->fetch_array("SELECT * FROM `users` WHERE `name` = '".addcslashes($_POST['username'], "'\\")."' LIMIT 1");
	$_SESSION['_user'] = @$_SESSION['_user'][0];
	if($_SESSION['_user']['access'] < 1 || mb_substr(@$_SESSION['_user']['password'], 0, 13) != mb_substr(crypt(@$_POST['password'], $_SESSION['_user']['password']), 0, 13))
		unset($_SESSION['_user']);
	unset($_POST);
}


if(empty($_SESSION['_user'])) {
	sleep(1);
	header('HTTP/1.0 401 Unauthorized');
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo(_('Login')); ?></title>
	</head>
	<body>
	<form action="" method="post" style="text-align:right; width:300px; margin:auto;">
	 <?php echo(_('Bruger:')); ?>
	 <input name="username" />
	 <br />
	 <?php echo(_('Adgangskode:')); ?>
	 <input type="password" name="password" />
	 <br />
	 <input type="submit" value="Log ind" />
	</form>
	</body>
	</html><?php
	die();
}
?>