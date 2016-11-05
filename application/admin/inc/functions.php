<?php

require_once __DIR__ . '/../../inc/functions.php';
require_once __DIR__ . '/../../inc/imap.php';
include_once __DIR__ . '/../../vendor/phpmailer/phpmailer/language/phpmailer.lang-dk.php';
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/class.smtp.php';
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/class.phpmailer.php';

/**
 * Optimize all tables
 *
 * @return string Always empty
 */
function optimizeTables(): string
{
    $tables = db()->fetchArray("SHOW TABLE STATUS");
    foreach ($tables as $table) {
        db()->query("OPTIMIZE TABLE `" . $table['Name'] . "`");
    }
    return '';
}

/**
 * Remove newletter submissions that are missing vital information
 *
 * @return string Always empty
 */
function removeBadSubmisions(): string
{
    db()->query(
        "
        DELETE FROM `email`
        WHERE `email` = ''
          AND `adresse` = ''
          AND `tlf1` = ''
          AND `tlf2` = '';
        "
    );

    return '';
}

/**
 * Delete bindings where either page or category is missing
 *
 * @return string Always empty
 */
function removeBadBindings(): string
{
    db()->query(
        "
        DELETE FROM `bind`
        WHERE (kat != 0 AND kat != -1
             AND NOT EXISTS (SELECT id FROM kat   WHERE id = bind.kat)
            ) OR NOT EXISTS (SELECT id FROM sider WHERE id = bind.side);
        "
    );

    return '';
}

/**
 * Remove bad tilbehor bindings
 *
 * @return string Always empty
 */
function removeBadAccessories(): string
{
    db()->query(
        "
        DELETE FROM `tilbehor`
        WHERE NOT EXISTS (SELECT id FROM sider WHERE tilbehor.side)
           OR NOT EXISTS (SELECT id FROM sider WHERE tilbehor.tilbehor);
        "
    );

    return '';
}

/**
 * Remove enteries for files that do no longer exist
 *
 * @return string Always empty
 */
function removeNoneExistingFiles(): string
{
    $files = db()->fetchArray('SELECT id, path FROM `files`');

    $deleted = 0;
    foreach ($files as $files) {
        if (!is_file($_SERVER['DOCUMENT_ROOT'].$files['path'])) {
            db()->query("DELETE FROM `files` WHERE `id` = " . $files['id']);
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
function deleteTempfiles(): string
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

/**
 * @return array
 */
function sendDelayedEmail(): string
{
    $emailsSendt = 0;
    $msg = ngettext(
        "%d e-mail was sent.",
        "%d e-mails was sent.",
        $emailsSendt
    );

    //Get emails that needs sending
    $emails = db()->fetchArray("SELECT * FROM `emails`");

    if (!$emails) {
        db()->query("UPDATE special SET dato = NOW() WHERE id = 0");
        return '';
    }

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
        $PHPMailer->Password   = $GLOBALS['_config']['emailpasswords'][0];
    } else {
        $PHPMailer->SMTPAuth   = false;
    }

    $imap = new IMAP(
        $GLOBALS['_config']['email'][$emailnr ? $emailnr : 0],
        $GLOBALS['_config']['emailpasswords'][$emailnr ? $emailnr : 0],
        $GLOBALS['_config']['imap'],
        $GLOBALS['_config']['imapport']
    );

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

        db()->query("DELETE FROM `emails` WHERE `id` = " . $email['id']);

        //Upload email to the sent folder via imap
        if ($GLOBALS['_config']['imap'] !== false) {
            $emailnr = array_search('', $GLOBALS['_config']['email']);
            $imap->append(
                $GLOBALS['_config']['emailsent'],
                $PHPMailer->CreateHeader() . $PHPMailer->CreateBody(),
                '\Seen'
            );
        }
    }

    db()->query("UPDATE special SET dato = NOW() WHERE id = 0");

    //Close SMTP connection
    $PHPMailer->SmtpClose();

    return printf($msg, $emailsSendt);
}
