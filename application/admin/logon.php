<?php


require_once __DIR__ . '/../inc/functions.php';
bootStrap();

// Load admin functions
require_once _ROOT_ . '/admin/inc/functions.php';

checkUserLoggedIn();
curentUser()->setLastLogin(time())->save();
