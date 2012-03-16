<?php
require_once 'inc/header.php';
header("Content-Type: application/rss+xml");
doConditionalGet(filemtime($_SERVER['SCRIPT_FILENAME']));
echo '<?xml version="1.0" encoding="utf-8"?>';
require_once 'inc/config.php';
?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
    <ShortName><?php echo $GLOBALS['_config']['site_name'] ?></ShortName>
    <Description><?php
    printf(_('Find in %s'), $GLOBALS['_config']['site_name']);
    ?></Description><?php
    echo '<Url type="text/html" template="' .$GLOBALS['_config']['base_url']
    .'/?q={searchTerms}" />';
?></OpenSearchDescription>

