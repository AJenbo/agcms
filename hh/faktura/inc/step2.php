<?php

if(!$fakturas = $mysqli->fetch_array('SELECT * FROM `fakturas` WHERE id = '.$_GET['id'].' AND (status = \'new\' OR status = \'pbserror\' OR status = \'locked\') LIMIT 1')) {

	$GLOBALS['generatedcontent']['headline'] = 'Der er opstod følgende fejl';
	$GLOBALS['generatedcontent']['text'] = 'Ordren er muligvis allerede betalt.';
	
} else {

	$mysqli->query('UPDATE `fakturas` SET `status` = \'locked\' WHERE `id` = '.$_GET['id'].' LIMIT 1 ;');
	
	$GLOBALS['generatedcontent']['headline'] = 'Adresse og kontakt info';
	$GLOBALS['generatedcontent']['text'] = '<script type="text/javascript" src="/faktura/javascript/javascript.js"></script><script type="text/javascript" src="/javascript/zipcodedk.js"></script>';
	
	$update = '';
	$redirect = false;
	$_POST = trim_array($_POST);
	
	if(isset($_POST['navn']))
		$update[] = '`navn` = \''.$_POST['navn'].'\'';
	if(isset($_POST['att']))
		$update[] = '`att` = \''.$_POST['att'].'\'';
	if(isset($_POST['adresse']))
		$update[] = '`adresse` = \''.$_POST['adresse'].'\'';
	if(isset($_POST['postnr']))
		$update[] = '`postnr` = \''.$_POST['postnr'].'\'';
	if(isset($_POST['by']))
		$update[] = '`by` = \''.$_POST['by'].'\'';
	if(isset($_POST['land']))
		$update[] = '`land` = \''.$_POST['land'].'\'';
	if(isset($_POST['tlf1']))
		$update[] = '`tlf1` = \''.$_POST['tlf1'].'\'';
	if(isset($_POST['tlf2']))
		$update[] = '`tlf2` = \''.$_POST['tlf2'].'\'';
	if(isset($_POST['email']))
		$update[] = '`email` = \''.$_POST['email'].'\'';
	
	if($update) {
		$redirect = true;
		$update = implode(', ', $update);
		$mysqli->query('UPDATE `fakturas` SET '.$update.' WHERE `id` = '.$_GET['id'].' LIMIT 1 ;');
	}
	
	if(!$fakturas = $mysqli->fetch_array('SELECT * FROM `fakturas` WHERE id = '.$_GET['id'].' AND (status = \'new\' OR status = \'pbserror\' OR status = \'locked\') LIMIT 1'))
		die('Ordren er muligvis allerede betalt.');
	
	
	$fakturas = trim_array($fakturas);
	
	if(!$fakturas[0]['land'])
		$fakturas[0]['land'] = 'Danmark';
	
	function valide_mail_host($host) {
		return getmxrr($host, $dummy);
	}
	
	$GLOBALS['generatedcontent']['text'] .= '<strong>Trin 2 af 3 - Faktureringsoplysninger</strong><form action="" method="post" onsubmit="return validate()"><table><tbody><tr><td>Navn:</td><td colspan="2"><input name="navn" id="navn" style="width:157px" value="'.$fakturas[0]['navn'].'" /></td>';
				if($_POST && !$fakturas[0]['navn']) {
					$GLOBALS['generatedcontent']['text'] .= '<td class="requred">Feltet &quot;Navn:&quot; skal udfyldes!</td>';
					$redirect = false;
				}
	$GLOBALS['generatedcontent']['text'] .= '</tr><tr><td> Att:</td><td colspan="2"><input name="att" id="att" style="width:157px" value="'.$fakturas[0]['att'].'" /></td></tr><tr><td> Adresse:</td><td colspan="2"><input name="adresse" id="adresse" style="width:157px" value="'.$fakturas[0]['adresse'].'" /></td>';
	
	if($_POST && !$fakturas[0]['adresse']) {
		$GLOBALS['generatedcontent']['text'] .= '<td class="requred">&quot;Adresse:&quot; skal indeholde både gadenavn og husnummer!</td>';
		$redirect = false;
	}
	
	$GLOBALS['generatedcontent']['text'] .= '</tr><tr><td> Postnr:</td><td><input name="postnr" id="postnr" style="width:35px" onchange="chnageZipCode(this.value);" onkeyup="chnageZipCode(this.value);" onblur="chnageZipCode(this.value);" value="'.$fakturas[0]['postnr'].'" /></td><td align="right">By: <input name="by" id="by" style="width:90px" value="'.$fakturas[0]['by'].'" /></td><td class="requred">';
	
	if($_POST && !$fakturas[0]['postnr']) {
		$GLOBALS['generatedcontent']['text'] .= 'Feltet &quot;Postnr:&quot; skal udfyldes med et gyldigt postnr!';
		$redirect = false;
		$nopostnr = true;
	}
	if($_POST && !$fakturas[0]['by']) {
		if($nopostnr)
			$GLOBALS['generatedcontent']['text'] .= '<br />';
		$GLOBALS['generatedcontent']['text'] .= 'Feltet &quot;By:&quot; skal udfyldes med et gyldigt by navn!';
		$redirect = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '</td></tr><tr><td> Land:</td>';
	
	$othercontry = true;
	
	$GLOBALS['generatedcontent']['text'] .= '<td colspan="2" style="white-space:nowrap"><select style="width:100%" onchange="selectedCountry(this.options[this.selectedIndex].value);" onkeyup="selectedCountry(this.options[this.selectedIndex].value);"><option value="Danmark"';
	
	if($fakturas[0]['land'] == 'Danmark') { $GLOBALS['generatedcontent']['text'] .= ' selected="selected"'; $othercontry = false; }
	
	$GLOBALS['generatedcontent']['text'] .= '>Danmark</option><option value="England"';
	if($fakturas[0]['land'] == 'England') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>England</option><option value="Færøerne"';
	if($fakturas[0]['land'] == 'Færøerne') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>Færøerne</option><option value="Grønland"';
	if($fakturas[0]['land'] == 'Grønland') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>Grønland</option><option value="Holland"';
	if($fakturas[0]['land'] == 'Holland') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>Holland</option><option value="Island"';
	if($fakturas[0]['land'] == 'Island') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>Island</option><option value="Norge"';
	if($fakturas[0]['land'] == 'Norge') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>Norge</option><option value="Portugal"';
	if($fakturas[0]['land'] == 'Portugal') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>Portugal</option><option value="Sverige"';
	if($fakturas[0]['land'] == 'Sverige') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>Sverige</option><option value="Tyskland"';
	if($fakturas[0]['land'] == 'Tyskland') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>Tyskland</option><option value="USA"';
	if($fakturas[0]['land'] == 'USA') {
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
		$othercontry = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '>USA</option><option value=""';
	if($othercontry)
		$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
	$GLOBALS['generatedcontent']['text'] .= '>Andet</option></select><span id="hiddencountry"';
	if(!$othercontry)
		$GLOBALS['generatedcontent']['text'] .= ' style="display:none"';
	
	$GLOBALS['generatedcontent']['text'] .= '><br /><input name="land" id="land" value="'.$fakturas[0]['land'].'" style="width:157px" /></span></td>';
	
	if($_POST && !$fakturas[0]['land']) {
		$GLOBALS['generatedcontent']['text'] .= '<td class="requred">Feltet &quot;Land:&quot; skal udfyldes med et gyldigt lande navn!</td>';
		$redirect = false;
	}
	
	$GLOBALS['generatedcontent']['text'] .= '</tr><tr><td> Telfon:</td><td colspan="2"><input name="tlf1" id="tlf1" style="width:157px" value="'.$fakturas[0]['tlf1'].'" /></td>';
	
	if($_POST && !$fakturas[0]['email'] && !$fakturas[0]['tlf1'] && !$fakturas[0]['tlf2']) {
		$GLOBALS['generatedcontent']['text'] .= '<td class="requred">Du skal indtaste en gyltig email eller et telefon nummer!</td>';
		$redirect = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '</tr><tr><td> Mobil:</td><td colspan="2"><input name="tlf2" id="tlf2" style="width:157px" value="'.$fakturas[0]['tlf2'].'" /></td>';
	
	if($_POST && !$fakturas[0]['email'] && !$fakturas[0]['tlf1'] && !$fakturas[0]['tlf2']) {
		$GLOBALS['generatedcontent']['text'] .= '<td class="requred">Du skal indtaste en gyltig email eller et telefon nummer!</td>';
		$redirect = false;
	}
	$GLOBALS['generatedcontent']['text'] .= '</tr>
	<tr>
	<td> Email:</td>
	<td colspan="2"><input name="email" id="email" style="width:157px" value="'.$fakturas[0]['email'].'" /></td><td class="requred">';
	
	if($_POST && $fakturas[0]['email'] && (!preg_match('/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i', $fakturas[0]['email']) || !valide_mail_host(preg_replace('/.+?@(.?)/u', '$1', $fakturas[0]['email'])))) {
		$GLOBALS['generatedcontent']['text'] .= 'Du skal indtaste en gyltig email!';
		$redirect = false;
	}
	if($_POST && !$fakturas[0]['email'] && !$fakturas[0]['tlf1'] && !$fakturas[0]['tlf2']) {
		$GLOBALS['generatedcontent']['text'] .= 'Du skal indtaste en gyltig email eller et telefon nummer!';
		$redirect = false;
	}
	
	if($redirect) {
		header('Location: https://pay.scannet.dk/3010000287/order.htm?id='.$_GET['id'].'&checkid='.$_GET['checkid'], TRUE, 303);
		die();
	}
	//TODO support eDankort
	$GLOBALS['generatedcontent']['text'] .= '</td></tr></tbody></table><input style="font-weight:bold;" type="submit" value="Fortsæt til betalingen" /></form>';
	$GLOBALS['generatedcontent']['crumbs'] = NULL;
	$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => 'Faktura', 'link' => '/faktura/');
	$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Fakturerings oplysninger', 'link' => '?step=2&amp;id='.$_GET['id'].'&amp;checkid='.$_GET['checkid']);
}
?>