<?php

use AGCMS\Render;
use AGCMS\Entity\File;

require_once __DIR__ . '/logon.php';

Render::output('admin-phpup-swf', ['file' => File::getByPath($_GET['url'])]);
