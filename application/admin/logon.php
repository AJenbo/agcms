<?php

use AGCMS\Entity\User;
use AGCMS\ORM;

require_once __DIR__ . '/../inc/functions.php';
bootStrap();

// Load admin functions
require_once _ROOT_ . '/admin/inc/functions.php';

//access
//0:ny, ingen ratigheder.
//1:supper admin.
//2:admin.
//3:klader.
//4:gaest, ikke gemme.

session_start();

checkUserLoggedIn();

ORM::getOne(User::class, $_SESSION['_user']['id'])->setLastLogin(time())->save();
