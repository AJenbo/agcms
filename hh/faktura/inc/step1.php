<?php
$GLOBALS['generatedcontent']['crumbs'] = NULL;
$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => 'Faktura', 'link' => '/faktura/');
$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Købs liste', 'link' => '/faktura/?id='.$_GET['id'].'&amp;checkid='.$_GET['checkid']);

if(!$fakturas = $mysqli->fetch_array('SELECT * FROM `fakturas` WHERE id = '.$_GET['id'].' AND (status = \'new\' OR status = \'pbserror\' OR status = \'locked\') LIMIT 1')) {

	$GLOBALS['generatedcontent']['headline'] = 'Der er opstod følgende fejl';
	$GLOBALS['generatedcontent']['text'] = 'Ordren er muligvis allerede betalt.';
	
} else {

	$GLOBALS['generatedcontent']['headline'] = 'Elektronisk faktura';
	$GLOBALS['generatedcontent']['text'] = '';
	
	//Display faktura start
	$momssats = 1+$fakturas[0]['momssats'];
	
	$quantities = explode('<', $fakturas[0]['quantities']);
	$products = explode('<', $fakturas[0]['products']);
	$values = explode('<', $fakturas[0]['values']);
	
	function addMoms($value) {
		global $momssats;
		
		return $value*$momssats;
	}
	
	if(!$fakturas[0]['premoms'])
		$values = array_map('addMoms' ,$values);
	
	$GLOBALS['generatedcontent']['text'] .= '<table id="faktura" cellspacing="0" style="width:80%; margin:20px auto"><thead><tr><td>Beskrivels</td><td>Stk</td><td align="center">á</td><td align="right">I alt</td></tr></thead>';
	
	$temp = '';
	for($i=0;$i<count($quantities);$i++) {
		$temp .= '<tr><td>'.$products[$i].'</td><td style="text-align:right">'.$quantities[$i].'</td><td style="text-align:right">'.number_format($values[$i], 2, ',', '.').'</td><td style="text-align:right">'.number_format($values[$i]*$quantities[$i], 2, ',', '.').'</td></tr>';
		$total += $values[$i]*$quantities[$i];
	}
	
	$GLOBALS['generatedcontent']['text'] .= '<tfoot>';
	if($fakturas[0]['fragt'] > 0) {
		$GLOBALS['generatedcontent']['text'] .= '<tr><td>Fragt:</td><td></td><td></td><td style="text-align:right">'.number_format($fakturas[0]['fragt'], 2, ',', '.').'</td></tr>';
	}
	$GLOBALS['generatedcontent']['text'] .= '<tr style="font-weight:bold"><td>Betalingsbeløb inkl. moms:</td><td></td><td></td><td style="text-align:right">DKK '.number_format($total+$fakturas[0]['fragt'], 2, ',', '.').'</td></tr>';
	$GLOBALS['generatedcontent']['text'] .= '<tr><td>Heraf moms:</td><td></td><td></td><td style="text-align:right">DKK '.number_format($total-($total/$momssats), 2, ',', '.').'</td></tr>
	</tfoot><tbody>'.$temp.'</tbody></table>';
	//Display faktura end
	
	$GLOBALS['generatedcontent']['text'] .= '<p>';
	$GLOBALS['generatedcontent']['text'] .= $fakturas[0]['note'];
	$GLOBALS['generatedcontent']['text'] .= '</p>';
	
	$GLOBALS['generatedcontent']['text'] .= '<h1>Handels betingelser</h1>';
	
	$special = $mysqli->fetch_array("SELECT text FROM special WHERE id = 3 LIMIT 1");
	
	$GLOBALS['generatedcontent']['text'] .= $special[0]['text'];
		
	$GLOBALS['generatedcontent']['text'] .= '<form style="text-align:center" action="" method="get"><input name="step" type="hidden" value="2" /><input name="id" type="hidden" value="'.$_GET['id'].'" /><input name="checkid" type="hidden" value="'.$_GET['checkid'].'" /><input type="submit" value="Jeg accepterer hermed handelsbetingelserne" /></form>';
}
?>