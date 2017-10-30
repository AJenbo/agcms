<?php

use AGCMS\Config;
use AGCMS\DB;
use AGCMS\Entity\Invoice;
use AGCMS\Render;
use AJenbo\Imap;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Symfony\Component\HttpFoundation\Request;

function request(): Request
{
    static $request;
    if (!$request) {
        $request = Request::createFromGlobals();
    }

    return $request;
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

/**
 * Build a url string from an array.
 *
 * @param array $parsedUrl Array as returned by parse_url()
 *
 * @return string The URL
 */
function unparseUrl(array $parsedUrl): string
{
    $scheme = !empty($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
    $host = !empty($parsedUrl['host']) ? $parsedUrl['host'] : '';
    $port = !empty($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
    $user = !empty($parsedUrl['user']) ? $parsedUrl['user'] : '';
    $pass = !empty($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
    $pass .= ($user || $pass) ? '@' : '';
    $path = !empty($parsedUrl['path']) ? $parsedUrl['path'] : '';
    $query = !empty($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
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
 * @param array[] $aryData     Array to sort
 * @param string  $strIndex    Key of unique id
 * @param string  $strSortBy   Key to sort by
 * @param string  $strSortType Revers sorting
 *
 * @return array[]
 */
function arrayNatsort(array $aryData, string $strIndex, string $strSortBy, string $strSortType = 'asc'): array
{
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

function invoiceFromSession(): Invoice
{
    $items = [];
    $invoiceData = $_SESSION['faktura'];
    foreach ($invoiceData['products'] as $key => $title) {
        $items[] = [
            'quantity' => $invoiceData['quantities'][$key] ?? 0,
            'title'    => $title,
            'value'    => $invoiceData['values'][$key] ?? 0,
        ];
    }
    $items = json_encode($items);

    $note = '';
    $payMethod = $invoiceData['paymethod'] ?? '';
    if ('creditcard' === $payMethod) {
        $note .= _('I would like to pay via credit card.');
    } elseif ('bank' === $payMethod) {
        $note .= _('I would like to pay via bank transaction.');
    } elseif ('cash' === $payMethod) {
        $note .= _('I would like to pay via cash.');
    }
    $note .= "\n";
    $delevery = $invoiceData['delevery'] ?? '';
    if ('pickup' === $delevery) {
        $note .= _('I will pick up the goods in your shop.');
    } elseif ('postal' === $delevery) {
        $note .= _('Please send the goods by mail.');
    }
    $note = trim($note . "\n" . $invoiceData['note'] ?? '');

    return new Invoice([
        'item_data'            => $items,
        'has_shipping_address' => (bool) ($invoiceData['altpost'] ?? false),
        'amount'               => (int) ($invoiceData['amount'] ?? 0),
        'name'                 => $invoiceData['navn'] ?? '',
        'att'                  => $invoiceData['att'] ?? '',
        'address'              => $invoiceData['adresse'] ?? '',
        'postbox'              => $invoiceData['postbox'] ?? '',
        'postcode'             => $invoiceData['postnr'] ?? '',
        'city'                 => $invoiceData['by'] ?? '',
        'country'              => $invoiceData['land'] ?? '',
        'email'                => $invoiceData['email'] ?? '',
        'phone1'               => $invoiceData['tlf1'] ?? '',
        'phone2'               => $invoiceData['tlf2'] ?? '',
        'shipping_phone'       => $invoiceData['posttlf'] ?? '',
        'shipping_name'        => $invoiceData['postname'] ?? '',
        'shipping_att'         => $invoiceData['postatt'] ?? '',
        'shipping_address'     => $invoiceData['postaddress'] ?? '',
        'shipping_address2'    => $invoiceData['postaddress2'] ?? '',
        'shipping_postbox'     => $invoiceData['postpostbox'] ?? '',
        'shipping_postcode'    => $invoiceData['postpostalcode'] ?? '',
        'shipping_city'        => $invoiceData['postcity'] ?? '',
        'shipping_country'     => $invoiceData['postcountry'] ?? '',
        'note'                 => $note,
    ]);
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
    $mailer->Host = $emailConfig['smtpHost'];
    $mailer->Port = $emailConfig['smtpPort'];
    $mailer->CharSet = 'utf-8';

    $mailer->setFrom($emailConfig['address'], Config::get('site_name'));
    if ($from !== $emailConfig['address']) {
        $mailer->AddReplyTo($from, $fromName);
    }

    foreach ($bcc as $email) {
        $mailer->AddBCC($email['email'], $email['navn']);
    }

    $mailer->Subject = $subject;
    $mailer->MsgHTML($htmlBody, _ROOT_);
    $mailer->AddAddress($recipient, $recipientName);

    try {
        $success = $mailer->Send();
    } catch (PHPMailerException $e) {
        $success = false;
    }
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
            '
            INSERT INTO `emails` (`date`, `subject`, `body`, `from`, `to`)
            VALUES (NOW(),
                ' . db()->eandq($subject) . ',
                ' . db()->eandq($htmlBody) . ',
                ' . db()->eandq($from . '<' . $fromName . '>') . ',
                ' . db()->eandq($recipient . '<' . $recipientName . '>') . '
            );
            '
        );
        Render::addLoadedTable('emails');
    }

    return $success;
}
