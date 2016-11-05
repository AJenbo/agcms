<?php
/**
 * Print RSS feed contaning the 20 last changed pages
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

mb_language('uni');
mb_detect_order('UTF-8, ISO-8859-1');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Copenhagen');

require_once 'inc/config.php';
require_once 'inc/mysqli.php';
require_once 'inc/functions.php';

$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

$tabels = $mysqli->fetchArray("SHOW TABLE STATUS");
$updatetime = 0;
foreach ($tabels as $tabel) {
    $updatetime = max($updatetime, strtotime($tabel['Update_time']));
}

if ($updatetime < 1) {
    $updatetime = time();
}

doConditionalGet($updatetime);

$time = 0;
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $time = strtotime(stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']));
}

$limit = '';
if ($time > 1000000000) {
    $where = " WHERE `dato` > '" . date('Y-m-d h:i:s', $time) . "'";
} else {
    $limit = ' LIMIT 20';
}

$sider = $mysqli->fetchArray(
    "
    SELECT sider.id,
        sider.maerke,
        sider.navn,
        UNIX_TIMESTAMP(dato) AS dato,
        billed,
        kat.id AS kat_id,
        kat.navn AS kat_navn
    FROM sider
    JOIN bind ON (side = sider.id)
    JOIN kat ON (kat.id = kat)
    " . @$where . "
    GROUP BY id
    ORDER BY - dato" . $limit
);

//check for inactive
if ($sider) {
    for ($i=0; $i<count($sider); $i++) {
        if (binding($sider[$i]['kat_id']) == -1) {
            array_splice($sider, $i, 1);
            $i--;
        }
    }
}

header("Content-Type: application/rss+xml");
doConditionalGet($sider[0]['dato']);

$search = array (
    '@<script[^>]*?>.*?</script>@si', // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
    '@([\r\n])[\s]+@',                // Strip out white space
    '@&(&|#197);@i'
);

$replace = array (
    ' ',
    ' ',
    '\1',
    ' '
);

require_once 'inc/config.php';

echo '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <atom:link href="' . $GLOBALS['_config']['base_url']
    . '/rss.php" rel="self" type="application/rss+xml" />

    <title>'.$GLOBALS['_config']['site_name'].'</title>
    <link>'.$GLOBALS['_config']['base_url'].'/</link>
    <description>De nyeste sider</description>
    <language>da</language>
    <lastBuildDate>' . gmdate('D, d M Y H:i:s', $sider[0]['dato'])
    . ' GMT</lastBuildDate>
    <managingEditor>' . $GLOBALS['_config']['email'][0] . ' ('
    . $GLOBALS['_config']['site_name'] . ')</managingEditor>';
for ($i = 0; $i < count($sider); $i++) {
    htmlspecialchars($sider[$i]['navn'], ENT_COMPAT | ENT_XML1);
    if (!$sider[$i]['navn'] = trim($name)) {
        $sider[$i]['navn'] = $GLOBALS['_config']['site_name'];
    }
    $sideText = $mysqli->fetchArray(
        "
        SELECT text
        FROM sider
        WHERE id = " . $sider[$i]['id']
    );

    echo '
    <item>
    <title>'.$sider[$i]['navn'].'</title>
    <link>' . $GLOBALS['_config']['base_url'] . '/kat' . $sider[$i]['kat_id'] . '-'
    . rawurlencode(clearFileName($sider[$i]['kat_navn'])) . '/side'
    . $sider[$i]['id'] . '-' . rawurlencode(clearFileName($sider[$i]['navn']))
    . '.html</link>
    <description>';
    if ($sider[$i]['billed']
        && $sider[$i]['billed'] != '/images/web/intet-foto.jpg'
    ) {
        echo '&lt;img style="float:left;margin:0 10px 5px 0;" src="'
        . $GLOBALS['_config']['base_url'] . $sider[$i]['billed'] . '" &gt;&lt;p&gt;';
        //TODO limit to summery
    }

    $cleaned = trim(preg_replace($search, $replace, $sideText[0]['text']));
    echo htmlspecialchars($cleaned, ENT_COMPAT | ENT_XML1) . '</description>
    <pubDate>' . gmdate('D, d M Y H:i:s', $sider[$i]['dato']) . ' GMT</pubDate>
    <guid>' . $GLOBALS['_config']['base_url'] . '/kat' . $sider[$i]['kat_id'] . '-'
    . rawurlencode(clearFileName($sider[$i]['kat_navn'])) . '/side'
    . $sider[$i]['id'] . '-' . rawurlencode(clearFileName($sider[$i]['navn']))
    . '.html</guid>';
    $bind = $mysqli->fetchArray(
        "
        SELECT `kat`
        FROM bind
        WHERE side = " . $sider[$i]['id']
    );

    $kats = '';
    for ($ibind = 0; $ibind < count($bind); $ibind++) {
        $kats[] = $bind[$ibind]['kat'];

        $temp = $mysqli->fetchArray(
            "
            SELECT bind
            FROM `kat`
            WHERE id = '" . $bind[$ibind]['kat'] . "'
            LIMIT 1
            "
        );
        if (@$temp[0]) {
            while ($temp && !in_array($temp[0]['bind'], $kats)) {
                $kats[] = $temp[0]['bind'];
                $temp = $mysqli->fetchArray(
                    "
                    SELECT bind
                    FROM `kat`
                    WHERE id = '" . $temp[0]['bind']."'
                    LIMIT 1
                    "
                );
            }
        }
    }

    //$kats = array_unique($kats);

    for ($icategory = 0; $icategory < count($kats); $icategory++) {
        if ($kats[$icategory]) {
            $kat = $mysqli->fetchArray(
                "
                SELECT `navn`
                FROM kat
                WHERE id = " . $kats[$icategory] . "
                LIMIT 1
                "
            );
            $cleaned = trim(preg_replace($search, $replace, @$kat[0]['navn']));
            if ($category = $cleaned) {
                echo '<category>';
                echo htmlspecialchars($category, ENT_NOQUOTES | ENT_XML1);
                echo '</category>';
            }
        }
    }
    if ($sider[$i]['maerke']) {
        $maerker = explode(',', $sider[$i]['maerke']);
        $maerker_nr = count($maerker);
        $where = '';
        for ($imaerker=0; $imaerker<$maerker_nr; $imaerker++) {
            if ($imaerker > 0) {
                $where .= ' OR';
            }
            $where .= ' id = '.$maerker[$imaerker];
        }
        $maerker = $mysqli->fetchArray(
            "
            SELECT `navn`
            FROM maerke
            WHERE " . $where . "
            LIMIT " . $maerker_nr
        );
        $maerker_nr = count($maerker);
        for ($imaerker = 0; $imaerker < $maerker_nr; $imaerker++) {
            $cleaned = preg_replace($search, $replace, $maerker[$imaerker]['navn']);
            $cleaned = trim($cleanName);
            if ($category = $cleaned) {
                echo '<category>';
                echo htmlspecialchars($category, ENT_NOQUOTES | ENT_XML1);
                echo '</category>';
            }
        }
    }

    echo '</item>';
}
$mysqli->close();
echo '</channel></rss>';
