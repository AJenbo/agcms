<?php
/**
 * Set default values and declare earlie common functions
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

define('ENT_XHTML', 32);

mb_language("uni");
mb_detect_order("UTF-8, ISO-8859-1");
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Copenhagen');

/**
 * Set Last-Modified and ETag http headers
 * and use cache if no updates since last visit
 *
 * @param int $timestamp Unix time stamp of last update to content
 *
 * @return null
 */
function doConditionalGet($timestamp)
{
    // A PHP implementation of conditional get, see
    // http://fishbowl.pastiche.org/archives/001132.html
    $last_modified = mb_substr(date('r', $timestamp), 0, -5).'GMT';
    $etag = '"'.$timestamp.'"';
    // Send the headers

    header("Cache-Control: max-age=0, must-revalidate");    // HTTP/1.1
    header("Pragma: no-cache");    // HTTP/1.0
    header('Last-Modified: '.$last_modified);
    header('ETag: '.$etag);
    // See if the client has provided the required headers
    $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
        stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) :
        false;
    $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
        stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) :
        false;
    if (!$if_modified_since && !$if_none_match) {
        return;
    }
    // At least one of the headers is there - check them
    if ($if_none_match && $if_none_match != $etag) {
        return; // etag is there but doesn't match
    }
    if ($if_modified_since && $if_modified_since != $last_modified) {
        return; // if-modified-since is there but doesn't match
    }

    // Nothing has changed since their last request - serve a 304 and exit
    apache_setenv('no-gzip', 1);
    ini_set('zlib.output_compression', '0');
    header("HTTP/1.1 304 Not Modified", true, 304);
    die();
}

