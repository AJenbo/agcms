<?php

require_once("inc/config.php");

//Colect interests
$interests = '';
foreach($GLOBALS['_config']['interests'] as $interest) {
	if(@$_POST[preg_replace('/\\s/u', '_', $interest)]) {
		if($interests)
			$interests .= '<';
		$interests .= $interest;
	}
}

$downloaded = 1-@$_POST['nodownload'];

//Does the host have a valid 
function valide_mail_host($host) {
	return getmxrr(preg_replace('/.+?@(.?)/u', '$1', $host), $dummy);
}

//is the email valid
if(!preg_match('/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i', $_POST['email']) || !valide_mail_host($_POST['email'])) {
	$_POST['email'] = '';
	$email_rejected = true;
}
	
if(($_POST['adresse'] && ($_POST['post'] || $_POST['by'])) || !$email_rejected || $_POST['tlf1'] || $_POST['tlf2']) {
	//Save to database
	require_once("inc/mysqli.php");
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	
	$mysqli->query("INSERT INTO `email` (`navn`, `email`, `adresse`, `land`, `post`, `by`, `tlf1`, `tlf2`, `kartotek`, `interests`, `dato` , `downloaded` , `ip` )
	VALUES ('".$_POST['navn']."', '".$_POST['email']."', '".$_POST['adresse']."', '".$_POST['land']."', '".$_POST['post']."', '".$_POST['by']."', '".$_POST['tlf1']."', '".$_POST['tlf2']."', '".$_POST['tilfoj']."', '".@$interests."', now(), '".$downloaded."', '".$_SERVER['REMOTE_ADDR']."')");
	
	$mysqli->close();
}
/*
if($_POST['nodownload']) {

	//Generate mail body
	$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>'.$GLOBALS['_config']['site_name'].'</title></head><body><p><strong>Et katalog skal sendes til:</strong>';
	if($_POST['navn']) $body .= '<br />'.$_POST['navn'];
	if($_POST['adresse']) $body .= '<br />'.$_POST['adresse'];
	if($_POST['post'] || $_POST['by']) $body .= '<br />'.$_POST['post'].' '.$_POST['by'];
	if($_POST['land']) $body .= '<br />'.$_POST['land'].'</p>';
	if($_POST['tlf1'] || $_POST['tlf2'] || $_POST['email']) {
		$body .= '<p><strong>Kontakt info:</strong>';
		if($_POST['tlf1']) $body .= '<br />Tlf.: '.$_POST['tlf1'];
		if($_POST['tlf2']) $body .= '<br />Tlf.: '.$_POST['tlf2'];
		if($_POST['email']) $body .= '<br />Email: '.$_POST['email'];
		$body .= '</p>';
	}
	if($_POST['text']) $body .= '<p><strong>Note:</strong><br />'.$_POST['text'].'</p>';
	$body .= '</body></html>';
	
	//Email sender
	if(!$_POST['email'])
		$from = $GLOBALS['_config']['email'][0];
	else
		$from = $_POST['email'];
	
	$subject = '2009 Katalog';
	
	//TODO rejeect on errors
*/
	//Generate return page
	$GLOBALS['generatedcontent']['activmenu'] = 545;
	
	$delayprint = true;
	
	require_once 'index.php';

	
	$GLOBALS['generatedcontent']['contenttype'] = 'page';		
	
	/*
	if($_POST['adresse'] && ($_POST['post'] || $_POST['by'])) {
		$GLOBALS['generatedcontent']['title'] = 'Tak for bestillingen';
		$GLOBALS['generatedcontent']['headline'] = 'Tak for bestillingen';
		$GLOBALS['generatedcontent']['text'] = '<p>Vi har med tak modtaget deres bestilling.<br />Vi sender dem et katalog inden for en uge.</p>';
		
		////////////////////////////////////////////////
		//send email
		include "inc/phpMailer/class.phpmailer.php";
		
		$mail             = new PHPMailer();
		$mail->SetLanguage('dk');
		
		$mail->IsSMTP();
		$mail->SMTPAuth   = true;                  // enable SMTP authentication
		$mail->Host       = "smtp.exserver.dk";      // sets the SMTP server
		$mail->Port       = 26;                   // set the SMTP port for the server
		
		$mail->Username   = $GLOBALS['_config']['email'][0];  //  username
		$mail->Password   = $GLOBALS['_config']['emailpassword'];            //  password
		
		$mail->CharSet    = 'utf-8';
		
		$mail->AddReplyTo($from, $_POST['navn']);
		
		$mail->From       = $from;
		$mail->FromName   = $_POST['navn'];
		
		$mail->Subject    = $subject;
		
		$mail->MsgHTML($body, $_SERVER['DOCUMENT_ROOT']);
		
		$mail->AddAddress($GLOBALS['_config']['email'][0], $GLOBALS['_config']['site_name']);
		
		$mail->Send();
		////////////////////////////////////////////////
		
	} else {
		*/
		$GLOBALS['generatedcontent']['title'] = 'Fejl i indtastningen';
		$GLOBALS['generatedcontent']['headline'] = 'Fejl i indtastningen';
		$GLOBALS['generatedcontent']['text'] = '<p>De har ikke indtasted en gyldig post adresse.</p>';
//	}
	
	if($email_rejected)
		$GLOBALS['generatedcontent']['text'] .= '<p>Deres email adresse blev ikke godkendt!</p>';
	
	$GLOBALS['generatedcontent']['text'] .= '<p>Med vendlig hilsen<br />'.$GLOBALS['_config']['site_name'].'</p>';

	$GLOBALS['generatedcontent']['keywords'] = NULL;
	$GLOBALS['generatedcontent']['list'] = NULL;

	//Print page
	require_once 'theme/index.php';
/*
} else {
	//Redirect to catalog
	header("Location: files/pdf/jagt-og-fiskerimagasinet-2009-katalog.pdf");
}
*/
?>
