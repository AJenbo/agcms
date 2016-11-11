<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/logon.php';

session_destroy();

redirect('/admin/');
