<?php
/**
 * Handle SEO frindly urls
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

session_start();
Render::sendCacheHeader();
Render::doRouting();

header('Status: 200', true, 200);
header('HTTP/1.1 200 OK', true, 200);
Render::outputPage();
