<?php
mb_language("uni");
mb_internal_encoding('UTF-8');

require_once 'snoopy/snoopy.class.php';
require_once '../inc/mysqli.php';
require_once '../inc/config.php';
require_once 'config.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

$pakke = $mysqli->fetch_array('SELECT id, shipmentId, packageId, bookingTime, bookingDate FROM `PNL` WHERE id = '.$_GET['id'].' AND `shipmentId` != \'\' AND `packageId` != \'\' AND `arrived` = 0 AND `inmotion` = 0');

if(!$pakke)
	die('Kunde ikke slette pakken!');

$pakke = $pakke[0];

$snoopy = new Snoopy;

//Logon start
$submit_url = 'http://online.pannordic.com/pn_logistics/index_tracking_email.jsp?id='.$pakke['packageId'].'&Search=search';
$snoopy->fetch($submit_url);
$snoopy->setcookies();

$submit_url = 'http://online.pannordic.com/pn_logistics/tracking/pub_package_details.jsp';

$submit_vars = array();
$submit_vars['shipmentId'] = $pakke['shipmentId'];
$submit_vars['accountId'] = $GLOBALS['_config']['username'];
$submit_vars['packageId'] = $pakke['packageId'];
$submit_vars['fromSetParm'] = 'Y';

$submit_vars = array_map('utf8_decode', $submit_vars);
$snoopy->submit($submit_url, $submit_vars);

if(preg_match('/Arrived\sat\s/i', $snoopy->results)
|| preg_match('/Departed\sfrom\s/i', $snoopy->results)
|| preg_match('/Awaiting\sCustoms\sClearance\s(import)/i', $snoopy->results)
|| preg_match('/Unsuccessful\sdelivery\sattempt/i', $snoopy->results)
|| preg_match('/Sorted\sat\sterminal\s/ui', $snoopy->results))
	$inmotion = 1;
else
	$inmotion = 0;


if(preg_match('/Delivered\sto\saddressee/i', $snoopy->results))
	$arrived = 1;
else
	$arrived = 0;


if($arrived || $inmotion) {
	$mysqli->query('UPDATE `PNL` SET `arrived` = \''.$arrived.'\', `inmotion` = \''.$inmotion.'\' WHERE `id` ='.$pakke['id'].' LIMIT 1');
	die('Kunde ikke slette pakken!');
}

$snoopy = new Snoopy;
	
//Logon start
$submit_url = 'http://online.pannordic.com/pn_logistics/index.jsp';

$submit_vars = array();
$submit_vars['username'] = $GLOBALS['_config']['username'];
$submit_vars['j_username'] = $GLOBALS['_config']['username'];
$submit_vars['password'] = $GLOBALS['_config']['password'];
$submit_vars['sv'] = '';
$submit_vars['j_password'] = $GLOBALS['_config']['password'];
$submit_vars['login_button'] = 'Login';

$submit_vars = array_map('utf8_decode', $submit_vars);
$snoopy->submit($submit_url, $submit_vars);
$snoopy->setcookies();

$submit_url = 'http://online.pannordic.com/pn_logistics/j_security_check';

$submit_vars = array();
$submit_vars['j_username'] = $GLOBALS['_config']['username'];
$submit_vars['j_password'] = strtoupper($GLOBALS['_config']['password']);

$submit_vars = array_map('utf8_decode', $submit_vars);
$snoopy->submit($submit_url, $submit_vars);
//Logon end

//Delete order
$submit_url = 'http://online.pannordic.com/pn_logistics/pnl_action2.jsp';
//$submit_url = 'http://online.pannordic.com/pn_logistics/pnl_action2.jsp?pnlAction=QUERY&bookingDate='.$pakke['bookingDate'].'&bookingTime='.$pakke['bookingTime'].'&bookingUserId='.$GLOBALS['_config']['username'].'&fromOverviewScreen=Y';

$submit_vars = array();
$submit_vars['pnlAction'] = 'DELETE';
$submit_vars['status'] = '04';
$submit_vars['waybill'] = '';
$submit_vars['outsideEu'] = '';
$submit_vars['bookingDate'] = $pakke['bookingDate'];
//bookingTime=12%3A24%3A05
$submit_vars['bookingTime'] = $pakke['bookingTime'];
$submit_vars['bookingUserId'] = $GLOBALS['_config']['username'];
$submit_vars['delete'] = 'Delete';
$submit_vars['twoLabelsPerPage'] = 'Y';
$submit_vars = array_map('utf8_decode', $submit_vars);
//print_r($submit_vars);
//print($submit_url);
$snoopy->submit($submit_url, $submit_vars);
//echo($snoopy->results);
$mysqli->query("DELETE FROM `PNL` WHERE `id` = ".$pakke['id']." LIMIT 1");

header("Location: liste.php", TRUE, 303);
?>