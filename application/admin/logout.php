<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/logon.php';

session_destroy();

ini_set('zlib.output_compression', '0');
header('Location: ./', 303);
