<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Opret ny bruger</title>
</head>
<body><?php
if(!$_POST['name'] || !$_POST['password'] || !$_POST['fullname'])
	die("Du skal ud fylde alle fleter.");

if($_POST['password'] != $_POST['password2'])
	die("Koden blev ikke indtasted ens prøv igen.");

require_once $_SERVER['DOCUMENT_ROOT'].'/inc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/inc/mysqli.php';

//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

if($mysqli->fetch_array('SELECT id FROM users WHERE name = \''.addcslashes($_POST['name'], "'").'\''))
	die("Bruger navnet er allered taget.");

$mysqli->query('INSERT INTO users SET name = \''.addcslashes($_POST['name'], "'").'\', password = \''.addcslashes(crypt($_POST['password']), "'").'\', fullname = \''.addcslashes($_POST['fullname'], "'").'\'');

$mysqli->close();
unset($mysqli);

mail('mail@jof.dk', utf8_decode('Nybryger på '.$GLOBALS['_config']['site_name']), utf8_decode('Nybryger på '.$GLOBALS['_config']['site_name']));
?>
Din brugerkonto er nu oprettet. I løbet af kort tid vil du kunne logge ind på administrationsiden og bruge den.
</body>
</html>
