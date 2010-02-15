<?php
require_once 'inc/sajax.php';
require_once 'inc/header.php';
require_once 'inc/functions.php';
require_once 'inc/config.php';
require_once 'inc/mysqli.php';

$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	
//If the database is older then the users cache, send 304 not modified
//WARNING: this results in the site not updating if new files are included later,
//the remedy is to update the database when new cms files are added.
$tables = $mysqli->fetch_array("SHOW TABLE STATUS");
$updatetime = 0;
foreach($tables as $table)
	$updatetime = max($updatetime, strtotime($table['Update_time']));

$included_files = get_included_files();
foreach($included_files as $filename) {
	$GLOBALS['cache']['updatetime']['filemtime'] = max(@$GLOBALS['cache']['updatetime']['filemtime'], filemtime($filename));
}
foreach($GLOBALS['cache']['updatetime'] as $time) {
	$updatetime = max($updatetime, $time);
}
if($updatetime < 1)
	$updatetime = time();

doConditionalGet($updatetime);
$updatetime = 0;

function get_kat($id, $sort) {
	global $mysqli;
	require_once $_SERVER['DOCUMENT_ROOT'].'/inc/liste.php';
	$GLOBALS['generatedcontent']['activmenu'] = $id;

	//check browser cache
	$updatetime = 0;
	$included_files = get_included_files();
	foreach($included_files as $filename) {
		$GLOBALS['cache']['updatetime']['filemtime'] = max($GLOBALS['cache']['updatetime']['filemtime'], filemtime($filename));
	}
	foreach($GLOBALS['cache']['updatetime'] as $time) {
		$updatetime = max($updatetime, $time);
	}
	if($updatetime < 1)
		$updatetime = time();
	
	doConditionalGet($updatetime);
	
	//Get pages list
	$bind = $mysqli->fetch_array("SELECT sider.id, sider.navn, sider.burde, sider.fra, sider.pris, sider.for, sider.varenr FROM bind JOIN sider ON bind.side = sider.id WHERE bind.kat = ".$GLOBALS['generatedcontent']['activmenu']." ORDER BY sider.".$sort." ASC");
	$bind = array_natsort($bind, 'id', $sort);
	if(!@$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['navn']) {
		$kat = $mysqli->fetch_array("SELECT navn, vis FROM kat WHERE id = ".$GLOBALS['generatedcontent']['activmenu']." LIMIT 1");
		$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['navn'] = $kat[0]['navn'];
	}
	
	
	if($bind) 
		$bind_nr = count($bind);

	return array("id" => 'kat'.$GLOBALS['generatedcontent']['activmenu'], "html" => kat_html($bind, $GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['navn']));
}

function get_address($phonenumber) {
	require_once $_SERVER['DOCUMENT_ROOT'].'/inc/getaddress.php';
	return getAddress($phonenumber);
}

sajax_export(
	array('name' => 'get_table', 'method' => 'GET'),
	array('name' => 'get_kat', 'method' => 'GET'),
	array('name' => 'get_address', 'method' => 'GET')
);
//	$sajax_remote_uri = "/ajax.php";
sajax_handle_client_request();
?>
