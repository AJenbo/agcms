<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
require_once 'inc/image-functions.php';
doConditionalGet(filemtime($_SERVER['DOCUMENT_ROOT'].$_GET['path']));
generateImage(
    $_GET['path'],
    $_GET['cropX'] ?? 0,
    $_GET['cropY'] ?? 0,
    $_GET['cropW'] ?? 0,
    $_GET['cropH'] ?? 0,
    $_GET['maxW'] ?? 0,
    $_GET['maxH'] ?? 0,
    $_GET['flip'] ?? 0,
    $_GET['rotate'] ?? 0,
    null
);
