<?php
require_once '../inc/config.php';
require_once '../inc/mysqli.php';

//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
$krav = $mysqli->fetch_array("SELECT * FROM krav WHERE id = ".$_GET['id']);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style type="text/css">
* {
    font-family:"Times New Roman", Times, serif;
    font-size:13px;
}
h1 {
    color:#333333;
    font-family:"Times New Roman",Times,serif;
    font-size:17px;
    font-weight:bold;
    margin:0;
}
body {
    background-color:#FFFFFF;
}
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $krav[0]['navn'] ?></title>
</head>

<body><h1><?php echo $krav[0]['navn'] ?></h1><div id="text"><?php echo $krav[0]['text'] ?></div></body>
</html>
