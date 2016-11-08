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

defined('_ROOT_') || define('_ROOT_', realpath(__DIR__ . '/..' ));
define('CATEGORY_HIDDEN', 0);
define('CATEGORY_GALLERY', 1);
define('CATEGORY_LIST', 2);

ini_set('display_errors', 1);
error_reporting(-1);
date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain('agcms', __DIR__ . '/../theme/locale');
bind_textdomain_codeset('agcms', 'UTF-8');
textdomain('agcms');
mb_language('uni');
mb_detect_order('UTF-8, ISO-8859-1');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/ORM.php';
require_once __DIR__ . '/Entity/Category.php';
require_once __DIR__ . '/Entity/Page.php';
require_once __DIR__ . '/sajax.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/imap.php';
include_once __DIR__ . '/../vendor/phpmailer/phpmailer/language/phpmailer.lang-dk.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/class.smtp.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/class.phpmailer.php';

function db()
{
    static $db;
    if (!$db) {
        $db = new DB(
            $GLOBALS['_config']['mysql_server'],
            $GLOBALS['_config']['mysql_user'],
            $GLOBALS['_config']['mysql_password'],
            $GLOBALS['_config']['mysql_database']
        );
    }
    return $db;
}

function redirect(string $url, int $code = 301)
{
    ini_set('zlib.output_compression', '0');
    header('Location: ' . $url, true, $code);
    die();
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

    if (filter_var($user . '@' . $domain, FILTER_VALIDATE_EMAIL) && checkMx($domain)) {
        return true;
    }

    return false;
}

function checkMx(string $domain): bool {
    static $ceche = [];

    if (!isset($ceche[$domain])) {
        $ceche[$domain] = getmxrr($domain, $dummy);
    }

    return $ceche[$domain];
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

    $kaliber = db()->fetchOne(
        "
        SELECT text
        FROM `tablesort`
        WHERE id = " . $intSortingOrder
    );
    if ($kaliber) {
        $kaliber = explode('<', $kaliber['text']);
    }

    Cache::addLoadedTable('tablesort');

    $arySort = $aryResult = [];

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
 * @param int $listid     Id of list
 * @param int $bycell     What cell to sort by
 * @param int $categoryId Id of current category
 *
 * @return array
 */
function getTable(int $listid, int $bycell = null, int $categoryId = null): array
{
    $category = $categoryId ? ORM::getOne(Category::class, $categoryId) : null;
    $html = '';

    Cache::addLoadedTable('lists');
    $list = db()->fetchOne("SELECT * FROM `lists` WHERE id = " . $listid);

    Cache::addLoadedTable('list_rows');
    $rows = db()->fetchArray(
        "
        SELECT *
        FROM `list_rows`
        WHERE `list_id` = " . $listid
    );
    if ($rows) {
        //Explode sorts
        $list['sorts'] = explode('<', $list['sorts']);
        $list['cells'] = explode('<', $list['cells']);
        $list['cell_names'] = explode('<', $list['cell_names']);

        if (!$bycell && $bycell !== '0') {
            $bycell = $list['sort'];
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
        if ($list['sorts'][$bycell] < 1) {
            $rows = arrayNatsort($rows, 'id', $bycell);
        } else {
            $rows = arrayListsort(
                $rows,
                'id',
                $bycell,
                $list['sorts'][$bycell]
            );
        }

        //unset temp holder for rows

        $html .= '<table class="tabel">';
        if ($list['title']) {
            $html .= '<caption>'.$list['title'].'</caption>';
        }
        $html .= '<thead><tr>';
        foreach ($list['cell_names'] as $key => $cell_name) {
            $html .= '<td><a href="" onclick="x_getTable(\'' . $list['id']
            . '\', \'' . $key . '\', ' . ($category ? $category->getId() : '')
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
                $page = ORM::getOne(Page::class, $row['link']);
                $row['link'] = '<a href="' . xhtmlEsc($page->getCanonicalLink($category)) . '">';
            }
            foreach ($list['cells'] as $key => $type) {
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
                        $files = db()->fetchOne(
                            "
                            SELECT *
                            FROM `files`
                            WHERE path = " . $row[$key]
                        );
                        Cache::addLoadedTable('files');

                        //TODO make image tag
                        if ($row['link']) {
                            $html .= $row['link'];
                        }
                        $html .= '<img src="' . $row[$key] . '" alt="'
                        . $files['alt'] . '" title="" width="' . $files['width']
                        . '" height="' . $files['height'] . '" />';
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

    doConditionalGet(Cache::getUpdateTime());

    return ['id' => 'table'.$listid, 'html' => $html];
}

/**
 * Generate html code for lists associated with a page
 *
 * @param int $pageId Id of page
 *
 * @return string
 */
function echoTable(int $pageId): string
{
    $tablesort = db()->fetchArray(
        "
        SELECT `navn`, `text`
        FROM `tablesort`
        ORDER BY `id`"
    );
    Cache::addLoadedTable('tablesort');

    foreach ($tablesort as $value) {
        $GLOBALS['tablesort_navn'][] = $value['navn'];
        $GLOBALS['tablesort'][] = array_map('trim', explode(',', $value['text']));
    }

    $lists = db()->fetchArray(
        "
        SELECT id
        FROM `lists`
        WHERE `page_id` = " . $pageId
    );
    Cache::addLoadedTable('lists');

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
 * @param Page     $page       A page
 * @param int      $renderMode Display mode
 * @param Category $category   Category
 *
 * @return null
 */
function vare(Page $page, int $renderMode, Category $category = null)
{
    if ($renderMode === CATEGORY_GALLERY) {
        $GLOBALS['generatedcontent']['list'][] = [
            'id' => $page->getId(),
            'name' => xhtmlEsc($page->getTitle()),
            'date' => $page->getTimeStamp(),
            'link' => $page->getCanonicalLink(false, $category),
            'icon' => $page->getImagePath(),
            'text' => $page->getExcerpt(),
            'price' => [
                'before' => $page->getOldPrice(),
                'now' => $page->getPrice(),
                'from' => $page->getPriceType(),
                'market' => $page->getOldPriceType(),
            ]
        ];
    } else {
        $GLOBALS['generatedcontent']['list'][] = [
            'id' => $page->getId(),
            'name' => xhtmlEsc($page->getTitle()),
            'date' => $page->getTimeStamp(),
            'link' => $page->getCanonicalLink(false, $category),
            'serial' => $page->getSku(),
            'price' => [
                'before' => $page->getOldPrice(),
                'now' => $page->getPrice(),
            ]
        ];
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
function liste(Category $category)
{
    $pages = $category->getPages();
    if (count($pages) === 1) {
        $GLOBALS['side']['id'] = reset($pages)->getId();
        side();
    } elseif ($pages) {
        $pages = arrayNatsort($pages, 'id', 'navn', 'asc');
        foreach ($pages as $page) {
            //Add space around all tags, strip all tags,
            //remove all unneded white space
            if ($category->getRenderMode() !== CATEGORY_HIDDEN) {
            }
            vare($value, $category->getRenderMode());
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
    $pages = [];

    if ($q) {
        $pages = ORM::getByQuery(
            Page::class,
            "
            SELECT *, MATCH(navn, text, beskrivelse) AGAINST ('$q') AS score
            FROM sider
            WHERE MATCH (navn, text, beskrivelse) AGAINST('$q') > 0
            $wheresider
            ORDER BY `score` DESC
            "
        );
        foreach ($pages as $page) {
            $pages[$page->getId()] = $page;
        }

        // Fulltext search doesn't catch things like 3 letter words etc.
        $qsearch = ['/\s+/u', "/'/u", '/´/u', '/`/u'];
        $qreplace = ['%', '_', '_', '_'];
        $simpleq = preg_replace($qsearch, $qreplace, $q);
        $pages = ORM::getByQuery(
            Page::class,
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
        foreach ($pages as $page) {
            $pages[$page->getId()] = $page;
        }

        $pages = ORM::getByQuery(
            Page::class,
            "
            SELECT sider.* FROM `list_rows`
            JOIN lists ON list_rows.list_id = lists.id
            JOIN sider ON lists.page_id = sider.id
            WHERE list_rows.`cells` LIKE '%$simpleq%'"
            . ($pages ? (" AND sider.id NOT IN (" . implode(',', array_keys($pages)) . ") ") : "")
        );
        Cache::addLoadedTable('list_rows');
        Cache::addLoadedTable('lists');
        foreach ($pages as $page) {
            $pages[$page->getId()] = $page;
        }

    } else {
        $pages = ORM::getByQuery(
            Page::class,
            "
            SELECT * FROM `sider` WHERE 1
            $wheresider
            ORDER BY `navn` ASC
            "
        );
        foreach ($pages as $page) {
            $pages[$page->getId()] = $page;
        }
    }

    // Remove inactive pages
    foreach ($pages as $key => $page) {
        if ($page->isInactive()) {
            unset($pages[$key]);
        }
    }

    return array_values($pages);
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
    $default = [
        'recName1'     => '',
        'recAddress1'  => '',
        'recZipCode'   => '',
        'recCVR'       => '',
        'recAttPerson' => '',
        'recAddress2'  => '',
        'recPostBox'   => '',
        'email'        => '',
    ];

    $dbs = [
        [
            'mysql_server'   => 'jagtogfiskerimagasinet.dk.mysql',
            'mysql_user'     => 'jagtogfiskerima',
            'mysql_password' => 'GxYqj5EX',
            'mysql_database' => 'jagtogfiskerima',
        ],
        [
            'mysql_server'   => 'huntershouse.dk.mysql',
            'mysql_user'     => 'huntershouse_dk',
            'mysql_password' => 'sabbBFab',
            'mysql_database' => 'huntershouse_dk',
        ],
        [
            'mysql_server'   => 'arms-gallery.dk.mysql',
            'mysql_user'     => 'arms_gallery_dk',
            'mysql_password' => 'hSKe3eDZ',
            'mysql_database' => 'arms_gallery_dk',
        ],
        [
            'mysql_server'   => 'geoffanderson.com.mysql',
            'mysql_user'     => 'geoffanderson_c',
            'mysql_password' => '2iEEXLMM',
            'mysql_database' => 'geoffanderson_c',
        ],
    ];

    foreach ($dbs as $db) {
        $db = new DB(
            $db['mysql_server'],
            $db['mysql_user'],
            $db['mysql_password'],
            $db['mysql_database']
        );

        //try packages
        $post = $db->fetchOne(
            "
            SELECT recName1, recAddress1, recZipCode
            FROM `post`
            WHERE `recipientID` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            "
        );
        if ($post) {
            $return = array_merge($default, $post);
            if ($return != $default) {
                return $return;
            }
        }

        //Try katalog orders
        $email = $db->fetchOne(
            "
            SELECT navn, email, adresse, post
            FROM `email`
            WHERE `tlf1` LIKE '" . $phoneNumber . "'
               OR `tlf2` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            "
        );
        if ($email) {
            $return['recName1'] = $email['navn'];
            $return['recAddress1'] = $email['adresse'];
            $return['recZipCode'] = $email['post'];
            $return['email'] = $email['email'];
            $return = array_merge($default, $return);

            if ($return != $default) {
                return $return;
            }
        }

        //Try fakturas
        $fakturas = $db->fetchOne(
            "
            SELECT navn, email, att, adresse, postnr, postbox
            FROM `fakturas`
            WHERE `tlf1` LIKE '" . $phoneNumber . "'
               OR `tlf2` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            "
        );
        if ($fakturas) {
            $return['recName1'] = $fakturas['navn'];
            $return['recAddress1'] = $fakturas['adresse'];
            $return['recZipCode'] = $fakturas['postnr'];
            $return['recAttPerson'] = $fakturas['att'];
            $return['recPostBox'] = $fakturas['postbox'];
            $return['email'] = $fakturas['email'];
            $return = array_merge($default, $return);

            if ($return != $default) {
                return $return;
            }
        }
    }

    //Addressen kunde ikke findes.
    return ['error' => _('The address could not be found.')];
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

    header('Cache-Control: max-age=0, must-revalidate');    // HTTP/1.1
    header('Pragma: no-cache');    // HTTP/1.0
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
    header('HTTP/1.1 304 Not Modified', true, 304);
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
        $page = ORM::getOne(Page::class, $GLOBALS['side']['id']);
        Cache::addUpdateTime($page->getTimeStamp());

        $GLOBALS['side'] = [
            'navn'   => $page->getTitle(),
            'burde'  => $page->getOldPriceType(),
            'fra'    => $page->getPriceType(),
            'text'   => $page->getHtml(),
            'pris'   => $page->getPrice(),
            'for'    => $page->getOldPrice(),
            'krav'   => $page->getRequirementId(),
            'maerke' => $page->getBrandId(),
            'varenr' => $page->getSku(),
            'dato'   => $page->getTimeStamp(),
        ];
    }

    $GLOBALS['generatedcontent']['headline'] = $GLOBALS['side']['navn'];
    $GLOBALS['generatedcontent']['serial']   = $GLOBALS['side']['varenr'];
    $GLOBALS['generatedcontent']['datetime'] = $GLOBALS['side']['dato'];
    $GLOBALS['generatedcontent']['text']     = $GLOBALS['side']['text'];

    if ($GLOBALS['side']['krav']) {
        $krav = db()->fetchOne(
            "
            SELECT navn
            FROM krav
            WHERE id = " . $GLOBALS['side']['krav']
        );
        Cache::addLoadedTable('krav');

        $GLOBALS['generatedcontent']['requirement']['icon'] = '';
        $GLOBALS['generatedcontent']['requirement']['name'] = $krav['navn'];
        $GLOBALS['generatedcontent']['requirement']['link'] = '/krav/'
        . $GLOBALS['side']['krav'] . '/' . clearFileName($krav['navn'])
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

    Cache::addLoadedTable('kat');

    $GLOBALS['generatedcontent']['email'] = $GLOBALS['_config']['email'];
    $category = ORM::getOne(Category::class, $GLOBALS['generatedcontent']['activmenu']);
    if ($category) {
        $GLOBALS['generatedcontent']['email'] = $category->getEmail();
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
        Cache::addLoadedTable('maerke');
        $maerker = array_merge($maerker, $temp);

        Cache::addLoadedTable('maerke');

        foreach ($maerker as $value) {
            $GLOBALS['generatedcontent']['brands'][] = [
                'name' => $value['navn'],
                'link' => '/mærke' . $value['id'] . '-' . clearFileName($value['navn']) . '/',
                'xlink' => $value['link'],
                'icon' => $value['ico']
            ];
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
    Cache::addLoadedTable('tilbehor');
    Cache::addLoadedTable('sider');

    foreach ($tilbehor as $value) {
        $url = '/';
        if ($value['kat']) {
            $category = ORM::getOne(Category::class, $value['kat']);
            $url .= $category ? $category->getSlug() : '';
        } else {
        }
        //TODO beskrivelse
        $GLOBALS['generatedcontent']['accessories'][] = [
            'name' => $value['navn'],
            'link' => $url . 'side' . $value['id'] . '-' . clearFileName($value['navn']) . '.html',
            'icon' => $value['billed'],
            'text' => '',
            'price' => [
                'before' => $value['for'],
                'now' => $value['pris'],
                'from' => $value['fra'],
                'market' => $value['burde']
            ]
        ];
    }
}

/**
 * Get list of sub categories in format fitting the generatedcontent structure
 *
 * @param int  $nr               Id of categorie to look under
 * @param bool $custom_sort_subs If set to false categories will be naturaly sorted
 *                               by title
 *
 * @return array
 */
function menu(int $nr, bool $custom_sort_subs = false): array
{
    $categories = ORM::getByQuery(
        Category::class,
        "
        SELECT *
        FROM kat
        WHERE kat.vis != " . CATEGORY_HIDDEN . "
            AND kat.bind = " . $GLOBALS['kats'][$nr] . "
        ORDER BY kat.`order`, kat.navn
        "
    );

    $menu = [];
    if (!$custom_sort_subs) {
        $categories = arrayNatsort($categories, 'id', 'navn', 'asc');
    }

    foreach ($categories as $category) {
        if (!$category->isVisable()) {
            continue;
        }

        //Er katagorien aaben
        $subs = [];
        if (@$GLOBALS['kats'][$nr+1] === $category->getId()) {
            $subs = menu($nr+1, $categoryId->getRenderMode());
        }

        //tegn under punkter
        $menu[] = [
            'id' => $category->getId(),
            'name' => xhtmlEsc($category->getTitle()),
            'link' => '/' . $category->getSlug(),
            'icon' => $category->getIconPath(),
            'sub' => $category->getChildren(true),
            'subs' => $subs,
        ];
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
function searchMenu(string $q, string $wherekat)
{
    $categories = [];
    $maerke = [];
    if ($q) {
        $categories = ORM::getByQuery(
            Category::class,
            "
            SELECT *, MATCH (navn) AGAINST ('$q') AS score
            FROM kat
            WHERE MATCH (navn) AGAINST('$q') > 0 " . $wherekat . "
                AND `vis` != '0'
            ORDER BY score, navn
            "
        );
        $qsearch = ['/\s+/u', "/'/u", '//u', '/`/u'];
        $qreplace = ['%', '_', '_', '_'];
        $simpleq = preg_replace($qsearch, $qreplace, $q);
        if (!$categories) {
            $categories = ORM::getByQuery(
                Category::class,
                "
                SELECT * FROM kat WHERE navn
                LIKE '%".$simpleq."%' " . $wherekat . "
                ORDER BY navn
                "
            );
        }
        $maerke = db()->fetchArray(
            "
            SELECT id, navn
            FROM `maerke`
            WHERE MATCH (navn) AGAINST ('$q') >  0
            "
        );
        Cache::addLoadedTable('maerke');
        if (!$maerke) {
            $maerke = db()->fetchArray(
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

    foreach ($maerke as $value) {
        $GLOBALS['generatedcontent']['search_menu'][] = [
            'id' => 0,
            'name' => xhtmlEsc($value['navn']),
            'link' => '/mærke' . $value['id'] . '-' .clearFileName($value['navn']) . '/'
        ];
    }

    foreach ($categories as $category) {
        if ($category->isVisable()) {
            $GLOBALS['generatedcontent']['search_menu'][] = [
                'id' => $category->getId(),
                'name' => xhtmlEsc($category->getTitle()),
                'link' => '/' . $category->getSlug(),
                'icon' => $category->getIconPath(),
                'sub' => (bool) $category->getChildren(true),
            ];
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
function isInactivePage(int $id): bool
{
    $categoryId = ORM::getOneByQuery(
        Category::class,
        "
        SELECT `kat`.*
        FROM `bind`
        JOIN `kat` ON `kat`.id = `bind`.`kat`
        WHERE `side` = " . $id
    );
    Cache::addLoadedTable('bind');
    if (!$categoryId || $categoryId->isInactive()) {
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

    return db()->escapeWildcards(db()->esc($s));
}

/**
 * Print XML for content bellonging to a category
 *
 * @param int $id Id of category
 *
 * @return null
 */
function listKats(Category $category = null)
{
    if (!$category) {
        $categories = ORM::getByQuery(Category::class, "SELECT * FROM kat WHERE bind = 0");
        foreach ($categories as $key => $category) {
            if (!$category->isVisable()) {
                unset($categories[$key]);
            }
        }
    } else {
        $categories = $category->getChildren(true);
    }

    foreach ($categories as $category) {
        //print xml
        ?><url><loc><?php
        echo htmlspecialchars($GLOBALS['_config']['base_url'] . '/' . $category->getSlug(), ENT_COMPAT | ENT_XML1);
        ?></loc><changefreq>weekly</changefreq><priority>0.5</priority></url><?php
        foreach ($category->getPages() as $page) {
            //print xml
            ?><url><loc><?php
            echo htmlspecialchars($GLOBALS['_config']['base_url'] . $page->getCanonicalLink(false, $category), ENT_COMPAT | ENT_XML1);
            ?></loc><lastmod><?php
            echo htmlspecialchars(mb_substr($page->getTimeStamp(), 0, -9, 'UTF-8'), ENT_COMPAT | ENT_XML1);
            ?></lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url><?php
        }

        listKats($category);
    }
}

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
    Cache::addLoadedTable('sider');
    Cache::addLoadedTable('bind');
    $bind = arrayNatsort($bind, 'id', $sort);

    $category = ORM::getOne(Category::class, $GLOBALS['generatedcontent']['activmenu']);

    //check browser cache
    doConditionalGet(Cache::getUpdateTime());

    return [
        'id' => 'kat' . $GLOBALS['generatedcontent']['activmenu'],
        'html' => katHTML($bind, $category->getTitle(), $GLOBALS['generatedcontent']['activmenu']),
    ];
}

/**
 * Generate a 5 didget code from the order id
 *
 * @param int $id Order id to generate code from
 *
 * @return string
 */
function getCheckid(int $id): string
{
    return substr(md5($id . $GLOBALS['_config']['pbssalt']), 3, 5);
}

/**
 * Checks that all nessesery contact information has been filled out correctly
 *
 * @param array $values Keys are: email, navn, land, postbox, adresse, postnr, by,
 *                      altpost (bool), postname, postpostbox, postaddress,
 *                      postcountry, postpostalcode, postcity
 *
 * @return array Key with bool true for each faild feald
 */
function validate(array $values): array
{
    $rejected = [];

    if (!valideMail(@$values['email'])) {
        $rejected['email'] = true;
    }
    if (empty($values['navn'])) {
        $rejected['navn'] = true;
    }
    if (empty($values['land'])) {
        $rejected['land'] = true;
    }
    if (empty($values['postbox'])
        && (empty($values['adresse']) || ($values['land'] == 'DK' && !preg_match('/\s/ui', @$values['adresse'])))
    ) {
        $rejected['adresse'] = true;
    }
    if (empty($values['postnr'])) {
        $rejected['postnr'] = true;
    }
    //TODO if land = DK and postnr != by
    if (empty($values['by'])) {
        $rejected['by'] = true;
    }
    if (!$values['land']) {
        $rejected['land'] = true;
    }
    if (!empty($values['altpost'])) {
        if (empty($values['postname'])) {
            $rejected['postname'] = true;
        }
        if (empty($values['land'])) {
            $rejected['land'] = true;
        }
        if (empty($values['postpostbox'])
            && (empty($values['postaddress']) || ($values['postcountry'] == 'DK' && !preg_match('/\s/ui', $values['postaddress'])))
        ) {
            $rejected['postaddress'] = true;
        }
        if (empty($values['postpostalcode'])) {
            $rejected['postpostalcode'] = true;
        }
        //TODO if postcountry = DK and postpostalcode != postcity
        if (empty($values['postcity'])) {
            $rejected['postcity'] = true;
        }
        if (empty($values['postcountry'])) {
            $rejected['postcountry'] = true;
        }
    }
    return $rejected;
}
