<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Digital faktura - Status</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
* {
	font-family:Arial, Helvetica, sans-serif;
}

a {
	text-decoration:none;
	color:#000000;
}

table {
	font-size:14px;
	border-collapse:collapse;
}

thead td, tfoot td {
	font-weight:bold;
}

td {
	border:1px #000000 solid;
	border-collapse:collapse;
}
tr:nth-child(1), tr:nth-child(5), tr:nth-child(8), tr:nth-child(9) {
text-align:right;
}

</style>
</head>
<body>
<?php


function getCheckid($id) {
	return mb_substr(md5($id.'salt24raej098'), 3, 5);
}


require_once("../../inc/mysqli.php");
require_once("../../inc/config.php");
//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

$new = $mysqli->fetch_array('SELECT *, UNIX_TIMESTAMP(date) as time FROM `fakturas` WHERE `status` IN(\'new\', \'locked\') ORDER BY -id');
$error = $mysqli->fetch_array('SELECT *, UNIX_TIMESTAMP(date) as time FROM `fakturas` WHERE `status` = \'pbserror\' ORDER BY -id');

?> <h2>Ubetalte  fakturaer</h2>
<table>
	<thead>
		<tr>
			<td>Id</td>
			<td>Oprettet</td>
			<td>- af</td>
			<td>- i</td>
			<td>Beløb</td>
			<td>Modtager</td>
			<td>Email</td>
			<td>Tlf1</td>
			<td>Tlf2</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td>Id</td>
			<td>Oprettet</td>
			<td>- af</td>
			<td>- i</td>
			<td>Beløb</td>
			<td>Modtager</td>
			<td>Email</td>
			<td>Tlf1</td>
			<td>Tlf2</td>
		</tr>
	</tfoot>
	<tbody>
		<?php
	foreach($new as $value) {
		?>
		<tr>
			<td style="text-align:right"><a href="/admin/fakturas.php?id=<?php echo($value['id']); ?>"><?php echo($value['id']); ?></a></td>
			<td style="text-align:right"><?php echo(date('j/n', $value['time'])); ?></td>
			<td><?php echo($value['clerk']); ?></td>
			<td><?php echo($value['department']); ?></td>
			<td style="text-align:right"><?php
			
            $value['quantities'] = explode('<', $value['quantities']);
            $value['values'] = explode('<', $value['values']);
			$totalsum = 0;
			
			foreach($value['quantities'] as $key => $temp) {
				$totalsum += $value['values'][$key]*$value['quantities'][$key];
			}
			$totalsum += $value['fragt'];
			
			echo(number_format($totalsum, 2, ',', '.')); ?></td>
			<td><?php echo($value['navn']); ?></td>
			<td><?php if($value['email']) { ?><a href="mailto:<?php echo($value['email']); ?>?subject=Forespørgsel vedr. elektronisk faktura&body=Vedr. ordre.%0A%0AVedlagt følger linket til den elektroniske faktura. Varerrne står reserveret og klar til forsendelse.%0A%0AVi mangler blot at modtage bekræftelsen på den elektroniske faktura%3A%0Ahttp%3A%2F%2Fhuntershouse.dk%2Ffaktura%2F%3Fid%3D<?php echo($value['id']); ?>%26checkid%3D<?php echo(getCheckid($value['id'])); ?>%0A%0AGiv os venligst en mail%2C hvis ordren er fortrudt eller der ønskes at betalingen foregår på anden vis.%0A%0AMed venlig hilsen%2C Hunters House."><?php echo($value['email']); ?></a><?php } ?></td>
			<td style="text-align:right"><?php echo($value['tlf1']); ?></td>
			<td style="text-align:right"><?php echo($value['tlf2']); ?></td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>
<br />
<h2>Fejl opstået under betalingen</h2>
<table>
	<thead>
		<tr>
			<td>Id</td>
			<td>Oprettet</td>
			<td>- af</td>
			<td>- i</td>
			<td>Beløb</td>
			<td>Modtager</td>
			<td>Email</td>
			<td>Tlf1</td>
			<td>Tlf2</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td>Id</td>
			<td>Oprettet</td>
			<td>- af</td>
			<td>- i</td>
			<td>Beløb</td>
			<td>Modtager</td>
			<td>Email</td>
			<td>Tlf1</td>
			<td>Tlf2</td>
		</tr>
	</tfoot>
	<tbody>
		<?php
	foreach($error as $value) {
		?>
		<tr>
			<td style="text-align:right"><a href="/admin/fakturas.php?id=<?php echo($value['id']); ?>"><?php echo($value['id']); ?></a></td>
			<td style="text-align:right"><?php echo(date('j/n G:i', $value['time'])); ?></td>
			<td><?php echo($value['clerk']); ?></td>
			<td><?php echo($value['department']); ?></td>
			<td style="text-align:right"><?php
			
            $value['quantities'] = explode('<', $value['quantities']);
            $value['values'] = explode('<', $value['values']);
			$totalsum = 0;
			
			foreach($value['quantities'] as $key => $temp) {
				$totalsum += $value['values'][$key]*$value['quantities'][$key];
			}
			
			echo(number_format($totalsum, 2, ',', '.')); ?></td>
			<td><?php echo($value['navn']); ?></td>
			<td><?php if($value['email']) { ?><a href="mailto:<?php echo($value['email']); ?>?subject=Forespørgsel vedr. elektronisk faktura&body=Vedr. ordre.%0A%0AVedlagt følger linket til den elektroniske faktura. Varerrne står reserveret og klar til forsendelse.%0A%0AVi mangler blot at modtage bekræftelsen på den elektroniske faktura%3A%0Ahttp%3A%2F%2Fhuntershouse.dk%2Ffaktura%2F%3Fid%3D<?php echo($value['id']); ?>%26checkid%3D<?php echo(getCheckid($value['id'])); ?>%0A%0AGiv os venligst en mail%2C hvis ordren er fortrudt eller der ønskes at betalingen foregår på anden vis.%0A%0AMed venlig hilsen%2C Hunters House."><?php echo($value['email']); ?></a><?php } ?></td>
			<td style="text-align:right"><?php echo($value['tlf1']); ?></td>
			<td style="text-align:right"><?php echo($value['tlf2']); ?></td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table><br />

<?php

function array_natsort($aryData, $strIndex, $strSortBy, $strSortType=false) {
		
	//if the parameters are invalid
	if(!is_array($aryData) || !$strIndex || !$strSortBy)
		return $aryData;
	
	//ignore
	$match = array('.', ',-');
	$replace = array('', '');
	
	//    create our temporary arrays
	$arySort = $aryResult = array();
	
	//    loop through the array
	foreach ($aryData as $aryRow)
	//    set up the value in the array
		$arySort[$aryRow[$strIndex]] = str_replace($match,$replace,$aryRow[$strSortBy]);
	
	//    apply the natural sort
	natcasesort($arySort);
	
	//    if the sort type is descending
	if ($strSortType == 'desc' || $strSortType == '-' )
	//    reverse the array
		arsort($arySort);
	
	//    loop through the sorted and original data
	foreach ($arySort as $arySortKey => $arySorted)
		foreach ($aryData as $aryOriginal)
		//    if the key matches
			if ($aryOriginal[$strIndex]==$arySortKey) {
				//    add it to the output array
				array_push($aryResult, $aryOriginal);
				break;
			}
	
	//    return the result
	return $aryResult;
}
$clerks = $mysqli->fetch_array('SELECT `clerk` FROM `fakturas` GROUP BY `clerk`');
foreach($clerks as $value) {
	$total = $mysqli->fetch_array('SELECT count(id) as count FROM `fakturas` WHERE `clerk` LIKE \''.$value['clerk'].'\'');
	$errors = $mysqli->fetch_array('SELECT count(id) as count FROM `fakturas` WHERE `status` IN (\'new\', \'pbserror\', \'locked\') AND `clerk` LIKE \''.$value['clerk'].'\'');
	$errorpct = round(100/$total[0]['count']*$errors[0]['count']);
	$usererrors[] = array('clerk' => $value['clerk'], 'pct' => $errorpct, 'total' => $total[0]['count'], 'errors' => $errors[0]['count']);
}
$usererrors = array_natsort($usererrors, 'clerk', 'pct');

foreach($usererrors as $value) {
		echo($value['pct'].'% ('.$value['errors'].'/'.$value['total'].') af <strong>'.$value['clerk'].'\'s</strong> fakturas er ikke blevet afslutted.<br />');
}



?>
</body>
</html>
