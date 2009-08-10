<?php
	
require_once 'snoopy/snoopy.class.php';

$clientID = '150007792';
$password = 'Chips18';
$userID = 'admin';
$accessCode = 'UC';
$exTime = '120';

//*
//DEMO
$clientID = '17';
$password = 'Demo17';
$userID = 'demo';
$accessCode = 'DE';
$exTime = '60';
/**/

$snoopy = new Snoopy;

$submit_url = "http://www.postdanmark.dk/pfs/PfsLoginServlet";

$submit_vars = array();
$submit_vars['gotoURL'] = "/pfs/PfsLoginServlet";
$submit_vars['clientID'] = $clientID;
$submit_vars['userID'] = $userID;
$submit_vars['password'] = $password;
	
$snoopy->submit($submit_url, $submit_vars);

//Get token
preg_match('/[a-f0-9]{192,240}/i', $snoopy->results, $matches);
$token = $matches[0];

$submit_url = 'http://www.postdanmark.dk/pfs/pfsFrameSet.jsp?token='.$token.'&programID=pfs&clientID='.$clientID.'&userID='.$userID.'&sessionID=0&accessCode='.$accessCode.'&exTime='.$exTime.'&consignment=yes';
$snoopy->fetch($submit_url);
//Set session cookie
$snoopy->setcookies();

$submit_url = 'http://www.postdanmark.dk/pfs/number80.do';

$submit_vars = array();
$submit_vars['token'] = $token;
$submit_vars['programID'] = 'pfs';
$submit_vars['clientID'] = $clientID;
$submit_vars['userID'] = $userID;
$submit_vars['sessionID'] = '0';
$submit_vars['accessCode'] = $accessCode;
$submit_vars['exTime'] = $exTime;
//$submit_vars['command'] = 'DETECT_X_FACILITIES';
//$submit_vars['cpID'] = 'pfsWelcome.jsp';
$submit_vars['userAction'] = 'NEW';
$submit_vars['sadsadasd'] = 'adawa';

$snoopy->submit($submit_url, $submit_vars);
echo($snoopy->results);

//Get token
preg_match('/[a-f0-9]{192,240}/i', $snoopy->results, $matches);

$token = $matches[0];
$snoopy->referer = 'http://www.postdanmark.dk/pfs/number80.do';

$submit_url = 'http://www.postdanmark.dk/pfs/number80.do';

$submit_vars = array();
$submit_vars['userAction'] = 'COUNTRY_SELECTED';
$submit_vars['formType'] = '';
$submit_vars['token'] = $token;
$submit_vars['programID'] = 'pfs';
$submit_vars['clientID'] = $clientID;
$submit_vars['userID'] = $userID;
$submit_vars['sessionID'] = '0';
$submit_vars['accessCode'] = $accessCode;
$submit_vars['exTime'] = $exTime;
$submit_vars['recipientUid'] = '0';
$submit_vars['returnAddressUid'] = '';
$submit_vars['recipient.interessentId'] = '';
$submit_vars['recipient.navn1'] = '';
$submit_vars['recipient.navn2'] = '';
$submit_vars['recipient.kontaktperson'] = '';
$submit_vars['recipient.adr1'] = '';
$submit_vars['recipient.adr2'] = '';
$submit_vars['recipient.postnr'] = '';
$submit_vars['recipient.bynavn'] = '';
$submit_vars['recipient.provinsStat'] = '';
$submit_vars['recipient.landeid'] = 'AF';
$submit_vars['recipient.tlfnr'] = '';
$submit_vars['recipient.mobilTlfnr'] = '';
$submit_vars['recipient.email'] = '';
$submit_vars['productId'] = '340';

$snoopy->submit($submit_url, $submit_vars);
die($snoopy->results);
die(htmlspecialchars($snoopy->results));


//Ready pdf
/*
$submit_url = 'http://online.pannordic.com/pn_logistics/print_label_query.jsp?printerType=LASER2&bookingDate='.date('Y-m-d').'&bookingTime='.$matches[0].'&bookingUserId=00156973&actionBookingsOverview=LASER2';
$snoopy->fetch($submit_url);
*/
//Get lable type
//preg_match('/document.([a-z]{3})/i', $snoopy->results, $matches);
