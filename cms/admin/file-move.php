﻿<?php
require_once '../inc/sajax.php'; 
require_once '../inc/config.php';
require_once '../inc/mysqli.php';
require_once 'inc/file-functions.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

sajax_init();
//$sajax_debug_mode = 1;
sajax_export(
	array('name' => 'listdirs', 'method' => 'GET')
);
//$sajax_remote_uri = "/ajax.php";
sajax_handle_client_request();

$pathinfo = pathinfo($_GET['path']);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Flyt fil</title>
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="javascript/explorer.js"></script>
<script type="text/javascript"><!--
<?php sajax_show_javascript(); ?>

var global_dir = '';

function movefile(dir) {
	global_dir = dir;
//TOdo issues with ie
	window.opener.document.getElementById('loading').style.display = '';
	window.opener.x_renamefile(<?php echo $_GET['id']; ?>,  '<?php echo $_GET['path']; ?>', dir,  '',        0,       movefile_r);
}

//TODO Closes on mouse out???
function movefile_r(data) {

	window.opener.document.getElementById('loading').style.display = 'none';
	
	if(data['path'] == '<?php echo $_GET['path']; ?>')
		return;

	if(data['error']) {
		alert(data['error']);
		//Somthing prevents the FF from closing when hovering the link so we remove it
		document.body.innerHTML = '';
		window.close();
	} else if(data['yesno']) {
		if(eval(confirm(data['yesno']))==true){
			document.getElementById('loading').style.display = '';
			window.opener.x_renamefile(<?php echo $_GET['id']; ?>, '<?php echo $_GET['path']; ?>', global_dir, '', 1, movefile_r);
		} else {
			//Somthing prevents the FF from closing when hovering the link so we remove it
			document.body.innerHTML = '';
			window.close();
		}
	} else {
		window.opener.document.getElementById('files').removeChild(window.opener.document.getElementById('tilebox'+data['id']));
		//Somthing prevents FF from closing when hovering the link so we remove it
		document.body.innerHTML = '';
		window.close();
	}
}
--></script><style type="text/css"><!--
* {
	font-family:Verdana, Arial, Helvetica, sans-serif;
}
body {
	margin:18px;
	font-size:10px;
}
#dir {
	overflow:auto;
	overflow-y:scroll;
	padding:5px;
	background-color:Window;
	font-size:11px;
	border:2px inset;
	position:absolute;
	top:60px;
	left:19px;
	right:19px;
	bottom:51px;
}
#dir * {
	vertical-align:middle;
}
#dir div div {
	margin-left:8px
}
#dir a {
	white-space:nowrap;
	cursor:pointer;
}
#dir a:hover {
	text-decoration:underline;
}
#dir a:hover img {
	text-decoration:none;
}
--></style>
</head>

<body style="background-color:ThreeDFace; border:0px #FF0000 none;"><p>Klik på den mappe, hvor du vil flytte filen '<?php echo $pathinfo['filename']; ?>' til.</p>
<img id="loading" style="float:right; cursor:default; display:none; padding:4px" src="images/loading.gif" width="16" height="16" alt="Loading" />
<div id="dir"><?php
/*
$listdirs = listdirs('/images', 1);
echo $listdirs['html'];
*/
?>
<div id="dir_.images"><img<?php if(@$_COOKIE['/images']) { echo ' style="display:none"'; }
?> src="images/+.gif" onclick="dir_expand(this, 1);" height="16" width="16" alt="" /><img<?php
if(!@$_COOKIE['/images']) { echo ' style="display:none"'; }
?> src="images/-.gif" onclick="dir_contract(this);" height="16" width="16" alt="" /><a onclick="movefile('/images')"><img src="images/folder.png" height="16" width="16" alt="" /> Billeder </a>
<div><?php
if(@$_COOKIE['/images']) {
	$listdirs = listdirs('/images', 1);
	echo $listdirs['html'];
}
?></div></div>
<div id="dir_.files"><img<?php if(@$_COOKIE['/files']) { echo ' style="display:none"'; } ?> src="images/+.gif" onclick="dir_expand(this, 1);" height="16" width="16" alt="" /><img<?php if(!@$_COOKIE['/files']) { echo ' style="display:none"'; } ?> src="images/-.gif" onclick="dir_contract(this);" height="16" width="16" alt="" /><a onclick="movefile('/files')"><img src="images/folder.png" height="16" width="16" alt="" /> Filer </a>
<div><?php
if(@$_COOKIE['/files']) {
	$listdirs = listdirs('/files', 1);
	echo $listdirs['html'];
}
?></div></div></div><p style="bottom:18px; position:absolute;">Klik på plustegnet ovenfor for at se undermapper.</p></body></html>