<?php
/**
 * Handle request for the site and decide on how to generate the page
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

/**/
ini_set('display_errors', 1);
error_reporting(-1);
/**/

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

session_start();

//ini_set('zlib.output_compression', 1);

require_once 'inc/header.php';
/*
//include the file
require_once("inc/firephp.class.php");
//create the object
if (!isset($firephp)) {
    $firephp = FirePHP::getInstance(true);
}
/*
if (!headers_sent()) {
    foreach ($GLOBALS as $key => $value) {
        $firephp->fb($key);
    }
}
*/

require_once 'inc/config.php';
require_once 'inc/mysqli.php';

//primitive runtime cache
$GLOBALS['cache'] = array();
$GLOBALS['cache']['updatetime'] = array();

if (!empty($_SESSION['faktura']['quantities'])) {
    $GLOBALS['cache']['updatetime'][] = time();
}

//Open database
$mysqli = new simple_mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

//If the database is older then the users cache, send 304 not modified
//WARNING: this results in the site not updating if new files are included later,
//the remedy is to update the database when new cms files are added.
if (!@$delayprint) {
    $tabels = $mysqli->fetch_array("SHOW TABLE STATUS");
    $updatetime = 0;
    foreach ($tabels as $tabel) {
        $updatetime = max($updatetime, strtotime($tabel['Update_time']));
    }
    /*
    if (!headers_sent()) {
        $firephp->fb($updatetime);
        foreach ($tabels as $tabel) {
            $firephp->fb($tabel['Update_time']);
            $firephp->fb(strtotime($tabel['Update_time']));
        }
    }
    */
    $included_files = get_included_files();
    $GLOBALS['cache']['updatetime']['filemtime'] = 0;
    foreach ($included_files as $filename) {
        $t = max($GLOBALS['cache']['updatetime']['filemtime'], filemtime($filename));
        $GLOBALS['cache']['updatetime']['filemtime'] = $t;
    }
    unset($included_files);
    unset($filename);
    foreach ($GLOBALS['cache']['updatetime'] as $time) {
        $updatetime = max($updatetime, $time);
    }
    /*
    if (!headers_sent()) {
        foreach ($GLOBALS['cache']['updatetime'] as $time) {
            $firephp->fb(date(DATE_RFC822, $time));
            $firephp->fb($time);
        }
        $firephp->fb($updatetime);
    }
    */
    if ($updatetime < 1) {
        $updatetime = time();
    }

    doConditionalGet($updatetime);
    $updatetime = 0;
}

require_once 'inc/functions.php';

/**
 * Get list of sub categories in format fitting the generatedcontent structure
 *
 * @param int  $nr               Id of categorie to look under
 * @param bool $custom_sort_subs If set to false categories will be naturaly sorted
 *                               by title
 *
 * @return array 
 */
function menu($nr, $custom_sort_subs = false)
{
    global $mysqli;

    /**
     * TODO inner join or HAVING COUNT(pb.id) > 0 posible way to
     * eliminate empty catagorys
     */

    $kat = $mysqli->fetch_array(
        "
        SELECT kat.id,
            kat.navn,
            kat.vis,
            kat.icon,
            kat.custom_sort_subs,
            MAX(bind.side) AS skriv,
            subkat.id AS sub
        FROM kat
        LEFT JOIN kat AS subkat
        ON kat.id = subkat.bind
            AND  subkat.vis != '0'
        LEFT JOIN bind
        ON kat.id = bind.kat
        WHERE kat.vis != '0'
            AND kat.bind = ".$GLOBALS['kats'][$nr]."
        GROUP BY kat.id
        ORDER BY kat.`order`, kat.navn
        "
    );

    if ($kat) {
        if (!$custom_sort_subs) {
            $kat = array_natsort($kat, 'id', 'navn', 'asc');
        }

        if (!$GLOBALS['cache']['kats'][$GLOBALS['kats'][$nr]]['navn']) {
            $katsnr_navn = $mysqli->fetch_array(
                "
                SELECT navn, vis, icon
                FROM kat
                WHERE id = ".$GLOBALS['kats'][$nr]
            );
            $GLOBALS['cache']['kats'][$GLOBALS['kats'][$nr]] = $katsnr_navn[0];
        }

        foreach ($kat as $value) {
            $subs = null;
            $GLOBALS['cache']['kats'][$value['id']]['skriv'] = false;
            if (@$GLOBALS['cache']['kats'][$value['id']]['skriv']
                || $value['skriv']
            ) {
                $GLOBALS['cache']['kats'][$value['id']]['skriv'] = true;
            } elseif ($value['sub']) {
                null
            }
            $GLOBALS['cache']['kats'][$value['id']]['vis'] = $value['vis'];

            /**
             * skriv() viser kun om kategorien skal krives, ikke om den ikke
             * skal så hvis siden har subs skal de undersøges nermer
             */
            if (skriv($value['id'])) {
                //Er katagorien aaben
                if (@$GLOBALS['kats'][$nr+1] == $value['id']) {
                    $subs = menu($nr+1, $value['custom_sort_subs']);
                }

                //tegn under punkter
                $menu[] = array('id' => $value['id'],
                    'name' => htmlspecialchars(
                        $value['navn'],
                        ENT_COMPAT | ENT_XHTML,
                        'UTF-8'
                    ),
                    'link' => '/kat'.$value['id'].'-'
                        .clear_file_name($value['navn']).'/',
                    'icon' => $value['icon'],
                    'sub' => $value['sub'] ? true : false,
                    'subs' => $subs);
            }
        }
    }
    if (!isset($menu)) {
        $menu = array();
    }

    return $menu;
}

/**
 * Search for categories and populate generatedcontent with results
 *
 * @param string $q        Seach string
 * @param string $wherekat Additional SQL for WHERE clause
 *
 * @return null
 */
function searchMenu($q, $wherekat)
{
    global $mysqli;
    global $qext;

    if ($qext) {
        $qext = ' WITH QUERY EXPANSION';
    } else {
        $qext = '';
    }

    if ($q) {
        $kat = $mysqli->fetch_array(
            "
            SELECT id, navn, icon, MATCH (navn) AGAINST ('".$q."'".$qext.") AS score
            FROM kat
            WHERE MATCH (navn) AGAINST('".$q."'".$qext.") > 0 " . $wherekat . "
                AND `vis` != '0'
            ORDER BY score, navn
            "
        );
        if (!$kat) {
            $qsearch = array ("/ /","/'/","//","/`/");
            $qreplace = array ("%","_","_","_");
            $simpleq = preg_replace($qsearch, $qreplace, $q);
            $kat = $mysqli->fetch_array(
                "
                SELECT id, navn, icon
                FROM kat
                WHERE navn
                LIKE '%".$simpleq."%' " . $wherekat . "
                ORDER BY navn
                "
            );
        }
        $maerke = $mysqli->fetch_array(
            "
            SELECT id, navn
            FROM `maerke`
            WHERE MATCH (navn) AGAINST ('".$q."'".$qext.") >  0
            "
        );
        if (!$maerke) {
            if (!@$simpleq) {
                $qsearch = array ("/ /","/'/","//","/`/");
                $qreplace = array ("%","_","_","_");
                $simpleq = preg_replace($qsearch, $qreplace, $q);
            }
            $maerke = $mysqli->fetch_array(
                "
                SELECT id, navn
                FROM maerke
                WHERE navn
                LIKE '%" .$simpleq ."%'
                ORDER BY navn
                "
            );
        }
    }

    if (@$maerke) {
        foreach ($maerke as $value) {
            $GLOBALS['generatedcontent']['search_menu'][] = array('id' => 0,
                'name' => htmlspecialchars(
                    $value['navn'],
                    ENT_COMPAT | ENT_XHTML,
                    'UTF-8'
                ),
                'link' => '/mærke'.$value['id']
                    .'-' .clear_file_name($value['navn']) .'/');
        }
    }

    if (@$kat) {
        foreach ($kat as $value) {
            if (skriv($value['id'])) {
                $GLOBALS['generatedcontent']['search_menu'][] = array(
                    'id' => $value['id'],
                    'name' => htmlspecialchars(
                        $value['navn'],
                        ENT_COMPAT | ENT_XHTML,
                        'UTF-8'
                    ),
                    'link' => '/kat'.$value['id']
                        .'-' .clear_file_name($value['navn']) .'/',
                    'icon' => $value['icon'],
                    'sub' => subs($value['id'])
                );
            }
        }
    }
}

/**
 * Check if page is inactive
 *
 * @param int $id Page id
 *
 * @return bool
 */
function isInactivePage($id)
{
    global $mysqli;
    $bind = $mysqli->fetch_array(
        "
        SELECT `kat`
        FROM `bind`
        WHERE `side` = ".$id."
        LIMIT 1
        "
    );
    if (binding($bind[0]['kat']) == -1) {
        return true;
    }

    return false;
}

/**
 * MySQL escape strin(s), including whildcards
 *
 * @param mixed $s String or array that should be escapted
 *
 * @return mixed The ecaped string or array
 */
function fullMysqliEscape($s)
{
    if (is_array($s)) {
        return array_map('fullMysqliEscape', $s);
    }

    if (get_magic_quotes_gpc()) {
        $s = stripslashes($s);
    }

    global $mysqli;

    return $mysqli->escape_wildcards($mysqli->real_escape_string($s));
}

$_GET = fullMysqliEscape($_GET);

//redirect af gamle urls
if (@$_GET['kat'] || @$_GET['side']) {

    //secure input
    $kat_id  = fullMysqliEscape($_GET['kat']);
    $side_id = fullMysqliEscape($_GET['side']);

    header('HTTP/1.1 301 Moved Permanently');
    if ($side_id) {
        $bind = $mysqli->fetch_array(
            "
            SELECT bind.kat, sider.navn AS side_navn, kat.navn AS kat_navn
            FROM bind
            JOIN sider
            ON bind.side = sider.id
            JOIN kat
            ON bind.kat = kat.id
            WHERE side =".$side_id."
            LIMIT 1
            "
        );
        $side_navn = $bind[0]['side_navn'];
        $kat_id = $bind[0]['kat'];
        $kat_name = $bind[0]['kat_navn'];
        unset($bind);
    }

    if (($kat_id && !$kat_name) || ($_GET['kat'] && $_GET['kat'] != $kat_id)) {
        /**
         * Get kat navn hvis der ikke var en side eller
         * kat ikke var standard for siden.
         */
        $kat_id = fullMysqliEscape($_GET['kat']));

        if (!$GLOBALS['cache']['kats'][$kat_id]['navn']) {
            $kats = $mysqli->fetch_array(
                "
                SELECT navn, vis, icon
                FROM kat
                WHERE id = " .$kat_id ."
                LIMIT 1
                "
            );

            getUpdateTime('kat');

            $GLOBALS['cache']['kats'][$kat_id] = $kats[0];
        }
        $kat_name = $GLOBALS['cache']['kats'][$kat_id]['navn'];
    }
    if ($side_navn) {

        //TODO rawurlencode $url (PIE doesn't do it buy it self :(
        $url = '/kat' .$kat_id .'-' .rawurlencode(clear_file_name($kat_name))
            .'/side' .$side_id .'-' .rawurlencode(clear_file_name($side_navn))
            .'.html';

        //redirect til en side
        header('Location: ' .$url);
        die();
    } elseif ($kat_name) {

        //TODO rawurlencode $url (PIE doesn't do it buy it self :(
        $url = '/kat'.$kat_id.'-'.rawurlencode(clear_file_name($kat_name)).'/';

        //redirect til en kategori
        header('Location: '.$url);
        die();
    } else {
        //inted fundet redirect til søge siden
        header('Location: /?sog=1&q=&varenr=&sogikke=&minpris=&maxpris=&maerke=');
        die();
    }
}

//primitive runtime cache
$GLOBALS['cache']['kats'] = array();

$activMenu =& $GLOBALS['generatedcontent']['activmenu'];

//Always blank kads
if (@$_GET['sog']
    || @$_GET['q']
    || @$_GET['varenr']
    || @$_GET['sogikke']
    || @$_GET['minpris']
    || @$_GET['maxpris']
    || @$_GET['maerke']
    || @$_GET['brod']
) {
    $activMenu = -1;
}


//Handle none existing pages
if (@$GLOBALS['side']['id'] > 0) {
    if (!$mysqli->fetch_array(
        "
        SELECT id
        FROM sider
        WHERE id = " .$GLOBALS['side']['id'] ."
        LIMIT 1
        "
    )
    ) {

        getUpdateTime('sider');

        $GLOBALS['side']['inactive'] = true;
        unset($GLOBALS['side']['id']);
        header('HTTP/1.1 404 Not Found');
    }
}

//Block inactive pages
if (@$GLOBALS['side']['id'] > 0 && isInactivePage($GLOBALS['side']['id'])) {
    $GLOBALS['side']['inactive'] = true;
    header('HTTP/1.1 404 Not Found');
}

/**
 * Hvis siden er kendt men katagorien ikke så find den første passende
 * katagori, hent også side indholdet.
 */
if (@$GLOBALS['side']['id'] > 0
    && !@$activMenu
    && !@$GLOBALS['side']['inactive']
) {
    $bind = $mysqli->fetch_array(
        "
        SELECT bind.kat,
            sider.navn,
            sider.burde,
            sider.fra,
            sider.text,
            sider.pris,
            sider.for,
            sider.krav,
            sider.maerke,
            sider.varenr,
            UNIX_TIMESTAMP(sider.dato) AS dato
        FROM bind
        JOIN sider
        ON bind.side = sider.id
        WHERE side = ".$GLOBALS['side']['id']."
        LIMIT 1
        "
    );

    getUpdateTime('bind');

    $activMenu                  = $bind[0]['kat'];
    $GLOBALS['side']['navn']    = $bind[0]['navn'];
    $GLOBALS['side']['burde']   = $bind[0]['burde'];
    $GLOBALS['side']['fra']     = $bind[0]['fra'];
    $GLOBALS['side']['text']    = $bind[0]['text'];
    $GLOBALS['side']['pris']    = $bind[0]['pris'];
    $GLOBALS['side']['for']     = $bind[0]['for'];
    $GLOBALS['side']['krav']    = $bind[0]['krav'];
    $GLOBALS['side']['maerke']  = $bind[0]['maerke'];
    $GLOBALS['side']['varenr']  = $bind[0]['varenr'];
    $GLOBALS['side']['dato']    = $bind[0]['dato'];
    $GLOBALS['cache']['updatetime']['side']	= $bind[0]['dato'];
    unset($bind);
} elseif (@$GLOBALS['side']['id'] > 0 && !@$GLOBALS['side']['inactive']) {
    //Hent side indhold
    $sider = $mysqli->fetch_array(
        "
        SELECT `navn`,
            `burde`,
            `fra`,
            `text`,
            `pris`,
            `for`,
            `krav`,
            `maerke`,
            varenr,
            UNIX_TIMESTAMP(dato) AS dato
        FROM sider
        WHERE id = ".$GLOBALS['side']['id']."
        LIMIT 1
        "
    );
    $GLOBALS['side']['navn']	= $sider[0]['navn'];
    $GLOBALS['side']['burde']	= $sider[0]['burde'];
    $GLOBALS['side']['fra']		= $sider[0]['fra'];
    $GLOBALS['side']['text']	= $sider[0]['text'];
    $GLOBALS['side']['pris']	= $sider[0]['pris'];
    $GLOBALS['side']['for']		= $sider[0]['for'];
    $GLOBALS['side']['krav']	= $sider[0]['krav'];
    $GLOBALS['side']['maerke']	= $sider[0]['maerke'];
    $GLOBALS['side']['varenr']	= $sider[0]['varenr'];
    $GLOBALS['side']['dato']	= $sider[0]['dato'];
    $GLOBALS['cache']['updatetime']['side']	= $sider[0]['dato'];

    unset($sider);
}

if (@$activMenu > 0) {
    $keywords = array();

    //get kat tree,
    $data = kats($activMenu);
    $nr = count($data);
    for ($i=$nr-1; $i>=0; $i--) {
        $kats[$i] = $data[$nr-$i-1];
    }

    $GLOBALS['kats'] = $kats;

    //Key words
    if ($GLOBALS['kats']) {
        foreach ($GLOBALS['kats'] as $value) {
            if (!@$GLOBALS['cache']['kats'][$value]['navn']) {
                $temp = $mysqli->fetch_array(
                    "
                    SELECT navn, vis, icon
                    FROM kat
                    WHERE id = ".$value."
                    LIMIT 1
                    "
                );

                getUpdateTime('kat');

                $GLOBALS['cache']['kats'][$value] = $temp[0];
            }

            $keyword = htmlspecialchars(
                $GLOBALS['cache']['kats'][$value]['navn'],
                ENT_COMPAT | ENT_XHTML,
                'UTF-8'
            );
            $keyword = trim($keyword);
            $keywords[] = $keyword;
        }
    }
    $GLOBALS['generatedcontent']['keywords'] = implode(',', $keywords);
}

//crumbs start
if (@$GLOBALS['kats']) {
    foreach ($GLOBALS['kats'] as $value) {
        if (!$GLOBALS['cache']['kats'][$value]['navn']) {
            $katsnr_navn = $mysqli->fetch_array(
                "
                SELECT navn, vis, icon
                FROM kat
                WHERE id = ".$value
            );

            getUpdateTime('kat');

            $GLOBALS['cache']['kats'][$value] = $katsnr_navn[0];
        }

        $GLOBALS['generatedcontent']['crumbs'][] = array(
            'name' => htmlspecialchars(
                $GLOBALS['cache']['kats'][$value]['navn'],
                ENT_COMPAT | ENT_XHTML,
                'UTF-8'
            ),
            'link' => '/kat' . $value . '-'
            . clear_file_name($GLOBALS['cache']['kats'][$value]['navn']) . '/',
            'icon' => $GLOBALS['cache']['kats'][$value]['icon']
        );
    }
    $GLOBALS['generatedcontent']['crumbs'] = array_reverse(
        array_values($GLOBALS['generatedcontent']['crumbs'])
    );
}
//crumbs end

//Get list of top categorys on the site.
$kat_fpc = $mysqli->fetch_array(
    "
    SELECT id,
        navn,
        vis,
        icon,
        custom_sort_subs,
        id IN (SELECT bind FROM kat WHERE vis != '0') AS sub,
        id IN (SELECT kat FROM bind) AS skriv
    FROM `kat`
    WHERE kat.vis != '0'
        AND kat.bind = 0
        AND (
            id IN (SELECT bind FROM kat WHERE vis != '0')
            OR id IN (SELECT kat FROM bind)
        )
    ORDER BY `order`, navn ASC
    "
);
getUpdateTime('kat');
foreach ($kat_fpc as $value) {

    $GLOBALS['cache']['kats'][$value['id']]['navn'] = $value['navn'];
    $GLOBALS['cache']['kats'][$value['id']]['vis'] = $value['vis'];
    $GLOBALS['cache']['kats'][$value['id']]['skriv'] = $value['skriv'] ? true : null;

    //TODO think about adding parent folders to url
    if (skriv($value['id'])) {
        $subs = null;
        if ($value['id'] == @$GLOBALS['kats'][0]) {
            $subs = menu(0, $value['custom_sort_subs']);
        }

        $GLOBALS['generatedcontent']['menu'][] = array('id' => $value['id'],
        'name' => htmlspecialchars($value['navn'], ENT_COMPAT | ENT_XHTML, 'UTF-8'),
        'link' => '/kat'.$value['id'].'-'.clear_file_name($value['navn']).'/',
        'icon' => $value['icon'],
        'sub' => $value['sub'] ? true : false,
        'subs' => $subs);
    }
}

unset($kat_fpc);
unset($subs);
unset($value);

//Front page pages
$kat_fpp = $mysqli->fetch_array(
    "
    SELECT sider.id, sider.navn
    FROM bind
    JOIN sider
    ON bind.side = sider.id
    WHERE kat = 0
    "
);

getUpdateTime('bind');
getUpdateTime('sider');

foreach ($kat_fpp as $value) {
    $GLOBALS['generatedcontent']['sider'][] = array(
        'id' => $value['id'],
        'name' => htmlspecialchars($value['navn'], ENT_COMPAT | ENT_XHTML, 'UTF-8'),
        'link' => '/side' .$value['id'] .'-'
            .clear_file_name($value['navn']) .'.html'
    );
}
unset($kat_fpp);
unset($value);

//TODO catch none existing kats
//Get page content and type
if (@$_GET['sog'] || @$GLOBALS['side']['inactive']) {
    $GLOBALS['generatedcontent']['contenttype'] = 'search';

    $text = '';

    if (@$GLOBALS['side']['inactive']) {
        $text .= '<p>'
            ._('Page could not be found. Try searching for a similar page.') .'</p>';
    }

    $text .= '<form action="/" method="get"><table>';
    $text .= '<tr><td>'._('Contains').'</td><td>';
    $text .= '<input name="q" size="31" value="';
    if (@$GLOBALS['side']['inactive']) {
        $text = preg_replace(
            array('/-/u', '/.*?side[0-9]+\s(.*?)[.]html/u'),
            array(' ', '\1'),
            urldecode($_SERVER['REQUEST_URI'])
        );
        $text = htmlspecialchars($text, ENT_COMPAT | ENT_XHTML, 'UTF-8');
    }
    $text .= '" /></td>';
    $text .= '<td><input type="submit" value="'._('Search').'" /></td></tr>';
    $text .= '<tr><td>'._('Part No.').'</td>';
    $text .= '<td><input name="varenr" size="31" value="" maxlength="63" /></td>';
    $text .= '</tr><tr><td>'._('Without the words').'</td><td>';
    $text .= '<input name="sogikke" size="31" value="" /></td></tr>';
    $text .= '<tr><td>'._('Enhanced:').'</td>';
    $text .= '<td><input name="qext" type="checkbox" value="1" /></td></tr>';
    $text .= '<tr><td>'._('Min price').'</td><td>';
    $text .= '<input name="minpris" size="5" maxlength="11" value="" />,-</td></tr>';
    $text .= '<tr><td>'._('Max price').'&nbsp;</td><td>';
    $text .= '<input name="maxpris" size="5" maxlength="11" value="" />,-</td></tr';
    $text .= '><tr><td>'._('Brand:').'</td><td><select name="maerke">';
    $text .= '<option value="0">'._('All').'</option>';

    $maerker = $mysqli->fetch_array(
        "
        SELECT `id`, `navn`
        FROM `maerke`
        ORDER BY `navn` ASC
        "
    );

    getUpdateTime('maerke');

    $maerker_nr = count($maerker);
    foreach ($maerker as $value) {
        $text .= '<option value="'.$value['id'].'">';
        $text .= htmlspecialchars(
            $value['navn'],
            ENT_COMPAT | ENT_XHTML,
            'UTF-8'
        ) . '</option>';
    }
    $text .= '</select></td></tr></table></form>';
    $GLOBALS['generatedcontent']['text'] .= $text;
} elseif (@$_GET['q']
    || @$_GET['varenr']
    || @$_GET['sogikke']
    || @$_GET['minpris']
    || @$_GET['maxpris']
    || @$_GET['maerke']
    || @$maerke
) {
    $GLOBALS['generatedcontent']['contenttype'] = 'tiles';

    if ((@$_GET['maerke'] || @$maerke)
        && !@$_GET['q']
        && !@$_GET['varenr']
        && !@$_GET['sogikke']
        && !@$_GET['minpris']
        && !@$_GET['maxpris']
    ) {
        //Brand only search
        $GLOBALS['generatedcontent']['contenttype'] = 'brand';
        if (@$_GET['maerke'] && !@$maerke) {
            $maerke = $_GET['maerke'];
        }
        $maerkeet = $mysqli->fetch_array(
            "
            SELECT `id`, `navn`, `link`, ico
            FROM `maerke`
            WHERE id = ".$maerke
        );

        getUpdateTime('maerke');

        $GLOBALS['generatedcontent']['brand'] = array('id' => $maerkeet[0]['id'],
        'name' => htmlspecialchars(
            $maerkeet[0]['navn'],
            ENT_COMPAT | ENT_XHTML,
            'UTF-8'
        ),
        'xlink' => $maerkeet[0]['link'],
        'icon' => $maerkeet[0]['ico']);

        include_once 'inc/liste.php';

        $wheresider = "AND (`maerke` LIKE '". $maerkeet[0]['id']
            ."' OR `maerke` LIKE '" .$maerkeet[0]['id'].",%' OR `maerke` LIKE '%,"
            .$maerkeet[0]['id'] .",%' OR `maerke` LIKE '%,"
            .$maerkeet[0]['id'] ."')";
        search_liste(false, $wheresider);
    } else {
        //Full search
        $wheresider = '';
        if (@$_GET['varenr']) {
            $wheresider .= ' AND varenr LIKE \''.$_GET['varenr'].'%\'';
        }
        if (@$_GET['minpris']) {
            $wheresider .= ' AND pris > '.$_GET['minpris'];
        }
        if (@$_GET['maxpris']) {
            $wheresider .= ' AND pris < '.$_GET['maxpris'];
        }
        if (@$_GET['maerke']) {
            $wheresider .= " AND (`maerke` LIKE '%," .$_GET['maerke']
                .",%' OR `maerke` LIKE '" .$_GET['maerke']
                .",%' OR `maerke` LIKE '%," .$_GET['maerke']
                ."' OR `maerke` LIKE '" .$_GET['maerke'] ."')";
        }
        if (@$nmaerke) {
            $wheresider .= " AND (`maerke` NOT LIKE '%," .$nmaerke
                .",%' AND `maerke` NOT LIKE '" .$nmaerke
                .",%' AND `maerke` NOT LIKE '%," .$nmaerke
                ."' AND `maerke` NOT LIKE '" .$nmaerke ."')";
        }
        if (@$_GET['sogikke']) {
            $wheresider .= " AND !MATCH (navn,text) AGAINST('" .$_GET['sogikke']
            ."') > 0";
        }
    }

    if (!@$start) {
        $start = "0";
    }

    if (!@$num) {
        $num = "10";
    }

    $limit =  ' LIMIT '.$start.' , '.$num;
    include_once 'inc/liste.php';
    search_liste(@$_GET['q'], $wheresider);

    $wherekat = '';
    if (@$_GET['sogikke']) {
        $wherekat .= ' AND !MATCH (navn) AGAINST(\''.$_GET['sogikke'].'\') > 0';
    }
    searchMenu(@$_GET['q'], $wherekat);

    if (!@$GLOBALS['generatedcontent']['list']
        && !@$GLOBALS['generatedcontent']['search_menu']
    ) {
        header('HTTP/1.1 404 Not Found');
    }
} elseif (@$GLOBALS['side']['id'] > 0) {
    $GLOBALS['generatedcontent']['contenttype'] = 'product';
    include_once 'inc/side.php';
    side();
} elseif (@$activMenu > 0) {
    include_once 'inc/liste.php';
    liste();
    if (@$GLOBALS['side']['id'] > 0) {
        $GLOBALS['generatedcontent']['contenttype'] = 'product';
    } elseif (@$GLOBALS['cache']['kats'][$activMenu]['vis'] == 2) {
        $GLOBALS['generatedcontent']['contenttype'] = 'list';
    } elseif (@$GLOBALS['cache']['kats'][$activMenu]['vis'] == 1) {
        $GLOBALS['generatedcontent']['contenttype'] = 'tiles';
    }
} else {
    $special = $mysqli->fetch_array(
        "
        SELECT text, UNIX_TIMESTAMP(dato) AS dato
        FROM special
        WHERE id = 1
        LIMIT 1
        "
    );
    $GLOBALS['cache']['updatetime']['special_1'] = $special[0]['dato'];

    $GLOBALS['generatedcontent']['contenttype'] = 'front';
    $GLOBALS['generatedcontent']['text'] = $special[0]['text'];
    unset($special);
}

//Extract title for current page.
if (@$maerkeet) {
    $GLOBALS['generatedcontent']['title'] = $maerkeet[0]['navn'];
} elseif (isset($GLOBALS['side']['navn'])) {
    $GLOBALS['generatedcontent']['title'] = htmlspecialchars(
        $GLOBALS['side']['navn'],
        ENT_COMPAT | ENT_XHTML,
        'UTF-8'
    );
    //Add page title to keywords
    if (@$GLOBALS['generatedcontent']['keywords']) {
        $GLOBALS['generatedcontent']['keywords'] .= "," . htmlspecialchars(
            $GLOBALS['side']['navn'],
            ENT_COMPAT | ENT_XHTML,
            'UTF-8'
        );
    } else {
        $GLOBALS['generatedcontent']['keywords'] = htmlspecialchars(
            $GLOBALS['side']['navn'],
            ENT_COMPAT | ENT_XHTML,
            'UTF-8'
        );
    }
} elseif (@$GLOBALS['side']['id'] && !@$GLOBALS['side']['inactive']) {
    $sider_navn = $mysqli->fetch_array(
        "
        SELECT navn, UNIX_TIMESTAMP(dato) AS dato
        FROM sider
        WHERE id = ".$GLOBALS['side']['id']."
        LIMIT 1
        "
    );

    $GLOBALS['cache']['updatetime']['sider'] = $sider_navn[0]['dato'];

    $GLOBALS['generatedcontent']['title'] = htmlspecialchars(
        $sider_navn[0]['navn'],
        ENT_COMPAT | ENT_XHTML,
        'UTF-8'
    );
}

if (empty($GLOBALS['generatedcontent']['title'])
    && @$activMenu > 0
) {
    if (!$GLOBALS['cache']['kats'][$activMenu]['navn']) {
        $kat_navn = $mysqli->fetch_array(
            "
            SELECT navn, vis
            FROM kat
            WHERE id = " .$activMenu ."
            LIMIT 1
            "
        );

        getUpdateTime('kat');

        $GLOBALS['cache']['kats'][$activMenu] = $kat_navn[0];
    }

    $GLOBALS['generatedcontent']['title'] = htmlspecialchars(
        $GLOBALS['cache']['kats'][$activMenu]['navn'],
        ENT_COMPAT | ENT_XHTML,
        'UTF-8'
    );

    //TODO add to url
    if (!empty($GLOBALS['cache']['kats'][$activMenu]['icon'])) {
        $icon = $mysqli->fetch_array(
            "
            SELECT `alt`
            FROM `files`
            WHERE path = '" .$GLOBALS['cache']['kats'][$activMenu]['icon'] ."'
            LIMIT 1
            "
        );
    }

    if (!empty($icon[0]['alt']) && $GLOBALS['generatedcontent']['title']) {
        $GLOBALS['generatedcontent']['title'] .= ' ' . htmlspecialchars(
            $icon[0]['alt'],
            ENT_COMPAT | ENT_XHTML,
            'UTF-8'
        );
    } elseif (!empty($icon[0]['alt'])) {
        $GLOBALS['generatedcontent']['title'] = htmlspecialchars(
            $icon[0]['alt'],
            ENT_COMPAT | ENT_XHTML,
            'UTF-8'
        );
    } elseif (!$GLOBALS['generatedcontent']['title']) {
        $icon[0]['path'] = pathinfo($GLOBALS['cache']['kats'][$activMenu]['icon']);
        $GLOBALS['generatedcontent']['title'] = htmlspecialchars(
            ucfirst(
                preg_replace('/-/ui', ' ', $icon[0]['path']['filename'])
            ),
            ENT_COMPAT | ENT_XHTML,
            'UTF-8'
        );
    }
} elseif (empty($GLOBALS['generatedcontent']['title']) && @$_GET['sog'] == 1) {
    $GLOBALS['generatedcontent']['title'] = 'Søg på ' . htmlspecialchars(
        $GLOBALS['_config']['site_name'],
        ENT_COMPAT | ENT_XHTML,
        'UTF-8'
    );
}

if (empty($GLOBALS['generatedcontent']['title'])) {
    $GLOBALS['generatedcontent']['title'] = htmlspecialchars(
        $GLOBALS['_config']['site_name'],
        ENT_COMPAT | ENT_XHTML,
        'UTF-8'
    );
}
//end title

//Get email
$GLOBALS['generatedcontent']['email'] = $GLOBALS['_config']['email'][0];
if (@$activMenu > 0) {
    $email = $mysqli->fetch_array(
        "
        SELECT `email`
        FROM `kat`
        WHERE id = " .$activMenu
    );

    getUpdateTime('kat');

    if ($email[0]['email']) {
        $GLOBALS['generatedcontent']['email'] = $email[0]['email'];
    }
}

if (!@$delayprint) {
    $updatetime = 0;

    $included_files = get_included_files();

    $time = $GLOBALS['cache']['updatetime']['filemtime'];
    foreach ($included_files as $filename) {
        $time = max($time, filemtime($filename));
    }
    $GLOBALS['cache']['updatetime']['filemtime'] = $time;

    unset($included_files);
    unset($filename);
    foreach ($GLOBALS['cache']['updatetime'] as $time) {
        $updatetime = max($updatetime, $time);
    }
    unset($time);
    if ($updatetime < 1) {
        $updatetime = time();
    }
    /*
    if (!headers_sent()) {
        foreach ($GLOBALS['cache']['updatetime'] as $time) {
            //$firephp->fb(date(DATE_RFC822, $time));
            $firephp->fb($time);
        }
        $firephp->fb($updatetime);
    }
    */
    doConditionalGet($updatetime);
    unset($updatetime);

    unset($cache);

    include_once 'theme/index.php';
}
/*
?><!--
<?php
print_r($GLOBALS['generatedcontent']);
print_r($GLOBALS['cache']['kats']);
?>
--><?php
/**/

