<?php
/**
 * Declare common functions
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once __DIR__ . '/mysqli.php';
require_once __DIR__ . '/sajax.php';
require_once __DIR__ . '/config.php';

function db()
{
    static $mysqli;
    if (!$mysqli) {
        $mysqli = new Simple_Mysqli(
            $GLOBALS['_config']['mysql_server'],
            $GLOBALS['_config']['mysql_user'],
            $GLOBALS['_config']['mysql_password'],
            $GLOBALS['_config']['mysql_database']
        );
    }
    return $mysqli;
}

/**
 * Checks if email an address looks valid and that an mx server is responding
 *
 * @param string $email The email address to check
 *
 * @return bool
 */
function valideMail(string $email): bool
{
    $user = preg_replace('/@.+$/u', '', $email);
    $domain = preg_replace('/^.+?@/u', '', $email);
    if (function_exists('idn_to_ascii')) {
        $domain = idn_to_ascii($domain);
    }

    if (filter_var($user . '@' . $domain, FILTER_VALIDATE_EMAIL)
        && getmxrr($domain, $dummy)
    ) {
        return true;
    }

    return false;
}

/**
 * Get last update time for table
 *
 * @param string $table Table name
 *
 * @return null
 */
function getUpdateTime(string $table)
{
    if (empty($GLOBALS['cache']['updatetime'][$table])) {
        $updatetime = db()->fetchArray("SHOW TABLE STATUS LIKE '".$table."'");
        $GLOBALS['cache']['updatetime'][$table] = strtotime($updatetime[0]['Update_time']);
    }
}

/**
 * Check if there are pages connected to a category
 *
 * @param int $id Category id
 *
 * @return bool
 */
function skriv(int $id): bool
{
    if (isset($GLOBALS['cache']['kats'][$id]['skriv'])) {
        return $GLOBALS['cache']['kats'][$id]['skriv'];
    }

    //er der en side på denne kattegori
    if ($sider = db()->fetchArray('SELECT id FROM bind WHERE kat = '.$id)) {
        getUpdateTime('bind');
        $GLOBALS['cache']['kats'][$id]['skriv'] = true;
        return true;
    }

    //ellers kig om der er en under kattegori med en side
    $kat = db()->fetchArray(
        "
        SELECT kat.id, bind.id as skriv
        FROM kat JOIN bind ON bind.kat = kat.id
        WHERE kat.bind = $id
        GROUP BY kat.id
        "
    );

    getUpdateTime('kat');

    //cache all results
    foreach ($kat as $value) {
        if ($value['skriv']) {
            $GLOBALS['cache']['kats'][$value['id']]['skriv'] = true;
            $return = true;
            //Load full result in to cache and return true if there was a hit
        }
    }

    if ($return = false) {
        $GLOBALS['cache']['kats'][$id]['skriv'] = true;
        return true;
    }

    //Search deeper if a result wasn't found yet
    foreach ($kat as $value) {
        if (skriv($value['id'])) {
            $GLOBALS['cache']['kats'][$value['id']]['skriv'] = true;
            return true;
        }

        //This category is empty or only contains empty categorys
        $GLOBALS['cache']['kats'][$value['id']]['skriv'] = false;
        return false;
    }

    return false;
}

/**
 * Test if category contain categories with content
 *
 * @param int $kat Category id
 *
 * @return bool
 */
function subs(int $kat): bool
{
    $sub = db()->fetchArray(
        "
        SELECT id
        FROM kat
        WHERE bind = $kat
        ORDER BY navn
        "
    );

    getUpdateTime('kat');

    foreach ($sub as $value) {
        //er der sider bundet til katagorien
        if (skriv($value['id'])) {
            return true;
        }
    }

    return false;
}

/**
 * Generate safe file name
 *
 * @param string $name String to clean
 *
 * @return string
 */
function clearFileName(string $name): string
{
    $replace = [
        '/[&?\/:*"<>|%\s-_#\\\\]+/u' => ' ',
        '/^\s+|\s+$/u'               => '', // trim
        '/\s+/u'                     => '-',
    ];
    return preg_replace(array_keys($replace), $replace, $name);
}

/**
 * Natsort an array
 *
 * @param array  $aryData     Array to sort
 * @param string $strIndex    Key of unique id
 * @param string $strSortBy   Key to sort by
 * @param string $strSortType Revers sorting
 *
 * @return array
 */
function arrayNatsort(array $aryData, string $strIndex, string $strSortBy, string $strSortType = 'asc'): array
{
    //Make sure the sort by is a string
    $strSortBy .= '';
    //Make sure the index is a string
    $strIndex .= '';

    //if the parameters are invalid
    if (!is_array($aryData) || $strIndex === '' || $strSortBy === '') {
        return $aryData;
    }

    //create our temporary arrays
    $arySort = $aryResult = [];

    //loop through the array
    foreach ($aryData as $aryRow) {
        //set up the value in the array
        $arySort[$aryRow[$strIndex]] = $aryRow[$strSortBy];
    }

    //apply the natural sort
    natcasesort($arySort);

    //if the sort type is descending
    if ($strSortType == 'desc' || $strSortType == '-') {
        //reverse the array
        arsort($arySort);
    }

    //loop through the sorted and original data
    foreach ($arySort as $arySortKey => $arySorted) {
        foreach ($aryData as $aryOriginal) {
            //if the key matches
            if ($aryOriginal[$strIndex]==$arySortKey) {
                //add it to the output array
                array_push($aryResult, $aryOriginal);
                break;
            }
        }
    }

    //return the result
    return $aryResult;
}

/**
 * Sort a 2D array based on a custome sort order an array
 *
 * @param array  $aryData         Array to sort
 * @param string $strIndex        Key of unique id
 * @param string $strSortBy       Key to sort by
 * @param int    $intSortingOrder Custome sorting to use
 * @param string $strSortType     Revers sorting
 *
 * @return array
 */
function arrayListsort(array $aryData, string $strIndex, string $strSortBy, int $intSortingOrder, string $strSortType = 'asc'): array
{
    if (!is_array($aryData) || !$strIndex || !$strSortBy) {
        return $aryData;
    }

    $kaliber = db()->fetchArray(
        "
        SELECT text
        FROM `tablesort`
        WHERE id = " . $intSortingOrder
    );
    if ($kaliber) {
        $kaliber = explode('<', $kaliber[0]['text']);
    }

    getUpdateTime('tablesort');

    $arySort = $aryResult = array();

    foreach ($aryData as $aryRow) {
        $arySort[$aryRow[$strIndex]] = -1;
        foreach ($kaliber as $kalKey => $kalSort) {
            if ($aryRow[$strSortBy]==$kalSort) {
                $arySort[$aryRow[$strIndex]] = $kalKey;
                    break;
            }
        }
    }

    natcasesort($arySort);

    if ($strSortType=="desc" || $strSortType=="-") {
        arsort($arySort);
    }

    foreach ($arySort as $arySortKey => $arySorted) {
        foreach ($aryData as $aryOriginal) {
            if ($aryOriginal[$strIndex]==$arySortKey) {
                array_push($aryResult, $aryOriginal);
                break;
            }
        }
    }

    return $aryResult;
}

/**
 * Return html for a sorted list
 *
 * @param int $listid      Id of list
 * @param int $bycell      What cell to sort by
 * @param int $current_kat Id of current category
 *
 * @return array
 */
function getTable(int $listid, int $bycell = null, int $current_kat = null): array
{
    $html = '';

    getUpdateTime('lists');
    $lists = db()->fetchArray("SELECT * FROM `lists` WHERE id = " . $listid);

    getUpdateTime('list_rows');
    $rows = db()->fetchArray(
        "
        SELECT *
        FROM `list_rows`
        WHERE `list_id` = " . $listid
    );
    if ($rows) {
        //Explode sorts
        $lists[0]['sorts'] = explode('<', $lists[0]['sorts']);
        $lists[0]['cells'] = explode('<', $lists[0]['cells']);
        $lists[0]['cell_names'] = explode('<', $lists[0]['cell_names']);

        if (!$bycell && $bycell !== '0') {
            $bycell = $lists[0]['sort'];
        }

        //Explode cells
        foreach ($rows as $row) {
            $cells = explode('<', $row['cells']);
            $cells['id'] = $row['id'];
            $cells['link'] = $row['link'];
            $rows_cells[] = $cells;
        }
        $rows = $rows_cells;
        unset($row);
        unset($cells);
        unset($rows_cells);

        //Sort rows
        if ($lists[0]['sorts'][$bycell] < 1) {
            $rows = arrayNatsort($rows, 'id', $bycell);
        } else {
            $rows = arrayListsort(
                $rows,
                'id',
                $bycell,
                $lists[0]['sorts'][$bycell]
            );
        }

        //unset temp holder for rows

        $html .= '<table class="tabel">';
        if ($lists[0]['title']) {
            $html .= '<caption>'.$lists[0]['title'].'</caption>';
        }
        $html .= '<thead><tr>';
        foreach ($lists[0]['cell_names'] as $key => $cell_name) {
            $html .= '<td><a href="" onclick="x_getTable(\'' . $lists[0]['id']
            . '\', \'' . $key . '\', ' . $current_kat
            . ', inject_html);return false;">' . $cell_name . '</a></td>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $i => $row) {
            $html .= '<tr';
            if ($i % 2) {
                $html .= ' class="altrow"';
            }
            $html .= '>';
            if ($row['link']) {
                getUpdateTime('sider');
                getUpdateTime('kat');
                $sider = db()->fetchArray(
                    "
                    SELECT `sider`.`navn`, `kat`.`navn` AS `kat_navn`
                    FROM `sider` JOIN `kat` ON `kat`.`id` = " . $current_kat . "
                    WHERE `sider`.`id` = " . $row['link'] . "
                    LIMIT 1
                    "
                );
                $row['link'] = '<a href="/kat' . $current_kat . '-'
                . clearFileName($sider[0]['kat_navn']) . '/side' . $row['link']
                . '-' . clearFileName($sider[0]['navn']) . '.html">';
            }
            foreach ($lists[0]['cells'] as $key => $type) {
                if (empty($row[$key])) {
                    $row[$key] = '';
                }

                switch ($type) {
                    case 0:
                        //Plain text
                        $html .= '<td>';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        $html .= $row[$key];
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        break;
                    case 1:
                        //number
                        $html .= '<td style="text-align:right;">';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        $html .= $row[$key];
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        break;
                    case 2:
                        //price
                        $html .= '<td style="text-align:right;" class="Pris">';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        if (is_numeric(@$row[$key])) {
                            $html .= str_replace(
                                ',00',
                                ',-',
                                number_format($row[$key], 2, ',', '.')
                            );
                        } else {
                            $html .= @$row[$key];
                        }
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                            $html .= '</td>';
                            $GLOBALS['generatedcontent']['has_product_table'] = true;
                        break;
                    case 3:
                        //new price
                        $html .= '<td style="text-align:right;" class="NyPris">';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        if (is_numeric(@$row[$key])) {
                            $html .= str_replace(
                                ',00',
                                ',-',
                                number_format($row[$key], 2, ',', '.')
                            );
                        } else {
                            $html .= @$row[$key];
                        }
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                            $html .= '</td>';
                            $GLOBALS['generatedcontent']['has_product_table'] = true;
                        break;
                    case 4:
                        //pold price
                        $html .= '<td style="text-align:right;" class="XPris">';
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        if (is_numeric(@$row[$key])) {
                            $html .= str_replace(
                                ',00',
                                ',-',
                                number_format($row[$key], 2, ',', '.')
                            );
                        }
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        break;
                    case 5:
                        //image
                        $html .= '<td>';
                        $files = db()->fetchArray(
                            "
                        SELECT *
                        FROM `files`
                        WHERE path = " . $row[$key] . "
                        LIMIT 1
                        "
                        );

                        getUpdateTime('files');

                        //TODO make image tag
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        $html .= '<img src="' . $row[$key] . '" alt="'
                        . $files[0]['alt'] . '" title="" width="' . $files[0]['width']
                        . '" height="' . $files[0]['height'] . '" />';
                        if ($row['link']) {
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        break;
                }
            }
            if (@$GLOBALS['generatedcontent']['has_product_table']) {
                $html .= '<td class="addtocart"><a href="/bestilling/?add_list_item='
                . $row['id'] . '"><img src="/theme/images/cart_add.png" title="'
                . _('Add to shopping cart') . '" alt="+" /></a></td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
    }


    $updatetime = 0;
    $included_files = get_included_files();
    foreach ($included_files as $filename) {
        $GLOBALS['cache']['updatetime']['filemtime'] = max(
            $GLOBALS['cache']['updatetime']['filemtime'],
            filemtime($filename)
        );
    }
    foreach ($GLOBALS['cache']['updatetime'] as $time) {
        $updatetime = max($updatetime, $time);
    }
    if ($updatetime < 1) {
        $updatetime = time();
    }

    doConditionalGet($updatetime);

    return array('id' => 'table'.$listid, 'html' => $html);
}

/**
 * Generate html code for lists associated with a page
 *
 * @param int $sideid Id of page
 *
 * @return string
 */
function echoTable(int $sideid): string
{
    $tablesort = db()->fetchArray(
        "
        SELECT `navn`, `text`
        FROM `tablesort`
        ORDER BY `id`"
    );

    getUpdateTime('tablesort');

    foreach ($tablesort as $value) {
        $GLOBALS['tablesort_navn'][] = $value['navn'];
        $GLOBALS['tablesort'][] = array_map('trim', explode(',', $value['text']));
    }
    //----------------------------------

    $lists = db()->fetchArray(
        "
        SELECT id
        FROM `lists`
        WHERE `page_id` = " . $sideid
    );

    getUpdateTime('lists');

    foreach ($lists as $list) {
        $html = '<div id="table'.$list['id'].'">';

        $table_html = getTable(
            $list['id'],
            null,
            $GLOBALS['generatedcontent']['activmenu']
        );
        $html .= $table_html['html'];
        $html .= '</div>';
    }

    if (!isset($html)) {
        $html = '';
    }

    return $html;
}

/**
 * Get alle gategories leading up a given one
 *
 * @param int $id Id of the end category
 *
 * @return array Ids of all the categories leading up to $id
 */
function kats(int $id): array
{
    $kat = db()->fetchOne(
        "
        SELECT bind
        FROM kat
        WHERE id = " . (int) $id . "
        LIMIT 1
        "
    );

    getUpdateTime('kat');

    if ($kat) {
        $data =  kats($kat['bind']);
        $nr = count($data);
        $kats[0] = $id;
        foreach ($data as $value) {
            $kats[] = $value;
        }
    }

    if (!isset($kats)) {
        $kats = array();
    }

    return $kats;
}

/**
 * Search for root
 *
 * @param int $bind Kategory id
 *
 * @return int Kategory id of the root branch where $bind belongs to
 */
function binding(int $bind): int
{
    if ($bind > 0) {
        $sog_kat = db()->fetchOne(
            "
            SELECT `bind`
            FROM `kat`
            WHERE id = '" . $bind . "'"
        );

        getUpdateTime('kat');

        return binding($sog_kat['bind']);
    }

    return $bind;
}

/**
 * @param string $string
 *
 * @return string
 */
function xhtmlEsc(string $string): string
{
    return htmlspecialchars($string, ENT_COMPAT | ENT_XHTML);
}

/**
 * Populate the generated global list with a page
 *
 * @param array  $side    A page
 * @param string $katnavn Title of category
 * @param int    $type    Display mode
 *
 * @return null
 */
function vare(array $side, string $katnavn, int $type)
{
    //Search categories does not have a fixed number, use first fixed per page
    if (!$GLOBALS['generatedcontent']['activmenu']) {
        $bind = db()->fetchArray(
            "
            SELECT kat
            FROM bind
            WHERE side = " . $side['id']
        );
        $GLOBALS['generatedcontent']['activmenu'] = $bind[0]['kat'];
        if (empty($GLOBALS['cache']['kats'][$bind[0]['kat']]['navn'])) {
            $kat = db()->fetchArray(
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
        $katnavn = $GLOBALS['cache']['kats'][$bind[0]['kat']]['navn'] ?? '';
    }

    $link = '/kat' . $GLOBALS['generatedcontent']['activmenu'] . '-'
    . clearFileName($katnavn) . '/side' . $side['id'] . '-'
    . clearFileName($side['navn']) . '.html';
    $name = xhtmlEsc($side['navn']);

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
function stringLimit(string $string, int $length = 50, string $ellipsis = '…'): string
{
    if (!$length || mb_strlen($string) <= $length) {
        return $string;
    }

    $length -= mb_strlen($ellipsis);
    $string = mb_substr($string, 0, $length);
    $string = trim($string);
    if (mb_strlen($string) >= $length) {
        $string = preg_replace('/\s+\S+$/u', '', $string);
    }

    return $string . (mb_strlen($string) === $length ? '' : ' ') . $ellipsis;
}

/**
 * Figure out how to display the active category
 */
function liste()
{
    $bind = db()->fetchArray(
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

    $kat = db()->fetchArray(
        "
        SELECT navn, vis
        FROM kat
        WHERE id = " . $GLOBALS['generatedcontent']['activmenu']
    );

    getUpdateTime('kat');

    if ($bind) {
        if (count($bind) == 1) {
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
 * @param array  $pages         Array of products
 * @param string $categoryTitle Title of the category
 * @param int    $categoryId    Id of the category
 *
 * @return string
 */
function katHTML(array $pages, string $categoryTitle, int $categoryId): string
{
    $html = '<table class="tabel"><thead><tr><td><a href="" onclick="x_getKat(\''
    . $categoryId
    . '\', \'navn\', inject_html);return false">Titel</a></td><td><a href="" onclick="x_getKat(\''
    . $categoryId
    . '\', \'for\', inject_html);return false">Før</a></td><td><a href="" onclick="x_getKat(\''
    . $categoryId
    . '\', \'pris\', inject_html);return false">Pris</a></td><td><a href="" onclick="x_getKat(\''
    . $categoryId
    . '\', \'varenr\', inject_html);return false">#</a></td></tr></thead><tbody>';

    $isEven = false;
    foreach ($pages as $page) {
        if (!$page['for']) {
            $page['for'] = '';
        } else {
            $page['for'] = $page['for'].',-';
        }

        if (!$page['pris']) {
            $page['pris'] = '';
        } else {
            $page['pris'] = $page['pris'].',-';
        }

        $html .= '<tr' . ($isEven ? ' class="altrow"' : '')
        . '><td><a href="/kat' . $categoryId . '-'
        . clearFileName($categoryTitle) . '/side' . $page['id'] . '-'
        . clearFileName($page['navn']) . '.html">' . $page['navn']
        . '</a></td><td class="XPris" align="right">' . $page['for']
        . '</td><td class="Pris" align="right">' . $page['pris']
        . '</td><td align="right" style="font-size:11px">'
        . $page['varenr'] . '</td></tr>';

        $isEven = !$isEven;
    }

    return $html . '</tbody></table>';
}

/**
 * Search for pages and generate a list or redirect if only one was found
 *
 * @param string $q          Tekst to search for
 * @param string $wheresider Additional sql where clause
 *
 * @return null
 */
function searchListe(string $q, string $wheresider)
{
    //TODO duplicate text with out html for better searching.
    global $qext;
    $pages = [];

    if ($qext) {
        $qext = ' WITH QUERY EXPANSION';
    } else {
        $qext = '';
    }

    if ($q) {
        $sider = db()->fetchArray(
            "
            SELECT *, MATCH(navn, text, beskrivelse) AGAINST ('$q'$qext) AS score
            FROM sider
            WHERE MATCH (navn, text, beskrivelse) AGAINST('$q'$qext) > 0
            $wheresider
            ORDER BY `score` DESC
            "
        );
        foreach ($sider as $page) {
            $pages[$page['id']] = $page;
        }
        unset($sider);

        // Fulltext search doesn't catch things like 3 letter words etc.
        $qsearch = array ("/ /", "/'/", "/´/", "/`/");
        $qreplace = array ("%", "_", "_", "_");
        $simpleq = preg_replace($qsearch, $qreplace, $q);
        $sider = db()->fetchArray(
            "
            SELECT * FROM `sider`
            WHERE (
                `navn` LIKE '%$simpleq%'
                OR `text` LIKE '%$simpleq%'
                OR `beskrivelse` LIKE '%$simpleq%'
            ) "
            . ($pages ? ("AND id NOT IN (" . implode(',', array_keys($pages)) . ") ") : "")
            . $wheresider
        );
        foreach ($sider as $page) {
            $pages[$page['id']] = $page;
        }
        unset($sider);

        $sider = db()->fetchArray(
            "
            SELECT sider.* FROM `list_rows`
            JOIN lists ON list_rows.list_id = lists.id
            JOIN sider ON lists.page_id = sider.id
            WHERE list_rows.`cells` LIKE '%$simpleq%'"
            . ($pages ? (" AND sider.id NOT IN (" . implode(',', array_keys($pages)) . ") ") : "")
        );
        foreach ($sider as $page) {
            $pages[$page['id']] = $page;
        }
        unset($sider);

        getUpdateTime('sider');
        getUpdateTime('list_rows');
        getUpdateTime('lists');
    } else {
        $sider = db()->fetchArray(
            "
            SELECT * FROM `sider` WHERE 1
            $wheresider
            ORDER BY `navn` ASC
            "
        );
        foreach ($sider as $page) {
            $pages[$page['id']] = $page;
        }
        unset($sider);

        getUpdateTime('sider');
    }
    // Remove inactive pages
    foreach ($pages as $key => $side) {
        if (isInactivePage($side['id'])) {
            unset($pages[$key]);
        }
    }

    return $pages;
}

/**
 * Get address from phone number
 *
 * @param string $phoneNumber Phone number
 *
 * @return array Array with address fitting the post table format
 */
function getAddress(string $phoneNumber): array
{
    $default['recName1'] = '';
    $default['recAddress1'] = '';
    $default['recZipCode'] = '';
    $default['recCVR'] = '';
    $default['recAttPerson'] = '';
    $default['recAddress2'] = '';
    $default['recPostBox'] = '';
    $default['email'] = '';

    $dbs[0]['mysql_server'] = 'jagtogfiskerimagasinet.dk.mysql';
    $dbs[0]['mysql_user'] = 'jagtogfiskerima';
    $dbs[0]['mysql_password'] = 'GxYqj5EX';
    $dbs[0]['mysql_database'] = 'jagtogfiskerima';
    $dbs[1]['mysql_server'] = 'huntershouse.dk.mysql';
    $dbs[1]['mysql_user'] = 'huntershouse_dk';
    $dbs[1]['mysql_password'] = 'sabbBFab';
    $dbs[1]['mysql_database'] = 'huntershouse_dk';
    $dbs[2]['mysql_server'] = 'arms-gallery.dk.mysql';
    $dbs[2]['mysql_user'] = 'arms_gallery_dk';
    $dbs[2]['mysql_password'] = 'hSKe3eDZ';
    $dbs[2]['mysql_database'] = 'arms_gallery_dk';
    $dbs[3]['mysql_server'] = 'geoffanderson.com.mysql';
    $dbs[3]['mysql_user'] = 'geoffanderson_c';
    $dbs[3]['mysql_password'] = '2iEEXLMM';
    $dbs[3]['mysql_database'] = 'geoffanderson_c';

    foreach ($dbs as $db) {
        $mysqli_ext = new Simple_Mysqli(
            $db['mysql_server'],
            $db['mysql_user'],
            $db['mysql_password'],
            $db['mysql_database']
        );

        //try packages
        $post = $mysqli_ext->fetchArray(
            "
            SELECT recName1, recAddress1, recZipCode
            FROM `post`
            WHERE `recipientID` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            LIMIT 1
            "
        );
        if ($post) {
            $return = array_merge($default, $post[0]);
            if ($return != $default) {
                return $return;
            }
        }

        //Try katalog orders
        $email = $mysqli_ext->fetchArray(
            "
            SELECT navn, email, adresse, post
            FROM `email`
            WHERE `tlf1` LIKE '" . $phoneNumber . "'
               OR `tlf2` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            LIMIT 1
            "
        );
        if ($email) {
            $return['recName1'] = $email[0]['navn'];
            $return['recAddress1'] = $email[0]['adresse'];
            $return['recZipCode'] = $email[0]['post'];
            $return['email'] = $email[0]['email'];
            $return = array_merge($default, $return);

            if ($return != $default) {
                return $return;
            }
        }

        //Try fakturas
        $fakturas = $mysqli_ext->fetchArray(
            "
            SELECT navn, email, att, adresse, postnr, postbox
            FROM `fakturas`
            WHERE `tlf1` LIKE '" . $phoneNumber . "'
               OR `tlf2` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            LIMIT 1
            "
        );
        if ($fakturas) {
            $return['recName1'] = $fakturas[0]['navn'];
            $return['recAddress1'] = $fakturas[0]['adresse'];
            $return['recZipCode'] = $fakturas[0]['postnr'];
            $return['recAttPerson'] = $fakturas[0]['att'];
            $return['recPostBox'] = $fakturas[0]['postbox'];
            $return['email'] = $fakturas[0]['email'];
            $return = array_merge($default, $return);

            if ($return != $default) {
                return $return;
            }
        }
    }

    //Addressen kunde ikke findes.
    return array('error' => _('The address could not be found.'));
}

/**
 * Set Last-Modified and ETag http headers
 * and use cache if no updates since last visit
 *
 * @param int $timestamp Unix time stamp of last update to content
 *
 * @return null
 */
function doConditionalGet(int $timestamp)
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
    ini_set('zlib.output_compression', '0');
    header("HTTP/1.1 304 Not Modified", true, 304);
    die();
}

/**
 * Populate the generatedcontent array with data relating to the page
 *
 * @return null
 */
function side()
{
    if (!isset($GLOBALS['side']['navn'])) {
        $sider = db()->fetchArray(
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
            WHERE id = " . $GLOBALS['side']['id']
        );

        if (!$sider) {
            header('HTTP/1.1 404 Not Found');
            //TODO lav en søgning
        }
        $GLOBALS['side']['navn']   = $sider[0]['navn'];
        $GLOBALS['side']['burde']  = $sider[0]['burde'];
        $GLOBALS['side']['fra']    = $sider[0]['fra'];
        $GLOBALS['side']['text']   = $sider[0]['text'];
        $GLOBALS['side']['pris']   = $sider[0]['pris'];
        $GLOBALS['side']['for']    = $sider[0]['for'];
        $GLOBALS['side']['krav']   = $sider[0]['krav'];
        $GLOBALS['side']['maerke'] = $sider[0]['maerke'];
        $GLOBALS['side']['varenr'] = $sider[0]['varenr'];
        $GLOBALS['side']['dato']   = $sider[0]['dato'];
        $GLOBALS['cache']['updatetime']['side'] = $sider[0]['dato'];

        unset($sider);
    }

    $GLOBALS['generatedcontent']['headline'] = $GLOBALS['side']['navn'];
    $GLOBALS['generatedcontent']['serial']   = $GLOBALS['side']['varenr'];
    $GLOBALS['generatedcontent']['datetime'] = $GLOBALS['side']['dato'];
    $GLOBALS['generatedcontent']['text']     = $GLOBALS['side']['text'];

    if ($GLOBALS['side']['krav']) {
        $krav = db()->fetchArray(
            "
            SELECT navn
            FROM krav
            WHERE id = " . $GLOBALS['side']['krav']
        );

        getUpdateTime('krav');

        $GLOBALS['generatedcontent']['requirement']['icon'] = '';
        $GLOBALS['generatedcontent']['requirement']['name'] = $krav[0]['navn'];
        $GLOBALS['generatedcontent']['requirement']['link'] = '/krav/'
        . $GLOBALS['side']['krav'] . '/' . clearFileName($krav[0]['navn'])
        . '.html';
    }

    $GLOBALS['generatedcontent']['price']['before']  = $GLOBALS['side']['for'];
    $GLOBALS['generatedcontent']['price']['now']    = $GLOBALS['side']['pris'];
    $GLOBALS['generatedcontent']['price']['from']   = $GLOBALS['side']['fra'];
    $GLOBALS['generatedcontent']['price']['market'] = $GLOBALS['side']['burde'];

    unset($GLOBALS['side']['text']);

    //TODO and figure out how to do the sorting using only js
    $GLOBALS['generatedcontent']['text'] .= echoTable($GLOBALS['side']['id']);

    $GLOBALS['generatedcontent']['price']['old']    = $GLOBALS['side']['for'];
    $GLOBALS['generatedcontent']['price']['market'] = $GLOBALS['side']['burde'];
    $GLOBALS['generatedcontent']['price']['new']    = $GLOBALS['side']['pris'];
    $GLOBALS['generatedcontent']['price']['from']   = $GLOBALS['side']['fra'];

    if (empty($GLOBALS['generatedcontent']['email'])) {
        $kat = db()->fetchArray(
            "
            SELECT `email`
            FROM `kat`
            WHERE id = " . $GLOBALS['generatedcontent']['activmenu']
        );
    }

    getUpdateTime('kat');

    if (empty($kat[0]['email'])) {
        $GLOBALS['generatedcontent']['email'] = $GLOBALS['_config']['email'];
    } else {
        $GLOBALS['generatedcontent']['email'] = $kat[0]['email'];
    }

    if ($GLOBALS['side']['maerke']) {
        $maerker = db()->fetchArray(
            "
            SELECT `id`, `navn`, `link`, `ico`
            FROM `maerke`
            WHERE `id` IN(" . $GLOBALS['side']['maerke'] . ") AND `ico` != ''
            ORDER BY `navn`
            "
        );
        $temp = db()->fetchArray(
            "
            SELECT `id`, `navn`, `link`, `ico`
            FROM `maerke`
            WHERE `id` IN(" . $GLOBALS['side']['maerke'] . ") AND `ico` = ''
            ORDER BY `navn`
            "
        );
        $maerker = array_merge($maerker, $temp);

        getUpdateTime('maerke');

        foreach ($maerker as $value) {
            $GLOBALS['generatedcontent']['brands'][] = array(
            'name' => $value['navn'],
            'link' => '/mærke' . $value['id'] . '-'
                . clearFileName($value['navn']) . '/',
            'xlink' => $value['link'],
            'icon' => $value['ico']);
        }
    }

    $tilbehor = db()->fetchArray(
        "
        SELECT sider.id,
            `bind`.`kat`,
            `sider`.`navn`,
            `billed`,
            `burde`,
            `fra`,
            `pris`,
            `for`,
            UNIX_TIMESTAMP(`dato`) AS dato
        FROM tilbehor
            JOIN sider ON tilbehor.tilbehor = sider.id
            JOIN bind ON bind.side = sider.id
        WHERE tilbehor.`side` = " . $GLOBALS['side']['id']
    );

    getUpdateTime('tilbehor');
    getUpdateTime('sider');

    foreach ($tilbehor as $value) {
        if ($value['kat']) {
            $kat = db()->fetchArray(
                "
                SELECT id, navn
                FROM kat
                WHERE id = " . $value['kat']
            );
            getUpdateTime('kat');
            $kat = '/kat'.$kat[0]['id'].'-'.clearFileName($kat[0]['navn']);
        } else {
            $kat = '';
        }
        //TODO beskrivelse
        $GLOBALS['generatedcontent']['accessories'][] = array(
            'name' => $value['navn'],
            'link' => $kat . '/side' . $value['id'] . '-'
                . clearFileName($value['navn']) . '.html',
            'icon' => $value['billed'],
            'text' => '',
            'price' => array('before' => $value['for'],
            'now' => $value['pris'],
            'from' => $value['fra'],
            'market' => $value['burde']));
    }
}
