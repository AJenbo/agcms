<?php

use AGCMS\Render;

/**
 * Handle SEO frindly urls.
 */
require_once __DIR__ . '/inc/Bootstrap.php';

session_start();
Render::sendCacheHeader();
Render::doRouting();

header('Status: 200', true, 200);
header('HTTP/1.1 200 OK', true, 200);
Render::outputPage();
