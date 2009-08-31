<?php

$GLOBALS['generatedcontent']['crumbs'] = NULL;
$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => 'Faktura', 'link' => '/faktura/');
$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Dankort fejl', 'link' => '?error='.rawurlencode($_GET['error']).'&amp;ordrenr='.$_GET['id'].'&amp;tekst1='.$_GET['checkid']);

if(!$fakturas = $mysqli->fetch_array('SELECT * FROM `fakturas` WHERE id = '.$_GET['id'].' AND (status = \'new\' OR status = \'pbserror\' OR status = \'locked\') LIMIT 1')) {

	$GLOBALS['generatedcontent']['headline'] = 'Der er opstod følgende fejl';
	$GLOBALS['generatedcontent']['text'] = 'Ordren er muligvis allerede betalt.';
	
} else {

	$mysqli->query('UPDATE `fakturas` SET status = \'pbserror\' WHERE id = '.$_GET['id'].' AND status = \'new\' LIMIT 1');

	$GLOBALS['generatedcontent']['headline'] = 'Dankort fejl';
	$GLOBALS['generatedcontent']['text'] = '<h1>Der er opstod følgende fejl, ved betalingen:</h1><p>'.utf8_encode($_GET['error']).' <a href="#" onclick="history.back(); return false;">Prøv igen.</a></p>';
	
	require_once 'inc/phpMailer/class.phpmailer.php';
	
	//To shop
	$mail             = new PHPMailer();
	$mail->SetLanguage('dk');
	$mail->IsSMTP();
	if($GLOBALS['_config']['emailpassword'] !== false) {
		$mail->SMTPAuth   = true; // enable SMTP authentication
		$mail->Username   = $GLOBALS['_config']['email'][0];
		$mail->Password   = $GLOBALS['_config']['emailpassword'];
	} else {
		$mail->SMTPAuth   = false;
	}
	$mail->Host       = $GLOBALS['_config']['smtp'];      // sets the SMTP server
	$mail->Port       = $GLOBALS['_config']['smtpport'];              //  password
	$mail->CharSet    = 'utf-8';
	$mail->From       = $GLOBALS['_config']['email'][0];
	$mail->FromName   = $GLOBALS['_config']['site_name'];
	$mail->Subject    = 'Att: '.$fakturas[0]['clerk'].' - Online ordre #'.$_GET['id'].' : Betaling gennemført';
	$mail->MsgHTML('
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Att: '.$fakturas[0]['clerk'].' - ordre #'.$_GET['id'].' : fejl ved betaling</title>
</head>
<body>
<p>Der er opstod følgende fejl, da '.$fakturas[0]['navn'].' skule betale:</p>
<p>'.utf8_encode($_GET['error']).'</p>
<br />
Vigtigt:<br />
<p>Åben administrations siden <a href="'.$GLOBALS['_config']['base_url'].'/admin/faktura.php?id='.$_GET['id'].'">her</a> for at behandle ordren.</p>
<p>Direkte kontakt til kunden kan ske på:<br />
    Tlf.: '.$fakturas[0]['tlf1'].'<br />
    Mobil: '.$fakturas[0]['tlf2'].'<br />
    Leverings tlf.: '.$fakturas[0]['posttlf'].'<br />
    Email: <a href="mailto:'.$fakturas[0]['email'].'">'.$fakturas[0]['email'].'</a></p>
<br />
Mvh Computeren
</body>
</html>
', $_SERVER['DOCUMENT_ROOT']);
	if($fakturas[0]['department'])
		$mail->AddAddress($fakturas[0]['department'], $GLOBALS['_config']['site_name']);
	else
		$mail->AddAddress($GLOBALS['_config']['email'][0], $GLOBALS['_config']['site_name']);
	$mail->Send();
	//End shop
}
?>