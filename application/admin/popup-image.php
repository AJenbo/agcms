<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/logon.php';

$file = db()->fetchOne(
    "
    SELECT alt, width, height
    FROM `files`
    WHERE `path` = '" . $_GET['url'] . "'"
);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title><?php
echo $_GET['url'];
?></title>
<style type="text/css"><!--
body {margin:0}
--></style></head>

<body><div><img src="<?php
echo $_GET['url'];
?>" alt="<?php
echo $file['alt'];
?>" title="<?php
echo $file['alt'];
?>" width="<?php
echo $file['width'];
?>" height="<?php
echo $file['height'];
?>" /></div></body></html>
