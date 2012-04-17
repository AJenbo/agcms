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

require_once 'inc/sajax.php';
require_once 'inc/header.php';
require_once 'inc/functions.php';
require_once 'inc/config.php';
require_once 'inc/mysqli.php';
require_once 'inc/getaddress.php';

$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

//If the database is older then the users cache, send 304 not modified
//WARNING: this results in the site not updating if new files are included later,
//the remedy is to update the database when new cms files are added.
$tables = $mysqli->fetchArray("SHOW TABLE STATUS");
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

/**
 * Get the html for content bellonging to a category
 *
 * @param int  $id   Id of activ category
 * @param bool $sort What column to sort by
 *
 * @return array Apropriate for handeling with javascript function inject_html()
 */
function getKat($id, $sort)
{
    global $mysqli;
    include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/liste.php';
    $GLOBALS['generatedcontent']['activmenu'] = $id;

    //check browser cache
    $updatetime = 0;
    $included_files = get_included_files();
    foreach ($included_files as $filename) {
        $filemtime = filemtime($filename);
        $filemtime = max($GLOBALS['cache']['updatetime']['filemtime'], $filemtime);
        $GLOBALS['cache']['updatetime']['filemtime'] = $filemtime;
    }
    foreach ($GLOBALS['cache']['updatetime'] as $time) {
        $updatetime = max($updatetime, $time);
    }
    if ($updatetime < 1) {
        $updatetime = time();
    }

    doConditionalGet($updatetime);

    //Get pages list
    $bind = $mysqli->fetchArray(
        "
        SELECT sider.id,
            sider.navn,
            sider.burde,
            sider.fra,
            sider.pris,
            sider.for,
            sider.varenr
        FROM bind
        JOIN sider ON bind.side = sider.id
        WHERE bind.kat = " . $GLOBALS['generatedcontent']['activmenu'] . "
        ORDER BY sider." . $sort . " ASC
        "
    );
    $bind = array_natsort($bind, 'id', $sort);

    $kat = @$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']];
    $name = $kat['navn'];
    if (!$name) {
        $kat = $mysqli->fetchArray(
            "
            SELECT navn, vis
            FROM kat
            WHERE id = " . $GLOBALS['generatedcontent']['activmenu'] . "
            LIMIT 1
            "
        );
        $name = $kat[0]['navn'];
    }

    return array(
        "id" => 'kat' . $GLOBALS['generatedcontent']['activmenu'],
        "html" => kat_html($bind, $name)
    );
}

sajax_export(
    array('name' => 'get_table', 'method' => 'GET'),
    array('name' => 'getKat', 'method' => 'GET'),
    array('name' => 'getAddress', 'method' => 'GET')
);
//	$sajax_remote_uri = "/ajax.php";
sajax_handle_client_request();

