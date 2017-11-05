<?php

require_once __DIR__ . '/logon.php';

session_start();
session_destroy();

redirect('/admin/');
