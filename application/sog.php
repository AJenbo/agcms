<?php

use AGCMS\Render;
use AGCMS\Config;

/**
 * Print an OpenSearch xml file
 */

require_once __DIR__ . '/inc/Bootstrap.php';

Render::sendCacheHeader(Render::getUpdateTime(false));
$data = [
    'shortName' => Config::get('site_name'),
    'description' => sprintf(_('Find in %s'), Config::get('site_name')),
    'url' => Config::get('base_url') . '/?q={searchTerms}',
];

header('Content-Type: application/opensearchdescription+xml');
Render::output('search', $data);
