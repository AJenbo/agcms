<?php
/**
 * Functinos for manintaning and optimizing the database
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once "inc/config.php";
require_once "inc/mysqli.php";
require_once 'inc/sajax.php';

//Open database
$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

/**
 * Optimize all tables
 *
 * @return string Always empty
 */
function optimizeTables()
{
    global $mysqli;

    $tables = $mysqli->fetchArray("SHOW TABLE STATUS");
    foreach ($tables as $table) {
        $mysqli->query("OPTIMIZE TABLE `" . $table['Name'] . "`");
    }
    return '';
}

/**
 * Remove newletter submissions that are missing vital information
 *
 * @return string Always empty
 */
function removeBadSubmisions()
{
    global $mysqli;

    $mysqli->query(
        "
        DELETE FROM `email`
        WHERE `email` = ''
          AND `adresse` = ''
          AND `tlf1` = ''
          AND `tlf2` = '';
        "
    );

    //return $mysqli->affected_rows;
    return '';
}

/**
 * Delete bindings where either page or category is missing
 *
 * @return string Always empty
 */
function removeBadBindings()
{
    global $mysqli;

    $mysqli->query(
        "
        DELETE FROM `bind`
        WHERE (kat != 0 AND kat != -1
             AND NOT EXISTS (SELECT id FROM kat   WHERE id = bind.kat)
            ) OR NOT EXISTS (SELECT id FROM sider WHERE id = bind.side);
        "
    );

    //return $mysqli->affected_rows;
    return '';
}

/**
 * Remove bad tilbehor bindings
 *
 * @return string Always empty
 */
function removeBadAccessories()
{
    global $mysqli;

    $mysqli->query(
        "
        DELETE FROM `tilbehor`
        WHERE NOT EXISTS (SELECT id FROM sider WHERE tilbehor.side)
           OR NOT EXISTS (SELECT id FROM sider WHERE tilbehor.tilbehor);
        "
    );

    //return $mysqli->affected_rows;
    return '';
}

/**
 * Remove enteries for files that do no longer exist
 *
 * @return string Always empty
 */
function removeNoneExistingFiles()
{
    global $mysqli;
    $files = $mysqli->fetchArray('SELECT id, path FROM `files`');

    $deleted = 0;
    foreach ($files as $files) {
        if (!is_file($_SERVER['DOCUMENT_ROOT'].$files['path'])) {
            $mysqli->query("DELETE FROM `files` WHERE `id` = " . $files['id']);
            $deleted++;
        }
    }

    return '';
}

/**
 * Delete all temporary files
 *
 * @return string Always empty
 */
function deleteTempfiles()
{
    $deleted = 0;
    $files = scandir($_SERVER['DOCUMENT_ROOT'] . '/admin/upload/temp');
    foreach ($files as $file) {
        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/admin/upload/temp/' . $file)) {
            @unlink($_SERVER['DOCUMENT_ROOT'] . '/admin/upload/temp/' . $file);
            $deleted++;
        }
    }

    return '';
}

$sajax_debug_mode = 0;
sajax_export(
    array(
        'name' => 'optimizeTables',
        'method' => 'POST',
        'asynchronous' => false
    ),
    array(
        'name' => 'removeBadSubmisions',
        'method' => 'POST',
        'asynchronous' => false
    ),
    array(
        'name' => 'removeBadBindings',
        'method' => 'POST',
        'asynchronous' => false
    ),
    array(
        'name' => 'removeBadAccessories',
        'method' => 'POST',
        'asynchronous' => false
    ),
    array(
        'name' => 'removeNoneExistingFiles',
        'method' => 'POST',
        'asynchronous' => false
    ),
    array(
        'name' => 'deleteTempfiles',
        'method' => 'POST',
        'asynchronous' => false
    )
);
sajax_handle_client_request();

