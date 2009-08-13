<?php
//If url !utf-8 make it fucking utf-8 and try again
$url = urldecode($_SERVER['REQUEST_URI']);
//can't detect windows-1252
$encoding = mb_detect_encoding($url, 'UTF-8, ISO-8859-1');
if($encoding != 'UTF-8') {
	//Firefox uses windows-1252 if it can get away with it
	//We can't detect windows-1252 from iso-8859-1 but it's a superset of the secound so bouth should handle fine as windows-1252
	if(!$encoding || $encoding == 'ISO-8859-1')
		$encoding = 'windows-1252';
	$url = mb_convert_encoding($url, 'UTF-8', $encoding);
	//TODO rawurlencode $url (PIE doesn't do it buy it self :(
	$url = implode("/", array_map("rawurlencode", explode("/", $url)));
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: '.$url);
	die();
}

if(preg_match('/(\=[^&].*)/u', $url)) {
	//is there more parameters after q?
	if(preg_match('/[q=.*&]/u', $url)) {
		$q = preg_replace('/.*q=(.*)[&]{1}.*|.*/u', '\1', $url);
	} else {
		$q = preg_replace('/.*q=(.*)$|.*/u', '\1', $url);
	}
	if (get_magic_quotes_gpc())
		$q = addslashes($q);
}

if(!$GLOBALS['generatedcontent']['activmenu'] = preg_replace('/.*\/kat([0-9]*)-.*|.*/u', '\1', $url)) {
	if($GLOBALS['generatedcontent']['activmenu'] = preg_replace('/.*kat=([0-9]+).*\s*|.*/u', '\1', $url))
		$redirect = 1;
}

//Try old query sting methode
if(!$GLOBALS['side']['id'] = preg_replace('/.*\/side([0-9]*)-.*|.*/u', '\1', $url)) {
	if($GLOBALS['side']['id'] = preg_replace('/.*side=([0-9]+).*\s*|.*/u', '\1', $url)
	//Try really old query sting methode
	|| $GLOBALS['side']['id'] = preg_replace('/.*id=([0-9]+).*\s*|.*/u', '\1', $url))
		$redirect = 1;
}

//Get maerke
if(!@$maerke)
	$maerke = preg_replace('/.*\/mærke([0-9]*)-.*|.*/u', '\1', $url);
if(!$maerke)
	$maerke = preg_replace('/.*\/maerke([0-9]*)-.*|.*/u', '\1', $url);
//TODO redirect to mærke

//Old url detected and redirect needed.
if(@$redirect) {
	require_once 'inc/config.php';
	require_once 'inc/mysqli.php';
	require_once 'inc/functions.php';
	
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

	header('HTTP/1.1 301 Moved Permanently');
	if($GLOBALS['side']['id']) {
		if(!$GLOBALS['generatedcontent']['activmenu']) {
			$bind = $mysqli->fetch_array('SELECT kat FROM bind WHERE side = '.$GLOBALS['side']['id'].' LIMIT 1');
			if(!$bind) {
				header('Location: /?sog=1&q=&sogikke=&qext=&minpris=&maxpris=&maerke=');
				die();
			}
			$kats = $mysqli->fetch_array('SELECT id, navn FROM kat WHERE id = '.$bind[0]['kat']);
		} else {
			$kats = $mysqli->fetch_array('SELECT id, navn FROM kat WHERE id = '.$GLOBALS['generatedcontent']['activmenu']);
		}
		$sider = $mysqli->fetch_array('SELECT id, navn FROM sider WHERE id = '.$GLOBALS['side']['id']);
		if(!$sider) {
			header('Location: /kat'.$kats[0]['id'].'-'.clear_file_name($kats[0]['navn']).'/');
			die();
		}
		header("Location: /kat".$kats[0]['id']."-".clear_file_name($kats[0]['navn'])."/side".$sider[0]['id']."-".clear_file_name($sider[0]['navn']).".html");
		die();
	} elseif($GLOBALS['generatedcontent']['activmenu']) {
		$kats = $mysqli->fetch_array("SELECT id, navn FROM kat WHERE id = ".$GLOBALS['generatedcontent']['activmenu']);
		header("Location: /kat".$kats[0]['id']."-".clear_file_name($kats[0]['navn'])."/");
		die();
	}
}

if(!@$sog && !$GLOBALS['generatedcontent']['activmenu'] && !$GLOBALS['side']['id'] && !@$q && !@$maerke){
	if (get_magic_quotes_gpc())
		$url = stripslashes($url);
		//TODO a %20 is inserted after ø
	$q = trim(preg_replace(
		array ('/\/|-|_|\.html|\.htm|\.php|\.gif|\.jpeg|\.jpg|\.png|\.php/u', '/([0-9]+)/u', '/([A-Z]?[a-z]+)/u', '/([\r\n])[\s]+/u'),
		array (' ', ' \1 ', ' \1', '\1'), $url));
	if (get_magic_quotes_gpc())
		$q = addslashes($q);
	$GLOBALS['generatedcontent']['activmenu'] = -1;
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: /?q=".rawurlencode($q)."&sogikke=&minpris=&maxpris=&maerke=0");
	die();
}

//TODO stop space efter æøå
header("Status: 200", true, 200);
header("HTTP/1.1 200 OK", true, 200);
include('index.php');
?>