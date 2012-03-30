<?php
/**
 * Send emails that failed to send immediately
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

/*
INSERT INTO `emails` (`subject`, `from`, `to`, `body`, `date`)
VALUES
    ('subject', 'from<t@f.dk>', 'to<t@f.dk>;to2<t2@f.dk>', '<div>test</div>', NOW());
*/

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

require_once "inc/config.php";
require_once "inc/mysqli.php";

//Open database
$mysqli = new simple_mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

//Get emails that needs sending
$emails = $mysqli->fetch_array("SELECT * FROM `emails`");

if (!$emails) {
    die(_('No e-mails to send.'));
}

$emailsSendt = 0;

//Load the PHPMailer class
require_once "inc/phpMailer/class.phpmailer.php";

//Set up PHPMailer
$PHPMailer = new PHPMailer();
$PHPMailer->SetLanguage('dk');
$PHPMailer->IsSMTP();
$PHPMailer->Host       = $GLOBALS['_config']['smtp'];
$PHPMailer->Port       = $GLOBALS['_config']['smtpport'];
$PHPMailer->CharSet    = 'utf-8';
if ($GLOBALS['_config']['emailpassword'] !== false) {
    $PHPMailer->SMTPAuth   = true; // enable SMTP authentication
    $PHPMailer->Username   = $GLOBALS['_config']['email'][0];
    $PHPMailer->Password   = $GLOBALS['_config']['emailpassword'];
} else {
    $PHPMailer->SMTPAuth   = false;
}

//Load the imap class, if imap is configured
if ($GLOBALS['_config']['imap'] !== false) {
    include_once "inc/imap.php";
}

foreach ($emails as $email) {
    $PHPMailer->ClearAddresses();
    $PHPMailer->ClearCCs();
    $PHPMailer->ClearReplyTos();
    $PHPMailer->ClearAllRecipients();
    $PHPMailer->ClearAttachments();

    $email['from'] = explode('<', $email['from']);
    $email['from'][1] = substr($email['from'][1], 0, -1);
    $PHPMailer->From       = $email['from'][1];
    $PHPMailer->FromName   = $email['from'][0];
    $PHPMailer->AddReplyTo($email['from'][1], $email['from'][0]);

    $email['to'] = explode(';', $email['to']);
    foreach ($email['to'] as $key => $to) {
        $email['to'][$key] = explode('<', $to);
        $email['to'][$key][1] = substr($email['to'][$key][1], 0, -1);
        $PHPMailer->AddAddress($email['to'][$key][1], $email['to'][$key][0]);
    }

    $PHPMailer->Subject = $email['subject'];
    $PHPMailer->MsgHTML($email['body'], $_SERVER['DOCUMENT_ROOT']);

    if (!$PHPMailer->Send()) {
        continue;
    }

    $emailsSendt++;

    $mysqli->query("DELETE FROM `emails` WHERE `id` = " . $email['id']);

    //Upload email to the sent folder via imap
    if ($GLOBALS['_config']['imap'] !== false) {
        $emailnr = array_search('', $GLOBALS['_config']['email']);
        $imap = new IMAP(
            $GLOBALS['_config']['email'][$emailnr ? $emailnr : 0],
            $GLOBALS['_config']['emailpasswords'][$emailnr ? $emailnr : 0],
            $GLOBALS['_config']['imap'],
            $GLOBALS['_config']['imapport']
        );
        $imap->append(
            $GLOBALS['_config']['emailsent'],
            $PHPMailer->CreateHeader() . $PHPMailer->CreateBody(),
            '\Seen'
        );
    }
}

//Close SMTP connection
$PHPMailer->SmtpClose();

$msg = ngettext(
    "%d e-mail was sent.",
    "%d e-mails was sent.",
    $emailsSendt
);
printf($msg, $emailsSendt);
