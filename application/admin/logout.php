<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/logon.php';

session_destroy();

redirect('./', 303);
