<?php

use AGCMS\Render;

/**
 * Print an OpenSearch xml file
 */

require_once __DIR__ . '/inc/Bootstrap.php';

header('Content-Type: application/opensearchdescription+xml');
Render::sendCacheHeader(Render::getUpdateTime(false));
echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
    <ShortName><?php echo Config::get('site_name'); ?></ShortName>
    <Description><?php
    printf(_('Find in %s'), Config::get('site_name'));
    ?></Description><?php
    echo '<Url type="text/html" template="' .Config::get('base_url')
    .'/?q={searchTerms}" />';
?></OpenSearchDescription>
