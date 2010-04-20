<?php
/*
ini_set('display_errors', 1);
error_reporting(-1);
/**/
	$url = urldecode($_SERVER['REQUEST_URI']);
	//can't detect windows-1252
	$encoding = mb_detect_encoding($url, 'UTF-8, ISO-8859-1');
	if($encoding != 'UTF-8') {
		//Firefox uses windows-1252 if it can get away with it
		//We can't detect windows-1252 from iso-8859-1 but it's a superset of the secound so bouth should handle fine as windows-1252
		if(!$encoding || $encoding == 'ISO-8859-1')
			$encoding = 'windows-1252';
		$url = mb_convert_encoding($url, 'UTF-8', $encoding);
		//rawurlencode $url (PIE doesn't do it buy it self :(
		function queryrawurlencode($array) {
			return implode('=', array_map("rawurlencode", explode('=', $array)));
		}
		$url = explode("?", $url);
			$url[0] = implode('/', array_map("rawurlencode", explode('/', $url[0])));
			$url[1] = implode('&', array_map("queryrawurlencode", explode('&', $url[1])));
		$url = implode('?', $url);
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '.$url);
		die();
	}
	
	require_once("snoopy/snoopy.class.php");
	require_once("../inc/sajax.php");
	require_once("../inc/getaddress.php");
	require_once("../inc/mysqli.php");
	require_once("../inc/config.php");
	
	
	function getToken($passwordOverwrite = NULL, $usealt = false) {
		require("config.php");
		if($passwordOverwrite)
			$password = $passwordOverwrite;
		$snoopy = new Snoopy;
		
		$submit_url = "http://www.postdanmark.dk/pfs/PfsLoginServlet";
		
		$submit_vars['gotoURL'] = "http://www.postdanmark.dk/pfs/PfsLoginServlet";
		$submit_vars['clientID'] = $clientID;
		$submit_vars['userID'] = "admin";
		$submit_vars['password'] = $password;
			
		$snoopy->submit($submit_url, $submit_vars);
		
		if($snoopy->results == '' || $snoopy->results == 'No backend servers available') {
			die('var res = { "error": \'Postdanmark server er nede, prøv igen senere.\' }; res;');
		}
		if(!$usealt) {
			preg_match('/token=([a-f0-9]+)&/i', $snoopy->results, $matches);
			$matches[0] = substr($matches[0], 6);
			return substr($matches[0], 0, strlen($matches[0])-1);
		}
		//alt methode
		//return preg_replace('/.*token=([a-f0-9]+)&.*/ism', '$1', strip_tags($snoopy->results));
		preg_match('/[a-f0-9]{240}/i', $snoopy->results, $matches);
		return $matches[0];
	}
	
	function deleteID($id) {
		$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
		$mysqli->query('DELETE FROM `post` WHERE id = '.$delete);
	}

	function getPDFURL(
	$optRecipType,
	$recZipCode,
	$recCityName,
	$recPoValue,
	$recPoPostOffice,
	$recipientID,
	$recCVR,
	$recAttPerson,
	$recName1,
	$recName2,
	$orderID,
	$recAddress1,
	$recAddress2,
	$recPostBox,
	$remarks,
	$formDate,
	$weight,
	$emailChecked,
	$email,
	$emailTxt,
	$c_no,
	$c_w,
	$c_rem,
	$ss1,
	$ss2,
	$ss46,
	$ss5amount,
	$checkAddress,
	$height,
	$width,
	$length,
	$porto,
	$ub,
	$formSenderID,
	$fakturaid) {
		if($ub)
			$ub = 'true';
		else
			$ub = 'false';
		if($ss1)
			$ss1 = 'true';
		else
			$ss1 = 'false';
		if($ss2)
			$ss2 = 'true';
		else
			$ss2 = 'false';
		if($ss46)
			$ss46 = 'true';
		else
			$ss46 = 'false';
		
		setcookie('formSenderID', $formSenderID, time()+365*24*60*60, '/post/');

		require("config.php");
		$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

		$mysqli->query("INSERT INTO `post` (
				`fakturaid`,
				`formSenderID`,
				`recName1`,
				`recAddress1`,
				`recZipCode`,
				`recPoValue`,
				`recipientID`,
				`formDate`,
				`optRecipType`,
				`weight`,
				`ss1`,
				`ss2`,
				`ss46`,
				`ss5amount`,
				`height`,
				`width`,
				`length`,
				`porto`,
				`ub`)
			VALUES (
				'".addcslashes($fakturaid, "'\\")."',
				'".addcslashes($formSenderID, "'\\")."',
				'".addcslashes($recName1, "'\\")."',
				'".addcslashes($recAddress1, "'\\")."',
				'".addcslashes($recZipCode, "'\\")."',
				'".addcslashes($recPoValue, "'\\")."',
				'".addcslashes($recipientID, "'\\")."',
				'".addcslashes(preg_replace('/^([0-9]{2})[.]([0-9]{2})[.]([0-9]{4})$/i', '$3-$2-$1', $formDate), "'\\")."',
				'".addcslashes($optRecipType, "'\\")."',
				'".addcslashes($weight, "'\\")."',
				'".addcslashes($ss1, "'\\")."',
				'".addcslashes($ss2, "'\\")."',
				'".addcslashes($ss46, "'\\")."',
				'".addcslashes($ss5amount, "'\\")."',
				'".addcslashes($height, "'\\")."',
				'".addcslashes($width, "'\\")."',
				'".addcslashes($length, "'\\")."',
				'".addcslashes($porto, "'\\")."',
				'".addcslashes($ub, "'\\")."'
				);
		");

		$dbid = $mysqli->insert_id;
	 
		$snoopy = new Snoopy;
		
		$submit_url = "http://www.postdanmark.dk/pfs/PfsLabelServlet";
		
		$submit_vars['recName1'] = utf8_decode($recName1);
		$submit_vars['countryID'] = 'DK';
		$submit_vars['userID'] = 'admin';
		if($ss5amount > 4600) {
			$submit_vars['ss5amount'] = $ss5amount;
			$submit_vars['ss5'] = 'V%E6rdi';
		}
		$submit_vars['exTime'] = '120';
		$submit_vars['recCityName'] = utf8_decode($recCityName);
		$submit_vars['countryName'] = 'Danmark';
		$submit_vars['oldCountryID'] = 'DK';
		if($ss1 == 'true')
			$submit_vars['ss1'] = 'Forsigtig';
		if($ss2 == 'true')
			$submit_vars['ss2'] = 'Volumen';
		if($ss46 == 'true')
			$submit_vars['ss46'] = 'L%F8rdagsekspres';
		$submit_vars['optRecipType'] = utf8_decode($optRecipType);
		$submit_vars['orderID'] = utf8_decode($orderID);
		$submit_vars['recAddress1'] = utf8_decode($recAddress1);
		$submit_vars['recAddress2'] = utf8_decode($recAddress2);
		$submit_vars['remarks'] = utf8_decode($remarks);
		$submit_vars['spID'] = '/pfs/pfsWelcome.jsp';
		$submit_vars['accessCode'] = 'UC';
		$submit_vars['speedText'] = 'Indenlandske+pakker';
		if($email != '')
			$submit_vars['emailChecked'] = 'checked';
		else
			$submit_vars['emailChecked'] = '';
		$submit_vars['programID'] = 'pfs';
		$submit_vars['token'] = changeUser($formSenderID);
		$submit_vars['recName2'] = utf8_decode($recName2);
		$submit_vars['sessionID'] = '0';
		$submit_vars['disabledCityName'] = utf8_decode($recCityName);
		$submit_vars['recAttPerson'] = utf8_decode($recAttPerson);
		$submit_vars['speedID'] = 'PIP';
		$submit_vars['recReturParcel'] = '';
		$submit_vars['formDate'] = utf8_decode($formDate);
		$submit_vars['typedRecCVR'] = '';
		$submit_vars['recZipCode'] = utf8_decode($recZipCode);
		$submit_vars['recPostBox'] = utf8_decode($recPost);
		$submit_vars['recPoValue'] = $recPoValue;
		$submit_vars['recPoRef'] = str_replace('.', '', $formDate);
		while (strlen($submit_vars['recPoRef'])+strlen($dbid)<16) {
			$submit_vars['recPoRef'] .= '0';
		}
		$submit_vars['recPoRef'] .= $dbid;
		$submit_vars['oldRecType'] = '';
		$submit_vars['errorID'] = '';
		$submit_vars['buttonPressed'] = 'NOW';
		$submit_vars['cpID'] = '/pfs/pfsNewParcelDK.jsp';
		$submit_vars['optRecipRadio'] = 'on';
		$submit_vars['email'] = utf8_decode($email);
		$submit_vars['recipientID'] = utf8_decode($recipientID);
		$submit_vars['recCVR'] = utf8_decode($recCVR);
		$submit_vars['clientID'] = $clientID;
		$submit_vars['weight'] = $weight;
		$submit_vars['productNo'] = '-1';
		$submit_vars['recPoPostOffice'] = utf8_decode($recPoPostOffice);
		$submit_vars['emailTxt'] = utf8_decode($emailTxt);
		$submit_vars['c_no'] = $c_no;
		$c_w = unserialize($c_w);
		$c_rem = unserialize($c_rem);
		for($i=0;$i<$c_no;$i++) {
			$submit_vars['c_'.$i.'_w'] = utf8_decode($c_w[$i]);
			$submit_vars['c_'.$i.'_rem'] = utf8_decode($c_rem[$i]);
		}
		if($checkAddress == true) {
			$submit_url = 'http://www.postdanmark.dk/pfs/PfsLabelServlet?vltWasHere=1';
			$submit_vars['vltForce'] = 1;
			$submit_vars['vltStreet'] = preg_replace('/(.+?)([0-9]+)([a-zA-Z]?)(.*)/i', '$1', utf8_decode($recAddress1));
			$submit_vars['vltStreetNo'] =  preg_replace('/(.+?)([0-9]+)([a-zA-Z]?)(.*)/i', '$2', utf8_decode($recAddress1));
			$submit_vars['vltStreetLetter'] =  preg_replace('/(.+?)([0-9]+)([a-zA-Z]?)(.*)/i', '$3', utf8_decode($recAddress1));
			$submit_vars['vltStreetOther'] =  preg_replace('/(.+?)([0-9]+)([a-zA-Z]?)(.*)/i', '$4', utf8_decode($recAddress1));
			$submit_vars['vltZipCode'] = utf8_decode($recZipCode);
		}
		$snoopy->submit($submit_url, $submit_vars);
		
		//Did any error ocure
		preg_match('/errorID\sVALUE=["](.*?)["]/', $snoopy->results, $error);

		if($error && $error[1] == 'TokenCorrupt') {
			//Password posibly outof date, try to update
			$error == NULL;
			updatePassword();
			
			//Resent the request with a new token
			$submit_vars['token'] = getToken();
			
			$mysqli->query('DELETE FROM `post` WHERE id = '.$dbid);
			$mysqli->query('ALTER TABLE `post` AUTO_INCREMENT = 0');
			
			$snoopy->submit($submit_url, $submit_vars);
			//Did any error ocure
			preg_match('/errorID\sVALUE=["](.*?)["]/', $snoopy->results, $error);
		}
		
		if($error && $error[1] != '') {
			preg_match('/Broedtekst["][>](.*?)[<]/', $snoopy->results, $errortext);
			
			$mysqli->query('DELETE FROM `post` WHERE id = '.$dbid);
			$mysqli->query('ALTER TABLE `post` AUTO_INCREMENT = 0');
			
			return array('error' => 'Fejl: '.utf8_encode($error[1])."\n".'Beskrivelse: '.utf8_encode($errortext[1]));
		} elseif(!preg_match('/forsID=([0-9]*)/', $snoopy->results, $matches)) {
			$mysqli->query('DELETE FROM `post` WHERE id = '.$dbid);
			$mysqli->query('ALTER TABLE `post` AUTO_INCREMENT = 0');
				
			if($checkAddress != true) {
				return array('yesno' => $dbid);
			} else {
				return array('error' => 'Fejl: Der opstod en ukendt fejl i programmet.', 'html' => $snoopy->results);
			}
		}
		
		preg_match('/[a-f0-9]{240}/i', $snoopy->results, $token);
		
		$mysqli->query('UPDATE `post` SET `token` = \''.$matches[1].'\', `STREGKODE` = \''.getSTREGKODE($matches[1], $token[0]).'\' WHERE `id` ='.$dbid.' LIMIT 1');
//		$mysqli->query('DELETE FROM `post` WHERE `STREGKODE` = \'\' AND `token` = \'\'');
		
		return array('url' => $matches[1], 'clientID' => $clientID);
	}
	
	function updatePassword() {
		require("config.php");
	
		$snoopy = new Snoopy;
		
		$submit_url = "http://www.postdanmark.dk/pfs/PfsSetPasswordServlet";
		
		$submit_vars['token'] = getToken(NULL, TRUE);
		$submit_vars['programID'] = 'pfs';
		$submit_vars['clientID'] = $clientID;
		$submit_vars['userID'] = 'admin';
		$submit_vars['sessionID'] = '0';
		$submit_vars['accessCode'] = 'UC';
		$submit_vars['isAtLogin'] = '0';
		$submit_vars['exTime'] = '120';
		$submit_vars['spID'] = '';
		$submit_vars['cpID'] = '';
		$submit_vars['buttonPressed'] = 'OK';
		$submit_vars['targetID'] = $clientID;
		$submit_vars['formUserID'] = 'admin';
		//Not needed !!!!!!!, but ok we will play nice incease they fix this at some point
		$submit_vars['formOldPassword'] = $password;
		//5-20 of upper case, lower case and numbers
		$submit_vars['formPassword'] = 'Ab123';
		$submit_vars['formRepeatPassword'] = 'Ab123';
		
		//change password to Chips19
		$snoopy->submit($submit_url, $submit_vars);
		$submit_vars['token'] = getToken('Ab123');
		//Not needed !!!!!!!, but ok we will play nice incease they fix this at some point
		$submit_vars['formOldPassword'] = 'Ab123';
		$submit_vars['formPassword'] = $password;
		$submit_vars['formRepeatPassword'] = $password;
		
		//Change the password back to the one in config.php
		$snoopy->submit($submit_url, $submit_vars);
	}
	
	function getSTREGKODE($consignmentID, $token) {
		require("config.php");
		$snoopy = new Snoopy;
		
		$submit_url = "http://www.postdanmark.dk/pfs/pfsDayListDetails.jsp";
		
		$submit_vars['token'] = $token;
		$submit_vars['consignmentID'] = $consignmentID;
		$submit_vars['kolliNo'] = '1';
		$submit_vars['programID'] = 'pfs';
		$submit_vars['clientID'] = $clientID;
		$submit_vars['userID'] = "admin";
		$submit_vars['sessionID'] = '0';
		$submit_vars['accessCode'] = 'UC';
		$submit_vars['exTime'] = '120';
		$submit_vars['startDate'] = '';
		$submit_vars['stopDate'] = '';
		$submit_vars['paramOrderID'] = '';
		$submit_vars['paramRecID'] = '';
		$submit_vars['paramRecName1'] = '';
		$submit_vars['uniqueID'] = 'on';
			
		$snoopy->submit($submit_url, $submit_vars);
		
		preg_match('/>(.+?)<\\/a>/i', $snoopy->results, $STREGKODE);
		
		return $STREGKODE[1];
	}
	
	function changeUser($formSenderID) {
		require("config.php");
		$snoopy = new Snoopy;
		
		$submit_url = "http://www.postdanmark.dk/pfs/PfsSenderServlet";
		
		$submit_vars['formSenderID'] = $formSenderID;
		$submit_vars['token'] = getToken();
		$submit_vars['programID'] = 'pfs';
		$submit_vars['clientID'] = $clientID;
		$submit_vars['userID'] = 'admin';
		$submit_vars['sessionID'] = '0';
		$submit_vars['accessCode'] = 'UC';
		$submit_vars['exTime'] = '120';
		$submit_vars['spID'] = 'pfsWelcome.jsp';
		$submit_vars['cpID'] = 'pfsSenderManagement.jsp';
		$submit_vars['buttonPressed'] = 'FAVORITE';
		$submit_vars['errorID'] = '';
		$submit_vars['favorite'] = 'N';
			
		$snoopy->submit($submit_url, $submit_vars);
		
		
		if($snoopy->results == '' || $snoopy->results == 'No backend servers available') {
			die('var res = { "error": \'Postdanmark server er nede, prøv igen senere.\' }; res;');
		}
		
		preg_match('/[a-f0-9]{240}/i', $snoopy->results, $matches);
		return $matches[0];
	}
	
	function payerstatus($recipientID) {
		$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
		$returns = $mysqli->fetch_array("SELECT count(*) as 'returns' FROM `post` WHERE `recipientID` = '".$recipientID."' AND `pd_return` = 'true'");
		$nonepayed = $mysqli->fetch_array("SELECT count(*) as 'returns' FROM `post` WHERE `recipientID` = '".$recipientID."' AND `pd_return` = 'true' AND `optRecipType` = 'O'");
		if($nonepayed[0]['returns'])
			return $responce = 'Denne kunde er dårlig betaler og har lad pakken gå retur '.$returns[0]['returns'].' gang(e)!';
		elseif($returns[0]['returns'])
			return 'Denne kunde har lad pakken gå retur '.$returns[0]['returns'].' gang(e)!';
		else
			return false;
	}
	
//	$sajax_debug_mode = 1;
//	$sajax_remote_uri = "/ajax.php";
	sajax_export('changeUser','getPDFURL','getAddress','deleteID', 'payerstatus', 'wait');
	sajax_handle_client_request();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Indenlands Pakker - Post Anders</title>
<script type="text/javascript" src="calcpakkepris.js"></script>
<script type="text/javascript" src="/javascript/zipcodedk.js"></script>
<script type="text/javascript" src="javascript.js"></script>
<script type="text/javascript" src="/javascript/json2.stringify.js"></script> 
<script type="text/javascript" src="/javascript/json_stringify.js"></script>
<script type="text/javascript" src="/javascript/json_parse_state.js"></script> 
<script type="text/javascript" src="/javascript/sajax.js"></script> 
<script type="text/javascript" src="index.js"></script>
<script type="text/javascript"><!--
function init2() {
	<?php
	if(mb_substr(preg_replace('/\s+/u', '', @$_GET['tlf1'] ? $_GET['tlf1'] : @$_GET['tlf2']), 0, 10))
			echo("$('recipientID').value = '".addcslashes(mb_substr(preg_replace('/\s+/u', '', @$_GET['tlf1'] ? $_GET['tlf1'] : @$_GET['tlf2']), 0, 10), "'")."';\r\n");
	if(mb_substr(@$_GET['name'], 0, 34))
			echo("$('recName1').value = '".addcslashes(mb_substr(@$_GET['name'], 0, 34), "'")."';\r\n");
	if(mb_substr(@$_GET['att'], 0, 34))
			echo("$('recAttPerson').value = '".addcslashes(mb_substr(@$_GET['att'], 0, 34), "'")."';\r\n");
	if(@mb_substr($_GET['address'], 0, 34))
			echo("$('recAddress1').value = '".addcslashes(@mb_substr($_GET['address'], 0, 34), "'")."';\r\n");
	if(@mb_substr($_GET['address2'], 0, 34))
			echo("$('recAddress2').value = '".addcslashes(@mb_substr($_GET['address2'], 0, 34), "'")."';\r\n");
	if(@mb_substr($_GET['postbox'], 0, 20))
			echo("$('recPostBox').value = '".addcslashes(@mb_substr($_GET['postbox'], 0, 20), "'")."';\r\n");
	if(mb_substr(@$_GET['zipcode'], 0, 4))
			echo("$('recZipCode').value = '".addcslashes(mb_substr(@$_GET['zipcode'], 0, 4), "'")."';\r\n");
	if(@$_GET['email'])
			echo("$('email').value = '".addcslashes(@$_GET['email'], "'")."';\r\n");
	if(@mb_substr($_GET['value'], 0, 12))
			echo("$('recPoValue').value = '".addcslashes(@mb_substr($_GET['value'], 0, 12), "'")."';\r\n");
	if(@mb_substr($_GET['porto'], 0, 12))
			echo("$('porto').value = '".addcslashes(@mb_substr($_GET['porto'], 0, 12), "'")."';\r\n");
	if(!empty($_GET['fakturaid']))
			echo("$('fakturaid').value = '".addcslashes($_GET['fakturaid'], "'")."';\r\n");
	if(isset($_GET) && !empty($_GET['porto']) && $_GET['porto'] == '0,00')
		echo("$('ub').checked = true;");
	
	?>
}
--></script>
<link href="style.css" rel="stylesheet" type="text/css" />
</head>
<body onload="init2();init();"><div id="loading" style="display:none;"><img src="load.gif" width="228" height="144" alt="" title="Komunikere med post danmark..." /><br />
<input type="button" onclick="cancel();" value="Annuller" /></div>
<?php
include('menu.html');
include('config.php');
?>
<div style="width:650px; margin:10px 0 0 180px">
<div id="popup" style="position:absolute; display:none; left:0; right:0; top:0; bottom:0; background-color:#666666;z-index:2; color:#FFFFFF;opacity:0.9;filter:alpha(opacity=90);"> Din popup blokker (stop extra vinduer), har forhindret at PDF filen blev åbnet automatisk.<br />
	<a id="popuplink" href="#" style="text-decoration:underline" onclick="this.parentNode.style.display = 'none';" target="_blank">Tryk på dette link for at åbne PDF filen manuelt.</a></div>
<div style="position:relative;width:100%;z-index:1000;background-color:#000000; top:0; left:0;color:#FFFFFF;font-weight:bold; text-align:center; font-size:40px;opacity:0.5;filter:alpha(opacity=50);" id="postdkloading">Venter på Post Danmark's server.</div>
<img src="http://www.postdanmark.dk/pfs/grafik/pakker.gif" alt="" style="float:right; padding:0 0 5px 0" height="50" /><h2 style="padding:25px 0 0 0; margin:0">Indenlandsk Pakke</h2>
<hr />
<table style="width:653px"><tbody><tr><td style="width:33%"><strong>Afsender</strong><br />
<select name="formSenderID" id="formSenderID">
<option value="0" selected="selected">Vælge afsender</option><?php
foreach($brugere as $key => $value) {
	?><option value="<?php echo($key);?>"<?php if($key == @$_GET['senderid'] || $key == @$_COOKIE['formSenderID']) echo(' selected="selected"'); ?>><?php echo($value);?></option><?php
}
?></select></td><td style="width:33%"><strong>Modtagerland</strong><br />
Danmark</td><td style="width:33%"><strong>Forsendelsestype</strong><br />
Indenlandske pakker</td></tr></tbody></table><form action="" method="post" name="CallLabelServlet" id="CallLabelServlet" onsubmit="validate();return false;"><input name="fakturaid" id="fakturaid" value="" style="display:none;" />
<hr />
  <table>
    <tbody>
      <tr>
        <td><label><input style="width:auto;height:auto;" type="radio" name="optRecipType" value="P" onchange="changeOptRecipType()" onclick="changeOptRecipType()"<?php if(@$_GET['type'] == 'P') echo(' checked="checked"'); ?> />
          Privat</label>
          <label><input style="width:auto;height:auto;" type="radio" name="optRecipType" value="E" onchange="changeOptRecipType()" onclick="changeOptRecipType()"<?php if(@$_GET['type'] == 'E') echo(' checked="checked"'); ?> />
          Erhverv</label>
          <label><input style="width:auto;height:auto;" type="radio" name="optRecipType" value="O" onchange="changeOptRecipType()" onclick="changeOptRecipType()"<?php if(@$_GET['type'] == 'O' || !@$_GET['type']) echo(' checked="checked"'); ?> />
          Postopkrævning</label>
          <h3 style="margin:0; padding:0">
          <br />Modtager</h3>
          <table>
            <tbody>
              <tr id="trCvr"><td>CVR-nr.:</td><td><input style="width:80px;text-align:right;" name="recCVR" id="recCVR" maxlength="8" /></td></tr>
              <tr style="display:none">
                <td> Ordrenr.:</td>
                <td><input style="width:80px;text-align:right;" name="orderID" id="orderID" maxlength="20" /></td>
              </tr>
              <tr>
                <td> Telefon nr.:</td>
                <td><input style="width:80px;" name="recipientID" id="recipientID" maxlength="10" value="" />
                  <input type="button" value="Hent" style="width:auto;height:auto;" onclick="getAddress($('recipientID').value);" /></td>
              </tr><tr><?php

$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$frequentRecipients = $mysqli->fetch_array('SELECT recipientID, recName1, count(*) AS n FROM post WHERE deleted = 0 AND recipientID != \'\'
 GROUP BY recipientID HAVING n >1 ORDER BY n DESC LIMIT 10');

foreach($frequentRecipients as $key => $value) {
	$temp[$value['recipientID']] = $value['recName1'];
}
$frequentRecipients = $temp;

$frequentRecipients[70157015] = 'Brock og Michelsen';
$frequentRecipients[96802000] = 'GUNTEX A/S';
$frequentRecipients[74223520] = 'Deerhunter';
$frequentRecipients[43410410] = 'Seeland International A/S';
$frequentRecipients[97122766] = 'Ocean Rainwear';
$frequentRecipients[35424143] = 'NagPeople Aps';
$frequentRecipients[98450180] = 'Poul Villaume';
$frequentRecipients[75679522] = 'S & L Produktion';
$frequentRecipients[36165018] = 'Mercia Outdoor ApS';
$frequentRecipients[87939925] = 'Tretorn';

$temp = natsort($frequentRecipients);

?><td colspan="2"><script type="text/javascript"><!--
function frequentRecipients(recipientID) {
	$('recipientID').value = recipientID;
	if(recipientID)
		getAddress(recipientID);
}



--></script><select onchange="frequentRecipients(this.options[this.selectedIndex].value)" onkeyup="frequentRecipients(this.options[this.selectedIndex].value)">
<option value="" selected="selected">Hyppige modtagere...</option><?php
foreach($frequentRecipients as $key => $value) {
	?><option value="<?php echo($key);?>"><?php echo($value);?></option><?php
}
?></select></td></tr>
              <tr>
                <td> Navn:</td>
                <td><input name="recName1" id="recName1" maxlength="34" value="" />
                  <br />
                  <input name="recName2" id="recName2" maxlength="34" style="display:none" /></td>
              </tr>
              <tr>
                <td> Att.:</td>
                <td><input name="recAttPerson" id="recAttPerson" maxlength="34" value="" /></td>
              </tr>
              <tr>
                <td> <?php echo(_('Adresse:')); ?></td>
                <td><input value="" name="recAddress1" maxlength="34" id="recAddress1" onchange="changeRecAddress1(this.value)" onblur="changeRecAddress1(this.value)" onkeyup="changeRecAddress1(this.value)" onkeydown="changeRecAddress1(this.value)" onfocus="changeRecAddress1(this.value)" />
                  <br />
                  <input value="" name="recAddress2" maxlength="34" id="recAddress2" /> </td>
              </tr>
              <tr>
                <td> Postboks:</td>
                <td><input value="" name="recPostBox" maxlength="20" id="recPostBox" style="text-align:right;" onchange="changeRecPostBox(this.value)" onblur="changeRecPostBox(this.value)" onkeyup="changeRecPostBox(this.value)" onkeydown="changeRecPostBox(this.value)" onfocus="changeRecPostBox(this.value)" /> </td>
              </tr>
              <tr>
                <td> Postnr./By:</td>
                <td><input value="" style="width:40px;text-align:right;" name="recZipCode" maxlength="4" id="recZipCode" onchange="chnageZipCode(this.value)" onkeyup="chnageZipCode(this.value)" onblur="chnageZipCode(this.value)" /> <input style="width:104px;" id="recCityName" name="recCityName" disabled="disabled" /> </td>
              </tr>
              <tr>
                <td><label><input  style="display:none;width:auto;height:auto;" name="emailChecked" id="emailChecked" value="emailChecked" type="checkbox" onclick="clickEmailChecked(this.checked)" />
                		 E-mail:</label></td>
                <td><input name="email" id="email" value="" /></td>
              </tr>
            </tbody>
          </table><hr />
          <br />
          <input type="submit" value="Generere PDF" style="width:auto" /><input type="reset" style="width:auto" value="Tøm felter" onclick="setTimeout('changeOptRecipType()',100);changeRecPostBox(''); changeRecAddress1(''); " /></td>
        <td><table style="width:380px">
            <tbody>
              <tr>
                <td> Dato for afsending:</td>
                <td><select name="formDate" id="formDate">
                    <option value="" selected="selected"></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                    <option value=""></option>
                  </select></td>
              </tr>
              <tr>
                  <td>Standard pakker </td>
                  <td>                 
               <select onchange="standard(this.selectedIndex)" onkeyup="standard(this.selectedIndex)">
                    <option>Vælge en størelse</option>
                    <option>SB4</option>
                    <option>SB5</option>
                    <option>SB6</option>
                    <option>SB27</option>
                    <option>SB30</option>
                    <option>SB34</option>
                    <option>SB35</option>
                    <option>1,5 meter rør + SB4</option>
                    <option>1,5 meter rør + SB5</option>
                    <option>1,5 meter rør + SB6</option>
                    <option>2 meter rør + SB4</option>
                    <option>2 meter rør + SB5</option>
                    <option>2 meter rør + SB6</option>
                    <option>1.5 meter rør</option>
                    <option>2 meter rør</option>
                </select></td>
                </tr>
              <tr>
                  <td>Højde </td>
                  <td><input id="height" name="height" class="text" onchange="calc()" onkeyup="calc()" style="width:40px;text-align:right;" />
                    cm</td>
                </tr>
              <tr>
                  <td>Brede </td>
                  <td><input id="width" name="width" class="text" onchange="calc()" onkeyup="calc()" style="width:40px;text-align:right;" />
                    cm</td>
                </tr>
              <tr>
                  <td>Længde </td>
                  <td><input id="length" name="length" class="text" onchange="calc()" onkeyup="calc()" style="width:40px;text-align:right;" />
                    cm</td>
                </tr>
              <tr>
                <td> Vægt:</td>
                <td><label>
                  <input onchange="calc()" onkeyup="calc()" name="weight" id="weight" maxlength="11" style="width:40px;text-align:right;" value="" />
                  kg. </label></td>
              </tr>
              <tr style="display:none">
                <td> Bemærkning:</td>
                <td><input name="remarks" id="remarks" maxlength="60" />                </td>
              </tr>
              <tr style="display: none;" id="trRecPo1">
                <td colspan="2"><strong>Afhentnings Posthus:</strong></td></tr>
              <tr style="display: none;" id="trRecPo2">
<td colspan="2"><select name="recPoPostOffice" id="recPoPostOffice">
<option value="0" selected="selected">Vælg Afhentnings Posthus hvis andet end standard ønskes</option>
<option value="1000">1000 Købmagergade, Posthus</option>
<option value="1240">1240 Christiansborg, Postbutik</option>
<option value="809">1302 Nyboder, Posthus</option>
<option value="819">1415 Christianshavn, Posthus</option>
<option value="821">1570 Hovedbanegården, Posthus</option>
<option value="1541">1620 Vesterbrogade Postbutik, Postbutik</option>
<option value="815">1620 Vesterport, Posthus</option>
<option value="1529">1810 Platan, Posthus</option>
<option value="806">1879 H.C. Ørstedsvej, Posthus</option>
<option value="2000">2000 Frederiksberg, Posthus</option>
<option value="818">2000 Borups Alle, Postbutik</option>
<option value="2003">2000 Falkoner, Posthus</option>
<option value="2001">2000 Domus Vista, Postbutik</option>
<option value="2100">2100 Østerbro, Posthus</option>
<option value="831">2100 Studsgaardsgade, Posthus</option>
<option value="2103">2100 Østerfælled, Postbutik</option>
<option value="829">2100 Christiansmindevej, Posthus</option>
<option value="808">2100 Strandboulevarden, Postbutik</option>
<option value="2200">2200 Nørrebro, Posthus</option>
<option value="2201">2200 Hamletsgade, Postbutik</option>
<option value="830">2200 Søernes, Posthus</option>
<option value="2301">2300 Bella Centeret, Postbutik</option>
<option value="2305">2300 Amager, Postbutik</option>
<option value="2310">2300 Sønderbro, Posthus</option>
<option value="2307">2300 Sundby, Postbutik</option>
<option value="2306">2300 Vermlandsgade, Postbutik</option>
<option value="812">2300 Islands Brygge, Posthus</option>
<option value="2400">2400 Nordvest, Posthus</option>
<option value="2450">2450 Sydvestkvarteret, Posthus</option>
<option value="2503">2500 Søndre Fasanvej, Postbutik</option>
<option value="848">2500 Frugthaven, Postbutik</option>
<option value="827">2500 Tingstedet, Posthus</option>
<option value="2614">2600 Glostrup, Posthus</option>
<option value="2613">2610 Rødovre, Posthus</option>
<option value="2612">2610 Islev, Postbutik</option>
<option value="2622">2620 Albertslund, Posthus</option>
<option value="2631">2630 Blåkilde, Postbutik</option>
<option value="2634">2630 Sengeløse, Postbutik</option>
<option value="2639">2630 Taastrup, Posthus</option>
<option value="2629">2635 Ishøj, Posthus</option>
<option value="2643">2640 Hedehusene, Posthus</option>
<option value="2651">2650 Avedøre, Postbutik</option>
<option value="852">2650 Friheden, Posthus</option>
<option value="2652">2650 Hvidovre, Posthus</option>
<option value="2661">2660 Brøndby Strand, Posthus</option>
<option value="2604">2665 Vallensbæk, Postbutik</option>
<option value="2672">2670 Hundige, Posthus</option>
<option value="2675">2670 Greve Center, Posthus</option>
<option value="2680">2680 Solrød Strand, Posthus</option>
<option value="2700">2700 Brønshøj, Posthus</option>
<option value="2702">2700 Husum, Postbutik</option>
<option value="2703">2700 Tingbjerg, Posthus</option>
<option value="2720">2720 Vanløse, Posthus</option>
<option value="2730">2730 Herlev, Posthus</option>
<option value="2735">2730 Mørkhøj, Postbutik</option>
<option value="2740">2740 Skovlunde, Posthus</option>
<option value="2750">2750 Ballerup, Posthus</option>
<option value="2753">2750 Grantoften, Postbutik</option>
<option value="2761">2760 Måløv , Postbutik</option>
<option value="2757">2765 Smørumnedre, Posthus</option>
<option value="2771">2770 Vestamager, Postbutik</option>
<option value="2775">2770 Kastrup, Posthus</option>
<option value="2772">2770 Tårnby, Postbutik</option>
<option value="2791">2791 Dragør, Postbutik</option>
<option value="2800">2800 Lyngby, Posthus</option>
<option value="2802">2800 Brede, Postbutik</option>
<option value="2820">2820 Gentofte, Postbutik</option>
<option value="2830">2830 Virum, Posthus</option>
<option value="2841">2840 Holte, Postbutik</option>
<option value="2850">2850 Nærum, Posthus</option>
<option value="2860">2860 Søborg, Posthus</option>
<option value="2882">2880 Bagsværd, Postbutik</option>
<option value="2900">2900 Hellerup, Posthus</option>
<option value="2923">2920 Charlottenlund, Postbutik</option>
<option value="2930">2930 Klampenborg, Postbutik</option>
<option value="2952">2950 Vedbæk, Postbutik</option>
<option value="2970">2970 Hørsholm, Posthus</option>
<option value="2973">2970 Kongevejs-Centret, Postbutik</option>
<option value="2980">2980 Kokkedal, Postbutik</option>
<option value="2991">2990 Nivå, Postbutik</option>
<option value="3000">3000 Helsingør, Posthus</option>
<option value="3002">3000 Prøvestenen, Posthus</option>
<option value="3004">3000 Lappen, Postbutik</option>
<option value="3050">3050 Humlebæk, Posthus</option>
<option value="3060">3060 Espergærde, Posthus</option>
<option value="3101">3100 Hornbæk, Posthus</option>
<option value="3140">3140 Ålsgårde, Posthus</option>
<option value="3200">3200 Helsinge, Posthus</option>
<option value="3220">3220 Tisvildeleje, Postbutik</option>
<option value="3230">3230 Græsted, Postbutik</option>
<option value="3231">3230 Esbønderup, Postbutik</option>
<option value="3250">3250 Gilleleje, Postbutik</option>
<option value="3300">3300 Frederiksværk, Posthus</option>
<option value="3310">3310 Ølsted, Postbutik</option>
<option value="3320">3320 Skævinge, Postbutik</option>
<option value="3391">3390 Hundested, Postbutik</option>
<option value="3400">3400 Hillerød, Posthus</option>
<option value="3450">3450 Allerød, Posthus</option>
<option value="3460">3460 Birkerød, Posthus</option>
<option value="3480">3480 Fredensborg, Posthus</option>
<option value="3500">3500 Værløse, Posthus</option>
<option value="3520">3520 Farum, Posthus</option>
<option value="3540">3540 Lynge, Postbutik</option>
<option value="3550">3550 Slangerup, Posthus</option>
<option value="3600">3600 Frederikssund, Posthus</option>
<option value="3630">3630 Jægerspris, Posthus</option>
<option value="3650">3650 Ølstykke, Posthus</option>
<option value="3660">3660 Stenløse, Postbutik</option>
<option value="2752">3660 Ganløse, Postbutik</option>
<option value="3700">3700 Rønne, Posthus</option>
<option value="3781">3700 Nyker, Postbutik</option>
<option value="3713">3720 Pedersker, Postbutik</option>
<option value="3721">3720 Aakirkeby, Postbutik</option>
<option value="3730">3730 Nexø, Posthus</option>
<option value="3731">3730 Snogebæk, Postbutik</option>
<option value="3751">3751 Østermarie, Postbutik</option>
<option value="3752">3760 Østerlars, Postbutik</option>
<option value="3761">3760 Christiansø, Posthuse (Minus Billetnet)</option>
<option value="3760">3760 Gudhjem, Postbutik</option>
<option value="3784">3770 Tejn, Postbutik</option>
<option value="3771">3770 Allinge, Postbutik</option>
<option value="3782">3782 Klemensker, Postbutik</option>
<option value="3790">3790 Hasle, Postbutik</option>
<option value="4000">4000 Roskilde, Posthus</option>
<option value="4012">4000 Hyrdehøj, Postbutik</option>
<option value="4015">4000 Svogerslev, Postbutik</option>
<option value="4024">4000 Tune, Postbutik</option>
<option value="4025">4000 Vindinge, Postbutik</option>
<option value="4023">4000 Osted, Postbutik</option>
<option value="4014">4000 Roskilde Øst, Posthus</option>
<option value="4041">4040 Jyllinge, Postbutik</option>
<option value="4052">4050 Skibby, Postbutik</option>
<option value="4060">4060 Kirke Såby, Postbutik</option>
<option value="4051">4070 Ejby, Postbutik</option>
<option value="4070">4070 Kirke Hyllinge, Postbutik</option>
<option value="4100">4100 Ringsted, Posthus</option>
<option value="4102">4100 Benløse, Postbutik</option>
<option value="4131">4130 Viby Sjælland, Postbutik</option>
<option value="4141">4140 Borup, Postbutik</option>
<option value="4160">4160 Herlufmagle, Postbutik</option>
<option value="4171">4171 Glumsø, Postbutik</option>
<option value="4183">4180 Pedersborg, Postbutik</option>
<option value="4181">4180 Sorø, Posthus</option>
<option value="4201">4200 Slagelse, Posthus</option>
<option value="4202">4200 Slagelse Syd, Postbutik</option>
<option value="4215">4200 Kirke Stillinge, Postbutik</option>
<option value="4216">4200 Sørbymagle, Postbutik</option>
<option value="4221">4220 Korsør, Posthus</option>
<option value="4245">4230 Omø, Postbutik</option>
<option value="4231">4230 Skælskør, Postbutik</option>
<option value="4241">4241 Vemmelev, Postbutik</option>
<option value="4242">4242 Boeslunde, Postbutik</option>
<option value="4243">4243 Bisserup, Postbutik</option>
<option value="4251">4250 Fuglebjerg, Postbutik</option>
<option value="4261">4261 Dalmose, Postbutik</option>
<option value="4271">4270 Høng, Postbutik</option>
<option value="4281">4281 Gørlev, Postbutik</option>
<option value="4285">4281 Reersø, Postbutik</option>
<option value="4291">4291 Ruds Vedby, Postbutik</option>
<option value="4297">4293 Dianalund, Posthus</option>
<option value="4298">4295 Stenlille, Postbutik</option>
<option value="4300">4300 Holbæk, Posthus</option>
<option value="4307">4300 Udby, Postbutik</option>
<option value="4308">4300 Hagested, Postbutik</option>
<option value="4306">4300 Tuse, Postbutik</option>
<option value="4301">4300 Holbæk Øst, Postbutik</option>
<option value="4305">4300 Orø, Postbutik</option>
<option value="4320">4320 Lejre, Postbutik</option>
<option value="4331">4330 Hvalsø, Postbutik</option>
<option value="4342">4340 Tølløse, Postbutik</option>
<option value="4341">4340 Undløse, Postbutik</option>
<option value="4350">4350 Ugerløse, Postbutik</option>
<option value="4370">4370 Store Merløse, Postbutik</option>
<option value="4390">4390 Vipperød, Postbutik</option>
<option value="4400">4400 Kalundborg, Posthus</option>
<option value="4401">4400 Kalundborg Syd, Postbutik</option>
<option value="4405">4400 Rørby, Postbutik</option>
<option value="4407">4400 Ulstrup, Postbutik</option>
<option value="4402">4400 Raklev, Postbutik</option>
<option value="4420">4420 Regstrup, Postbutik</option>
<option value="4440">4440 Mørkøv, Postbutik</option>
<option value="4450">4450 Jyderup, Postbutik</option>
<option value="4470">4470 Svebølle, Postbutik</option>
<option value="4490">4490 Jerslev Sjælland, Postbutik</option>
<option value="4501">4500 Nykøbing Sj, Posthus</option>
<option value="4521">4520 Svinninge, Postbutik</option>
<option value="4532">4532 Gislinge, Postbutik</option>
<option value="4534">4534 Hørve, Postbutik</option>
<option value="4540">4540 Fårevejle, Postbutik</option>
<option value="4551">4550 Asnæs, Postbutik</option>
<option value="4560">4560 Vig, Postbutik</option>
<option value="4571">4571 Grevinge, Postbutik</option>
<option value="4573">4573 Højby, Postbutik</option>
<option value="4581">4581 Rørvig, Postbutik</option>
<option value="4583">4583 Sjællands Odde, Postbutik</option>
<option value="4592">4592 Sejerø, Postbutik</option>
<option value="4593">4593 Eskebjerg, Postbutik</option>
<option value="4600">4600 Køge, Posthus</option>
<option value="4607">4600 Ølby Lyng, Posthus</option>
<option value="4621">4621 Gadstrup, Postbutik</option>
<option value="4625">4622 Havdrup, Postbutik</option>
<option value="4623">4623 Lille Skensved, Postbutik</option>
<option value="4633">4632 Bjæverskov, Postbutik</option>
<option value="4641">4640 Faxe, Postbutik</option>
<option value="4652">4652 Hårlev, Postbutik</option>
<option value="4653">4653 Karise, Postbutik</option>
<option value="4654">4654 Faxe Ladeplads, Postbutik</option>
<option value="4661">4660 Store Heddinge, Postbutik</option>
<option value="4671">4671 Strøby, Postbutik</option>
<option value="4673">4673 Rødvig Stevns, Postbutik</option>
<option value="4681">4681 Herfølge, Posthus</option>
<option value="4683">4683 Rønnede, Postbutik</option>
<option value="4684">4684 Holmegaard, Postbutik</option>
<option value="4685">4684 Fensmark, Postbutik</option>
<option value="4691">4690 Haslev, Postbutik</option>
<option value="4700">4700 Næstved, Posthus</option>
<option value="4742">4700 Mogenstrup, Postbutik</option>
<option value="4701">4700 Fodbygårdsvej, Postbutik</option>
<option value="4704">4700 Sct. Jørgen, Posthus</option>
<option value="4702">4700 Stor-Centeret, Postbutik</option>
<option value="4724">4720 Præstø, Postbutik</option>
<option value="4733">4733 Tappernøje, Postbutik</option>
<option value="4735">4735 Mern, Postbutik</option>
<option value="4736">4736 Karrebæksminde, Postbutik</option>
<option value="4750">4750 Lundby, Postbutik</option>
<option value="4760">4760 Vordingborg, Posthus</option>
<option value="4774">4760 Nyråd, Postbutik</option>
<option value="4765">4760 Ørslev, Postbutik</option>
<option value="4771">4771 Kalvehave, Postbutik</option>
<option value="4773">4773 Stensved, Postbutik</option>
<option value="4781">4780 Stege, Postbutik</option>
<option value="4791">4791 Borre, Postbutik</option>
<option value="4793">4793 Bogø By, Postbutik</option>
<option value="4800">4800 Nykøbing F, Posthus</option>
<option value="4806">4800 Sundby, Postbutik</option>
<option value="4841">4840 Nørre Alslev, Postbutik</option>
<option value="4852">4850 Stubbekøbing, Postbutik</option>
<option value="4862">4862 Guldborg, Postbutik</option>
<option value="4863">4863 Eskilstrup, Postbutik</option>
<option value="4871">4871 Horbelev, Postbutik</option>
<option value="4872">4872 Idestrup, Postbutik</option>
<option value="4876">4873 Væggerløse, Postbutik</option>
<option value="4874">4874 Gedser, Postbutik</option>
<option value="4880">4880 Nysted, Postbutik</option>
<option value="4891">4891 Toreby L, Postbutik</option>
<option value="4892">4892 Kettinge, Postbutik</option>
<option value="4901">4900 Nakskov, Posthus</option>
<option value="4913">4913 Horslunde, Postbutik</option>
<option value="4920">4920 Søllested, Postbutik</option>
<option value="4930">4930 Maribo, Posthus</option>
<option value="4944">4944 Fejø, Postbutik</option>
<option value="4952">4952 Stokkemarke, Postbutik</option>
<option value="4960">4960 Holeby, Postbutik</option>
<option value="4979">4970 Rødby Havn, Postbutik</option>
<option value="4971">4970 Rødby, Postbutik</option>
<option value="4983">4983 Dannemare, Postbutik</option>
<option value="4990">4990 Sakskøbing, Postbutik</option>
<option value="5000">5000 Odense Banegård, Posthus</option>
<option value="5001">5000 Brandts, Posthus</option>
<option value="5210">5210 Tarup, Posthus</option>
<option value="5461">5210 Korup, Postbutik</option>
<option value="5220">5220 Rosengård, Posthus</option>
<option value="5280">5220 Fraugde, Postbutik</option>
<option value="5240">5240 Odense Nord Øst, Posthus</option>
<option value="5250">5250 Dalum, Posthus</option>
<option value="5681">5250 Bellinge, Postbutik</option>
<option value="5793">5260 Højby, Postbutik</option>
<option value="5271">5270 Stige, Postbutik</option>
<option value="5272">5270 Søhus Center, Postbutik</option>
<option value="5301">5300 Kerteminde, Postbutik</option>
<option value="5320">5320 Bullerup, Postbutik</option>
<option value="5330">5330 Munkebo, Postbutik</option>
<option value="5370">5370 Mesinge, Postbutik</option>
<option value="5401">5400 Bogense, Postbutik</option>
<option value="5450">5450 Otterup, Postbutik</option>
<option value="5465">5464 Brenderup Fyn, Postbutik</option>
<option value="5470">5471 Søndersø, Postbutik</option>
<option value="5493">5492 Vissenbjerg, Postbutik</option>
<option value="5500">5500 Middelfart, Posthus</option>
<option value="5594">5500 Strib, Postbutik</option>
<option value="5541">5540 Ullerslev, Postbutik</option>
<option value="5551">5550 Langeskov, Postbutik</option>
<option value="5561">5560 Aarup, Postbutik</option>
<option value="5580">5580 Nørre Aaby, Postbutik</option>
<option value="5591">5591 Gelsted, Postbutik</option>
<option value="5595">5592 Ejby, Postbutik</option>
<option value="5764">5600 Vester Aaby, Postbutik</option>
<option value="5601">5600 Faaborg, Postbutik</option>
<option value="5662">5600 Håstrup, Postbutik</option>
<option value="5610">5610 Assens, Postbutik</option>
<option value="5621">5620 Glamsbjerg, Postbutik</option>
<option value="5631">5631 Ebberup, Postbutik</option>
<option value="5651">5672 Allested, Postbutik</option>
<option value="5672">5672 Broby, Postbutik</option>
<option value="5671">5672 Brobyværk, Postbutik</option>
<option value="5684">5683 Haarby, Posthuse (Minus Billetnet)</option>
<option value="5573">5690 Tommerup, Postbutik</option>
<option value="5572">5690 Tommerup St, Postbutik</option>
<option value="5700">5700 Svendborg, Posthus</option>
<option value="5706">5700 Tved, Postbutik</option>
<option value="5750">5750 Ringe, Postbutik</option>
<option value="5761">5762 Ollerup, Postbutik</option>
<option value="5763">5762 Vester Skerninge, Postbutik</option>
<option value="5772">5772 Kværndrup, Postbutik</option>
<option value="5791">5792 Årslev, Postbutik</option>
<option value="5653">5792 Nørre Lyndelse, Postbutik</option>
<option value="5800">5800 Nyborg, Posthus</option>
<option value="5861">5800 Avnslev, Postbutik</option>
<option value="5852">5853 Ørbæk, Postbutik</option>
<option value="5874">5874 Hesselager, Postbutik</option>
<option value="5885">5884 Gudme, Postbutik</option>
<option value="5892">5892 Gudbjerg Sydfyn, Postbutik</option>
<option value="5900">5900 Rudkøbing, Postbutik</option>
<option value="5931">5900 Lindelse, Postbutik</option>
<option value="5932">5932 Humble, Postbutik</option>
<option value="5935">5935 Bagenkop, Postbutik</option>
<option value="5952">5953 Tullebølle, Postbutik</option>
<option value="5956">5953 Lohals, Postbutik</option>
<option value="5958">5953 Snøde, Postbutik</option>
<option value="5961">5960 Marstal, Postbutik</option>
<option value="5971">5970 Ærøskøbing, Postbutik</option>
<option value="5985">5985 Søby Ærø, Postbutik</option>
<option value="6000">6000 Kolding, Posthus</option>
<option value="6017">6000 Munkebo, Posthus</option>
<option value="6016">6000 Seest, Postbutik</option>
<option value="6081">6000 Vonsild, Postbutik</option>
<option value="6054">6040 Gravens, Postbutik</option>
<option value="6041">6040 Egtved, Postbutik</option>
<option value="6055">6040 Vester-Nebel, Postbutik</option>
<option value="6064">6064 Jordrup, Postbutik</option>
<option value="6071">6070 Christiansfeld, Postbutik</option>
<option value="6091">6091 Bjert, Postbutik</option>
<option value="6092">6092 Sønder Stenderup, Postbutik</option>
<option value="6093">6093 Sjølund, Postbutik</option>
<option value="6094">6094 Hejls, Postbutik</option>
<option value="6100">6100 Haderslev, Posthus</option>
<option value="6192">6100 Øsby, Postbutik</option>
<option value="6200">6200 Aabenraa, Posthus</option>
<option value="6221">6200 Løjt Kirkeby, Postbutik</option>
<option value="6223">6200 Felsted, Postbutik</option>
<option value="6230">6230 Rødekro, Postbutik</option>
<option value="6241">6240 Løgumkloster, Postbutik</option>
<option value="6261">6261 Bredebro, Postbutik</option>
<option value="6270">6270 Tønder, Postbutik</option>
<option value="6280">6280 Højer, Postbutik</option>
<option value="6301">6300 Gråsten, Postbutik</option>
<option value="6310">6310 Broager, Postbutik</option>
<option value="6330">6330 Padborg, Posthus</option>
<option value="6340">6340 Kruså, Postbutik</option>
<option value="6350">6340 Kollund, Postbutik</option>
<option value="6361">6360 Tinglev, Postbutik</option>
<option value="6372">6372 Bylderup-Bov, Postbutik</option>
<option value="6385">6400 Vester-Sottrup, Postbutik</option>
<option value="6411">6400 Dybbøl, Postbutik</option>
<option value="6400">6400 Sønderborg, Posthus</option>
<option value="6431">6430 Nordborg, Postbutik</option>
<option value="6461">6430 Guderup, Postbutik</option>
<option value="6440">6440 Augustenborg, Postbutik</option>
<option value="6415">6470 Hørup, Postbutik</option>
<option value="6470">6470 Sydals, Postbutik</option>
<option value="6531">6500 Nustrup, Postbutik</option>
<option value="6501">6500 Vojens, Postbutik</option>
<option value="6511">6510 Gram, Postbutik</option>
<option value="6521">6520 Toftlund, Postbutik</option>
<option value="6534">6534 Agerskov, Postbutik</option>
<option value="6541">6541 Bevtoft, Postbutik</option>
<option value="6560">6560 Sommersted, Postbutik</option>
<option value="6581">6580 Vamdrup, Postbutik</option>
<option value="6600">6600 Vejen, Posthus</option>
<option value="6624">6600 Andst, Postbutik</option>
<option value="6621">6621 Gesten, Postbutik</option>
<option value="6622">6622 Bække, Postbutik</option>
<option value="6623">6623 Vorbasse, Postbutik</option>
<option value="6632">6630 Rødding, Postbutik</option>
<option value="6543">6630 Sønder-Hygum, Postbutik</option>
<option value="6631">6630 Jels, Postbutik</option>
<option value="6641">6640 Lunderskov, Postbutik</option>
<option value="6652">6650 Brørup , Postbutik</option>
<option value="6651">6650 Lindknud, Postbutik</option>
<option value="6670">6670 Holsted, Postbutik</option>
<option value="6682">6682 Hovborg, Postbutik</option>
<option value="6690">6690 Gørding, Postbutik</option>
<option value="6700">6700 Esbjerg H, Posthus</option>
<option value="6713">6700 Darumvej, Postbutik</option>
<option value="6714">6700 Skrænten, Postbutik</option>
<option value="6701">6700 Esbjerg B, Posthus</option>
<option value="6705">6705 Esbjerg Ø, Posthus</option>
<option value="6734">6710 Hjerting, Postbutik</option>
<option value="6710">6710 Esbjerg V, Posthus</option>
<option value="6715">6715 Gjesing Centret, Postbutik</option>
<option value="6736">6715 Vester Nebel, Postbutik</option>
<option value="6721">6720 Fanø , Postbutik</option>
<option value="6731">6731 Tjæreborg, Postbutik</option>
<option value="6740">6740 Bramming, Postbutik</option>
<option value="6751">6740 Vejrup, Postbutik</option>
<option value="6752">6752 Glejbjerg, Postbutik</option>
<option value="6753">6753 Agerbæk, Postbutik</option>
<option value="6760">6760 Ribe, Posthus</option>
<option value="6772">6760 Hviding, Postbutik</option>
<option value="6771">6771 Gredstedbro, Postbutik</option>
<option value="6781">6780 Skærbæk, Postbutik</option>
<option value="6800">6800 Varde, Posthus</option>
<option value="6861">6800 Sig, Postbutik</option>
<option value="6821">6800 Næsbjerg, Postbutik</option>
<option value="6815">6800 Alslev, Postbutik</option>
<option value="6818">6818 Årre, Postbutik</option>
<option value="6825">6823 Ansager, Postbutik</option>
<option value="6831">6830 Nørre-Nebel, Postbutik</option>
<option value="6842">6840 Oksbøl, Postbutik</option>
<option value="6851">6851 Janderup Vestj, Postbutik</option>
<option value="6852">6852 Billum, Postbutik</option>
<option value="6855">6855 Outrup, Postbutik</option>
<option value="6862">6862 Tistrup, Postbutik</option>
<option value="6871">6870 Ølgod, Postbutik</option>
<option value="6880">6880 Tarm, Postbutik</option>
<option value="6881">6880 Lyne, Postbutik</option>
<option value="6900">6900 Skjern, Postbutik</option>
<option value="6931">6900 Borris, Postbutik</option>
<option value="6920">6920 Videbæk, Postbutik</option>
<option value="6930">6933 Kibæk, Postbutik</option>
<option value="6934">6933 Skarrild, Postbutik</option>
<option value="6940">6940 Lem St, Postbutik</option>
<option value="6950">6950 Ringkøbing, Posthus</option>
<option value="6960">6960 Hvide Sande, Postbutik</option>
<option value="6970">6971 Spjald, Postbutik</option>
<option value="6972">6971 Grønbjerg, Postbutik</option>
<option value="6973">6973 Ørnhøj, Postbutik</option>
<option value="6980">6980 Tim, Postbutik</option>
<option value="6981">6980 Stadil, Postbutik</option>
<option value="6990">6990 Ulfborg, Postbutik</option>
<option value="7000">7000 Fredericia, Posthus</option>
<option value="7012">7000 Nordbyen, Postbutik</option>
<option value="7013">7000 Vest-Centret, Posthus</option>
<option value="7014">7000 Erritsø, Postbutik</option>
<option value="7090">7080 Brejning, Postbutik</option>
<option value="7081">7080 Børkop, Postbutik</option>
<option value="7100">7100 Vejle, Posthus</option>
<option value="7122">7100 Jerlev, Postbutik</option>
<option value="7185">7100 Grejs, Postbutik</option>
<option value="7125">7100 Ødsted, Postbutik</option>
<option value="7123">7100 Lindved, Postbutik</option>
<option value="7117">7100 Nørremarken, Postbutik</option>
<option value="7102">7100 Vindinggård Center, Postbutik</option>
<option value="7114">7100 Skolegade, Postbutik</option>
<option value="7111">7100 Søndermarken, Postbutik</option>
<option value="7126">7120 Vejle Øst, Postbutik</option>
<option value="7131">7130 Juelsminde, Postbutik</option>
<option value="7161">7160 Tørring, Postbutik</option>
<option value="8764">7160 Åle, Postbutik</option>
<option value="7171">7171 Uldum, Postbutik</option>
<option value="7173">7173 Vonge, Postbutik</option>
<option value="7179">7182 Bredsten, Postbutik</option>
<option value="7190">7190 Billund, Postbutik</option>
<option value="6754">7200 Tofterup, Postbutik</option>
<option value="7350">7200 Filskov, Postbutik</option>
<option value="7200">7200 Grindsted, Posthus</option>
<option value="6755">7200 Krogager, Postbutik</option>
<option value="7250">7250 Hejnsvig, Postbutik</option>
<option value="7260">7260 Sønder-Omme, Postbutik</option>
<option value="7280">7280 Sønder-Felding, Postbutik</option>
<option value="7301">7300 Jelling, Postbutik</option>
<option value="7321">7321 Gadbjerg, Postbutik</option>
<option value="7323">7323 Give, Postbutik</option>
<option value="7324">7323 Thyregod, Postbutik</option>
<option value="7331">7330 Brande, Postbutik</option>
<option value="7361">7361 Ejstrupholm, Postbutik</option>
<option value="7423">7400 Hammerum, Postbutik</option>
<option value="7417">7400 Tjørring, Postbutik</option>
<option value="7416">7400 Snejbjerg, Postbutik</option>
<option value="7414">7400 Holtbjerg, Postbutik</option>
<option value="7400">7400 Herning, Posthus</option>
<option value="7431">7430 Ikast, Posthus</option>
<option value="7440">7441 Bording, Postbutik</option>
<option value="7442">7442 Engesvang, Postbutik</option>
<option value="7451">7451 Sunds, Postbutik</option>
<option value="7452">7451 Ilskov, Postbutik</option>
<option value="7471">7470 Karup J, Postbutik</option>
<option value="7453">7470 Frederiks, Postbutik</option>
<option value="7480">7480 Vildbjerg, Postbutik</option>
<option value="7522">7490 Hodsager, Postbutik</option>
<option value="7491">7490 Aulum, Postbutik</option>
<option value="7511">7500 Nørreland, Postbutik</option>
<option value="7514">7500 Holstebro, Posthus</option>
<option value="7516">7500 Nørre Felding, Postbutik</option>
<option value="7524">7500 Skave, Postbutik</option>
<option value="7521">7500 Borbjerg-Hvam, Postbutik</option>
<option value="7499">7500 Tvis, Postbutik</option>
<option value="7525">7540 Feldborg, Postbutik</option>
<option value="7560">7560 Hjerm, Postbutik</option>
<option value="7570">7570 Vemb, Postbutik</option>
<option value="7600">7600 Struer, Posthus</option>
<option value="7604">7600 Asp, Postbutik</option>
<option value="7621">7620 Lemvig, Postbutik</option>
<option value="7625">7620 Nissum Seminarieby, Postbutik</option>
<option value="7624">7620 Fjaltring, Postbutik</option>
<option value="7650">7650 Bøvlingbjerg, Postbutik</option>
<option value="7660">7660 Bækmarksbro, Postbutik</option>
<option value="7673">7673 Harboøre, Postbutik</option>
<option value="7681">7680 Thyborøn, Postbutik</option>
<option value="7744">7700 Hundborg, Postbutik</option>
<option value="7751">7700 Sjørring, Postbutik</option>
<option value="7705">7700 Thisted, Posthus</option>
<option value="7746">7700 Nørre-Vorupør, Postbutik</option>
<option value="7749">7700 Nors, Postbutik</option>
<option value="7731">7730 Hanstholm, Postbutik</option>
<option value="7740">7741 Frøstrup, Postbutik</option>
<option value="7742">7742 Vesløs, Postbutik</option>
<option value="7752">7752 Snedsted, Postbutik</option>
<option value="7753">7752 Hørdum, Postbutik</option>
<option value="7755">7755 Bedsted Thy, Postbutik</option>
<option value="7760">7760 Hurup Thy, Postbutik</option>
<option value="7771">7770 Agger, Postbutik</option>
<option value="7790">7790 Thyholm, Postbutik</option>
<option value="7800">7800 Skive, Posthus</option>
<option value="7801">7800 Egeris, Postbutik</option>
<option value="7814">7800 Vridsted, Postbutik</option>
<option value="7830">7830 Vinderup, Postbutik</option>
<option value="7831">7830 Ejsing, Postbutik</option>
<option value="7840">7840 Højslev, Postbutik</option>
<option value="7851">7850 Stoholm Postbutik, Postbutik</option>
<option value="7861">7860 Spøttrup, Postbutik</option>
<option value="7862">7860 Lem, Postbutik</option>
<option value="7866">7860 Oddense, Postbutik</option>
<option value="7879">7870 Breum, Postbutik</option>
<option value="7881">7870 Jebjerg, Postbutik</option>
<option value="7882">7870 Durup, Postbutik</option>
<option value="7885">7870 Selde, Postbutik</option>
<option value="7884">7884 Fur, Postbutik</option>
<option value="7900">7900 Nykøbing M, Posthus</option>
<option value="7911">7900 Sejerslev, Postbutik</option>
<option value="7930">7950 Sundby M, Postbutik</option>
<option value="7950">7950 Erslev, Postbutik</option>
<option value="7960">7960 Karby, Postbutik</option>
<option value="7970">7970 Redsted M, Postbutik</option>
<option value="7980">7980 Vils, Postbutik</option>
<option value="7990">7990 Øster-Assels, Postbutik</option>
<option value="8003">8000 Frederiksbjerg Torv, Posthus</option>
<option value="8009">8000 Vesterbro Torv, Posthus</option>
<option value="8010">8000 Banegårdspladsen, Posthus</option>
<option value="8200">8200 Storcenter Nord, Posthus</option>
<option value="8201">8200 Trøjborg, Postbutik</option>
<option value="8221">8220 Hovedgaden, Postbutik</option>
<option value="8220">8220 City Vest, Posthus</option>
<option value="8230">8230 Åbyhøj, Posthus</option>
<option value="8242">8240 Veri Center, Posthus</option>
<option value="8250">8250 Egå, Postbutik</option>
<option value="8260">8260 Viby Centret, Posthus</option>
<option value="8270">8270 Højbjerg, Posthus</option>
<option value="8300">8300 Odder, Postbutik</option>
<option value="8799">8300 Tunø Kattegat, Postbutik</option>
<option value="8773">8300 Gylling, Postbutik</option>
<option value="8791">8305 Tranebjerg Samsø, Postbutik</option>
<option value="8795">8305 Nordby Samsø, Postbutik</option>
<option value="8311">8310 Tranbjerg J, Postbutik</option>
<option value="8320">8320 Mårslet, Postbutik</option>
<option value="8330">8330 Beder, Postbutik</option>
<option value="8355">8355 Solbjerg, Postbutik</option>
<option value="8364">8361 Hasselager, Postbutik</option>
<option value="8362">8362 Hørning, Postbutik</option>
<option value="8370">8370 Hadsten, Postbutik</option>
<option value="8380">8380 Trige, Postbutik</option>
<option value="8381">8381 Tilst, Posthus</option>
<option value="8382">8382 Hinnerup, Postbutik</option>
<option value="8383">8382 Søften, Postbutik</option>
<option value="8400">8400 Ebeltoft, Postbutik</option>
<option value="8411">8410 Thorsager, Postbutik</option>
<option value="8412">8410 Rønde, Posthus</option>
<option value="8442">8410 Feldballe, Postbutik</option>
<option value="8421">8420 Knebel, Postbutik</option>
<option value="8444">8444 Balle, Postbutik</option>
<option value="8450">8450 Hammel, Postbutik</option>
<option value="8462">8462 Harlev J, Postbutik</option>
<option value="8464">8464 Galten, Postbutik</option>
<option value="8473">8464 Stjær, Postbutik</option>
<option value="8471">8471 Sabro, Postbutik</option>
<option value="8500">8500 Grenaa, Posthus</option>
<option value="8520">8520 Lystrup, Posthus</option>
<option value="8530">8530 Hjortshøj, Postbutik</option>
<option value="8541">8541 Skødstrup, Postbutik</option>
<option value="8543">8543 Hornslet, Postbutik</option>
<option value="8544">8544 Mørke, Postbutik</option>
<option value="8545">8544 Lime, Postbutik</option>
<option value="8964">8550 Pindstrup, Postbutik</option>
<option value="8551">8550 Ryomgård, Postbutik</option>
<option value="8560">8560 Kolind, Postbutik</option>
<option value="8570">8570 Trustrup, Postbutik</option>
<option value="8584">8585 Glesborg, Postbutik</option>
<option value="8587">8585 Fjellerup, Postbutik</option>
<option value="8592">8592 Anholt, Posthuse (Minus Bikketnet)</option>
<option value="8600">8600 Silkeborg, Posthus</option>
<option value="8615">8600 Virklund, Postbutik</option>
<option value="8613">8600 Sejs, Postbutik</option>
<option value="8601">8600 Vestergade, Postbutik</option>
<option value="8622">8620 Kjellerup, Postbutik</option>
<option value="8641">8641 Sorring, Postbutik</option>
<option value="8643">8643 Aas By, Postbutik</option>
<option value="8653">8653 Them, Postbutik</option>
<option value="8654">8654 Bryrup, Postbutik</option>
<option value="8363">8660 Stilling, Postbutik</option>
<option value="8660">8660 Skanderborg, Posthus</option>
<option value="8682">8680 Ry, Postbutik</option>
<option value="8727">8700 Lund, Postbutik</option>
<option value="8729">8700 Sejet, Postbutik</option>
<option value="8735">8700 Søvind, Postbutik</option>
<option value="8731">8700 Tvingstruo, Postbutik</option>
<option value="8713">8700 Torsted, Postbutik</option>
<option value="8715">8700 Sundparken, Posthus</option>
<option value="8726">8700 Glud, Postbutik</option>
<option value="8789">8700 Endelave, Posthuse (Minus Bikketnet)</option>
<option value="8700">8700 Horsens, Posthus</option>
<option value="8721">8721 Daugård, Postbutik</option>
<option value="8722">8722 Hedensted, Posthus</option>
<option value="8733">8723 Løsning, Postbutik</option>
<option value="8732">8732 Hovedgård, Postbutik</option>
<option value="8741">8740 Brædstrup, Postbutik</option>
<option value="8751">8751 Gedved, Postbutik</option>
<option value="8752">8752 Østbirk, Postbutik</option>
<option value="8763">8763 Rask Mølle, Postbutik</option>
<option value="8765">8765 Klovborg, Postbutik</option>
<option value="8767">8767 Nørre Snede, Postbutik</option>
<option value="8781">8781 Stenderup, Postbutik</option>
<option value="8783">8783 Hornsyld, Postbutik</option>
<option value="8804">8800 Houlkær, Postbutik</option>
<option value="8805">8800 Viborg, Posthus</option>
<option value="8803">8800 Vestervang, Postbutik</option>
<option value="8813">8800 Mønsted, Postbutik</option>
<option value="8806">8800 Koldingvej, Postbutik</option>
<option value="8833">8830 Tjele, Postbutik</option>
<option value="8834">8830 Hammershøj, Postbutik</option>
<option value="9633">8832 Ulbjerg, Postbutik</option>
<option value="8835">8832 Skals, Postbutik</option>
<option value="8840">8840 Rødkærsbro, Postbutik</option>
<option value="8850">8850 Bjerringbro, Posthus</option>
<option value="8861">8860 Ulstrup, Postbutik</option>
<option value="8871">8870 Langå, Postbutik</option>
<option value="8880">8881 Thorsø, Postbutik</option>
<option value="8882">8882 Fårvang, Postbutik</option>
<option value="8900">8900 Randers, Posthus</option>
<option value="8918">8900 Glentevej, Postbutik</option>
<option value="8925">8900 Harridslev, Postbutik</option>
<option value="8965">8900 Assentoft, Postbutik</option>
<option value="8901">8900 Vorup, Postbutik</option>
<option value="8902">8900 Boulevarden, Posthus</option>
<option value="8951">8950 Ørsted, Postbutik</option>
<option value="8952">8961 Allingåbro, Postbutik</option>
<option value="8963">8963 Auning, Postbutik</option>
<option value="8972">8970 Havndal, Postbutik</option>
<option value="8980">8981 Spentrup, Postbutik</option>
<option value="8983">8983 Gjerlev J, Postbutik</option>
<option value="8984">8983 Øster-Tørslev, Postbutik</option>
<option value="9002">9000 Vejgaard, Posthus</option>
<option value="9005">9000 Hasseris, Postbutik</option>
<option value="9008">9000 Algade, Posthus</option>
<option value="9011">9000 Fyensgade, Postbutik</option>
<option value="9202">9200 Skalborg STORCENTER, Posthus</option>
<option value="9231">9200 Frejlev, Postbutik</option>
<option value="9007">9210 Aalborg Sø Postbutik, Postbutik</option>
<option value="9222">9220 Planetcentret, Postbutik</option>
<option value="9230">9230 Svenstrup J, Postbutik</option>
<option value="9241">9240 Nibe, Postbutik</option>
<option value="9243">9240 Farstrup, Postbutik</option>
<option value="9262">9260 Gistrup, Postbutik</option>
<option value="9261">9260 Fjellerad, Postbutik</option>
<option value="9271">9270 Klarup, Postbutik</option>
<option value="9281">9280 Storvorde, Postbutik</option>
<option value="9282">9280 Guduhmholm, Postbutik</option>
<option value="9283">9280 Mou, Postbutik</option>
<option value="9297">9293 Kongerslev, Postbutik</option>
<option value="9300">9300 Sæby, Postbutik</option>
<option value="9357">9300 Lyngså, Postbutik</option>
<option value="9356">9300 Hørby, Postbutik</option>
<option value="9311">9310 Vodskov, Postbutik</option>
<option value="9361">9310 Vester-Hassing, Postbutik</option>
<option value="9312">9310 Langholt, Postbutik</option>
<option value="9321">9320 Hjallerup, Postbutik</option>
<option value="9359">9320 Klokkerholm, Postbutik</option>
<option value="9353">9330 Præstbro, Postbutik</option>
<option value="9351">9330 Flauenskjold, Postbutik</option>
<option value="9331">9330 Dronninglund, Postbutik</option>
<option value="9339">9330 Agersted, Postbutik</option>
<option value="9340">9340 Asaa, Postbutik</option>
<option value="9352">9352 Dybvad, Postbutik</option>
<option value="9362">9362 Gandrup, Postbutik</option>
<option value="9371">9370 Hals, Postbutik</option>
<option value="9363">9370 Ulsted, Postbutik</option>
<option value="9364">9370 Hou, Postbutik</option>
<option value="9383">9380 Vestbjerg, Postbutik</option>
<option value="9382">9382 Tylstrup, Postbutik</option>
<option value="9400">9400 Nørresundby, Posthus</option>
<option value="9401">9400 Nr. Uttrup Torv, Postbutik</option>
<option value="9429">9430 Vadum, Postbutik</option>
<option value="9431">9430 Nørre-Halne, Postbutik</option>
<option value="9440">9440 Aabybro, Postbutik</option>
<option value="9447">9440 Birkelse, Postbutik</option>
<option value="9468">9460 Skovsgård, Postbutik</option>
<option value="9461">9460 Brobdt, Postbutik</option>
<option value="9485">9480 Løkken, Postbutik</option>
<option value="9484">9480 Vrensted, Postbutik</option>
<option value="9499">9490 Kås, Postbutik</option>
<option value="9491">9490 Pandrup, Postbutik</option>
<option value="9492">9492 Blokhus, Postbutik</option>
<option value="9493">9493 Saltum, Postbutik</option>
<option value="9500">9500 Hobro, Posthus</option>
<option value="9597">9500 Store-Rørbæk, Postbutik</option>
<option value="9509">9510 Arden, Postbutik</option>
<option value="9511">9510 Astrup, Postbutik</option>
<option value="9521">9520 Skørping, Postbutik</option>
<option value="9292">9520 Blenstrup, Postbutik</option>
<option value="9294">9520 Årestrup, Postbutik</option>
<option value="9531">9530 Øster-Hornum, Postbutik</option>
<option value="9532">9530 Støvring, Postbutik</option>
<option value="9542">9541 Suldrup, Postbutik</option>
<option value="9551">9550 Assens, Postbutik</option>
<option value="9552">9550 Mariager, Postbutik</option>
<option value="9561">9560 Hadsund, Postbutik</option>
<option value="9582">9560 Øster-Hurup, Postbutik</option>
<option value="9581">9560 Als, Postbutik</option>
<option value="9573">9560 Veddum, Postbutik</option>
<option value="9574">9574 Bælum, Postbutik</option>
<option value="9577">9575 Terndrup, Postbutik</option>
<option value="9601">9600 Aars , Postbutik</option>
<option value="9609">9600 Hornum, Postbutik</option>
<option value="9611">9610 Nørager, Postbutik</option>
<option value="9512">9610 Haverslev, Postbutik</option>
<option value="9513">9610 Ravnkilde, Postbutik</option>
<option value="9620">9620 Aalestrup, Postbutik</option>
<option value="9622">9620 Simested, Postbutik</option>
<option value="9631">9631 Gedsted, Postbutik</option>
<option value="9634">9632 Bjerregrav, Postbutik</option>
<option value="9641">9640 Farsø, Postbutik</option>
<option value="9653">9640 Ullits, Postbutik</option>
<option value="9657">9640 Vester Hornum, Postbutik</option>
<option value="9654">9640 Hvalpsund, Postbutik</option>
<option value="9671">9670 Løgstør, Postbutik</option>
<option value="9683">9670 Aggersund , Postbutik</option>
<option value="9682">9670 Overlade, Postbutik</option>
<option value="9681">9681 Ranum, Postbutik</option>
<option value="9475">9690 Vester-Thorup, Postbutik</option>
<option value="9690">9690 Fjerritslev, Postbutik</option>
<option value="9700">9700 Brønderslev, Posthus</option>
<option value="9732">9700 Serritslev, Postbutik</option>
<option value="9737">9700 Hallund, Postbutik</option>
<option value="9734">9700 Vester-Hjermitslev, Postbutik</option>
<option value="9740">9740 Jerslev J, Postbutik</option>
<option value="9750">9750 Øster-Vrå, Postbutik</option>
<option value="9762">9760 Vrå, Postbutik</option>
<option value="9840">9800 Lønstrup, Postbutik</option>
<option value="9842">9800 Bjerhby, Postbutik</option>
<option value="9812">9800 Bispensgade, Postbutik</option>
<option value="9811">9800 Højene, Postbutik</option>
<option value="9800">9800 Hjørring, Posthus</option>
<option value="9832">9830 Tårs, Postbutik</option>
<option value="9851">9850 Hirtshals, Postbutik</option>
<option value="9860">9850 Tornby, Postbutik</option>
<option value="9883">9870 Lendum, Postbutik</option>
<option value="9871">9870 Sindal, Postbutik</option>
<option value="9882">9881 Tversted, Postbutik</option>
<option value="9884">9881 Bindslev, Postbutik</option>
<option value="9896">9900 Gærum, Postbutik</option>
<option value="9900">9900 Frederikshavn, Posthus</option>
<option value="9902">9900 Bangsbo, Postbutik</option>
<option value="9903">9900 Olfert Ffschersvej, Postbutik</option>
<option value="9904">9900 Abildgårdsvej, Postbutik</option>
<option value="9898">9900 Elling, Postbutik</option>
<option value="9950">9940 Vesterø HAVN, Postbutik</option>
<option value="9960">9940 Østerby HAVN, Postbutik</option>
<option value="9941">9940 Byrum, Postbutik</option>
<option value="9970">9970 Strandby, Postbutik</option>
<option value="9981">9981 Jerup, Postbutik</option>
<option value="9983">9982 Ålbæk, Postbutik</option>
<option value="9991">9990 Skagen, Postbutik</option>
</select></td>
              </tr>
              <tr id="trRecPo3">
                <td> Postopkr. beløb:</td>
                <td><label>
                  <input value="" style="width:80px;text-align:right;" name="recPoValue" id="recPoValue" maxlength="12" />
                  kr. </label></td>
              </tr>
              <tr>
                <td> Her af porto:</td>
                <td><label>
                  <input value="" style="width:80px;text-align:right;" name="porto" id="porto" maxlength="12" />
                  kr. </label> <input style="width:auto;height:auto;" type="checkbox" name="ub" id="ub" /> UB</td>
              </tr>
              <tr id="trReturPakkeRadio1" style="display:none">
                <td>Med afhentning</td>
                <td><!-- TODO Unknown value, unknown send value --><input onclick="changeReturPakkeRadio(1)" style="width:auto;height:auto;" name="returPakkeRadio" id="returPakkeRadio1" value="1" type="checkbox" /></td>
              </tr>
              <tr id="trReturPakkeRadio2" style="display:none">
                <td>Uden afhentning</td>
                <td><!-- TODO Unknown value, unknown send value --><input onclick="changeReturPakkeRadio(2)" style="width:auto;height:auto;" name="returPakkeRadio" id="returPakkeRadio2" value="2" type="checkbox" /></td>
              </tr>
              <tr id="trReturPakkeRadio3" style="display:none">
                <td>Udskriv label lokalt</td>
                <td><!-- TODO Unknown value, unknown send value --><input onclick="changeReturPakkeRadio(3)" style="width:auto;height:auto;" name="returPakkeRadio" id="returPakkeRadio3" value="3" type="checkbox" /></td>
              </tr>
              <tr>
                <td> Forsigtig:</td>
                <td><input style="width:auto;height:auto;" name="ss1" id="ss1" value="Forsigtig" type="checkbox" onchange="calc()" onkeyup="calc()" /></td>
              </tr>
              <tr id="trVolume">
                <td> Volume:</td>
                <td><input style="width:auto;height:auto;" name="ss2" id="ss2" value="Volume" type="checkbox" onchange="calc()" onkeyup="calc()" /></td>
              </tr>
              <tr id="trExpress">
                <td> Lørdags express:</td>
                <td><input style="width:auto;height:auto;" name="ss46" id="ss46" value="Express" type="checkbox" onchange="calc()" onkeyup="calc()" /></td>
              </tr>
              <tr>
                <td> Værdi:</td>
                <td><label> <input name="ss5amount" id="ss5amount" value="000" style="text-align:right;" onchange="calc()" onkeyup="calc()" /> 
                kr. <br />
                Hvis netto pris overstiger 4600,-</label></td>
              </tr>
              <tr style="display:none">
                <td><label><input style="width:auto;height:auto;" name="emailChecked" id="emailChecked" value="emailChecked" type="checkbox" onclick="clickEmailChecked(this.checked)" /> Send email til adressen:</label></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td colspan="2"><textarea name="emailTxt" id="emailTxt" style="width:365px; display:none;" rows="7" cols="" title="Skriv e-mailteksten her - max. 2000 tegn"></textarea></td>
              </tr>
              <tr>
                <td><img id="volumeIcon" src="http://postdanmark.dk/pfs/grafik/ss26.gif" width="20" height="15" alt="Volume pakke" title="Volume pakke" style="display:none" /></td>
              </tr>
            </tbody>
          </table>
          </td>
      </tr>
    </tbody>
  </table>
  <div style="display:none"><strong>Kolli:</strong>
  <input name="c_no" id="c_no" value="1" type="hidden" />
  <table>
    <thead style="background-color:#999999">
      <tr>
        <td>Nr.</td>
        <td>Bruttovægt</td>
        <td>Bemærkninger</td>
      </tr>
    </thead>
    <tfoot><tr><td colspan="3"><input type="button" value="Tilføj kolli" style="width:100%" onclick="addrow()" /></td></tr></tfoot>
    <tbody id="kolliTable"><tr><td>1.</td><td><input style="width:42px" maxlength="5" name="c_0_w" size="2" /><!-- Waight -->  kg </td><td><input name="c_0_rem" /><!-- name --></td></tr></tbody>
  </table></div>
</form>
</div></body>
</html>
