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
		require_once 'calcpakkepris'.$_POST['Y'].'.php';
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
					$post = $mysqli->fetch_one('SELECT *, UNIX_TIMESTAMP(`formDate`) as date FROM `post` WHERE deleted = 0 AND `STREGKODE` = \''.$matches[3][$key].'\' LIMIT 1');
					//TODO correct any mismatch betwean database and this
					if($post) {
						$array[$key]['day'] = $matches[1][$key];
						$array[$key]['month'] = $matches[2][$key];
						$array[$key]['barcode'] = $matches[3][$key];
						$array[$key]['weight'] = $matches[4][$key];
						$array[$key]['length'] = $matches[5][$key];
						$array[$key]['width'] = $matches[6][$key];
						if(!$array[$key]['weight'] && !$post['weight'])
							$array[$key]['weight'] = 5000;
						$array[$key]['height'] = $matches[7][$key];
						//If we agree that it is volume then force it

						if($post['ss2'] == 'true' && preg_match('/Vo/ui', $matches[8][$key])) {
							$array[$key]['volume'] = true;
						//if we don't aggree then make a note of it
						} elseif($post['ss2'] != 'true' && preg_match('/Vo/ui', $matches[8][$key])) {
							$array[$key]['volumeerr'] = true;
						}
						$array[$key]['ss1'] = preg_match('/Fo/u', $matches[8][$key]);
						$array[$key]['ss46'] = preg_match('/Lø/u', $matches[8][$key]);
						$array[$key]['ss5amount'] = 0;
						if(preg_match('/Va/u' ,$matches[8][$key])) 
							$array[$key]['ss5amount'] = $post['ss5amount'];
						$array[$key]['price'] = str_replace(',','.',$matches[9][$key]);
					} else {
						$GLOBALS['unknownpackages'][$matches[3][$key]] = str_replace(',','.',$matches[9][$key]);
						if(!($type == 'P' && $matches[4][$key]/1000 < 20))
							$GLOBALS['unknownpackages'][$matches[3][$key]] *= 1.25;
					}
				}
			}
			unset($matches);
			unset($key);
			unset($line);
			$diff = 0;
			foreach($array as $pacakage) {
				$fragt = pakkepris($pacakage['height']/10, $pacakage['width']/10, $pacakage['length']/10, $pacakage['weight']/1000, $type, $pacakage['ss1'] ? 'true' : 'false', $pacakage['ss46'] ? 'true' : 'false', $pacakage['ss5amount'], false, $pacakage['volume']);
				if(round($pacakage['price'] - $fragt, 2) > 0 || $pacakage['sserror']) {
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
					.'</td><td>');
if($pacakage['ss1']) echo('Fo');
					echo('</td><td>');
if($pacakage['ss46']) echo('Lø');
					echo('</td><td>');
if($pacakage['volume']) echo('Vo');
if($pacakage['volumeerr']) echo('<span style="color:red;">!Vo</span>');
					echo('</td><td>');
if($pacakage['ss5amount']) echo(number_format($pacakage['ss5amount'], 2, ',', '.'));
					echo('</td><td>');
if(!($type == 'P' && $pacakage['weight']/1000 < 20)) echo('Ja');
					echo('</td><td>'
					.number_format($pacakage['price'], 2, ',', '.')
					.'</td><td>'
					.number_format($fragt, 2, ',', '.')
					.'</td></tr>');
					if(!($type == 'P' && $pacakage['weight']/1000 < 20))
						$diff += $pacakage['price']*1.25-$fragt*1.25;
					else
						$diff += $pacakage['price']-$fragt;
				}
			}
			if($diff)
				echo('<tr><td colspan="10">Diff (inc. moms) : '.number_format($diff, 2, ',', '.').'</td></tr>');
		}
		
		echo('<h1>Pakker hvor prisen ikke stemmer!</h1>');
		?><table><tr><td>Stregkode</td><td>Height</td><td>Width</td><td>Length</td><td>Weight</td><td>Type</td><td>Forsigtig</td><td>Lørdag</td><td>Volume</td><td>Værdi</td><td>Moms pligtig</td><td>Post</td><td>Os</td></tr><?php
		parseSpec($_POST['E'], 'E');
		parseSpec($_POST['P'], 'P');
		parseSpec($_POST['O'], 'O');
		
		?></table><?php
		
		echo('<h1>Pakker vi ikke har sendt!</h1>');
		$total = 0;
		foreach($GLOBALS['unknownpackages'] as $barcode => $price) {
			echo($barcode.' '.number_format($price, 2, ',', '.').'<br />');
			$total += $price;
		}
		echo('Total: '.number_format($total, 2, ',', '.').'<br />');
	}
?><br /><form action="" method="post">
Faktura år: <input name="Y" maxlength="4" size="4" type="text" value="<?php echo(date('Y')); ?>" />
<br />
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
