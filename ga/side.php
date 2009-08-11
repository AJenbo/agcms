<?php

require_once 'inc/config.php';
require_once 'inc/mysqli.php';
require_once 'inc/functions.php';

$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

header('HTTP/1.1 301 Moved Permanently');
if($_GET['id']) {
	$sider = $mysqli->fetch_array('SELECT id, navn FROM sider WHERE id = '.$_GET['id']);
	if(!$sider) {
		header("Location: /");
	} else {
		$bind = $mysqli->fetch_array('SELECT kat FROM bind WHERE side = '.$_GET['id'].' LIMIT 1');
		if(!$bind) {
			header('Location: /?sog=1&q=&sogikke=&qext=&minpris=&maxpris=&maerke=');
		} else {
			if($kats[0]['id'] > 0) {
				$kats = $mysqli->fetch_array('SELECT id, navn FROM kat WHERE id = '.$bind[0]['kat']);
				header("Location: /kat".$kats[0]['id']."-".clear_file_name($kats[0]['navn'])."/side".$sider[0]['id']."-".clear_file_name($sider[0]['navn']).".html");
			} else {
				header("Location: /side".$sider[0]['id']."-".clear_file_name($sider[0]['navn']).".html");
			}
		}
	}
} else {
	header("Location: /");
}
?>