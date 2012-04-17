<?php
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
require_once '../inc/config.php';


function removewidth($text)
{
    global $mysqli;
    return $mysqli->real_escape_string(
        preg_replace('/(<img[^>]+)\swidth="[0-9]+"([^>]+>)/iu', '$1$2', $text)
    );
}

//Open database
$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

/*
function unhtmlentitiesUtf8($string)
{
    // replace numeric entities
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
    // replace literal entities
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    // changing translation table to UTF-8
    foreach ( $trans_tbl as $key => $value ) {
        $trans_tbl[$key] = utf8_encode($value);
    }
    return strtr($string, $trans_tbl);
}
*/

$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);
$sider = $mysqli->fetchArray("SELECT id, text, beskrivelse FROM `sider` WHERE text LIKE '%<img%width=%' OR beskrivelse LIKE '%<img%width=%'");

foreach ($sider as $key => $side) {
    $mysqli->query("UPDATE `sider` SET `text` = '".removewidth($side['text'])."', `beskrivelse` = '".removewidth($side['beskrivelse'])."' WHERE `id` = ".$side['id']." LIMIT 1");
    unset($sider[$key]);
    echo $key . ' - ';
}
$sider = $mysqli->fetchArray("SELECT id, text FROM `special` WHERE text LIKE '%<img%width=%'");

foreach ($sider as $key => $side) {
    $mysqli->query("UPDATE `special` SET `text` = '".removewidth($side['text'])."' WHERE `id` = ".$side['id']." LIMIT 1");
    unset($sider[$key]);
    echo $key . ' - ';
}
?>Done!
</body>
</html>
