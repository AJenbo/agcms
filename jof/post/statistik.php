<?php
	require_once("snoopy/snoopy.class.php");
	require_once("../inc/sajax.php");
	
	function getToken() {
		require("config.php");
		$snoopy = new Snoopy;
		
		$submit_url = "http://www.postdanmark.dk/pfs/PfsLoginServlet";
		
		$submit_vars['gotoURL'] = "http://www.postdanmark.dk/pfs/PfsLoginServlet";
		$submit_vars['clientID'] = $clientID;
		$submit_vars['userID'] = "admin";
		$submit_vars['password'] = $password;
			
		$snoopy->submit($submit_url, $submit_vars);
		preg_match('/token=([a-z0-9]+)&/i', $snoopy->results, $matches);
		$matches[0] = substr($matches[0], 6);
		return substr($matches[0], 0, strlen($matches[0])-1);
		//alt methode
		return preg_replace('/.*token=([a-z0-9]+)&.*/ism','$1',strip_tags($snoopy->results));
	}
	
	function dayListReport() {
		require("config.php");
		$snoopy = new Snoopy;
		
		$submit_url = "http://www.postdanmark.dk/pfs/pfsDayListReport.jsp";
		
		$submit_vars['token'] = getToken();
		$submit_vars['programID'] = 'pfs';
		$submit_vars['clientID'] = $clientID;
		$submit_vars['userID'] = 'admin';
		$submit_vars['sessionID'] = '0';
		$submit_vars['accessCode'] = 'UC';
		$submit_vars['exTime'] = '120';
		$submit_vars['startDate'] = '14.02.2008';
		$submit_vars['stopDate'] = date("d.m.Y");
		$submit_vars['paramOrderID'] = '';
		$submit_vars['paramRecID'] = '';
		$submit_vars['paramRecName1'] = '';
			
		$snoopy->submit($submit_url, $submit_vars);
	
		return $snoopy->results;
	}
	
	function dayListDetails() {
		require("config.php");
		$snoopy = new Snoopy;
		
		$submit_url = "http://www.postdanmark.dk/pfs/pfsDayListDetails.jsp";
		
		$submit_vars['token'] = getToken();
		$submit_vars['consignmentID'] = $_GET['consignmentID'];
		$submit_vars['kolliNo'] = '1';
		$submit_vars['programID'] = 'pfs';
		$submit_vars['clientID'] = $clientID;
		$submit_vars['userID'] = 'admin';
		$submit_vars['sessionID'] = '0';
		$submit_vars['accessCode'] = 'UC';
		$submit_vars['exTime'] = '120';
		$submit_vars['startDate'] = '14.02.2008';
		$submit_vars['stopDate'] = '14.02.2008';
		$submit_vars['paramOrderID'] = '';
		$submit_vars['paramRecID'] = '';
		$submit_vars['paramRecName1'] = '';
		$submit_vars['uniqueID'] = 'on';
			
		$snoopy->submit($submit_url, $submit_vars);
	
		return $snoopy->results;
	}
	
//	$sajax_debug_mode = 1;
//	$sajax_remote_uri = "/ajax.php";
	sajax_export("changeUser","getPDFURL");
	sajax_handle_client_request();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Indenlands Pakker - Post Anders</title>
<link href="style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
.lille {
	font-size:x-small;
}
.TabelOverskrifter {
	font-weight:bold;
}
#container {
	width:650px;
	margin:10px 0 0 180px;
}
#container a {
	color:#cc0000;
	text-decoration:underline;
}
</style>
<script type="text/javascript" src="/javascript/json2.stringify.js"></script> 
<script type="text/javascript" src="/javascript/json_stringify.js"></script>
<script type="text/javascript" src="/javascript/json_parse_state.js"></script> 
<script type="text/javascript" src="/javascript/sajax.js"></script> 
<script type="text/javascript"><!--
function handleClick(barCode) {
	window.open("http://www.postdanmark.dk/tracktrace/TrackTrace.do?i_stregkode=" + barCode + "&i_lang=IND", "ttWindow", "toolbar=no,location=yes,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=800,height=600");
}
//-->
</script>
</head>
<body><div id="loading" style="display:none;"><img src="load.gif" width="228" height="144" alt="" title="Komunikere med post danmark..." /></div>
<?php
include('menu.html');
?>
<base href="http://www.postdanmark.dk/" />
<div id="container">
  <img height="50" alt="" src="http://www.postdanmark.dk/pfs/grafik/pakker.gif" style="float:right" /><h2 style="padding:25px 0 0 0; margin:0">Statistik</h2>
  <hr />
<?php
	if(!$_GET['consignmentID'])
		echo utf8_encode(preg_replace(array('#\s+#','#<html.+?>#i','#</html>#i','#</head>#i','#</body>#i','#</form>#i','#<form.+?>#i','#<input.+?>#i','#<link.+?>#i','#<head.+?</head>#i','#<script.+?</script>#i','#<style.+?</style>#i','#HTTP.+?Close#i'),' ',preg_replace('#<input.+?setConsignment.+?([0-9]+).+?>#i','<a href="?consignmentID=$1">Vis detaljer</a>',dayListReport())));
	else
		echo utf8_encode(preg_replace(array('#\s+#','#<html.+?>#i','#</html>#i','#</head>#i','#</body>#i','#<link.+?>#i','#<head.+?</head>#i','#<script.+?</script>#i','#<style.+?</style>#i','#HTTP.+?Close#i'),' ',dayListDetails()));
?></div>
</body>
</html>
