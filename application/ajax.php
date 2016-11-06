<?php
/**
 * Handle AJAX requests
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

//If the database is older then the users cache, send 304 not modified
//WARNING: this results in the site not updating if new files are included later,
//the remedy is to update the database when new cms files are added.
$tables = db()->fetchArray("SHOW TABLE STATUS");
$updatetime = 0;
foreach ($tables as $table) {
    $updatetime = max($updatetime, strtotime($table['Update_time']));
}

$included_files = get_included_files();
foreach ($included_files as $filename) {
    $filetime = filemtime($filename);
    $filetime = max(@$GLOBALS['cache']['updatetime']['filemtime'], $filetime);
    $GLOBALS['cache']['updatetime']['filemtime'] = $filetime;
}
foreach ($GLOBALS['cache']['updatetime'] as $time) {
    $updatetime = max($updatetime, $time);
}
if ($updatetime < 1) {
    $updatetime = time();
}

doConditionalGet($updatetime);
$updatetime = 0;

SAJAX::export(
    [
        'getTable'   => ['method' => 'GET'],
        'getKat'     => ['method' => 'GET'],
        'getAddress' => ['method' => 'GET'],
    ]
);
SAJAX::handleClientRequest();
