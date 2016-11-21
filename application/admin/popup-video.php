<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/logon.php';

$file = File::getByPath($_GET['url']);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php
echo $_GET['url'];
?></title>
<style type="text/css"><!--
* {
    margin:0;
}
--></style>
</head>

<body><embed src="<?php
echo xhtmlEsc($file->getPath());
?>" width="<?php
echo $file->getWidth();
?>" height="<?php
echo $file->getHeight();
?>" /></body>
</html>
