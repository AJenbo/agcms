<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
require_once 'inc/image-functions.php';
doConditionalGet(filemtime($_SERVER['DOCUMENT_ROOT'].$_GET['path']));
generateImage($_GET['path'], @$_GET['cropX'], @$_GET['cropY'], @$_GET['cropW'], @$_GET['cropH'], @$_GET['maxW'], @$_GET['maxH'], @$_GET['flip'], @$_GET['rotate'], NULL);

