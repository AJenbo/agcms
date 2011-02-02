<?php
	header("Content-Type: text/html; charset=iso-8859-1");
	require_once("snoopy/snoopy.class.php");
require_once("../inc/mysqli.php");
require_once("../inc/config.php");
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);


	function getTrackTrace($stregkode) {
		global $mysqli;

		$snoopy = new Snoopy;

		$submit_url = "http://www.postdanmark.dk/tracktrace/TrackTrace.do?i_lang=IND&i_stregkode=".$stregkode;

		$snoopy->fetch($submit_url);
		$snoopy->results = utf8_encode($snoopy->results);

		preg_match('/<td>([.0-9]+)\skg<\\/td>/ui', $snoopy->results, $kg);
		preg_match_all('/<td>([0-9]+)\smm.<\\/td>/ui', $snoopy->results, $vol);
		
		if(preg_match('/>Retur\stil\safsender</ui', $snoopy->results))
			$pd_return = 'true';
		else
			$pd_return = 'false';
		
		if(preg_match('/Afhentet/ui', $snoopy->results)
		|| preg_match('/Udleveret\s/ui', $snoopy->results)
		|| preg_match('/[>]Udleveret[<]/ui', $snoopy->results)
		|| preg_match('/Omdelt\slandzone/ui', $snoopy->results)
		|| preg_match('/Flexleveret/ui', $snoopy->results)
		|| preg_match('/Lørdagsomdelt/ui', $snoopy->results))
			$pd_arrived = 'true';
		else
			$pd_arrived = 'false';

		$return[0] = $kg[1];
		$return[1] = $vol[1][0]/10;
		$return[2] = $vol[1][1]/10;
		$return[3] = $vol[1][2]/10;
		$return[4] = $pd_return;
		$return[5] = $pd_arrived;
		$return[6] = preg_match('/Forsinket/ui', $snoopy->results);

		$return = array_map("html_entity_decode", $return);
		$return = array_map("trim", $return);

		return $return;
	}
	
	if(!$_GET['m'])
		$_GET['m'] = date('m');
	
	if(!$_GET['y'])
		$_GET['y'] = date('Y');

	$post = $mysqli->fetch_array('SELECT * FROM `post` WHERE deleted = 0 AND `STREGKODE` != \'\' AND `pd_arrived` = \'false\' AND `formDate` >= \''.$_GET['y'].'-'.$_GET['m'].'-01\' AND `formDate` <= \''.$_GET['y'].'-'.$_GET['m'].'-31\'');
	
	for($i=0;$i<count($post);$i++) {
		$size = getTrackTrace($post[$i]['STREGKODE']);
		$mysqli->query('UPDATE `post` SET `pd_return` = \''.$size[4].'\', `pd_arrived` = \''.$size[5].'\', `pd_weight` = \''.$size[0].'\', `pd_length` = \''.$size[1].'\', `pd_height` = \''.$size[2].'\', `pd_width` = \''.$size[3].'\' WHERE `id` ='.$post[$i]['id'].' LIMIT 1');
/*
		//sammel reklmationer
		if($post[$i]['pd_return'] == 'true' || $size[4])
			$delaydate = 2;
		else
			$delaydate = 0;
		if($size[6])
			$delaydate += 2;
		if($size[5] == 'false' &&  $post[$i]['reklmation'] == 'false') {
			$clsWorkDays = new clsWorkDays;
			$clsWorkDays->clsWorkDays();
			$WorkDays = $clsWorkDays->days_diff($post[$i]['formDate'], date("Y-m-d"));
			if(($WorkDays > 14+$delaydate && $post[$i]['optRecipType'] == 'O') || ($WorkDays > 7+$delaydate && $post[$i]['optRecipType'] == 'E'))
				$GLOBALS['reklmation'][] = $post[$i];
		}
		*/
	}
	
	/*
	//send reklmationer til post danmark.
	require_once("reklmation.php");
	*/
	header('Location: liste.php?y='.$_GET['y'].'&m='.$_GET['m']);
?>
