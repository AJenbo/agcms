<?php

require_once 'inc/header.php'; 
//include the file   
require_once("inc/firephp.class.php");

require_once 'inc/config.php';
require_once 'inc/mysqli.php';

//primitive runtime cache
$GLOBALS['cache'] = array();
$GLOBALS['cache']['updatetime'] = array();

//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

require_once 'inc/functions.php';

header('HTTP/1.1 301 Moved Permanently');

//redirect af gamle urls
if(@$_GET['kat'] || @$_GET['side']) {

	//secure input
	if (get_magic_quotes_gpc()) {
		$kat_id = $mysqli->escape_wildcards($mysqli->real_escape_string(stripslashes($_GET['kat'])));
		$side_id = $mysqli->escape_wildcards($mysqli->real_escape_string(stripslashes($_GET['side'])));
	} else {
		$kat_id = $mysqli->escape_wildcards($mysqli->real_escape_string($_GET['kat']));
		$side_id = $mysqli->escape_wildcards($mysqli->real_escape_string($_GET['side']));
	}
	
	if($side_id) {
		$bind = $mysqli->fetch_array("SELECT bind.kat, sider.navn AS side_navn, kat.navn AS kat_navn FROM bind JOIN sider ON bind.side = sider.id JOIN kat ON bind.kat = kat.id WHERE side =".$side_id." LIMIT 1 ");
		$side_navn = $bind[0]['side_navn'];
		$kat_id = $bind[0]['kat'];
		$kat_name = $bind[0]['kat_navn'];
		unset($bind);
	}

	if(($kat_id && !$kat_name) || ($_GET['kat'] && $_GET['kat'] != $kat_id)) {
		//get kat navn hvis der ikke var en side eller kat ikke var standard for siden.
		if (get_magic_quotes_gpc()) {
			$kat_id = $mysqli->escape_wildcards($mysqli->real_escape_string(stripslashes($_GET['kat'])));
		} else {
			$kat_id = $mysqli->escape_wildcards($mysqli->real_escape_string($_GET['kat']));
		}
		if(!$GLOBALS['cache']['kats'][$kat_id]['navn']) {
			$kats = $mysqli->fetch_array('SELECT navn, vis, icon FROM kat WHERE id = '.$kat_id.' LIMIT 1');
			
			$GLOBALS['cache']['kats'][$kat_id]['navn'] = $kats[0]['navn'];
			$GLOBALS['cache']['kats'][$kat_id]['vis'] = $kats[0]['vis'];
			$GLOBALS['cache']['kats'][$kat_id]['icon'] = $kats[0]['icon'];
		}
		$kat_name = $GLOBALS['cache']['kats'][$kat_id]['navn'];
	}
	if($side_navn) {

		//TODO rawurlencode $url (PIE doesn't do it buy it self :(
		$url = '/kat'.$kat_id.'-'.rawurlencode(clear_file_name($kat_name)).'/side'.$side_id.'-'.rawurlencode(clear_file_name($side_navn)).'.html';

		//redirect til en side
		header('Location: '.$url);
		die();
	} elseif($kat_name) {

		//TODO rawurlencode $url (PIE doesn't do it buy it self :(
		$url = '/kat'.$kat_id.'-'.rawurlencode(clear_file_name($kat_name)).'/';

		//redirect til en kategori
		header('Location: '.$url);
		die();
	} else {
		//inted fundet redirect til søge siden
		header('Location: /?sog=1&q=&varenr=&sogikke=&minpris=&maxpris=&maerke=');
		die();
	}
}

header('Location: /');
die();
?>