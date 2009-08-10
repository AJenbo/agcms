<?php
	header("Content-Type: text/html; charset=iso-8859-1");
	require_once("snoopy/snoopy.class.php");

	function getToken() {
		$snoopy = new Snoopy;

		$submit_url = "http://www.postdanmark.dk/pfs/PfsLoginServlet";

		$submit_vars['gotoURL'] = "http://www.postdanmark.dk/pfs/PfsLoginServlet";
		$submit_vars['clientID'] = "17";
		$submit_vars['userID'] = "demo";
		$submit_vars['password'] = "Demo17";

		$snoopy->submit($submit_url, $submit_vars);

		preg_match('/token=([a-z0-9]+?)&/i', $snoopy->results, $matches);

		return $matches[1];
	}

	/*===================================
	if count(32) PO pakke
		[0] => Afsender Navn
		[1] => Afsender Kontaktperson
		[2] =>  
		[3] => Afsender Adresse
		[4] => Afsender Telefonnr
		[5] =>  
		[6] => Afsender Postnr. By
		[7] => Afsender E-Mail
		[8] =>  
		[9] => 
		[10] => Afsender SE-nr.
		[11] =>  
		[12] => 
		[13] => Afsender Gironr.
		[14] =>  
		[15] =>  
		[16] => Afsender Girobank
		[17] => Modtager navn
		[18] => Modtager ID
		[19] =>  
		[20] => Modtager Adresse 1
		[21] => Modtager E-Mail
		[22] =>  
		[23] => Modtager Postnr. By
		[24] =>  
		[25] => Oprettet
		[26] => Forsendelsesdato
		[27] => Ordrenr.
		[28] => 
		[29] => Kolli
		[30] => Bruttovægt
		[31] => Stregkode
		
	if count(33)
		[31] => Po value<br>ref. nummer
		[32] => Stregkode
		
	if count(33) Alternative
		[31] => 
		[32] => Stregkode
		
	if count(34)
		[31] =>
		[32] =>
		[33] => Stregkode
		
	if count(35)
		[17] => Modtager E-Mail
		[18] =>
		[19] =>
		[20] => Modtager navn
		[21] => Modtager ID
		[22] => 
		[23] => Modtager Adresse 1
		[24] => Modtager E-Mail (with line breaks)
		[25] => 
		[26] => Modtager Postnr. By
		[27] => 
		[28] => Oprettet
		[29] => Forsendelsesdato
		[30] =>
		[31] =>
		[32] => Kolli
		[33] => 
		[34] => Stregkode
		
	if count(36)
		[20] => Modtager navn
		[21] => 
		[22] => 
		[23] => Modtager Adresse 1
		[24] => 
		[25] => 
		[26] => Modtager Postnr. By
		[27] => 
		[28] => 
		[29] => Oprettet
		[30] => Forsendelsesdato
		[31] =>
		[32] =>
		[33] => Kolli
		[34] => 
		[35] => Stregkode
		
	if count(37)
		[20] => Att
		[22] =>  
		[23] => Modtager Adresse 1
		[24] => Modtager E-Mail
		[25] =>  
		[26] => Modtager Postnr. By
		[27] =>  
		[28] =>  
		[29] => Oprettet
		[30] => Forsendelsesdato
		[30] => Ordrenr.
		[31] => 
		[32] => 
		[33] => Kolli
		[34] => Bruttovægt
		[35] => Po value<br>ref. nummer
		[36] => Stregkode
		
	if count(39)
		[17] => Modtager E-Mail
		[18] =>
		[19] =>
		[20] => Modtager navn
		[21] => Modtager ID
		[22] => 
		[23] => Att
		[24] => Modtager E-Mail
		[25] => 
		[26] => Modtager Adresse 1
		[27] => 
		[28] => 
		[29] => Modtager Postnr. By
		[30] => 
		[31] => 
		[32] => Oprettet
		[33] => Forsendelsesdato
		[34] =>
		[35] =>
		[36] => Kolli
		[37] => 
		[38] => Stregkode
		
	if count(40)
		[20] => Modtager navn
		[21] => 
		[22] => 
		[23] => Modtager Adresse 1
		[24] => 
		[25] => 
		[26] => Att
		[27] => 
		[28] => 
		[29] => 
		[30] => Modtager Postnr. By
		[31] => 
		[32] => 
		[33] => Oprettet
		[34] => Forsendelsesdato
		[35] =>
		[36] =>
		[37] => Kolli
		[38] => 
		[39] => Stregkode

	/*===================================*/

	//Avarage query time 1,6 sec
	function getPfsDayListDetails($id = '4005000') {

		$submit_vars['token'] = getToken();
		$submit_vars['consignmentID'] = $id;
		$submit_vars['kolliNo'] = '1';
		$submit_vars['programID'] = 'pfs';
		$submit_vars['clientID'] = "17";
		$submit_vars['userID'] = "demo";
		$submit_vars['sessionID'] = '0';
		$submit_vars['accessCode'] = 'DE';
		$submit_vars['exTime'] = '60';
		//A dummy date
		$submit_vars['startDate'] = '05.04.2008';
		$submit_vars['stopDate'] = '05.04.2008';
		$submit_vars['paramOrderID'] = '';
		$submit_vars['paramRecID'] = '';
		$submit_vars['paramRecName1'] = '';
		$submit_vars['uniqueID'] = 'on';

		$snoopy = new Snoopy;

		$submit_url = "http://www.postdanmark.dk/pfs/pfsDayListDetails.jsp";

		$snoopy->submit($submit_url, $submit_vars);
		
		preg_match_all('/TabelBroedtekst>(.*?)<\\/td>/i', $snoopy->results, $matches1);
		preg_match('/>(.+?)<\\/a>/i', $snoopy->results, $matches2);
		$matches1[1][] = $matches2[1];

		$matches1[1] = array_map("html_entity_decode", $matches1[1]);
		$matches1[1] = array_map("trim", $matches1[1]);
		
		//preg_match_all('/ss[0-9]+/i', $snoopy->results, $matches1);

		//debugish
		//echo $snoopy->results;

		return $matches1[1];
	}

	//0 - 40467 appears empty
//	if(!$_GET['id'])
//		$_GET['id'] = '40467';

//5675810
	set_time_limit(0);
	$totaltime = 0;
	$time_starts = microtime(true);
	function getsqlupdate($i) {
		$time_start = microtime(true);
		$value = getPfsDayListDetails($i);
		$time_end = microtime(true);
		if($value[10] == '13081387') {
			if($value[4] == '33222333')
				$afsender = '11856';
			elseif($value[4] == '35366666')
				$afsender = '11894';
			elseif($value[4] == '33337777')
				$afsender = '11861';
			elseif($value[4] == '33118338')
				$afsender = '11865';
		
			echo("\n#<br />");
			if(count($value) == 32) {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', recZipCode = '.substr($value[23], 0, 4).', recAddress1 = \''.$value[20].'\', recipientID = \''.$value[18].'\', token = '.$i.', recName1 = \''.$value[17].'\' WHERE STREGKODE = \''.$value[31].'\';');
			} elseif(count($value) == 33 && $value[31] != '') {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', formDate = STR_TO_DATE(\''.substr($value[31], -16, -8).'\', \'%d%m%Y\'), recPoValue = \''.preg_replace('/[.]/', '', substr($value[31], 0, -23)).'\', recZipCode = '.substr($value[23], 0, 4).', recAddress1 = \''.$value[20].'\', recipientID = \''.$value[18].'\', id = '.substr($value[31], -4).', token = '.$i.', recName1 = \''.$value[17].'\' WHERE STREGKODE = \''.$value[32].'\';');
			} elseif(count($value) == 33) {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', recZipCode = '.substr($value[23], 0, 4).', recAddress1 = \''.$value[20].'\', recipientID = \''.$value[18].'\', token = '.$i.', recName1 = \''.$value[17].'\' WHERE STREGKODE = \''.$value[32].'\';');
			} elseif(count($value) == 34) {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', recZipCode = '.substr($value[26], 0, 4).', recAddress1 = \''.$value[20].'\', recipientID = \''.$value[18].'\', token = '.$i.', recName1 = \''.$value[17].'\' WHERE STREGKODE = \''.$value[33].'\';');
			} elseif(count($value) == 35) {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', recZipCode = '.substr($value[26], 0, 4).', recAddress1 = \''.$value[23].'\', recipientID = \''.$value[21].'\', token = '.$i.', recName1 = \''.$value[20].'\' WHERE STREGKODE = \''.$value[34].'\';');
			} elseif(count($value) == 36) {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', recZipCode = '.substr($value[26], 0, 4).', recAddress1 = \''.$value[23].'\', recipientID = \''.$value[21].'\', token = '.$i.', recName1 = \''.$value[20].'\' WHERE STREGKODE = \''.$value[35].'\';');
			} elseif(count($value) == 37) {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', recZipCode = '.substr($value[26], 0, 4).', recAddress1 = \''.$value[23].'\', recipientID = \''.$value[18].'\', token = '.$i.', recName1 = \''.$value[17].'\' WHERE STREGKODE = \''.$value[36].'\';');
			} elseif(count($value) == 39) {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', recZipCode = '.substr($value[29], 0, 4).', recAddress1 = \''.$value[26].'\', recipientID = \''.$value[21].'\', token = '.$i.', recName1 = \''.$value[17].'\' WHERE STREGKODE = \''.$value[38].'\';');
			} elseif(count($value) == 40) {
				echo("\n".'UPDATE post SET formSenderID = \''.$afsender.'\', recZipCode = '.substr($value[30], 0, 4).', recAddress1 = \''.$value[23].'\', recipientID = \''.$value[18].'\', token = '.$i.', recName1 = \''.$value[17].'\' WHERE STREGKODE = \''.$value[39].'\';');
			} else print_r($value);
			echo("\n#<br />");
		}
		echo '#Id: '.$i.' - '.round($time_end-$time_start, 2)." sec <br />";
		$GLOBALS['totaltime'] += $time_end-$time_start;
	}
	
	function echoss($i) {
		$time_start = microtime(true);
		$value = getPfsDayListDetails($i);
		$time_end = microtime(true);
		print_r($value);
		echo '#Id: '.$i.' - '.round($time_end-$time_start, 2)." sec\n<br />";
		$GLOBALS['totaltime'] += $time_end-$time_start;
	}
	
	getsqlupdate(5667242);
	getsqlupdate(5667264);
	getsqlupdate(5667327);
	getsqlupdate(5667367);
	getsqlupdate(5667369);
	getsqlupdate(5667371);
	getsqlupdate(5670635);
	getsqlupdate(5670789);
	getsqlupdate(5670990);
	getsqlupdate(5671082);
	getsqlupdate(5671925);
	getsqlupdate(5672160);
	getsqlupdate(5672253);
	getsqlupdate(5673608);
	getsqlupdate(5674776);
	getsqlupdate(5674884);
	getsqlupdate(5675275);
	getsqlupdate(5675296);
	getsqlupdate(5675559);
	getsqlupdate(5675811);

	echo '#Total time '.round($totaltime, 2)." sec\n";

?>
