<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
$GLOBALS['mem'] = memory_get_usage();
function memstatus($string) {
	echo '<tr align=right><td>'.$string.'</td><td>'.(memory_get_usage() - $GLOBALS['mem']).'</td><td>/</td><td>'.memory_get_usage().'</td></tr>'; 
	$GLOBALS['mem'] = memory_get_usage();
}
memstatus('init');
?>