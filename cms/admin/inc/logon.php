<?php
//access
//0:ny, ingen ratigheder.
//1:supper admin.
//2:admin.
//3:klader.
//4:gaest, ikke gemme.

require_once $_SERVER['DOCUMENT_ROOT'].'/inc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/mysqli.php';

$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$_user = $mysqli->fetch_array("SELECT `id`, `fullname`, `name`, `access`, `password` FROM `users` WHERE `access` != '0' AND `name` = '".mb_strtolower($_SERVER['PHP_AUTH_USER'], 'UTF-8')."' LIMIT 1");
$_user = $_user[0];

if(!$_user['password'] == crypt(mb_strtolower($_SERVER['PHP_AUTH_PW'], 'UTF-8'), $_user['password'])) {
	unset($_user);
    header('WWW-Authenticate: Basic realm="Admin"'); 
    header('HTTP/1.0 401 Unauthorized');
	die('Du skal indtaste dit bruger navn og kode.');
} else {
	$mysqli->close();
	unset($mysqli);
}
?>