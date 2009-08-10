<?php
function send_reklmation() {
	global $mysqli;
	require("zipcodedk.php");

	if($GLOBALS['reklmation'][1])
		$body .= 'Vi efterlyser hermed følgende '.count($GLOBALS['reklmation']).' pakker.'."\n\n";
	else
		$body .= 'Vi efterlyser hermed denne pakke.'."\n\n";
	$body .= "\n-----------------------------------\n\n";
	
	for($i=0;$i<count($GLOBALS['reklmation']);$i++) {
		$body .= 'Stregkode: '.$GLOBALS['reklmation'][$i]['STREGKODE']."\n";
		$body .= 'Afsendt '.$GLOBALS['reklmation'][$i]['formDate']."\n";
		if($GLOBALS['reklmation'][$i]['recPoValue'])
			$body .= 'Postopkrævning: '.$GLOBALS['reklmation'][$i]['recPoValue'].",-\n";
		if($GLOBALS['reklmation'][$i]['ss5amount'])
			$body .= 'Værdi: '.$GLOBALS['reklmation'][$i]['ss5amount'].",-\n";
		
		$body .= "\nAfsender:\n";
		if($GLOBALS['reklmation'][$i]['formSenderID'] == 11856) {
			$body .= 'Hunters House a/s'."\n";
			$body .= 'H.C. Ørsteds Vej 7B'."\n";
			$body .= '1879 Frederiksberg C'."\n";
		} elseif($GLOBALS['reklmation'][$i]['formSenderID'] == 11894) {
			$body .= 'Hunters House a/s'."\n";
			$body .= 'H.C. Ørsteds Vej 52A'."\n";
			$body .= '1879 Frederiksberg C'."\n";
		} elseif($GLOBALS['reklmation'][$i]['formSenderID'] == 11861) {
			$body .= 'Jagt og Fiskerimagasinet'."\n";
			$body .= 'Nørre Voldgade 8-10'."\n";
			$body .= '1358 København K'."\n";
		} elseif($GLOBALS['reklmation'][$i]['formSenderID'] == 11865) {
			$body .= 'Arms Gallery'."\n";
			$body .= 'Nybrogade 26 - 30'."\n";
			$body .= '1203 København K'."\n";
		}
		
		$body .= "\nModtager:\n";
		$body .= $GLOBALS['reklmation'][$i]['recName1']."\n";
		$body .= $GLOBALS['reklmation'][$i]['recAddress1']."\n";
		$body .= $GLOBALS['reklmation'][$i]['recZipCode'].' '.$arrayZipcode[$GLOBALS['reklmation'][$i]['recZipCode']]."\n";
		if($GLOBALS['reklmation'][$i]['recipientID'])
			$body .= 'Tlf.: '.$GLOBALS['reklmation'][$i]['recipientID']."\n";
		
		$body .= "\n-----------------------------------\n\n";
	}
	$body .= "Mvh Hunters House\n";
	$body .= "Telefon: 33222333\n";
	
	$email = 'mail@huntershouse.dk';
	$navn = 'Hunters House A/S';
	
	$headers = "MIME-Version: 1.0\n";
	$headers .= "Content-type: text/plain; charset=iso-8859-1\n";
	$headers .= "Content-Transfer-Encoding: 8bit\n";
	$headers .= "X-Priority: 3\n";
	$headers .= "X-MSMail-Priority: Normal\n";
	$headers .= 'From: "'.$navn.'" <'.$email.">\n";
	$headers .= "Reply-To: ".$email."\n";
	$headers .= "Bcc: Hunters House A/S <mail@huntershouse.dk>\n";

/*
	$headers .= 'Return-Path: '.$email."\n";
*/
	$headers .= "X-Mailer: PHP/".phpversion()."\n";
	$headers .= "X-originating-IP: ".$_SERVER['REMOTE_ADDR']."\n";
	if(mail(utf8_decode('fktsck101@post.dk'), utf8_decode('Reklamation'), utf8_decode($body), utf8_decode($headers))) {
		for($i=0;$i<count($GLOBALS['reklmation']);$i++) {
			$mysqli->query('UPDATE `post` SET `reklmation` = \'true\' WHERE `id` ='.$GLOBALS['reklmation'][$i]['id'].' LIMIT 1');
		}
	}
}

if($GLOBALS['reklmation'])
	send_reklmation();

?>