<?php

//is the email valid
function valide_mail($email) {
	if(preg_match('/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i', $email) && getmxrr(preg_replace('/.+?@(.?)/u', '$1', $email), $dummy))
		return true;
	else
		return false;
}

$email_rejected = valide_mail($_POST['email']);

if(!$email_rejected) {
	//Save to database
	require_once("inc/config.php");
	require_once("inc/mysqli.php");
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	
	$mysqli->query("INSERT INTO `hack-trap` (`log` ,`date` )VALUES ('".$_POST['adresse']." ".
	$_SERVER['REMOTE_ADDR']." ".
	$_POST['post']." ".
	$_POST['tlf1']." ".
	$_POST['by']." ".
	$_POST['tlf2']." ".
	$_POST['email']." ".
	$_POST['navn']." ".
	$_POST['land']." ".
	$_POST['tilfoj'].
	"', NOW());");
	
	$mysqli->close();
}

if(($_POST['adresse'] && ($_POST['post'] || $_POST['by'])) || !$email_rejected || $_POST['tlf1'] || $_POST['tlf2']) {
	//Save to database
	require_once("inc/config.php");
	require_once("inc/mysqli.php");
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	
	$mysqli->query("INSERT INTO `email` (`navn`, `email`, `adresse`, `land`, `post`, `by`, `tlf1`, `tlf2`, `kartotek`, `interests`, `dato` , `downloaded` , `ip` )
	VALUES ('".$_POST['navn']."', '".$_POST['email']."', '".$_POST['adresse']."', '".$_POST['land']."', '".$_POST['post']."', '".$_POST['by']."', '".$_POST['tlf1']."', '".$_POST['tlf2']."', '".$_POST['tilfoj']."', '".@$interests."', now(), '".$downloaded."', '".$_SERVER['REMOTE_ADDR']."')");
	
	$mysqli->close();
}

$GLOBALS['generatedcontent']['activmenu'] = 141;
$delayprint = true;
require_once 'index.php';
$GLOBALS['generatedcontent']['contenttype'] = 'page';
$GLOBALS['generatedcontent']['keywords'] = NULL;
$GLOBALS['generatedcontent']['list'] = NULL;

if(!$email_rejected) {
	$_POST['email'] = 'mail@arms-gallery.dk';
	$GLOBALS['generatedcontent']['title'] = 'Fejl';
	$GLOBALS['generatedcontent']['headline'] = 'Fejl';
	$GLOBALS['generatedcontent']['text'] = '<p>Vi er nød til at have din email adresse.</p>';	
} else {

	require_once("inc/config.php");
	
	$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Bestilling af skudsikker vest</title></head><body><div>Bestillings Formular (Fortrolig) På skudsikre veste:<table border="0">
	<tbody>';
	
	if($_POST['navn'])
		$body .= '<tr><td> Navn: </td><td>'.$_POST['navn'].'</td></tr>';
	
	if($_POST['adresse'])
		$body .= '<tr><td> Adresse: </td><td>'.$_POST['adresse'].'</td></tr>';
	
	if($_POST['postnr'] || $_POST['by'])
		$body .= '<tr><td> Post nr.: </td><td nowrap="nowrap">'.$_POST['postnr'].' By: '.$_POST['by'].'</td></tr>';
	
	if($_POST['tlf1'] || $_POST['tlf2']) {
		$body .= '<tr><td> Telefon: </td><td>';
		if($_POST['adresse']) {
			$body .= '(Dag) '.$_POST['tlf1']; 
		}
		if($_POST['adresse']) {
			$body .= ' (Aften) '.$_POST['tlf2'];
		} $body .= '</td></tr>';
	}
	
	if($_POST['email'])
		$body .= '<tr><td> E-mail: </td><td>'.$_POST['email'].'</td></tr>';
	
	$body .= '</tbody></table><br />';
	
	if($_POST['size'] == 'special')
		$body .= '<table border="0" width="100%"><tbody><tr><td> Køn: '.$_POST['sex'].'</td><td> Vesttype: '.$_POST['type'].'</td><td> Farve: '.$_POST['farve'].'</td></tr></tbody></table><p> Mål:</p><table border="0" width="100%"><tbody><tr><td align="middle"> Højde: '.$_POST['high'].'cm</td><td align="middle"> Vægt: '.$_POST['kg'].' Kg.</td></tr></tbody></table><p></p><p align="center"> <img src="/images/vagtudstyr/skudsikkert/bodyarmor-mål.png" alt="" width="475" /> </p><table border="0" width="100%"><tbody><tr><td colspan="3" align="middle"> Mål rundt om <br />kroppen <strong> stående </strong> </td><td rowspan="9" bgcolor="#000000"></td><td colspan="3" align="middle"> Mål fra pil til pil <br /><strong> stående </strong> </td><td rowspan="9" bgcolor="#000000"></td><td colspan="3" align="middle"> Mål fra pil til pil <br /><strong> siddende </strong> </td></tr><tr><td align="right"> 1: </td><td width="1%">'.$_POST['st1'].'</td><td> cm </td><td align="right"> 4: </td><td width="1%">'.$_POST['st4'].'</td><td> cm </td><td align="right"> 7: </td><td width="1%">'.$_POST['si7'].'</td><td> cm </td></tr><tr><td align="right"> 2: </td><td width="1%">'.$_POST['st2'].'</td><td> cm </td><td align="right"> 5: </td><td>'.$_POST['st5'].'</td><td> cm </td><td align="right"> 8: </td><td>'.$_POST['si8'].'</td><td> cm </td></tr><tr><td align="right"> 3: </td><td>'.$_POST['st3'].'</td><td> cm </td><td align="right"> 6: </td><td>'.$_POST['st6'].'</td><td> cm </td><td align="right"> 9: </td><td>'.$_POST['si9'].'</td><td> cm </td></tr><tr><td align="right"> Cup Str: </td><td>'.$_POST['cupstr'].'</td><td></td><td align="right"> 7: </td><td>'.$_POST['st7'].'</td><td> cm </td><td align="right"> BH Str: </td><td>'.$_POST['bhstr'].'</td></tr><tr><td colspan="3"></td><td align="right"> 8: </td><td>'.$_POST['st8'].'</td><td> cm </td></tr><tr><td colspan="3"></td><td align="right"> 9: </td><td>'.$_POST['st9'].'</td><td> cm </td></tr></tbody>
	</table>';
	else
		$body .= 'Størelse: '.$_POST['size'];
	
	if($_POST['ovrige'])
		$body .= '<p>Øvrige ønsker og modificeringer:<br />'.$_POST['ovrige'].'</p>';
	
	$body .= '</div></body></html>';
	
	
	////////////////////////////////////////////////
	//send email
	include "inc/phpMailer/class.phpmailer.php";
	
	$mail             = new PHPMailer();
	$mail->SetLanguage('dk');
	
	$mail->IsSMTP();
	$mail->SMTPAuth   = true;					// enable SMTP authentication
	$mail->Host       = 'smtp.exserver.dk';		// sets the SMTP server
	$mail->Port       = 26;						// set the SMTP port for the server
	
	$mail->Username   = $GLOBALS['_config']['email'][0];		//  username
	$mail->Password   = $GLOBALS['_config']['emailpassword'];	//  password
	
	$mail->CharSet    = 'utf-8';
	
	$mail->AddReplyTo($_POST['email'], $_POST['navn']);
	
	$mail->From       = $_POST['email'];
	$mail->FromName   = $_POST['navn'];
	
	$mail->Subject    = 'Bestilling af skudsikker vest';
	
	$mail->MsgHTML($body, $_SERVER['DOCUMENT_ROOT']);
	
	$mail->AddAddress($GLOBALS['_config']['email'][0], $GLOBALS['_config']['site_name']);
	
	if(!$mail->Send()) {
		//TODO secure this against injects and <; in the email and name
		$mysqli->query("INSERT INTO `emails` (`subject`, `from`, `to`, `body`, `date`) VALUES ('Bestilling af skudsikker vest', '".$_POST['navn']."<".$_POST['email'].">', '".$GLOBALS['_config']['site_name']."<".$GLOBALS['_config']['email'][0].">', '".$body."', NOW());");
	}
	////////////////////////////////////////////////

	$GLOBALS['generatedcontent']['title'] = 'Tak for bestillingen';
	$GLOBALS['generatedcontent']['headline'] = 'Vi har modtaget din bestilling af skud- og stiksikker vest';
	$GLOBALS['generatedcontent']['text'] = '<p>Vesten syes og hjemtages ud fra denne bestilling.</p>
<p>Du vil snarest modtage en mail med samlet pris, - når betaling er modtaget hos os, - etableres ordren hos vores fabrikant.</p>
<p>Med venlig hilsen,</p>
<p>Sikkerhedafdelingen<br />Arms Gallery.</p>';
}



require_once 'theme/index.php';
?>