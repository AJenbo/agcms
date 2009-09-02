<?php

$GLOBALS['generatedcontent']['crumbs'] = NULL;
$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => 'Faktura', 'link' => '/faktura/');
$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Kvitering', 'link' => '?id='.$_GET['id'].'&amp;checkid='.$_GET['checkid']);

if(!$fakturas = $mysqli->fetch_array('SELECT * FROM `fakturas` WHERE id = '.$_GET['id'].' AND (status = \'new\' OR status = \'pbserror\' OR status = \'locked\') LIMIT 1')) {

	$GLOBALS['generatedcontent']['headline'] = 'Der er opstod følgende fejl';
	$GLOBALS['generatedcontent']['text'] = 'Ordren er muligvis allerede betalt.';
	
} else {

	$mysqli->query('UPDATE `fakturas` SET status = \'pbsok\' WHERE id = '.$_GET['id'].' LIMIT 1');
	
	$GLOBALS['generatedcontent']['headline'] = 'Betaling gennemført:';
	$GLOBALS['generatedcontent']['text'] = '<p>Kære '.$fakturas[0]['navn'].', vi takker for din ordre.</p><p><strong>Modtagere:</strong><br />'.$fakturas[0]['navn'];
	if($fakturas[0]['att'])
		$GLOBALS['generatedcontent']['text'] .= '<br />Att.: '.$fakturas[0]['att'];
	$GLOBALS['generatedcontent']['text'] .= '<br />'.$fakturas[0]['adresse'].'<br />'.$fakturas[0]['postnr'].' '.$fakturas[0]['by'];
	if($fakturas[0]['land'])
		$GLOBALS['generatedcontent']['text'] .= '<br />'.$fakturas[0]['land'];
		
	$GLOBALS['generatedcontent']['text'] .= '</p>';
	
	
	
	//Display faktura start
	$momssats = 1+$fakturas[0]['momssats'];
	
	$quantities = explode('<', $fakturas[0]['quantities']);
	$products = explode('<', $fakturas[0]['products']);
	$values = explode('<', $fakturas[0]['values']);
	
	if($fakturas[0]['premoms']) {
		foreach($values as $key => $value) {
			$values[$key] = $value/1.25;
		}
	}
	
	$productslines = max(count($quantities), count($products), count($values));
	
	$GLOBALS['generatedcontent']['text'] .= '<table id="faktura" cellspacing="0" style="width:80%; margin:20px auto"><thead><tr><td>Beskrivels</td><td>Stk</td><td align="center">á</td><td align="right">I alt</td></tr></thead>';
	
	$temp = '';
	for($i=0;$i<$productslines;$i++) {
		$temp .= '<tr><td>'.$products[$i].'</td><td style="text-align:right">'.$quantities[$i].'</td><td style="text-align:right">'.number_format($values[$i]*$momssats, 2, ',', '.').'</td><td style="text-align:right">'.number_format($values[$i]*$quantities[$i]*$momssats, 2, ',', '.').'</td></tr>';
		$total += $values[$i]*$quantities[$i]*$momssats;
	}
	
	$GLOBALS['generatedcontent']['text'] .= '<tfoot>';
	if($fakturas[0]['fragt'] > 0) {
		$GLOBALS['generatedcontent']['text'] .= '<tr><td>Fragt:</td><td></td><td></td><td style="text-align:right">'.number_format($fakturas[0]['fragt'], 2, ',', '.').'</td></tr>';
	}
	$GLOBALS['generatedcontent']['text'] .= '<tr style="font-weight:bold"><td>Betalingsbeløb inkl. moms:</td><td></td><td></td><td style="text-align:right">DKK '.number_format($total+$fakturas[0]['fragt'], 2, ',', '.').'</td></tr>';
	$GLOBALS['generatedcontent']['text'] .= '<tr><td>Heraf moms:</td><td></td><td></td><td style="text-align:right">DKK '.number_format($total-($total/$momssats), 2, ',', '.').'</td></tr>
	</tfoot><tbody>'.$temp.'</tbody></table>';
	//Display faktura end
	
	$GLOBALS['generatedcontent']['track'] = ' pageTracker._addTrans("'.$fakturas[0]['id'].'", "", "'.$fakturas[0]['amount'].'", "'.($total-($total/$momssats)).'", "'.$fakturas[0]['fragt'].'", "'.$fakturas[0]['by'].'", "", "'.$countries[$fakturas[0]['land']].'");';
	foreach($products as $key => $product)
		$GLOBALS['generatedcontent']['track'] .= ' pageTracker._addItem("'.$fakturas[0]['id'].'", "'.$fakturas[0]['id'].$key.'", "'.$product.'", "", "'.($values[$key]*(1+$fakturas[0]['momssats'])).'", "'.$quantities[$key].'");';
	$GLOBALS['generatedcontent']['track'] .= ' pageTracker._trackTrans(); ';

	
	require_once 'inc/phpMailer/class.phpmailer.php';
	
	//To shop
	if($fakturas[0]['department'] != 'mail@huntershouse.dk') {
		$emailBody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Att: '.$fakturas[0]['clerk'].' - Online ordre #'.$_GET['id'].' : Betaling gennemført</title>
	<style type="text/css">
	td {
		border:1px solid #000;
		border-collapse:collapse;
	}
	</style></head><body>
	<p>'.$fakturas[0]['navn'].'<br />
		'.$fakturas[0]['adresse'].'<br />
		'.$fakturas[0]['postnr'].' '.$fakturas[0]['by'].'<br />
		'.$fakturas[0]['land'].'</p>
	<p>Har godkendt betalingen.
		Vigtigt: Husk at godkende betaling når varen sendes. Klik <a href="'.$GLOBALS['_config']['base_url'].'/admin/faktura.php?id='.$_GET['id'].'">her</a> for at åbne faktura siden.</p>
	<p><a href="mailto:'.$fakturas[0]['email'].'">'.$fakturas[0]['email'].'</a><br />
		Mobil: '.$fakturas[0]['tlf2'].'<br />
		Tlf.: '.$fakturas[0]['tlf1'].'<br />
		Leverings tlf.: '.$fakturas[0]['posttlf'].'</p>
		<table id="faktura" cellspacing="0" style="width:80%; margin:20px auto"><thead><tr><td>Beskrivels</td><td>Stk</td><td align="center">á</td><td align="right">I alt</td></tr></thead>
		<tfoot>';
		if($fakturas[0]['fragt'] > 0) {
			$emailBody .= '<tr><td>Fragt:</td><td></td><td></td><td style="text-align:right">'.number_format($fakturas[0]['fragt'], 2, ',', '.').'</td></tr>';
		}
		$emailBody .= '<tr style="font-weight:bold"><td>Betalingsbeløb inkl. moms:</td><td></td><td></td><td style="text-align:right">DKK '.number_format($total+$fakturas[0]['fragt'], 2, ',', '.').'</td></tr>';
		$emailBody .= '<tr><td>Heraf moms:</td><td></td><td></td><td style="text-align:right">DKK '.number_format($total-($total/$momssats), 2, ',', '.').'</td></tr></tfoot><tbody>'.$temp.'</tbody></table>';
		$emailBody .= '<p>Mvh Computeren</p></body></html>';
		
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
		$mail->MsgHTML($emailBody, $_SERVER['DOCUMENT_ROOT']);
		if($fakturas[0]['department'])
			$mail->AddAddress($fakturas[0]['department'], $GLOBALS['_config']['site_name']);
		else
			$mail->AddAddress($GLOBALS['_config']['email'][0], $GLOBALS['_config']['site_name']);
		$mail->Send();
	}
	//End shop
	
	if($fakturas[0]['email']) {
		//To customer
		
		$emailBody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ordrebekræftelse fra '.$GLOBALS['_config']['site_name'].'</title>
</head>
<body>
Kære '.$fakturas[0]['navn'].', vi takker for din ordre:<br />
Modtagere:<br />
'.$fakturas[0]['navn'].'<br />
'.$fakturas[0]['adresse'].'<br />
'.$fakturas[0]['postnr'].' '.$fakturas[0]['by'].'<br />
'.$fakturas[0]['email'].'<br />
<br />
'.date('d/m/Y').'<br />
'.$fakturas[0]['id'].'<br />
<br />';

		$emailBody .= '<table id="faktura" cellspacing="0" style="width:80%; margin:20px auto"><thead><tr><td>Beskrivels</td><td>Stk</td><td align="center">á</td><td align="right">I alt</td></tr></thead>';
		
		$emailBody .= '<tfoot>';
		if($fakturas[0]['fragt'] > 0) {
			$emailBody .= '<tr><td>Fragt:</td><td></td><td></td><td style="text-align:right">'.number_format($fakturas[0]['fragt'], 2, ',', '.').'</td></tr>';
		}
		$emailBody .= '<tr style="font-weight:bold"><td>Betalingsbeløb inkl. moms:</td><td></td><td></td><td style="text-align:right">DKK '.number_format($total+$fakturas[0]['fragt'], 2, ',', '.').'</td></tr>';
		$emailBody .= '<tr><td>Heraf moms:</td><td></td><td></td><td style="text-align:right">DKK '.number_format($total-($total/$momssats), 2, ',', '.').'</td></tr></tfoot><tbody>'.$temp.'</tbody></table>';
		$emailBody .= $GLOBALS['_config']['site_name'].'</body></html>';
		
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
		$mail->Subject    = 'Ordrebekræftelse fra '.$GLOBALS['_config']['site_name'];
		$mail->MsgHTML($emailBody, $_SERVER['DOCUMENT_ROOT']);
		$mail->AddAddress($fakturas[0]['email'], $fakturas[0]['navn']);
		$mail->Send();
		//End customer
		$GLOBALS['generatedcontent']['text'] .= '<p>Vi har sendt en kopi af ordrebekræftelse til adressen: <b>'.$fakturas[0]['email'].'</b></p>';
	}
	
	$GLOBALS['generatedcontent']['text'] .= '<p>Med venlig hilsen <br />'.$fakturas[0]['clerk'].'<br />Hunters House</p>';
}
?>