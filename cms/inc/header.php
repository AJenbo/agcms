<?php
mb_language("uni");
mb_detect_order("UTF-8, ISO-8859-1");
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Copenhagen');

//Optimize trafic by serving proper http header
function doConditionalGet($timestamp) {
    // A PHP implementation of conditional get, see 
    // http://fishbowl.pastiche.org/archives/001132.html
    $last_modified = mb_substr(date('r', $timestamp), 0, -5).'GMT';
    $etag = '"'.$timestamp.'"';
    // Send the headers
	
	header("Cache-Control: max-age=0, must-revalidate");    // HTTP/1.1 
	header("Pragma: no-cache");                            // HTTP/1.0 
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
    header('HTTP/1.0 304 Not Modified');
	ini_set ('zlib.output_compression', '0');
    die();
}
?>