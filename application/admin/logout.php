<?php

require_once __DIR__ . '/logon.php';

session_destroy();

redirect('/admin/');
