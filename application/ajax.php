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

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain('agcms', $_SERVER['DOCUMENT_ROOT'] . '/theme/locale');
bind_textdomain_codeset('agcms', 'UTF-8');
textdomain('agcms');
mb_language('uni');
mb_detect_order('UTF-8, ISO-8859-1');
mb_internal_encoding('UTF-8');

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/sajax.php';

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

/**
 * Get the html for content bellonging to a category
 *
 * @param int  $id   Id of activ category
 * @param bool $sort What column to sort by
 *
 * @return array Apropriate for handeling with javascript function inject_html()
 */
function getKat(int $id, bool $sort): array
{
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
    $bind = db()->fetchArray(
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
    $bind = arrayNatsort($bind, 'id', $sort);

    $kat = @$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']];
    $name = $kat['navn'];
    if (!$name) {
        $kat = db()->fetchArray(
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
        'id' => 'kat' . $GLOBALS['generatedcontent']['activmenu'],
        'html' => katHTML($bind, $name, $GLOBALS['generatedcontent']['activmenu'])
    );
}

sajax_export(
    array('name' => 'getTable', 'method' => 'GET'),
    array('name' => 'getKat', 'method' => 'GET'),
    array('name' => 'getAddress', 'method' => 'GET')
);
//	$sajax_remote_uri = "/ajax.php";
sajax_handle_client_request();
