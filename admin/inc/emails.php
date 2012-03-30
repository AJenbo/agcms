<?php

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain('agcms', $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset('agcms', 'UTF-8');
textdomain('agcms');

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
//TODO run countEmailTo() onload

function sendEmail($id, $from, $interests, $subject, $text)
{
    global $mysqli;
    if (!$mysqli->fetch_array('SELECT `id` FROM `newsmails` WHERE `sendt` = 0')) {
        //Nyhedsbrevet er allerede afsendt!
        return array('error' => _('The newsletter has already been sent!'));
    }

    saveEmail($id, $from, $interests, $subject, $text);

    include '../inc/phpMailer/class.phpmailer.php';

    $mail             = new PHPMailer();
    $mail->SetLanguage('dk');

    $mail->IsSMTP();
    if ($GLOBALS['_config']['emailpassword'] !== false) {
        $mail->SMTPAuth   = true; // enable SMTP authentication
        $mail->Username   = $GLOBALS['_config']['email'][0];
        $mail->Password   = $GLOBALS['_config']['emailpassword'];
    } else {
        $mail->SMTPAuth   = false;
    }
    $mail->Host       = $GLOBALS['_config']['smtp'];      // sets the SMTP server
    $mail->Port       = $GLOBALS['_config']['smtpport'];                   // set the SMTP port for the server

    $mail->AddReplyTo($from, $GLOBALS['_config']['site_name']);

    $mail->From       = $from;
    $mail->FromName   = $GLOBALS['_config']['site_name'];

    $mail->CharSet    = 'utf-8';

    $mail->Subject    = $subject;
    $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>'.$GLOBALS['_config']['site_name'].'</title>
    <style type="text/css">';
    $body .= file_get_contents($_SERVER['DOCUMENT_ROOT'].'/theme/email.css');
    $body .= '</style>
    <meta http-equiv="content-language" content="da" />
    <meta name="Description" content="Alt du har brug for i frilufts livet" />
    <meta name="Author" content="' . $GLOBALS['_config']['site_name'] . '" />
    <meta name="Classification" content="" />
    <meta name="Reply-to" content="'.$from.'" />
    <meta http-equiv="imagetoolbar" content="no" />
    <meta name="distribution" content="Global" />
    <meta name="robots" content="index,follow" />
    </head><body><div>';
    $body .= str_replace(' href="/', ' href="' . $GLOBALS['_config']['base_url'] . '/', stripcslashes(htmlUrlDecode($text)));
    $body .= '</div></body></html>';

    $mail->MsgHTML($body, $_SERVER['DOCUMENT_ROOT']);


    //Colect interests
    if ($interests) {
        $interests = explode('<', $interests);
        $andwhere = '';
        foreach ($interests as $interest) {
            if ($andwhere) {
                $andwhere .= ' OR ';
            }
            $andwhere .= '`interests` LIKE \'';
            $andwhere .= $interest;
            $andwhere .= '\' OR `interests` LIKE \'';
            $andwhere .= $interest;
            $andwhere .= '<%\' OR `interests` LIKE \'%<';
            $andwhere .= $interest;
            $andwhere .= '\' OR `interests` LIKE \'%<';
            $andwhere .= $interest;
            $andwhere .= '<%\'';
        }
        $andwhere = ' AND ('.$andwhere;
        $andwhere .= ')';
    }

    $mail->AddAddress($from, $GLOBALS['_config']['site_name']);

    global $mysqli;
    $emails = $mysqli->fetch_array(
        'SELECT navn, email
        FROM `email`
        WHERE `email` NOT LIKE \'\'
          AND `kartotek` = \'1\' '.$andwhere.'
        GROUP BY `email`'
    );

    foreach ($emails as $x => $email) {
        $emails_group[floor($x/99)][] = $email;
    }

    $error = '';

    foreach ($emails_group as $emails) {
        $mail->ClearBCCs();
        foreach ($emails as $email) {
            $mail->AddBCC($email['email'], $email['navn']);
        }

        if (!$mail->Send()) {
            //TODO upload if send fails
            $error .= $mail->ErrorInfo . "\n";
        }

        //Upload email to the sent folder via imap
        if ($GLOBALS['_config']['imap']) {
            include_once '../inc/imap.php';
            $emailnr = array_search($from, $GLOBALS['_config']['email']);
            $imap = new IMAP(
                $from,
                $GLOBALS['_config']['emailpasswords'][$emailnr ? $emailnr : 0],
                $GLOBALS['_config']['imap'],
                $GLOBALS['_config']['imapport']
            );
            $imap->append($GLOBALS['_config']['emailsent'], $mail->CreateHeader().$mail->CreateBody(), '\Seen');
        }
    }

    if ($error) {
        return array('error' => $error);
    } else {
        $mysqli->query('UPDATE `newsmails` SET `sendt` = 1 WHERE `id` = '.$id.' LIMIT 1');
        return true;
    }
}

function countEmailTo($interests)
{
    global $mysqli;

    $andwhere = '';

    //Colect interests
    if ($interests) {
        $interests = explode('<', $interests);
        $andwhere = '';
        foreach ($interests as $interest) {
            if ($andwhere) {
                $andwhere .= ' OR ';
            }
            $andwhere .= '`interests` LIKE \'';
            $andwhere .= $interest;
            $andwhere .= '\' OR `interests` LIKE \'';
            $andwhere .= $interest;
            $andwhere .= '<%\' OR `interests` LIKE \'%<';
            $andwhere .= $interest;
            $andwhere .= '\' OR `interests` LIKE \'%<';
            $andwhere .= $interest;
            $andwhere .= '<%\'';
        }
        $andwhere = ' AND ('.$andwhere;
        $andwhere .= ')';
    }

    $emails = $mysqli->fetch_array('SELECT count(DISTINCT email) as \'count\' FROM `email` WHERE `email` NOT LIKE \'\' AND `kartotek` = \'1\''.$andwhere);

    return $emails[0]['count'];
}

function getNewEmail()
{
    global $mysqli;
    $mysqli->query('INSERT INTO `newsmails` () VALUES ()');
    return getEmail($mysqli->insert_id);
}

function getEmail($id)
{
    global $mysqli;
    $newsmails = $mysqli->fetch_array('SELECT * FROM `newsmails` WHERE `id` = '.$id);

    $html = '<div id="headline">'._('Edit newsletter').'</div>';

    if ($newsmails[0]['sendt'] == 0) {
        $html .= '<form action="" method="post" onsubmit="return sendNews();"><input type="submit" accesskey="m" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />';
        $html .= '<input value="'.$id.'" id="id" type="hidden" />';
    }

    $html .= '<div>';

    //TODO error if value = ''
    if ($newsmails[0]['sendt'] == 0) {
        if (count($GLOBALS['_config']['email']) > 1) {
            $html .= _('Sender:').' <select id="from">';
            $html .= '<option value="">'._('Select sender').'</option>';
            foreach ($GLOBALS['_config']['email'] as $email) {
                $html .= '<option value="'.$email.'">'.$email.'</option>';
            }
            $html .= '</select>';
        } else {
            $html .= '<input value="'.$GLOBALS['_config']['email'][0].'" id="from" style="display:none;" />';
        }
    } else {
        $html .= _('Sender:').' '.$newsmails[0]['from'];
    }

    //Modtager
    if ($newsmails[0]['sendt'] == 1) {
        $html .= '<br /><br />'._('Recipient:');
    } else {
        $html .= '<br />'._('Restrict recipients to:');
    }
    $html .= '<div id="interests">';
    $newsmails[0]['interests_array'] = explode('<', $newsmails[0]['interests']);
    foreach ($GLOBALS['_config']['interests'] as $interest) {
        $html .= '<input';
        if (false !== array_search($interest, $newsmails[0]['interests_array'])) {
            $html .= ' checked="checked"';
        }
        if ($newsmails[0]['sendt'] == 1) {
            $html .= ' disabled="disabled"';
        } else {
            $html .= ' onchange="countEmailTo()" onclick="countEmailTo()"';
        }
        $html .= ' type="checkbox" value="'.$interest.'" id="'.$interest.'" /><label for="'.$interest.'"> '.$interest.'</label> ';
    }
    $html .= '<script type="text/javascript"><!--
countEmailTo();
--></script>';
    $html .= '</div>';

    if ($newsmails[0]['sendt'] == 0) {
        $html .= '<br />'._('Number of recipients:').' <span id="mailToCount">'.countEmailTo($newsmails[0]['interests']).'</span><br />';
    }

    if ($newsmails[0]['sendt'] == 1) {
        $html .= '<br />'._('Subject:').' '.$newsmails[0]['subject'].'<div style="width:'.$GLOBALS['_config']['text_width'].'px; border:1px solid #D2D2D2">'.$newsmails[0]['text'].'</div></div>';
    } else {
        $html .= '<br />' . _('Subject:') . ' <input class="admin_name" name="subject" id="subject" value="' . $newsmails[0]['subject'] . '" size="127" style="width:' . ($GLOBALS['_config']['text_width'] - 34) . 'px" /><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE(\'/admin/rtef/images/\', \'/admin/rtef/\', \'/theme/email.css\', true);
writeRichText(\'text\', \'' . rtefsafe($newsmails[0]['text']) . '\', \'\', ' . ($GLOBALS['_config']['text_width'] + 32) . ', 422, true, false, false);
//--></script></div></form>';
    }
    return $html;
}

function saveEmail($id, $from, $interests, $subject, $text)
{
    global $mysqli;

    $from = $mysqli->real_escape_string($from);
    $interests = $mysqli->real_escape_string($interests);
    $subject = $mysqli->real_escape_string($subject);
    $text = $mysqli->real_escape_string($text);

    $mysqli->query(
        "UPDATE `newsmails`
        SET `from` = '" .$from ."',
        `interests` = '" .$interests ."',
        `subject` = '" .$subject ."',
        `text` = '" .$text ."'
        WHERE `id` = " .$id ." LIMIT 1"
    );
    return true;
}

function getEmailList()
{
    global $mysqli;
    $newsmails = $mysqli->fetch_array('SELECT `id`, `subject`, `sendt` FROM `newsmails`');

    $html = '<div id="headline">'._('Newsletters').'</div><div><a href="?side=newemail"><img src="images/email_add.png" width="16" height="16" alt="" /> '._('Create new newsletter').'</a><br /><br />';
    foreach ($newsmails as $newemail) {
        if ($newemail['sendt'] == 0) {
            $html .= '<a href="?side=editemail&amp;id='.$newemail['id'].'"><img src="images/email_edit';
        } else {
            $html .= '<a href="?side=viewemail&amp;id='.$newemail['id'].'"><img src="images/email_open';
        }
        $html .= '.png" width="16" height="16" alt="" /> '.$newemail['subject'].'</a><br />';
    }
    $html .= '</div>';

    return $html;
}

