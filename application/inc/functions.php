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

defined('_ROOT_') || define('_ROOT_', realpath(__DIR__ . '/..'));

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

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';


spl_autoload_register(function ($class_name) {
    $classMap = [
        'Category' => 'Entity/Category',
        'Page' => 'Entity/Page',
    ];

    if (isset($classMap[$class_name])) {
        $class_name = $classMap[$class_name];
    }

    $file = __DIR__ . '/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once __DIR__ . '/' . $class_name . '.php';
    }
});

function db(DB $overwrite = null)
{
    static $db;
    if ($overwrite) {
        $db = $overwrite;
    } elseif (!$db) {
        $db = new DB(
            $GLOBALS['_config']['mysql_server'],
            $GLOBALS['_config']['mysql_user'],
            $GLOBALS['_config']['mysql_password'],
            $GLOBALS['_config']['mysql_database']
        );
    }
    return $db;
}

function redirect(string $url, int $status = 303)
{
    if (headers_sent()) {
        throw new Exception(_('Header already sent!'));
    }

    $url = parse_url($url);
    if (empty($url['scheme'])) {
        $url['scheme'] = !empty($_SERVER['HTTPS']) && mb_strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
    }
    if (empty($url['host'])) {
        if (!empty($_SERVER['HTTP_HOST'])) {
            // Browser
            $url['host'] = $_SERVER['HTTP_HOST'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            // Can both be from Browser and server (virtual) config
            $url['host'] = $_SERVER['SERVER_NAME'];
        } else {
            // IP
            $url['host'] = $_SERVER['SERVER_ADDR'];
        }
    }
    if (empty($url['path'])) {
        $url['path'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    } elseif (mb_substr($url['path'], 0, 1) !== '/') {
        //The redirect is relative to current path
        $path = [];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        preg_match('#^\S+/#u', $requestPath, $path);
        $url['path'] = $path[0] . $url['path'];
    }
    $url['path'] = encodeUrl($url['path']);
    $url = unparseUrl($url);

    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', 1);
    }
    ini_set('zlib.output_compression', 0);

    header('Location: ' . $url, true, $status);
    die();
}

/**
 * Build a url string from an array
 *
 * @param array $parsed_url Array as returned by parse_url()
 *
 * @return string The URL
 */
function unparseUrl(array $parsed_url): string
{
    $scheme   = !empty($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = !empty($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = !empty($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = !empty($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass     = !empty($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
    $pass     .= ($user || $pass) ? '@' : '';
    $path     = !empty($parsed_url['path']) ? $parsed_url['path'] : '';
    $query    = !empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = !empty($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
}

function encodeUrl(string $url): string
{
    $url = explode('/', $url);
    $url = array_map('rawurlencode', $url);
    return implode('/', $url);
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

function checkMx(string $domain): bool
{
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
    Cache::addLoadedTable('lists');
    Cache::addLoadedTable('list_rows');
    Cache::addLoadedTable('sider');
    Cache::addLoadedTable('bind');
    Cache::addLoadedTable('kat');
    doConditionalGet(Cache::getUpdateTime());

    $html = '';

    $list = db()->fetchOne("SELECT * FROM `lists` WHERE id = " . $listid);
    $rows = db()->fetchArray(
        "
        SELECT *
        FROM `list_rows`
        WHERE `list_id` = " . $listid
    );
    if (!$rows) {
        doConditionalGet(Cache::getUpdateTime());
        return ['id' => 'table' . $listid, 'html' => $html];
    }

    $category = $categoryId ? ORM::getOne(Category::class, $categoryId) : null;

    // Eager load data
    $pageIds = [];
    foreach ($rows as $row) {
        if ($row['link']) {
            $pageIds[] = $row['link'];
        }
    }
    if ($pageIds) {
        $pages = ORM::getByQuery(
            Page::class,
            "
            SELECT * FROM sider WHERE id IN (" . implode(",", $pageIds) . ")
            "
        );
    }

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
                        $html .= xhtmlEsc($row['link']);
                    }
                    $html .= '<img src="' . xhtmlEsc($row[$key]) . '" alt="'
                    . xhtmlEsc($files['alt']) . '" title="" width="' . $files['width']
                    . '" height="' . $files['height'] . '" />';
                    if (xhtmlEsc($row['link'])) {
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

    doConditionalGet(Cache::getUpdateTime());

    return ['id' => 'table' . $listid, 'html' => $html];
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
 * Search for pages and generate a list or redirect if only one was found
 *
 * @param string $q     Tekst to search for
 * @param string $where Additional sql where clause
 *
 * @return null
 */
function searchListe(string $q, string $where)
{
    $pages = [];

    if ($q) {
        //TODO match on keywords
        $columns = [];
        foreach (db()->fetchArray("SHOW COLUMNS FROM sider") as $column) {
            $columns[] = $column['Field'];
        }
        $simpleq = preg_replace('/\s+/u', '%', $q);
        $pages = ORM::getByQuery(
            Page::class,
            "
            SELECT `" . implode("`, `", $columns) . "` FROM (SELECT sider.*, MATCH(navn, text, beskrivelse) AGAINST ('$q') AS score
            FROM sider
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE MATCH (navn, text, beskrivelse) AGAINST('$q') > 0
            $where
            ORDER BY `score` DESC) x
            UNION
            SELECT sider.* FROM `list_rows`
            JOIN lists ON list_rows.list_id = lists.id
            JOIN sider ON lists.page_id = sider.id
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE list_rows.`cells` LIKE '%$simpleq%'"
            . $where
            . "
            UNION
            SELECT sider.* FROM `sider`
            JOIN bind ON sider.id = bind.side AND bind.kat != -1
            WHERE (
                `navn` LIKE '%$simpleq%'
                OR `text` LIKE '%$simpleq%'
                OR `beskrivelse` LIKE '%$simpleq%'
            ) "
            . $where
        );
        Cache::addLoadedTable('list_rows');
        Cache::addLoadedTable('lists');
    } else {
        $pages = ORM::getByQuery(
            Page::class,
            "
            SELECT * FROM `sider` WHERE 1
            $where
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
        'recAttPerson' => '',
        'recAddress1'  => '',
        'recAddress2'  => '',
        'recZipCode'   => '',
        'recPostBox'   => '',
        'recCVR'       => '',
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
        try {
            $db = new DB(
                $db['mysql_server'],
                $db['mysql_user'],
                $db['mysql_password'],
                $db['mysql_database']
            );
        } catch (Exception $e) {
            continue;
        }

        $tables = db()->fetchArray("SHOW TABLE STATUS WHERE Name IN('fakturas', 'email', 'post')");
        foreach ($tables as $table) {
            Cache::addUpdateTime(strtotime($table['Update_time'])) + db()->getTimeOffset();
        }

        //Try katalog orders
        $address = $db->fetchOne(
            "
            SELECT * FROM (
                SELECT navn recName1, att recAttPerson, adresse recAddress1, postnr recZipCode, postbox recPostBox, email
                FROM `fakturas`
                WHERE `tlf1` LIKE '" . $phoneNumber . "'
                   OR `tlf2` LIKE '" . $phoneNumber . "'
                ORDER BY id DESC
                LIMIT 1
            ) x
            UNION
            SELECT * FROM (
                SELECT navn recName1, '' recAttPerson, adresse recAddress1, post recZipCode, '' email, email
                FROM `email`
                WHERE `tlf1` LIKE '" . $phoneNumber . "'
                   OR `tlf2` LIKE '" . $phoneNumber . "'
                ORDER BY id DESC
                LIMIT 1
            ) x
            UNION
            SELECT * FROM (
                SELECT recName1, '' recAttPerson, recAddress1, recZipCode, '' recPostBox, '' email
                FROM `post`
                WHERE `recipientID` LIKE '" . $phoneNumber . "'
                ORDER BY id DESC
                LIMIT 1
            ) x
            "
        );

        if ($address) {
            $address = array_merge($default, $address);
            if ($address !== $default) {
                doConditionalGet($updateTime);
                return $address;
            }
        }
    }

    doConditionalGet($updateTime);

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
 * Get list of sub categories in format fitting the generatedcontent structure
 *
 * @param array $categories       Categories
 * @param array $categoryIds      Ids in active category trunk
 * @param array $weightedChildren Are the categories the list custome sorted
 *
 * @return array
 */
function menu(array $categories, array $categoryIds, bool $weightedChildren = true): array
{
    $menu = [];
    if (!$weightedChildren) {
        $objectArray = [];
        foreach ($categories as $categorie) {
            $objectArray[] = [
                'id'     => $categorie->getId(),
                'navn'   => $categorie->getTitle(),
                'object' => $categorie,
            ];
        }
        $objectArray = arrayNatsort($objectArray, 'id', 'navn', 'asc');
        $categories = [];
        foreach ($objectArray as $row) {
            $categories[] = $row['object'];
        }
    }

    foreach ($categories as $category) {
        if (!$category->isVisable()) {
            continue;
        }

        //Er katagorien aaben
        $subs = [];
        if (in_array($category->getId(), $categoryIds, true)) {
            $subs = menu(
                $category->getChildren(true),
                $categoryIds,
                $category->getWeightedChildren()
            );
        }


        //tegn under punkter
        $menu[] = [
            'id'   => $category->getId(),
            'name' => xhtmlEsc($category->getTitle()),
            'link' => '/' . $category->getSlug(),
            'icon' => $category->getIconPath(),
            'sub'  => $subs ? true : $category->hasChildren(true),
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
 * Get the html for content bellonging to a category
 *
 * @param int  $id   Id of activ category
 * @param bool $sort What column to sort by
 *
 * @return array Apropriate for handeling with javascript function inject_html()
 */
function getKat(int $categoryId, string $sort): array
{
    Cache::addLoadedTable('sider');
    Cache::addLoadedTable('bind');
    Cache::addLoadedTable('kat');
    doConditionalGet(Cache::getUpdateTime());

    if (!in_array($sort, ['navn', 'for', 'pris', 'varenr'])) {
        $sort = 'navn';
    }

    //Get pages list
    $category = ORM::getOne(Category::class, $categoryId);
    $pages = $category->getPages($sort);

    $objectArray = [];
    foreach ($pages as $page) {
        $objectArray[] = [
            'id' => $page->getId(),
            'navn' => $page->getTitle(),
            'for' => $page->getOldPrice(),
            'pris' => $page->getPrice(),
            'varenr' => $page->getSku(),
            'object' => $page,
        ];
    }
    $objectArray = arrayNatsort($objectArray, 'id', $sort);
    $pages = [];
    foreach ($objectArray as $item) {
        $pages[] = $item['object'];
    }

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
        $oldPrice = '';
        if ($page->getOldPrice()) {
            $oldPrice = $page->getOldPrice() . ',-';
        }

        $price = '';
        if ($page->getPrice()) {
            $price = $page->getPrice() . ',-';
        }

        $html .= '<tr' . ($isEven ? ' class="altrow"' : '')
        . '><td><a href="' . xhtmlEsc($page->getCanonicalLink($category)) . '">'
        . xhtmlEsc($page->getTitle())
        . '</a></td><td class="XPris" align="right">' . $oldPrice
        . '</td><td class="Pris" align="right">' . $price
        . '</td><td align="right" style="font-size:11px">'
        . xhtmlEsc($page->getSku()) . '</td></tr>';

        $isEven = !$isEven;
    }
    $html .= '</tbody></table>';

    return [
        'id' => 'kat' . $categoryId,
        'html' => $html,
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

    if (empty($values['navn']) || !valideMail($values['email'])) {
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

function sendEmails(
    string $subject,
    string $htmlBody,
    string $from = '',
    string $fromName = '',
    string $to = '',
    string $toName = '',
    bool $retry = true,
    array $bcc = []
): bool {
    $emailConfig = reset($GLOBALS['_config']['emails']);
    if (isset($GLOBALS['_config']['emails'][$from])) {
        $emailConfig = $GLOBALS['_config']['emails'][$from];
    }
    if (!$from || !valideMail($from)) {
        $from = $emailConfig['address'];
    }
    if (!$fromName) {
        $fromName = $GLOBALS['_config']['site_name'];
    }
    if (!$to) {
        $to = $emailConfig['address'];
        $toName = $GLOBALS['_config']['site_name'];
    } elseif (!$toName) {
        $toName = $to;
    }

    $PHPMailer = new PHPMailer();
    $PHPMailer->SetLanguage('dk');
    $PHPMailer->IsSMTP();
    $PHPMailer->SMTPAuth = false;
    if ($emailConfig['smtpAuth']) {
        $PHPMailer->SMTPAuth = true;
        $PHPMailer->Username = $emailConfig['address'];
        $PHPMailer->Password = $emailConfig['password'];
    }
    $PHPMailer->Host     = $emailConfig['smtpHost'];
    $PHPMailer->Port     = $emailConfig['smtpPort'];
    $PHPMailer->CharSet  = 'utf-8';
    $PHPMailer->From     = $emailConfig['address'];
    $PHPMailer->FromName = $GLOBALS['_config']['site_name'];

    if ($from !== $emailConfig['address']) {
        $PHPMailer->AddReplyTo($from, $fromName);
    }

    foreach ($bcc as $email) {
        $PHPMailer->AddBCC($email['email'], $email['navn']);
    }

    $PHPMailer->Subject = $subject;
    $PHPMailer->MsgHTML($htmlBody, _ROOT_);
    $PHPMailer->AddAddress($to, $toName);

    $success = $PHPMailer->Send();
    if ($success) {
        //Upload email to the sent folder via imap
        if ($emailConfig['imapHost']) {
            $imap = new IMAP(
                $emailConfig['address'],
                $emailConfig['password'],
                $emailConfig['imapHost'],
                $emailConfig['imapPort']
            );
            $imap->append(
                $emailConfig['sentBox'],
                $PHPMailer->CreateHeader() . $PHPMailer->CreateBody(),
                '\Seen'
            );
        }
    } elseif ($retry) {
        db()->query(
            "
            INSERT INTO `emails` (`subject`, `from`, `to`, `body`, `date`)
            VALUES (
                '" . db()->esc($subject) . "',
                '" . db()->esc($from . "<" . $fromName) . ">',
                '" . db()->esc($to . "<" . $toName) . ">',
                '" . db()->esc($htmlBody) . "',
                NOW()
            );
            "
        );
    }

    return $success;
}
