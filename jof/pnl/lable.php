<?php
mb_language("uni");
mb_internal_encoding('UTF-8');
require_once 'config.php';

require_once '../inc/mysqli.php';
require_once '../inc/config.php';

$_GET['l'] = ceil(preg_replace(array('/[^0-9,]/', '/,/'), array('', '.'), $_GET['l']));
$_GET['w'] = ceil(preg_replace(array('/[^0-9,]/', '/,/'), array('', '.'), $_GET['w']));
$_GET['h'] = ceil(preg_replace(array('/[^0-9,]/', '/,/'), array('', '.'), $_GET['h']));
$_GET['kg'] = ceil(preg_replace(array('/[^0-9,]/', '/,/'), array('', '.'), $_GET['kg']));
$_GET['insurance'] = ceil(preg_replace(array('/[^0-9,]/', '/,/'), array('', '.'), $_GET['insurance']));

$sender = array();
if($_GET['sender'] == 'JF') {
	$sender['name'] = 'Jagt & Fiskerimagasinet';
	$sender['address'] = 'Nørre Voldgade 8-10';
	$sender['postcode'] = '1358';
	$sender['city'] = 'København K';
	$sender['phonenumber'] = '+45 33337777';
} elseif($_GET['sender'] == 'AG') {
	$sender['name'] = 'Arms Gallery';
	$sender['address'] = 'Nybrogade 26 - 30';
	$sender['postcode'] = '1203';
	$sender['city'] = 'København K';
	$sender['phonenumber'] = '+45 33118338';
} elseif($_GET['sender'] == 'HH') {
	$sender['name'] = 'Hunters House';
	$sender['address'] = 'H.C. Ørsteds Vej 7 B';
	$sender['postcode'] = '1879';
	$sender['city'] = 'Frederiksberg C';
	$sender['phonenumber'] = '+45 33222333';
} elseif($_GET['sender'] == '52') {
	$sender['name'] = 'Hunters House';
	$sender['address'] = 'H.C. Ørsteds Vej 52 A';
	$sender['postcode'] = '1879';
	$sender['city'] = 'Frederiksberg C';
	$sender['phonenumber'] = '+45 35366666';
} else {
	echo('Du har ikke valgt en afsender!');
	return;
}
setcookie('sender', $_GET['sender'], time()+365*24*60*60, '/pnl/');

//TODO validate all


require_once 'snoopy/snoopy.class.php';
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

//Create order
$submit_url = 'http://online.pannordic.com/pn_logistics/pnl_action.jsp';

$submit_vars = array();
$submit_vars['action'] = 'CREATE';
$submit_vars['chosenTemplate'] = '';
$submit_vars['yPos'] = '';
$submit_vars['selectedReturnAddressCountry'] = 'DK';
$submit_vars['selectedDeliveryAddressCountry'] = '';
$submit_vars['cod'] = '';
$submit_vars['cargoInsuranceExists'] = '';
$submit_vars['service65'] = '';
$submit_vars['service41'] = '';
$submit_vars['service91'] = '';
$submit_vars['senderCountryCode'] = 'DK';
$submit_vars['selectedProduct'] = $_GET['product'];
$submit_vars['service'] = '';
$submit_vars['serviceDescription'] = '';
$submit_vars['selectedDepartureTime'] = '';
$submit_vars['selectedTypeOfContens'] = $_GET['contens'];
$submit_vars['selectedNonDeliveryInstructions'] = $_GET['return'];;
$submit_vars['selectedCodCurrency'] = '';
$submit_vars['returnAddressContactId'] = '';
$submit_vars['deliveryAddressContactId'] = '';
$submit_vars['productError'] = '';
$submit_vars['productWarning'] = 'N';
$submit_vars['pnlAction'] = '';
$submit_vars['bookingDate'] = '';
$submit_vars['bookingTime'] = '';
$submit_vars['bookingUserId'] = $GLOBALS['_config']['username'];
$submit_vars['bookingId'] = '';
$submit_vars['lastNumberOfPieces'] = '';
$submit_vars['templateName'] = '';
$submit_vars['templateAction'] = '';
$submit_vars['senderAddressName'] = $sender['name'];
$submit_vars['senderAddressAttention'] = '';
$submit_vars['senderAddressAddress'] = $sender['address'];
$submit_vars['senderAddressAddres2'] = '';
$submit_vars['senderAddressPostcode'] = $sender['postcode'];
$submit_vars['senderAddressCity'] = $sender['city'];
$submit_vars['senderAddressCountry'] = 'Denmark';
$submit_vars['bookingDateReturn'] = '';
$submit_vars['bookingTimeReturn'] = '';
$submit_vars['bookingUserIdReturn'] = '';
$submit_vars['bookingIdReturn'] = '';
$submit_vars['waybill'] = '';
$submit_vars['outsideEu'] = '';
$submit_vars['defaultTemplate'] = '';
$submit_vars['clearAction'] = '';
$submit_vars['mandatory'] = 'N';
$submit_vars['products'] = $_GET['product'];
$submit_vars['myAddressee.idParticipant'] = '';
$submit_vars['addresseeName'] = $_GET['name'];
$submit_vars['addresseeAttention'] = $_GET['att'];
$submit_vars['addresseeAddress'] = $_GET['address'];
$submit_vars['addresseeAddres2'] = $_GET['address2'];
$submit_vars['addresseePostcode'] = $_GET['postcode'];
$submit_vars['addresseeCity'] = $_GET['city'];
$submit_vars['addresseeCountry'] = $_GET['country'];
$submit_vars['selectedAddresseeCountry'] = $_GET['country'];
$submit_vars['addresseePhoneNumber'] = '';
$submit_vars['addresseeAccountNumber'] = '';
$submit_vars['addresseeContactId'] = '';
$submit_vars['returnAddressBox'] = 'Y';
$submit_vars['returnAddressName'] = $sender['name'];
$submit_vars['deliveryAddressName'] = '';
$submit_vars['returnAddressAttention'] = '';
$submit_vars['deliveryAddressAttention'] = '';
$submit_vars['returnAddressAddress'] = $sender['address'];
$submit_vars['deliveryAddressAddress'] = '';
$submit_vars['returnAddressAddres2'] = '';
$submit_vars['deliveryAddressAddres2'] = '';
$submit_vars['returnAddressPostcode'] = $sender['postcode'];
$submit_vars['deliveryAddressPostcode'] = '';
$submit_vars['returnAddressCity'] = $sender['city'];
$submit_vars['deliveryAddressCity'] = '';
$submit_vars['returnAddressCountry'] = 'DK';
$submit_vars['returnAddressPhoneNumber'] = $sender['phonenumber'];
$submit_vars['deliveryAddressPhoneNumber'] = '';
$submit_vars['returnAddressAccountNumber'] = '';
$submit_vars['deliveryAddressAccountNumber'] = '';
$submit_vars['codAmount'] = '';
$submit_vars['amountInWriting'] = '';
$submit_vars['codAccount'] = '';
$submit_vars['codReference'] = '';
$submit_vars['amountDkk'] = '';
$submit_vars['amountDkkInWriting'] = '';
$submit_vars['amountSdr'] = '';
$submit_vars['deliveryInstructions1'] = '';
$submit_vars['departureDate'] = date('Y-m-d');
$submit_vars['departureTime'] = '';
$submit_vars['deliveryInstructions2'] = '';
$submit_vars['numberOfPieces'] = '1';
$submit_vars['contents'] = $_GET['text'];
$submit_vars['weightStr'] = $_GET['kg'];
$submit_vars['lengthStr'] = $_GET['l'];
$submit_vars['widthStr'] = $_GET['w'];
$submit_vars['heigthStr'] = $_GET['h'];
$submit_vars['typeOfContens'] = $_GET['contens'];
$submit_vars['volumeStr'] = round($_GET['l']*$_GET['w']*$_GET['h']/1000000, 3);
$submit_vars['savedVolume'] = '';
$submit_vars['customerReference'] = $_GET['ref'];
if($_GET['insurance'])
	$submit_vars['cargoInsurance'] = 'Y';
$submit_vars['message'] = '';
if($_GET['insurance'])
	$submit_vars['typeOfGoods'] = '001';
else
	$submit_vars['typeOfGoods'] = '';
$submit_vars['valueIncludingFreight'] = $_GET['insurance'];
if($_GET['insurance'])
	$submit_vars['cargoCurrencyCode'] = 'dkk';
else
	$submit_vars['cargoCurrencyCode'] = '';
$submit_vars['nonDeliveryInstructions'] = $_GET['return'];

$submit_vars = array_map('utf8_decode', $submit_vars);
$snoopy->submit($submit_url, $submit_vars);
//get bookingTime
preg_match('/[0-9]+:[0-9]+:[0-9]+/i', $snoopy->results, $bookingTime);

//Ready pdf
$submit_url = 'http://online.pannordic.com/pn_logistics/print_label_query.jsp?printerType=LASER2&bookingDate='.date('Y-m-d').'&bookingTime='.$bookingTime[0].'&bookingUserId='.$GLOBALS['_config']['username'].'&actionBookingsOverview=LASER2';
$snoopy->fetch($submit_url);
//Get lable type
preg_match('/document[.]([a-z]{3})/i', preg_replace('/<!--.*?-->/siu', '',  $snoopy->results), $labelType);

$submit_url = 'http://online.pannordic.com/pn_logistics/PrintPdfServ?multipleLabel=Y&printerType=LASER2&labelType='.strtoupper($labelType[1]);
$snoopy->fetch($submit_url);
//Forwared header
foreach($snoopy->headers as $header) {
	header($header);
}

//Out put pdf
echo($snoopy->results);

//Get Id's
$submit_url = 'http://online.pannordic.com/pn_logistics/pnl_action2.jsp?pnlAction=QUERY&bookingDate='.date('Y-m-d').'&bookingTime='.$bookingTime[0].'&bookingUserId='.$GLOBALS['_config']['username'].'&fromOverviewScreen=Y';
$snoopy->fetch($submit_url);


preg_match('/[0-9]{17}/i', $snoopy->results, $shipmentId);
preg_match('/[A-Z]{2}[0-9]{9}[A-Z]{2}/i', $snoopy->results, $packageId);

$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$mysqli->query("INSERT INTO `PNL` (`fakturaid`,					`bookingDate`, 			`bookingTime`, 			`shipmentId`, 			`packageId`, 			`labelType`, 						`sender`, 				`name`, 				`att`, 				`address`, 				`address2`, 				`postcode`, 				`city`, 				`country`, 				`product`, 				`contens`, 				`text`, 				`kg`, 				`w`, 				`h`, 				`l`, 				`return`, 				`ref`, 				`insurance` ) VALUES (
								   '".$_GET['fakturaid']."', 	'".date('Y-m-d')."', 	'".$bookingTime[0]."', 	'".$shipmentId[0]."', 	'".$packageId[0]."', 	'".strtoupper($labelType[1])."', 	'".$_GET['sender']."', 	'".$_GET['name']."', 	'".$_GET['att']."', '".$_GET['address']."', '".$_GET['address2']."', 	'".$_GET['postcode']."', 	'".$_GET['city']."', 	'".$_GET['country']."', '".$_GET['product']."', '".$_GET['contens']."', '".$_GET['text']."', 	'".$_GET['kg']."', 	'".$_GET['w']."', 	'".$_GET['h']."', 	'".$_GET['l']."', 	'".$_GET['return']."', 	'".$_GET['ref']."', '".$_GET['insurance']."'
);");


if($_GET['email']) {
	
	require_once 'countries.php';
	$emailBody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Forsendelsesadvis</title>
</head>
<body>
<p>Kære '.($_GET['att'] ? $_GET['att'] : $_GET['name']).'</p>
<p>Tak for din ordre. Den vil blive leveret af PNL på følgende adresse:</p>
<p>'.$_GET['name'];

if($_GET['att']) $emailBody .= '<br />Att.: '.$_GET['att'];

$emailBody .= '<br />'.$_GET['address'];

if($_GET['att']) $emailBody .= '<br />'.$_GET['address2'];

$emailBody .= '<br />'.$_GET['postcode'].' '.$_GET['city'].'<br />'.$countries[$_GET['country']].'</p>
<p>Forsendelsen pakkes med følgende track &amp; trace nummer: '.$packageId[0].'.<br />
	Du kan således gå ind og følge din forsendelse på PNLs hjemmeside: <br />
<a href="http://online.pannordic.com/pn_logistics/index_tracking_email.jsp?id='.$packageId[0].'&Search=search">http://online.pannordic.com/pn_logistics/index_tracking_email.jsp?id='.$packageId[0].'&Search=search</a>.</p>
<p>Har du spørgsmål, er du velkommen til at kontakte os på telefon '.$sender['phonenumber'].'.</p>
<p>Med venlig hilsen</p>
<p>'.$sender['name'].'</p>
</body>
</html>';
	
	include "../inc/phpMailer/class.phpmailer.php";
	include "../inc/config.php";
	$mail             = new PHPMailer();
	$mail->SetLanguage('dk');
	$mail->IsSMTP();
	$mail->SMTPAuth   = true;                  // enable SMTP authentication
	$mail->Host       = $GLOBALS['_config']['smtp'];      // sets the SMTP server
	$mail->Port       = 25;                   // set the SMTP port for the server
	$mail->Username   = $GLOBALS['_config']['email'][0];  //  username
	$mail->Password   = $GLOBALS['_config']['emailpassword'];            //  password
	$mail->CharSet    = 'utf-8';
	$mail->AddReplyTo($GLOBALS['_config']['email'][0], $GLOBALS['_config']['site_name']);
	$mail->From       = $GLOBALS['_config']['email'][0];
	$mail->FromName   = $GLOBALS['_config']['site_name'];
	$mail->Subject    = 'Forsendelsesadvis';
	$mail->MsgHTML($emailBody, $_SERVER['DOCUMENT_ROOT']);
	$mail->AddAddress($_GET['email'], ($_GET['att'] ? $_GET['att'] : $_GET['name']));
	if($mail->Send()) {
		//TODO secure this against injects and <; in the email and name
		$mysqli->query("INSERT INTO `emails` (`subject`, `from`, `to`, `body`, `date`) VALUES ('Forsendelsesadvis', '".$GLOBALS['_config']['site_name']."<".$GLOBALS['_config']['email'][0].">', '".($_GET['att'] ? $_GET['att'] : $_GET['name'])."<".$_GET['email'].">', '".$$emailBody."', NOW());");
	}
	
	//Upload email to the sent folder via imap
	if($GLOBALS['_config']['imap']) {
		require_once "inc/imap.inc.php";
		$imap = new IMAPMAIL;
		$imap->open($GLOBALS['_config']['imap'], $GLOBALS['_config']['imapport']);
		$imap->login($GLOBALS['_config']['email'][0], $GLOBALS['_config']['emailpasswords'][0]);
		$imap->append_mail($GLOBALS['_config']['emailsent'], $mail->CreateHeader().$mail->CreateBody(), '\Seen');
		$imap->close();
	}
}
?>