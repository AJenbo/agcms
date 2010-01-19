<?php

require_once "inc/config.php";
require_once "inc/mysqli.php";
require_once 'inc/sajax.php';

//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

function optimize_tables() {
	global $mysqli;
	
	$tables = $mysqli->fetch_array("SHOW TABLE STATUS");
	foreach($tables as $table) {
		$mysqli->query("OPTIMIZE TABLE `".$table['Name']."`");
	}
	return '';
}

function remove_bad_submisions() {
	global $mysqli;
	
	$mysqli->query("DELETE FROM `email` WHERE `email` = '' AND `adresse` = '' AND `tlf1` = '' AND `tlf2` = '';");
	
	//return $mysqli->affected_rows;
	return '';
}

function remove_bad_bindings() {
	global $mysqli;
	
	$mysqli->query('DELETE FROM `bind` WHERE (kat != 0 AND kat != -1 AND kat NOT IN (SELECT id FROM kat)) OR side NOT IN ( SELECT id FROM sider );');
	
	//return $mysqli->affected_rows;
	return '';
}

function remove_bad_accessories() {
	global $mysqli;
	
	//Remove bad tilbehor bindings
	$mysqli->query('DELETE FROM `tilbehor` WHERE side NOT IN ( SELECT id FROM sider ) OR tilbehor NOT IN ( SELECT id FROM sider );');
		
	//return $mysqli->affected_rows;
	return '';
}

function remove_none_existing_files() {
	global $mysqli;
	$files = $mysqli->fetch_array('SELECT id, path FROM `files`');

	$deleted = 0;
	foreach($files as $files) {
		if(!is_file($_SERVER['DOCUMENT_ROOT'].$files['path'])) {
			$mysqli->query("DELETE FROM `files` WHERE `id` = ".$files['id']);
			$deleted++;
		}
	}
	
	return '';
}

function delete_tempfiles() {
	$deleted = 0;
	$files = scandir($_SERVER['DOCUMENT_ROOT'].'/upload/temp');
	foreach($files as $file) {
		if(is_file($_SERVER['DOCUMENT_ROOT'].'/upload/temp/'.$file)) {
			@unlink($_SERVER['DOCUMENT_ROOT'].'/upload/temp/'.$file);
			$deleted++;
		}
	}
	
	return '';
}

$sajax_debug_mode = 0;
sajax_export(
	array('name' => 'optimize_tables', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'remove_bad_submisions', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'remove_bad_bindings', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'remove_bad_accessories', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'remove_none_existing_files', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'delete_tempfiles', 'method' => 'POST', "asynchronous" => false)
);
sajax_handle_client_request();
?>
