<?php

require_once '../inc/config.php';
require_once '../inc/mysqli.php';
$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

if($_GET['id'])
    $faktura = $mysqli->fetchArray("SELECT `text` FROM `sider` WHERE `id` = ".$_GET['id']);

echo 'Før:
';
echo $faktura[0]['text'];



require_once 'inc/htmlpurifier/HTMLPurifier.auto.php';


$config = HTMLPurifier_Config::createDefault();
//$config->set('Filter.YouTube', true);
$config->set('HTML.SafeObject', true);
$config->set('HTML.SafeEmbed', true);
$config->set('HTML.Doctype', 'XHTML 1.0 Strict'); // replace with your doctype
$purifier = new HTMLPurifier($config);

$clean_html = $purifier->purify($faktura[0]['text']);
echo '
Efter:
'.$clean_html;

