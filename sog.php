<?php
/**
 * Print an OpenSearch xml file
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

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

