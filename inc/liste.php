<?php
/**
 * Declare functions related to list items
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

/**
 * Populate the generated global list with a page
 *
 * @param array  $side    A page
 * @param string $katnavn Title of category
 * @param int    $type    Display mode
 *
 * @return null
 */
function vare($side, $katnavn, $type)
{
    global $mysqli;

    //Search categories does not have a fixed number, use first fixed per page
    if (!$GLOBALS['generatedcontent']['activmenu']) {
        $bind = $mysqli->fetchArray(
            "
            SELECT kat
            FROM bind
            WHERE side = " . $side['id']
        );
        $GLOBALS['generatedcontent']['activmenu'] = $bind[0]['kat'];
        if (!@$GLOBALS['cache']['kats'][$bind[0]['kat']]['navn']) {
            $kat = $mysqli->fetchArray(
                "
                SELECT navn, vis
                FROM kat
                WHERE id = " . $bind[0]['kat']
            );
            if ($kat) {
                getUpdateTime('kat');

                $GLOBALS['cache']['kats'][$bind[0]['kat']]['navn'] = $kat[0]['navn'];
                $GLOBALS['cache']['kats'][$bind[0]['kat']]['vis'] = $kat[0]['vis'];
            }
        }
        $katnavn = @$GLOBALS['cache']['kats'][$bind[0]['kat']]['navn'];
    }

    $link = '/kat' . $GLOBALS['generatedcontent']['activmenu'] . '-'
    . clearFileName($katnavn) . '/side' . $side['id'] . '-'
    . clearFileName($side['navn']) . '.html';
    $name = htmlspecialchars(
        $side['navn'],
        ENT_COMPAT | ENT_XHTML,
        'UTF-8'
    );

    if ($type == 1) {
        if (!$side['beskrivelse'] && $side['text']) {
            $side['beskrivelse'] = stringLimit($side['text'], 100);
        }
        $GLOBALS['generatedcontent']['list'][] = array(
            'id' => @$side['id'],
            'name' => $name,
            'date' => @$side['dato'],
            'link' => $link,
            'icon' => @$side['billed'],
            'text' => @$side['beskrivelse'],
            'price' => array(
                'before' => @$side['for'],
                'now' => @$side['pris'],
                'from' => @$side['fra'],
                'market' => @$side['burde']));
    } else {
        $GLOBALS['generatedcontent']['list'][] = array(
            'id' => @$side['id'],
            'name' => $name,
            'date' => @$side['dato'],
            'link' => $link,
            'serial' => @$side['varenr'],
            'price' => array(
                'before' => @$side['for'],
                'now' => @$side['pris']));
    }
}

/**
 * Crope a string to a given max lengt, round by word
 *
 * @param string $string   String to crope
 * @param int    $length   Crope length
 * @param string $ellipsis String to add at the end, with in the limit
 *
 * @return string
 */
function stringLimit($string, $length = 50, $ellipsis = '…')
{
    if (!$length || mb_strlen($string) <= $length) {
        return $string;
    }

    $string = mb_substr($string, 0, $length - mb_strlen($ellipsis));
    $string = preg_replace('/\s+\S*$/u', '', $string);

    return $string . $ellipsis;
}

/**
 * Figure out how to display the active category
 *
 * @return string
 */
function liste()
{
    global $mysqli;

    $bind = $mysqli->fetchArray(
        "
        SELECT sider.id,
            UNIX_TIMESTAMP(dato) AS dato,
            sider.navn,
            sider.beskrivelse,
            sider.text,
            sider.pris,
            sider.for,
            sider.burde,
            sider.fra,
            sider.varenr,
            sider.billed
        FROM bind JOIN sider ON bind.side = sider.id
        WHERE bind.kat = " . $GLOBALS['generatedcontent']['activmenu'] . "
        ORDER BY sider.navn ASC
        "
    );

    getUpdateTime('bind');
    getUpdateTime('sider');

    $kat = $mysqli->fetchArray(
        "
        SELECT navn, vis
        FROM kat
        WHERE id = " . $GLOBALS['generatedcontent']['activmenu']
    );

    getUpdateTime('kat');

    if ($bind) {
        if (count($bind) == 1) {
            include_once 'inc/side.php';
            $GLOBALS['side']['id'] = $bind[0]['id'];
            side();
        } else {
            $bind = arrayNatsort($bind, 'id', 'navn', 'asc');
            foreach ($bind as $value) {
                //Add space around all tags, strip all tags,
                //remove all unneded white space
                if ($kat[0]['vis'] == 1) {
                    $value['text'] = preg_replace(
                        '/\s+/',
                        ' ',
                        strip_tags(
                            preg_replace(
                                array('/</', '/>/', '/\s+/'),
                                array(' <', '> ', ' '),
                                $value['text']
                            )
                        )
                    );
                }
                vare($value, $kat[0]['navn'], $kat[0]['vis']);
            }
        }
    }
}

/**
 * Generate HTML of products for a category in list form
 *
 * @param array  $side     Array of products
 * @param string $kat_navn Title of the category
 *
 * @return string
 */
function katHTML($side, $kat_navn)
{
    $html = "<table class=\"tabel\"><thead><tr><td><a href=\"\" onclick=\"x_getKat('"
    . $GLOBALS['generatedcontent']['activmenu']
    . "', 'navn', inject_html);return false;\">Titel</a></td><td><a href=\"\" onclick=\"x_getKat('"
    . $GLOBALS['generatedcontent']['activmenu']
    . "', 'for', inject_html);return false;\">Før</a></td><td><a href=\"\" onclick=\"x_getKat('"
    . $GLOBALS['generatedcontent']['activmenu']
    . "', 'pris', inject_html);return false;\">Pris</a></td><td><a href=\"\" onclick=\"x_getKat('"
    . $GLOBALS['generatedcontent']['activmenu']
    . "', 'varenr', inject_html);return false;\">#</a></td></tr></thead><tbody><tr>";
    $i = 0;
    foreach ($side as $value) {
        if (!$value['for']) {
            $value['for'] = '';
        } else {
            $value['for'] = $value['for'].',-';
        }
        if (!$value['pris']) {
            $value['pris'] = '';
        } else {
            $value['pris'] = $value['pris'].',-';
        }
        $html .= '<td><a href="/kat' . $GLOBALS['generatedcontent']['activmenu']
        . '-' . clearFileName($kat_navn) . '/side' . $value['id'] . '-'
        . clearFileName($value['navn']) . '.html">' . $value['navn']
        . '</a></td><td class="XPris" align="right">' . $value['for']
        . '</td><td class="Pris" align="right">' . $value['pris']
        . '</td><td align="right" style="font-size:11px">'
        . $value['varenr'] . '</td>';

        if ($i % 2) {
            $html .= '</tr><tr>';
        } else {
            $html .= '</tr><tr class="altrow">';
        }
        $i++;
    }
    $html .= '</tr></tbody></table>';
    return $html;
}

/**
 * Search for pages and generate a list or redirect if only one was found
 *
 * @param string $q          Tekst to search for
 * @param string $wheresider Additional sql where clause
 *
 * @return null
 */
function searchListe($q, $wheresider)
{
    //TODO duplicate text with out html for better searching.
    global $qext;
    global $mysqli;

    if ($qext) {
        $qext = ' WITH QUERY EXPANSION';
    } else {
        $qext = '';
    }
    //Temporarly store the katalog number so it can be restored when search is over
    $temp_kat = $GLOBALS['generatedcontent']['activmenu'];
    if ($q) {

        $sider = $mysqli->fetchArray(
            "
            SELECT id,
                beskrivelse,
                text,
                navn,
                pris,
                `for`,
                sider.burde,
                sider.fra,
                billed,
                MATCH(navn,text,beskrivelse) AGAINST ('$q'$qext) AS score
            FROM sider
            WHERE MATCH (navn,text,beskrivelse) AGAINST('$q'$qext) > 0
            $wheresider
            ORDER BY `score` DESC
            "
        );

        getUpdateTime('sider');

        //Fulltext search dosn't catch things like 3 letter words etc.
        $qsearch = array ("/ /", "/'/", "/´/", "/`/");
        $qreplace = array ("%", "_", "_", "_");
        $simpleq = preg_replace($qsearch, $qreplace, $q);
        $sidersimple = $mysqli->fetchArray(
            "
            SELECT id, beskrivelse, text, navn, pris, `for`, billed
            FROM `sider`
            WHERE (
                `navn` LIKE '%$simpleq%'
                OR `text` LIKE '%$simpleq%'
                OR `beskrivelse` LIKE '%$simpleq%'
            ) " . $wheresider
        );

        getUpdateTime('sider');

        //join $sidersimple to $sider
        foreach ($sidersimple as $value) {
            $match = false;

            foreach ($sider as $sider_value) {
                if (@$sider_value['side'] == $value['id']) {
                    $match = true;
                    break;
                }
            }
            unset($sider_value);
            if (!$match) {
                $sider[] = $value;
            }
        }
        unset($value);

        $table = $mysqli->fetchArray(
            "
            SELECT `list_id`
            FROM `list_rows`
            WHERE `cells`
            LIKE '%$simpleq%'
            GROUP BY `list_id`
            "
        );

        getUpdateTime('list_rows');

        //join $table to $sider
        foreach ($table as $value) {
            $match = false;
            $lists = $mysqli->fetchArray(
                "
                SELECT sider.id,
                    sider.beskrivelse,
                    sider.text,
                    sider.navn,
                    sider.pris,
                    sider.for,
                    sider.varenr,
                    sider.billed
                FROM `lists`
                JOIN sider ON lists.page_id = sider.id
                WHERE lists.id = " . $value['list_id'] . "
                LIMIT 1
                "
            );

            getUpdateTime('lists');
            getUpdateTime('sider');

            foreach ($sider as $value) {
                if (!empty($value['side']) && $value['side'] == $lists[0]['id']) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                $sider[] = $lists[0];
            }
        }

    } else {
        $sider = $mysqli->fetchArray(
            "
            SELECT `id`, beskrivelse, text, navn, pris, `for`, billed
            FROM `sider`
            WHERE 1
            $wheresider
            ORDER BY `navn` ASC
            "
        );

        getUpdateTime('sider');
    }
    if ($sider) {

        //erace duplicates
        $sider = array_merge(array_filter($sider, 'uniquecol'));

        //remove inactive pages
        for ($i = 0; $i < count($sider); $i++) {
            if (isInactivePage($sider[$i]['id'])) {
                array_splice($sider, $i, 1);
                $i--;
            }
        }
    }

    //Draw the list
    if (count($sider) == 1
        && $GLOBALS['generatedcontent']['contenttype'] != 'brand'
    ) {
        ini_set('zlib.output_compression', '0');
        header('HTTP/1.1 302 Found');
        $sider[0]['id'];
        //TODO cach
        $kat = $mysqli->fetchArray(
            "
            SELECT kat.id, kat.navn
            FROM bind JOIN kat ON kat.id = bind.kat
            WHERE `side` = 45
            LIMIT 1
            "
        );

        getUpdateTime('bind');

        getUpdateTime('kat');

        //TODO rawurlencode $url (PIE doesn't do it buy it self :(
        $url = '';
        if (!empty($kat[0]['id'])) {
            $url = '/kat'.$kat[0]['id'] . '-'
            . $folderName = rawurlencode(clearFileName($kat[0]['navn']));
        }
        $url .= '/side' . $sider[0]['id'] . '-'
        . rawurlencode(clearFileName($sider[0]['navn'])) . '.html';

        //redirect til en side
        header('Location: '.$url);
        die();
    } elseif (count($sider) > 0) {
        foreach ($sider as $value) {
            $GLOBALS['generatedcontent']['activmenu'] = 0;
            $value['text'] = strip_tags($value['text']);
            vare($value, 0, 1);
        }
    }
    $GLOBALS['generatedcontent']['activmenu'] =  $temp_kat;
}
