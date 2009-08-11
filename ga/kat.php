<?php

require_once 'inc/config.php';
require_once 'inc/mysqli.php';
require_once 'inc/functions.php';

$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

header('HTTP/1.1 301 Moved Permanently');
if($_GET['kat0'] || $_GET['kat1']) {
	if(!$_GET['kat0'])
	$_GET['kat0'] = $_GET['kat1'];
	$kat = $mysqli->fetch_array('SELECT id, navn FROM kat WHERE id = '.$_GET['kat0']);
	if(!$kat) {
		header("Location: /");
	} else {
		if($kat[0]['id']) {
			header("Location: /kat".$kat[0]['id']."-".clear_file_name($kat[0]['navn'])."/");
		}
		header("Location: /");
	}
} else {
	header("Location: /");
}
?>