<?php

use AGCMS\Config;
use AGCMS\DB;
use AGCMS\Entity\Category;
use AGCMS\ORM;
use AGCMS\Render;
use AJenbo\Imap;
use PHPMailer\PHPMailer\PHPMailer;

function bootStrap(): void
{
    require_once __DIR__ . '/../vendor/autoload.php';

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
}

/**
 * Declare common functions.
 */
function db(DB $overwrite = null): DB
{
    static $connection;
    if ($overwrite) {
        $connection = $overwrite;
    } elseif (!$connection) {
        $connection = new DB(
            Config::get('mysql_server'),
            Config::get('mysql_user'),
            Config::get('mysql_password'),
            Config::get('mysql_database')
        );
    }

    return $connection;
}

function redirect(string $url, int $status = 303): void
{
    if (headers_sent()) {
        throw new Exception(_('Header already sent!'));
    }

    $url = parse_url($url);
    if (empty($url['scheme'])) {
        $url['scheme'] = !empty($_SERVER['HTTPS']) && mb_strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
    }
    if (empty($url['host'])) {
        // IP
        $url['host'] = $_SERVER['SERVER_ADDR'];
        if (!empty($_SERVER['HTTP_HOST'])) {
            // Browser
            $url['host'] = $_SERVER['HTTP_HOST'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            // Can both be from Browser and server (virtual) config
            $url['host'] = $_SERVER['SERVER_NAME'];
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
    exit;
}

/**
 * Build a url string from an array.
 *
 * @param array $parsed_url Array as returned by parse_url()
 *
 * @return string The URL
 */
function unparseUrl(array $parsedUrl): string
{
    $scheme   = !empty($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
    $host     = !empty($parsedUrl['host']) ? $parsedUrl['host'] : '';
    $port     = !empty($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
    $user     = !empty($parsedUrl['user']) ? $parsedUrl['user'] : '';
    $pass     = !empty($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
    $pass     .= ($user || $pass) ? '@' : '';
    $path     = !empty($parsedUrl['path']) ? $parsedUrl['path'] : '';
    $query    = !empty($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
    $fragment = !empty($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

    return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
}

function encodeUrl(string $url): string
{
    $url = explode('/', $url);
    $url = array_map('rawurlencode', $url);

    return implode('/', $url);
}

/**
 * Get first element from an array that can't be referenced.
 *
 * @return mixed
 */
function first(array $array)
{
    return reset($array);
}

/**
 * Generate safe file name.
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
 * Natsort an array.
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
    //if the parameters are invalid
    if (!$strIndex || !$strSortBy) {
        return $aryData;
    }

    //loop through the array
    $arySort = [];
    foreach ($aryData as $aryRow) {
        //set up the value in the array
        $arySort[$aryRow[$strIndex]] = $aryRow[$strSortBy];
    }

    //apply the natural sort
    natcasesort($arySort);

    //if the sort type is descending
    if (in_array($strSortType, ['desc', '-'], true)) {
        //reverse the array
        arsort($arySort);
    }

    //loop through the sorted and original data
    $aryResult = [];
    foreach (array_keys($arySort) as $arySortKey) {
        foreach ($aryData as $aryRow) {
            if ($aryRow[$strIndex] == $arySortKey) {
                $aryResult[] = $aryRow;
                break;
            }
        }
    }

    //return the result
    return $aryResult;
}

/**
 * Return html for a sorted list.
 *
 * @param int $listid     Id of list
 * @param int $bycell     What cell to sort by
 * @param int $categoryId Id of current category
 *
 * @return array
 */
function getTable(int $listid, int $bycell = null, int $categoryId = null): array
{
    Render::addLoadedTable('lists');
    Render::addLoadedTable('list_rows');
    Render::addLoadedTable('sider');
    Render::addLoadedTable('bind');
    Render::addLoadedTable('kat');
    Render::sendCacheHeader();

    $html = Render::getTableHtml(
        $listid,
        $bycell,
        $categoryId ? ORM::getOne(Category::class, $categoryId) : null
    );

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
 * Crope a string to a given max lengt, round by word.
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
 * Get address from phone number.
 *
 * @param string $phoneNumber Phone number
 *
 * @return array Array with address fitting the post table format
 */
function getAddress(string $phoneNumber): array
{
    $updateTime = 0;
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

    $dbs = Config::get('altDBs', []);
    $dbs[] = [
        'mysql_server'   => Config::get('mysql_server'),
        'mysql_user'     => Config::get('mysql_user'),
        'mysql_password' => Config::get('mysql_password'),
        'mysql_database' => Config::get('mysql_database'),
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

        $tables = $db->fetchArray("SHOW TABLE STATUS WHERE Name IN('fakturas', 'email', 'post')");
        foreach ($tables as $table) {
            $updateTime = max($updateTime, strtotime($table['Update_time']) + db()->getTimeOffset());
        }

        //Try katalog orders
        $address = $db->fetchOne(
            "
            SELECT * FROM (
                SELECT
                    navn recName1,
                    att recAttPerson,
                    adresse recAddress1,
                    postnr recZipCode,
                    postbox recPostBox,
                    email
                FROM `fakturas`
                WHERE `tlf1` LIKE '" . $phoneNumber . "'
                   OR `tlf2` LIKE '" . $phoneNumber . "'
                ORDER BY id DESC
                LIMIT 1
            ) x
            UNION
            SELECT * FROM (
                SELECT
                    navn recName1,
                    '' recAttPerson,
                    adresse recAddress1,
                    post recZipCode,
                    '' recPostBox,
                    email
                FROM `email`
                WHERE `tlf1` LIKE '" . $phoneNumber . "'
                   OR `tlf2` LIKE '" . $phoneNumber . "'
                ORDER BY id DESC
                LIMIT 1
            ) x
            UNION
            SELECT * FROM (
                SELECT
                    recName1,
                    '' recAttPerson,
                    recAddress1,
                    recZipCode,
                    '' recPostBox,
                    '' email
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
                Render::sendCacheHeader($updateTime);

                return $address;
            }
        }
    }

    Render::sendCacheHeader($updateTime);

    //Addressen kunde ikke findes.
    return ['error' => _('The address could not be found.')];
}

/**
 * Get the html for content bellonging to a category.
 *
 * @param int  $id   Id of activ category
 * @param bool $sort What column to sort by
 *
 * @return array Apropriate for handeling with javascript function inject_html()
 */
function getKat(int $categoryId, string $sort): array
{
    Render::addLoadedTable('sider');
    Render::addLoadedTable('bind');
    Render::addLoadedTable('kat');
    Render::sendCacheHeader();

    $category = ORM::getOne(Category::class, $categoryId);
    $html = Render::getKatHtml($category, $sort);

    return [
        'id' => 'kat' . $categoryId,
        'html' => $html,
    ];
}

/**
 * Checks if email an address looks valid and that an mx server is responding.
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
        $dummy = [];
        $ceche[$domain] = getmxrr($domain, $dummy);
    }

    return $ceche[$domain];
}

function sendEmails(
    string $subject,
    string $htmlBody,
    string $from = '',
    string $fromName = '',
    string $recipient = '',
    string $recipientName = '',
    bool $retry = true,
    array $bcc = []
): bool {
    $emailConfig = first(Config::get('emails'));
    if (isset(Config::get('emails')[$from])) {
        $emailConfig = Config::get('emails')[$from];
    }
    if (!$from || !valideMail($from)) {
        $from = $emailConfig['address'];
    }
    if (!$fromName) {
        $fromName = Config::get('site_name');
    }
    if (!$recipient) {
        $recipient = $emailConfig['address'];
        $recipientName = Config::get('site_name');
    } elseif (!$recipientName) {
        $recipientName = $recipient;
    }

    $mailer = new PHPMailer(true);
    $mailer->SetLanguage('dk');
    $mailer->IsSMTP();
    $mailer->SMTPAuth = false;
    if ($emailConfig['smtpAuth']) {
        $mailer->SMTPAuth = true;
        $mailer->Username = $emailConfig['address'];
        $mailer->Password = $emailConfig['password'];
    }
    $mailer->Host     = $emailConfig['smtpHost'];
    $mailer->Port     = $emailConfig['smtpPort'];
    $mailer->CharSet  = 'utf-8';
    $mailer->From     = $emailConfig['address'];
    $mailer->FromName = Config::get('site_name');

    if ($from !== $emailConfig['address']) {
        $mailer->AddReplyTo($from, $fromName);
    }

    foreach ($bcc as $email) {
        $mailer->AddBCC($email['email'], $email['navn']);
    }

    $mailer->Subject = $subject;
    $mailer->MsgHTML($htmlBody, _ROOT_);
    $mailer->AddAddress($recipient, $recipientName);

    $success = $mailer->Send();
    if ($success) {
        //Upload email to the sent folder via imap
        if ($emailConfig['imapHost']) {
            $imap = new Imap(
                $emailConfig['address'],
                $emailConfig['password'],
                $emailConfig['imapHost'],
                $emailConfig['imapPort']
            );
            $imap->append(
                $emailConfig['sentBox'],
                $mailer->getSentMIMEMessage(),
                '\Seen'
            );
        }
    } elseif ($retry) {
        db()->query(
            "
            INSERT INTO `emails` (`date`, `subject`, `body`, `from`, `to`)
            VALUES (NOW(),
                " . db()->eandq($subject) . ",
                " . db()->eandq($htmlBody) . ",
                " . db()->eandq($from . '<' . $fromName . '>') . ",
                " . db()->eandq($recipient . '<' . $recipientName . '>') . "
            );
            "
        );
        Render::addLoadedTable('emails');
    }

    return $success;
}
