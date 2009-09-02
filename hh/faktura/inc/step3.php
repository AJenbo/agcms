<?php
$GLOBALS['generatedcontent']['crumbs'] = NULL;
$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => 'Faktura', 'link' => '/faktura/');
$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Dankort oplysninger', 'link' => '?id='.$_GET['id'].'&amp;checkid='.$_GET['checkid']);

if(!$fakturas = $mysqli->fetch_array('SELECT * FROM `fakturas` WHERE id = '.$_GET['id'].' AND (status = \'new\' OR status = \'pbserror\' OR status = \'locked\') LIMIT 1')) {

	$GLOBALS['generatedcontent']['headline'] = 'Der er opstod følgende fejl';
	$GLOBALS['generatedcontent']['text'] = 'Ordren er muligvis allerede betalt.';
	
} else {

	$GLOBALS['generatedcontent']['headline'] = 'Dankort oplysninger';
	$GLOBALS['generatedcontent']['text'] = '<script type="text/javascript"><!--
	function $(id) {
		return document.getElementById(id);
	}
	function checkmodulo16(s) {
		var sum = 0;
		for(var i=0; i<16; i++) {
			numval = s.charAt(i) - \'0\';
			if (i % 2 == 0) {
				numval *= 2;
				if (numval >= 10) {
					sum += 1 + numval % 10;
				} else {
					sum += numval;
				}
			} else {
				sum += numval;
			}
		}
		return sum % 10 == 0;
	}
	
	function validateKortnr(s) {
		s = s.replace(/[^0-9]+/g, \'\');
		//is (Visa Dankort || is Dankort) || is valid
		if(s.length >= 4) {
			if(s.substring(0,4) == 4571 || s.substring(0,4) == 5019) {
				if(s.length == 16) {
					if(checkmodulo16(s)) {
						$(\'kortnrvalid\').style.display = \'none\';
						$(\'kortnrenter\').style.display = \'none\';
					} else {
						$(\'kortnrvalid\').style.display = \'\';
						$(\'kortnrenter\').style.display = \'none\';
					}
				} else if(s.length > 16) {
						$(\'kortnrvalid\').style.display = \'\';
						$(\'kortnrenter\').style.display = \'none\';
				} else {
					$(\'kortnrvalid\').style.display = \'none\';
					$(\'kortnrenter\').style.display = \'\';
				}
			} else {
				$(\'kortnrvalid\').style.display = \'\';
				$(\'kortnrenter\').style.display = \'\';
			}
		} else {
			$(\'kortnrvalid\').style.display = \'none\';
			$(\'kortnrenter\').style.display = \'\';
		}
		
		s = s.replace(/[^0-9]+/g, \'\');
		if(s.length >= 1 && (s.substring(0,1) != 4 && s.substring(0,1) != 5)) {
			$(\'kortnrvalid\').style.display = \'\';
			$(\'kortnrenter\').style.display = \'\';
		}
		if(s.length >= 2 && (s.substring(1,2) != 5 && s.substring(1,2) != 0)) {
			$(\'kortnrvalid\').style.display = \'\';
			$(\'kortnrenter\').style.display = \'\';
		}
		if(s.length >= 3 && (s.substring(2,3) != 7 && s.substring(2,3) != 1)) {
			$(\'kortnrvalid\').style.display = \'\';
			$(\'kortnrenter\').style.display = \'\';
		}
		if(s.length >= 4 && (s.substring(3,4) != 1 && s.substring(3,4) != 9)) {
			$(\'kortnrvalid\').style.display = \'\';
			$(\'kortnrenter\').style.display = \'\';
		}
	}
	
	function validateKontrol(s) {
		if(s.replace(/[^0-9]+/g, \'\').length == 3)
			$(\'kontrolenter\').style.display = \'none\';
		else
			$(\'kontrolenter\').style.display = \'\';
	}
	
	function validate() {
		s = $(\'kortnr\').value.replace(/[^0-9]+/g, \'\');
		if(s.length != 16 || !(s.substring(0,4) == 4571 || s.substring(0,4) == 5019) || !checkmodulo16(s)) {
			alert(\'Det indtastede kortnummer er ikke korrekt.\');
			$(\'kortnr\').focus();
			return false;
		}
		if($(\'kontrol\').value.replace(/[^0-9]+/g, \'\').length != 3) {
			alert(\'Du skal indtaste et kontroldnummer.\');
			$(\'kontrol\').focus();
			return false;
		}
		$(\'kortnr\').value = $(\'kortnr\').value.replace(/[^0-9]+/g, \'\');
		$(\'kontrol\').value = $(\'kontrol\').value.replace(/[^0-9]+/g, \'\');
		return true;
	}
	
	--></script>';
	
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
	
	
	$GLOBALS['generatedcontent']['text'] .= '<p>'.$fakturas[0]['note'];
	$GLOBALS['generatedcontent']['text'] .= '</p><form style="width:175px; margin:0 auto;" action="https://pay.scannet.dk/cgi-bin/auth2.pl" method="post" autocomplete="off" onsubmit="return validate()"><input type="hidden" name="butiksnummer" value="3010000287" /><input type="hidden" name="ordrenr" value="'.$_GET['id'].'" /><input type="hidden" name="valuta" value="208" /><input type="hidden" name="dkvalues" value="1" />';
	
	for($i=0;$i<$productslines;$i++) {
		if($values[$i] > 0) {
			$GLOBALS['generatedcontent']['text'] .= '<input type="hidden" name="vare_'.($i+1).'_antal" value="'.$quantities[$i].'" /><input type="hidden" name="vare_'.($i+1).'_navn" value="'.$products[$i].'" /><input type="hidden" name="vare_'.($i+1).'_pris" value="'.number_format($values[$i]*$momssats, 2, ',', '.').'" />';
			$GLOBALS['generatedcontent']['text'] .= '<input type="hidden" name="vare_'.($i+1).'_exmoms" value="'.number_format($values[$i], 2, ',', '.').'" />';
		}
	}
	
	if($fakturas[0]['fragt'] > 0) {
		$GLOBALS['generatedcontent']['text'] .= '<input type="hidden" name="vare_'.($i+1).'_antal" value="1" />
		<input type="hidden" name="vare_'.($i+1).'_navn" value="Fragt" />
		<input type="hidden" name="vare_'.($i+1).'_pris" value="'.number_format($fakturas[0]['fragt'], 2, ',', '.').'" />
		<input type="hidden" name="vare_'.($i+1).'_exmoms" value="'.number_format($fakturas[0]['fragt'], 2, ',', '.').'" />';
	}
	
	$GLOBALS['generatedcontent']['text'] .= '<input type="hidden" name="navn" value="'.$fakturas[0]['navn'].'" />
	  <input type="hidden" name="adresse" value="'.$fakturas[0]['adresse'].'" />
	  <input type="hidden" name="postnr" value="'.$fakturas[0]['postnr'].'" />
	  <input type="hidden" name="by" value="'.$fakturas[0]['by'].'" />
	  <input type="hidden" name="email" value="'.$fakturas[0]['email'].'" />
	
	  <input type="hidden" name="tekst1" value="'.$_GET['checkid'].'" />
	  <textarea name="tekst2" cols="40" rows="6" style="display:none">Att: '.$fakturas[0]['att'].'
	
	Tlf1: '.$fakturas[0]['tlf1'].'
	
	Tlf2: '.$fakturas[0]['tlf2'].'
	
	E-mail: '.$fakturas[0]['email'].'
	
	
	Afdeling: '.$fakturas[0]['department'].'
	
	Ekspedient: '.$fakturas[0]['clerk'];
	
	$GLOBALS['generatedcontent']['text'] .= '</textarea><br />Kortnummer<br /><input name="kortnr" value="" size="16" id="kortnr" onkeyup="validateKortnr(this.value)" /> <span id="kortnrenter" style="vertical-align:top;">*</span><br /><span class="requred" id="kortnrvalid" style="display:none;">Det indtastede kortnummer<br />er ikke korrekt!</span><br />Udløbsdato<br /><select name="udloebsmaaned"><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option></select><select name="udloebsaar">';
	
	$y = date('y', time());
	for($i=0;$i<11;$i++) {
		$GLOBALS['generatedcontent']['text'] .= '<option value="'.date('y', mktime(0, 0, 0, 1, 1, $y+$i)).'">'.date('y', mktime(0, 0, 0, 1, 1, $y+$i)).'</option>';
	}
	$GLOBALS['generatedcontent']['text'] .= '</select><br /><br /><span class="stivalgt">Kontrolcifre</span><br /><input name="kontrol" id="kontrol" value="" size="3" onkeyup="validateKontrol(this.value)" /><span id="kontrolenter" style="">*</span><br /><br /><input type="submit" value="Godkend betaling" /><br /><br /><img src="https://pay.scannet.dk/images/dan-xs.gif" width="32" height="20" alt="Dankort" title="" /> <img src="https://pay.scannet.dk/images/visa-xs.gif" width="32" height="20" alt="Visa" title="" /></form>';
}	
?>
