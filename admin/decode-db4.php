<?php
/**
 * Apply HTMLPurifier to all content, as part of the upgrade to using it
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
<style type="text/css"><!--
* {
    font-size:14px;
}
--></style>
</head>

<body><?php

require_once '../inc/mysqli.php';


/**
 * Use HTMLPurifier to clean HTML-code, preserves youtube videos
 *
 * @param string $string Sting to clean
 *
 * @return string Cleaned stirng
 **/
function purifyHTML($string) {
    require_once 'inc/htmlpurifier/HTMLPurifier.auto.php';

    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^http://www.youtube.com/embed/%u');
    $config->set('HTML.SafeObject', true);
    $config->set('Output.FlashCompat', true);
    $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
    $purifier = new HTMLPurifier($config);

    return $purifier->purify($string);
}

//Open database
$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

$mysqli = new Simple_Mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

$rows = $mysqli->fetchArray("SELECT id, text, beskrivelse FROM `sider` WHERE text != '' OR beskrivelse != '' ORDER BY id");
foreach ($rows as $row) {
    $mysqli->query("UPDATE `sider` SET `text` = '".addcslashes(purifyHTML($row['text']), "'\\")."', `beskrivelse` = '".addcslashes(purifyHTML($row['beskrivelse']), "'\\")."' WHERE `id` = ".$row['id']." LIMIT 1");
    echo $row['id'] . ' - ';
}
unset($rows);

$rows = $mysqli->fetchArray("SELECT id, text FROM `newsmails` WHERE text != '' ORDER BY id");
foreach ($rows as $row) {
    $mysqli->query("UPDATE `newsmails` SET `text` = '".addcslashes(purifyHTML($row['text']), "'\\")."' WHERE `id` = ".$row['id']." LIMIT 1");
    echo $row['id'] . ' - ';
}
unset($rows);

$rows = $mysqli->fetchArray("SELECT id, text FROM `krav` WHERE text != '' ORDER BY id");
foreach ($rows as $row) {
    $mysqli->query("UPDATE `krav` SET `text` = '".addcslashes(purifyHTML($row['text']), "'\\")."' WHERE `id` = ".$row['id']." LIMIT 1");
    echo $row['id'] . ' - ';
}
unset($rows);

$rows = $mysqli->fetchArray("SELECT id, text FROM `special` WHERE text != '' ORDER BY id");
foreach ($rows as $row) {
    $mysqli->query("UPDATE `special` SET `text` = '".addcslashes(purifyHTML($row['text']), "'\\")."' WHERE `id` = ".$row['id']." LIMIT 1");
    echo $row['id'] . ' - ';
}
unset($rows);

?>Done!
</body>
</html>
