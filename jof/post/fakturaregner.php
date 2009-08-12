<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Beregn faktura spec</title>
<style type="text/css">
table td {
	text-align:right;
}
</style>
</head>

<body><?php
$GLOBALS['unknownpackages'] = array();
if(isset($_POST['E']) || isset($_POST['P']) || isset($_POST['O'])) {
		require_once 'calcpakkepris2009.php';
		require_once '../inc/config.php';
		require_once '../inc/mysqli.php';
		
		//Open database
		$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

		function parseSpec($text, $type) {
			global $mysqli;
			preg_match_all('/([0-9]{2})([0-9]{2})\s[0-9]+\s\/\s[0-9]+\s([PO0-9DK]{13})\s([0-9]+)\s[0-9]+\s[0-9]+\s([0-9]+)\s([0-9]+)\s([0-9]+)\s([A-Zæøå\s]*)([0-9,]+)/ui', $text, $matches);
			unset($text);
			$array = array();
			if(!empty($matches[0])) {
				foreach($matches[0] as $key => $line) {
					$post = $mysqli->fetch_array('SELECT *, UNIX_TIMESTAMP(`formDate`) as date FROM `post` WHERE deleted = 0 AND `STREGKODE` = \''.$matches[3][$key].'\' LIMIT 1');
					//TODO correct any mismatch betwean database and this
					if($post) {
						$array[$key]['day'] = $matches[1][$key];
						$array[$key]['month'] = $matches[2][$key];
						$array[$key]['barcode'] = $matches[3][$key];
						$array[$key]['weight'] = $matches[4][$key];
						$array[$key]['length'] = $matches[5][$key];
						$array[$key]['width'] = $matches[6][$key];
						$array[$key]['height'] = $matches[7][$key];
						if($post[0]['ss2'] = 'true' && preg_match('/vo/ui', $matches[8][$key]) && !calcvolume($array[$key]['length'], $array[$key]['width'], $array[$key]['height'])) {
							$array[$key]['length'] = 1500;
							$array[$key]['width'] = 500;
							$array[$key]['height'] = 500;
						}
						
						if($array[$key]['ss1'] = preg_match('/Fo/u', $matches[8][$key]) && $post[0]['ss1'] == 'false')
							$array[$key]['sserror'] = true;
						if($array[$key]['ss46'] = preg_match('/Lø/u', $matches[8][$key]) && $post[0]['ss46'] == 'false')
							$array[$key]['sserror'] = true;
						if(preg_match('/Va/u' ,$matches[8][$key])) 
							$array[$key]['ss5amount'] = $post[0]['ss5amount'];
						$array[$key]['price'] = str_replace(',','.',$matches[9][$key]);
					} else {
						$GLOBALS['unknownpackages'][] = $matches[3][$key];
					}
				}
			}
			unset($matches);
			unset($key);
			unset($line);
			foreach($array as $pacakage) {
				$fragt = pakkepris($pacakage['height']/10, $pacakage['width']/10, $pacakage['length']/10, $pacakage['weight']/1000, $type, $pacakage['ss1'] ? 'true' : 'false', $pacakage['ss46'] ? 'true' : 'false', $pacakage['ss5amount'], false);
				if(round($fragt, 2) != round($pacakage['price'], 2) || $pacakage['sserror'])
					echo('<tr><td><b>'
					.$pacakage['barcode']
					.'</b></td><td>'
					.($pacakage['height']/10)
					.'</td><td>'
					.($pacakage['width']/10)
					.'</td><td>'
					.($pacakage['length']/10)
					.'</td><td>'
					.($pacakage['weight']/1000)
					.'</td><td>'
					.$type
					.'</td><td>'
					.$pacakage['ss1']
					.'</td><td>'
					.$pacakage['ss46']
					.'</td><td>'
					.$pacakage['ss5amount']
					.'</td><td>'
					.round($pacakage['price'] - $fragt, 2)
					.'</td></tr>');
			$totalPorto += $fragt;
			}
			echo('<tr><td colspan="10">Total : '.$totalPorto.'</td></tr>');
		}
		
		echo('<h1>Pakker hvor prisen ikke stemmer!</h1>');
		?><table><tr><td>Stregkode</td><td>Height</td><td>Width</td><td>Length</td><td>Weight</td><td>Type</td><td>Forsigtig</td><td>Lørdag</td><td>Værdi</td><td>Diff</td></tr><?php
		parseSpec($_POST['E'], 'E');
		parseSpec($_POST['P'], 'P');
		parseSpec($_POST['O'], 'O');
		
		?></table><?php
		
		echo('<h1>Pakker vi ikke har sendt!</h1>');
		foreach($GLOBALS['unknownpackages'] as $unknownpackage) {
			echo($unknownpackage.'<br />');
		}
	}
?><br /><form action="" method="post">
Erhverves pakker:<br />
<textarea name="E" cols="70" rows="20"></textarea>
<br />
<br />
Privat pakker:<br />
<textarea name="P" cols="70" rows="20"></textarea>
<br />
<br />
Postopkrævnings pakker:<br />
<textarea name="O" cols="70" rows="20"></textarea>
<br />
<br />
<input type="submit" value="Beregn" />
</form>
</body>
</html>
