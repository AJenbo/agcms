<?php

use AGCMS\Render;
use AGCMS\Entity\File;

require_once __DIR__ . '/logon.php';

Render::output('admin-phpup-audio', ['file' => File::getByPath(request()->get('url'))]);
