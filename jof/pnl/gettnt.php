<?php
mb_language("uni");
mb_internal_encoding('UTF-8');
require_once 'snoopy/snoopy.class.php';
require_once '../inc/mysqli.php';
require_once '../inc/config.php';
require_once 'config.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

function getTrackTrace($shipmentId, $packageId) {
	$snoopy = new Snoopy;
	
	//Logon start
	$submit_url = 'http://online.pannordic.com/pn_logistics/index_tracking_email.jsp?id='.$packageId.'&Search=search';
	$snoopy->fetch($submit_url);
	$snoopy->setcookies();
	
	$submit_url = 'http://online.pannordic.com/pn_logistics/tracking/pub_package_details.jsp';
	
	$submit_vars = array();
	$submit_vars['shipmentId'] = $shipmentId;
	$submit_vars['accountId'] = $GLOBALS['_config']['username'];
	$submit_vars['packageId'] = $packageId;
	$submit_vars['fromSetParm'] = 'Y';
	
	$submit_vars = array_map('utf8_decode', $submit_vars);
	$snoopy->submit($submit_url, $submit_vars);
	
	if(preg_match('/Arrived\sat\s/i', $snoopy->results)
	|| preg_match('/Departed\sfrom\s/i', $snoopy->results)
	|| preg_match('/Awaiting\sCustoms\sClearance\s(import)/i', $snoopy->results)
	|| preg_match('/Unsuccessful\sdelivery\sattempt/i', $snoopy->results)
	|| preg_match('/Sorted\sat\sterminal/i', $snoopy->results))
		$inmotion = 1;
	else
		$inmotion = 0;
	
	
	if(preg_match('/Delivered\sto\saddressee/i', $snoopy->results))
		$arrived = 1;
	else
		$arrived = 0;
		
	return array('inmotion' => $inmotion, 'arrived' => $arrived);
}

$post = $mysqli->fetch_array('SELECT id, shipmentId, packageId FROM `PNL` WHERE `shipmentId` != \'\' AND `packageId` != \'\' AND `arrived` = 0 AND `bookingDate` >= \''.$_GET['bookingDate'].'\'');

foreach($post as $pakke) {
	$status = getTrackTrace($pakke['shipmentId'], $pakke['packageId']);
	$mysqli->query('UPDATE `PNL` SET `arrived` = \''.$status['arrived'].'\', `inmotion` = \''.$status['inmotion'].'\' WHERE `id` ='.$pakke['id'].' LIMIT 1');
}

header("Location: liste.php",TRUE,303);

?>