<?php
	require_once 'post/snoopy/snoopy.class.php';
	require_once 'inc/mysqli.php';
	require_once 'inc/config.php';
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

	if(date('m') != 1) {
		$lm  = date('m')-1;
		$ly = date('Y');
	} else {
		$lm = 12;
		$ly = date('Y')-1;
	}
	

	$post = $mysqli->fetch_array('SELECT id, STREGKODE FROM `post` WHERE token > 0 AND deleted = 0 AND `STREGKODE` != \'\' AND `pd_arrived` = \'false\' AND `formDate` >= \''.$ly.'-'.$lm.'-01\'');
	
	foreach($post as $pakke) {
		$size = getTrackTrace($pakke['STREGKODE']);
		$mysqli->query('UPDATE `post` SET `pd_return` = \''.$size[4].'\', `pd_arrived` = \''.$size[5].'\', `pd_weight` = \''.$size[0].'\', `pd_length` = \''.$size[1].'\', `pd_height` = \''.$size[2].'\', `pd_width` = \''.$size[3].'\' WHERE `id` ='.$pakke['id'].' LIMIT 1');
	}

	echo('Der blev søgt på '.count($post).' pakker.');
?>
