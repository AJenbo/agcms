<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body><div style="clear:both"><?php
require_once("../inc/mysqli.php");
require_once("../inc/config.php");


require_once("calcpakkepris".$_GET['y'].".php");
//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	
$post = $mysqli->fetch_array('SELECT * 
FROM `post` 
WHERE deleted = 0 AND `formDate` >= \''.$_GET['y'].'-'.$_GET['m'].'-01\'
AND `formDate` <= \''.$_GET['y'].'-'.$_GET['m'].'-31\'
ORDER BY `post`.`id` ASC');
	echo('<table>');
	
	
	for($i=0;$i<count($post);$i++) {
		$fragt = pakkepris($post[$i]['pd_height'], $post[$i]['pd_width'], $post[$i]['pd_length'], $post[$i]['pd_weight'], $post[$i]['optRecipType'], $post[$i]['ss1'], $post[$i]['ss46'], $post[$i]['ss5amount'], true);
		$Tporto += $post[$i]['porto'];
		$Tfragt += $fragt;
		if($post[$i]['ub'] == 'true')
			$fragtub += $fragt;
		if(calcvolume($post[$i]['pd_height'], $post[$i]['pd_width'], $post[$i]['pd_length']) && $post[$i]['optRecipType'] == 'P') {
			$GLOBALS['volume']++;
		}
	}
	
	
	
	?></div><table border="0"><?php
	if($GLOBALS['e1'])
		echo '<tr><td>Erhvervspakke 0-1 Kg</td><td style="text-align:right;">'.$GLOBALS['e1'].'</td></tr>';
	if($GLOBALS['e5'])
		echo '<tr><td>Erhvervspakke 1-5 Kg</td><td style="text-align:right;">'.$GLOBALS['e5'].'</td></tr>';
	if($GLOBALS['e10'])
		echo '<tr><td>Erhvervspakke 5-10 Kg</td><td style="text-align:right;">'.$GLOBALS['e10'].'</td></tr>';
	if($GLOBALS['e15'])
		echo '<tr><td>Erhvervspakke 10-15 Kg</td><td style="text-align:right;">'.$GLOBALS['e15'].'</td></tr>';
	if($GLOBALS['e20'])
		echo '<tr><td>Erhvervspakke 15-20 Kg</td><td style="text-align:right;">'.$GLOBALS['e20'].'</td></tr>';
	if($GLOBALS['e25'])
		echo '<tr><td>Erhvervspakke 20-25 Kg</td><td style="text-align:right;">'.$GLOBALS['e25'].'</td></tr>';
	if($GLOBALS['e30'])
		echo '<tr><td>Erhvervspakke 25-30 Kg</td><td style="text-align:right;">'.$GLOBALS['e30'].'</td></tr>';
	if($GLOBALS['e35'])
		echo '<tr><td>Erhvervspakke 30-35 Kg</td><td style="text-align:right;">'.$GLOBALS['e35'].'</td></tr>';
	if($GLOBALS['e40'])
		echo '<tr><td>Erhvervspakke 35-40 Kg</td><td style="text-align:right;">'.$GLOBALS['e40'].'</td></tr>';
	if($GLOBALS['e45'])
		echo '<tr><td>Erhvervspakke 40-45 Kg</td><td style="text-align:right;">'.$GLOBALS['e45'].'</td></tr>';
	if($GLOBALS['e50'])
		echo '<tr><td>Erhvervspakke 45-50 Kg</td><td style="text-align:right;">'.$GLOBALS['e50'].'</td></tr>';
	if($GLOBALS['e50b'])
		echo '<tr><td>Erhvervspakke over 50 Kg</td><td style="text-align:right;">'.$GLOBALS['e50b'].'</td></tr>';
		
	if($GLOBALS['p1'])
		echo '<tr><td>Privatpakke 0-1 Kg</td><td style="text-align:right;">'.$GLOBALS['p1'].'</td></tr>';
	if($GLOBALS['p5'])
		echo '<tr><td>Privatpakke 1-5 Kg</td><td style="text-align:right;">'.$GLOBALS['p5'].'</td></tr>';
	if($GLOBALS['p10'])
		echo '<tr><td>Privatpakke 5-10 Kg</td><td style="text-align:right;">'.$GLOBALS['p10'].'</td></tr>';
	if($GLOBALS['p15'])
		echo '<tr><td>Privatpakke 10-15 Kg</td><td style="text-align:right;">'.$GLOBALS['p15'].'</td></tr>';
	if($GLOBALS['p20'])
		echo '<tr><td>Privatpakke 15-20 Kg</td><td style="text-align:right;">'.$GLOBALS['p20'].'</td></tr>';
	if($GLOBALS['p25'])
		echo '<tr><td>Privatpakke 20-25 Kg</td><td style="text-align:right;">'.$GLOBALS['p25'].'</td></tr>';
	if($GLOBALS['p30'])
		echo '<tr><td>Privatpakke 25-30 Kg</td><td style="text-align:right;">'.$GLOBALS['p30'].'</td></tr>';
	if($GLOBALS['p35'])
		echo '<tr><td>Privatpakke 30-35 Kg</td><td style="text-align:right;">'.$GLOBALS['p35'].'</td></tr>';
	if($GLOBALS['p40'])
		echo '<tr><td>Privatpakke 35-40 Kg</td><td style="text-align:right;">'.$GLOBALS['p40'].'</td></tr>';
	if($GLOBALS['p45'])
		echo '<tr><td>Privatpakke 40-45 Kg</td><td style="text-align:right;">'.$GLOBALS['p40'].'</td></tr>';
	if($GLOBALS['p50'])
		echo '<tr><td>Postopkrævningspakke 45-50 Kg</td><td style="text-align:right;">'.$GLOBALS['o50'].'</td></tr>';
		
	if($GLOBALS['p1l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 0-1 Kg</td><td style="text-align:right;">'.$GLOBALS['p1l'].'</td></tr>';
	if($GLOBALS['p5l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 1-5 Kg</td><td style="text-align:right;">'.$GLOBALS['p5l'].'</td></tr>';
	if($GLOBALS['p10l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 5-10 Kg</td><td style="text-align:right;">'.$GLOBALS['p10l'].'</td></tr>';
	if($GLOBALS['p15l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 10-15 Kg</td><td style="text-align:right;">'.$GLOBALS['p15l'].'</td></tr>';
	if($GLOBALS['p20l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 15-20 Kg</td><td style="text-align:right;">'.$GLOBALS['p20l'].'</td></tr>';
	if($GLOBALS['p25l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 20-25 Kg</td><td style="text-align:right;">'.$GLOBALS['p25l'].'</td></tr>';
	if($GLOBALS['p30l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 25-30 Kg</td><td style="text-align:right;">'.$GLOBALS['p30l'].'</td></tr>';
	if($GLOBALS['p35l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 30-35 Kg</td><td style="text-align:right;">'.$GLOBALS['p35l'].'</td></tr>';
	if($GLOBALS['p40l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 35-40 Kg</td><td style="text-align:right;">'.$GLOBALS['p40l'].'</td></tr>';
	if($GLOBALS['p45l'])
		echo '<tr><td>Privatpakke, Lø.Omd. 40-45 Kg</td><td style="text-align:right;">'.$GLOBALS['p40l'].'</td></tr>';
	if($GLOBALS['p50l'])
		echo '<tr><td>Postopkrævningspakke, Lø.Omd. 45-50 Kg</td><td style="text-align:right;">'.$GLOBALS['o50l'].'</td></tr>';
		
	if($GLOBALS['o1'])
		echo '<tr><td>Postopkrævningspakke 0-1 Kg</td><td style="text-align:right;">'.$GLOBALS['o1'].'</td></tr>';
	if($GLOBALS['o5'])
		echo '<tr><td>Postopkrævningspakke 1-5 Kg</td><td style="text-align:right;">'.$GLOBALS['o5'].'</td></tr>';
	if($GLOBALS['o10'])
		echo '<tr><td>Postopkrævningspakke 5-10 Kg</td><td style="text-align:right;">'.$GLOBALS['o10'].'</td></tr>';
	if($GLOBALS['o15'])
		echo '<tr><td>Postopkrævningspakke 10-15 Kg</td><td style="text-align:right;">'.$GLOBALS['o15'].'</td></tr>';
	if($GLOBALS['o20'])
		echo '<tr><td>Postopkrævningspakke 15-20 Kg</td><td style="text-align:right;">'.$GLOBALS['o20'].'</td></tr>';
	if($GLOBALS['o25'])
		echo '<tr><td>Postopkrævningspakke 20-25 Kg</td><td style="text-align:right;">'.$GLOBALS['o25'].'</td></tr>';
	if($GLOBALS['o30'])
		echo '<tr><td>Postopkrævningspakke 25-30 Kg</td><td style="text-align:right;">'.$GLOBALS['o30'].'</td></tr>';
	if($GLOBALS['o35'])
		echo '<tr><td>Postopkrævningspakke 30-35 Kg</td><td style="text-align:right;">'.$GLOBALS['o35'].'</td></tr>';
	if($GLOBALS['o40'])
		echo '<tr><td>Postopkrævningspakke 35-40 Kg</td><td style="text-align:right;">'.$GLOBALS['o40'].'</td></tr>';
	if($GLOBALS['o45'])
		echo '<tr><td>Postopkrævningspakke 40-45 Kg</td><td style="text-align:right;">'.$GLOBALS['o40'].'</td></tr>';
	if($GLOBALS['o50'])
		echo '<tr><td>Postopkrævningspakke 45-50 Kg</td><td style="text-align:right;">'.$GLOBALS['o50'].'</td></tr>';
	if($GLOBALS['o50b'])
		echo '<tr><td>Postopkrævningspakke over 50 Kg</td><td style="text-align:right;">'.$GLOBALS['o50b'].'</td></tr>';
	
	if($GLOBALS['valuem']) {
		ksort($GLOBALS['valuem']);
		foreach($GLOBALS['valuem'] as $key => $value) {
			echo '<tr><td>Værdi '.$key.' Momspligtig</td><td style="text-align:right;">'.$value.'</td></tr>';
		}
	}
	
	if($GLOBALS['valuenm']) {
		ksort($GLOBALS['valuenm']);
		foreach($GLOBALS['valuenm'] as $key => $value) {
			echo '<tr><td>Værdi '.$key.'</td><td style="text-align:right;">'.$value.'</td></tr>';
		}
	}
	
	if($GLOBALS['volume'])
		echo '<tr><td>Volumen</td><td style="text-align:right;">'.$GLOBALS['volume'].'</td></tr>';
		
	if($GLOBALS['lørdag'])
		echo '<tr><td>lørdagsomdeling</td><td style="text-align:right;">'.$GLOBALS['lørdag'].'</td></tr>';

	if($GLOBALS['forsigtig'])
		echo '<tr><td>Forsigtig</td><td style="text-align:right;">'.$GLOBALS['forsigtig'].'</td></tr>';

	if($GLOBALS['moms'])
		echo '<tr><td>Moms</td><td style="text-align:right;">'.number_format($GLOBALS['moms'], 2, ',', '.').'</td></tr>';
		
?></table><br />
<?php
echo('Opkrævet porto: '.number_format($Tporto, 2, ',', '.').'<br />');
echo('Porto: '.number_format($Tfragt, 2, ',', '.').'<br />');
echo('UB: '.number_format($fragtub, 2, ',', '.'));

?>
</body>
</html>
