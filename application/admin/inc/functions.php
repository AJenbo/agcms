<?php

require_once __DIR__ . '/../../inc/functions.php';

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
        if (!is_file(_ROOT_ . $files['path'])) {
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
    $files = scandir(_ROOT_ . '/admin/upload/temp');
    foreach ($files as $file) {
        if (is_file(_ROOT_ . '/admin/upload/temp/' . $file)) {
            @unlink(_ROOT_ . '/admin/upload/temp/' . $file);
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
    //Get emails that needs sending
    $emails = db()->fetchArray("SELECT * FROM `emails`");
    if (!$emails) {
        ORM::getOne(CustomPage::class, 0)->save();
        return '';
    }

    $emailsSendt = 0;
    foreach ($emails as $email) {
        $email['from'] = explode('<', $email['from']);
        $email['from'][1] = substr($email['from'][1], 0, -1);
        $email['to'] = explode('<', $email['to']);
        $email['to'][1] = substr($email['to'][1], 0, -1);

        $success = sendEmails(
            $email['subject'],
            $email['body'],
            $email['from'][1],
            $email['from'][0],
            $email['to'][1],
            $email['to'][0],
            false
        );
        if (!$success) {
            continue;
        }

        $emailsSendt++;

        db()->query("DELETE FROM `emails` WHERE `id` = " . $email['id']);
    }

    ORM::getOne(CustomPage::class, 0)->save();

    $msg = ngettext(
        "%d e-mail was sent.",
        "%d e-mails was sent.",
        $emailsSendt
    );
    return '<b>' . sprintf($msg, $emailsSendt) . '</b>';
}

/**
 * Convert PHP size string to bytes
 *
 * @param string $val PHP size string (eg. '2M')
 *
 * @return int Byte size
 */
function returnBytes(string $val): int
{
    $last = mb_strtolower($val{mb_strlen($val, 'UTF-8')-1}, 'UTF-8');
    switch ($last) {
        case 'g':
            $val *= 1024;
            /*keep going*/
        case 'm':
            $val *= 1024;
            /*keep going*/
        case 'k':
            $val *= 1024;
    }
    return $val;
}

/**
 * @param string $filepath
 *
 * @return string
 */
function get_mime_type(string $filepath): string
{
    $mime = '';
    if (function_exists('finfo_file')) {
        $mime = finfo_file($finfo = finfo_open(FILEINFO_MIME), _ROOT_ . $filepath);
        finfo_close($finfo);
    }
    if (!$mime && function_exists('mime_content_type')) {
        $mime = mime_content_type(_ROOT_ . $filepath);
    }

    //Some types can't be trusted, and finding them via extension seams to give better resutls.
    $unknown = ['text/plain', 'application/msword', 'application/octet-stream'];
    if (!$mime || in_array($mime, $unknown, true)) {
        $mimes = [
            'doc'   => 'application/msword',
            'dot'   => 'application/msword',
            'eps'   => 'application/postscript',
            'hqx'   => 'application/mac-binhex40',
            'pdf'   => 'application/pdf',
            'ai'    => 'application/postscript',
            'ps'    => 'application/postscript',
            'pps'   => 'application/vnd.ms-powerpoint',
            'ppt'   => 'application/vnd.ms-powerpoint',
            'xlb'   => 'application/vnd.ms-excel',
            'xls'   => 'application/vnd.ms-excel',
            'xlt'   => 'application/vnd.ms-excel',
            'zip'   => 'application/zip',
            '7z'    => 'application/x-7z-compressed',
            'sit'   => 'application/x-stuffit',
            'swf'   => 'application/x-shockwave-flash',
            'swfl'  => 'application/x-shockwave-flash',
            'tar'   => 'application/x-tar',
            'taz'   => 'application/x-gtar',
            'tgz'   => 'application/x-gtar',
            'gtar'  => 'application/x-gtar',
            'gz'    => 'application/x-gzip',
            'kar'   => 'audio/midi',
            'mid'   => 'audio/midi',
            'midi'  => 'audio/midi',
            'm4a'   => 'audio/mpeg',
            'mp2'   => 'audio/mpeg',
            'mp3'   => 'audio/mpeg',
            'mpega' => 'audio/mpeg',
            'mpga'  => 'audio/mpeg',
            'wav'   => 'audio/x-wav',
            'wma'   => 'audio/x-ms-wma',
            'bmp'   => 'image/x-ms-bmp',
            'psd'   => 'image/x-photoshop',
            'tiff'  => 'image/tiff',
            'tif'   => 'image/tiff',
            'css'   => 'text/css',
            'asc'   => 'text/plain',
            'diff'  => 'text/plain',
            'pot'   => 'text/plain',
            'text'  => 'text/plain',
            'txt'   => 'text/plain',
            'html'  => 'text/html',
            'htm'   => 'text/html',
            'shtml' => 'text/html',
            'rtf'   => 'text/rtf',
            'asf'   => 'video/x-ms-asf',
            'asx'   => 'video/x-ms-asf',
            'avi'   => 'video/x-msvideo',
            'flv'   => 'video/x-flv',
            'mov'   => 'video/quicktime',
            'mpeg'  => 'video/mpeg',
            'mpe'   => 'video/mpeg',
            'mpg'   => 'video/mpeg',
            'qt'    => 'video/quicktime',
            'wm'    => 'video/x-ms-wm',
            'wmv'   => 'video/x-ms-wmv',
        ];
        $mime = 'application/octet-stream';
        $pathinfo = pathinfo($filepath);
        if (isset($mimes[mb_strtolower($pathinfo['extension'] ?? '')])) {
            $mime = $mimes[mb_strtolower($pathinfo['extension'] ?? '')];
        }
    }

    $mime = explode(';', $mime);
    $mime = array_shift($mime);

    return $mime;
}

/**
 * @param int $id
 * @param string $from      From
 * @param string $interests Interests
 * @param string $subject   Subject
 * @param string $text      Content
 */
function sendEmail(int $id, string $from, string $interests, string $subject, string $text)
{
    if (!db()->fetchArray('SELECT `id` FROM `newsmails` WHERE `sendt` = 0')) {
        //Nyhedsbrevet er allerede afsendt!
        return ['error' => _('The newsletter has already been sent!')];
    }

    $text = purifyHTML($text);
    $text = htmlUrlDecode($text);

    saveEmail($id, $from, $interests, $subject, $text);

    $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>' . Config::get('site_name') . '</title>
    <style type="text/css">';
    $body .= file_get_contents(_ROOT_ . '/theme/email.css');
    $body .= '</style>
    <meta http-equiv="content-language" content="da" />
    <meta name="Description" content="Alt du har brug for i frilufts livet" />
    <meta name="Author" content="' . Config::get('site_name') . '" />
    <meta name="Classification" content="" />
    <meta name="Reply-to" content="'.$from.'" />
    <meta http-equiv="imagetoolbar" content="no" />
    <meta name="distribution" content="Global" />
    <meta name="robots" content="index,follow" />
    </head><body><div>';
    $body .= str_replace(' href="/', ' href="' . Config::get('base_url') . '/', $text);
    $body .= '</div></body></html>';

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

    $emails = db()->fetchArray(
        'SELECT navn, email
        FROM `email`
        WHERE `email` NOT LIKE \'\'
          AND `kartotek` = \'1\' '.$andwhere.'
        GROUP BY `email`'
    );
    foreach ($emails as $x => $email) {
        $emails_group[floor($x/99)+1][] = $email;
    }

    $error = '';
    foreach ($emails_group as $of => $emails) {
        $success = sendEmails(
            $subject,
            $body,
            $from,
            '',
            '',
            '',
            true,
            $emails
        );

        if (!$success) {
            //TODO upload if send fails
            $error .= 'Email ' . $of . '/' . count($emails) . ' failed to be sent.' . "\n";
        }
    }

    if ($error) {
        return ['error' => trim($error)];
    } else {
        db()->query("UPDATE `newsmails` SET `sendt` = 1 WHERE `id` = " . $id);
        return true;
    }
}

/**
 * @param string $interests Interests
 *
 * @return int
 */
function countEmailTo(string $interests): int
{
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

    $emails = db()->fetchOne("SELECT count(DISTINCT email) as 'count' FROM `email` WHERE `email` NOT LIKE '' AND `kartotek` = '1'" . $andwhere);

    return $emails['count'];
}

/**
 * @return string
 */
function getNewEmail(): string
{
    db()->query('INSERT INTO `newsmails` () VALUES ()');
    return getEmail(db()->insert_id);
}

/**
 * @param int $id The id
 *
 * @return string
 */
function getEmail(int $id): string
{
    $newsmail = db()->fetchOne('SELECT * FROM `newsmails` WHERE `id` = '.$id);

    $html = '<div id="headline">'._('Edit newsletter').'</div>';

    if ($newsmail['sendt'] == 0) {
        $html .= '<form action="" method="post" onsubmit="return sendNews();"><input type="submit" accesskey="m" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />';
        $html .= '<input value="'.$id.'" id="id" type="hidden" />';
    }

    $html .= '<div>';

    //TODO error if value = ''
    if ($newsmail['sendt'] == 0) {
        if (count(Config::get('emails')) > 1) {
            $html .= _('Sender:').' <select id="from">';
            $html .= '<option value="">'._('Select sender').'</option>';
            foreach (Config::get('emails', []) as $email => $dummy) {
                $html .= '<option value="'.$email.'">'.$email.'</option>';
            }
            $html .= '</select>';
        } else {
            $email = first(Config::get('emails'))['address'];
            $html .= '<input value="' . $email . '" id="from" style="display:none;" />';
        }
    } else {
        $html .= _('Sender:') . ' ' . $newsmail['from'];
    }

    //Modtager
    if ($newsmail['sendt'] == 1) {
        $html .= '<br /><br />'._('Recipient:');
    } else {
        $html .= '<br />'._('Restrict recipients to:');
    }
    $html .= '<div id="interests">';
    $newsmail['interests_array'] = explode('<', $newsmail['interests']);
    foreach (Config::get('interests', []) as $interest) {
        $html .= '<input';
        if (false !== array_search($interest, $newsmail['interests_array'])) {
            $html .= ' checked="checked"';
        }
        if ($newsmail['sendt'] == 1) {
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

    if ($newsmail['sendt'] == 0) {
        $html .= '<br />'._('Number of recipients:').' <span id="mailToCount">'.countEmailTo($newsmail['interests']).'</span><br />';
    }

    if ($newsmail['sendt'] == 1) {
        $html .= '<br />' . _('Subject:') . ' ' . $newsmail['subject'] . '<div style="width:' . Config::get('text_width') . 'px; border:1px solid #D2D2D2">' . $newsmail['text'] . '</div></div>';
    } else {
        $html .= '<br />' . _('Subject:') . ' <input class="admin_name" name="subject" id="subject" value="' . $newsmail['subject'] . '" size="127" style="width:' . (Config::get('text_width') - 34) . 'px" /><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE(\'/admin/rtef/images/\', \'/admin/rtef/\', \'/theme/email.css\', true);
writeRichText(\'text\', \'' . rtefsafe($newsmail['text']) . '\', \'\', ' . (Config::get('text_width') + 32) . ', 422, true, false, false);
//--></script></div></form>';
    }
    return $html;
}

/**
 * @param int    $id        The ID
 * @param string $from      From
 * @param string $interests Interests
 * @param string $subject   Subject
 * @param string $text      Content
 *
 * @return bool
 */
function saveEmail(int $id, string $from, string $interests, string $subject, string $text): bool
{
    $from = db()->esc($from);
    $interests = db()->esc($interests);
    $subject = db()->esc($subject);
    $text = db()->esc($text);

    db()->query(
        "UPDATE `newsmails`
        SET `from` = '" . $from . "',
        `interests` = '" . $interests . "',
        `subject` = '" . $subject . "',
        `text` = '" . $text . "'
        WHERE `id` = " . $id
    );
    return true;
}

/**
 * @return string
 */
function getEmailList(): string
{
    $newsmails = db()->fetchArray('SELECT `id`, `subject`, `sendt` FROM `newsmails`');

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

/**
 * @parram int $id
 *
 * @return array
 */
function kattree(int $id): array
{
    $kat = db()->fetchOne("SELECT id, navn, bind FROM `kat` WHERE id = " . $id);

    $kattree = [];
    $id = null;
    if ($kat) {
        $id = $kat['bind'];
        $kattree[] = [
            'id' => $kat['id'],
            'navn' => $kat['navn'],
        ];

        while ($kat['bind'] > 0) {
            $kat = db()->fetchOne("SELECT id, navn, bind FROM `kat` WHERE id = " . $kat['bind']);
            $id = $kat['bind'];
            $kattree[]['id'] = $kat['id'];
            $kattree[count($kattree) - 1]['navn'] = $kat['navn'];
        }
    }

    if (!$id) {
        $kattree[]['id'] = 0;
        $kattree[count($kattree)-1]['navn'] = _('Frontpage');
    } else {
        $kattree[]['id'] = -1;
        $kattree[count($kattree)-1]['navn'] = _('Inactive');
    }
    return array_reverse($kattree);
}

/**
 * @parram int $id
 *
 * @return array
 */
function katspath(int $id): array
{
    $html = _('Select location:').' ';
    foreach (kattree($id) as $kat) {
        $html .= '/'.trim($kat['navn']);
    }
    $html .= '/';

    return ['id' => 'katsheader', 'html' => $html];
}

/**
 * @parram int $id
 *
 * @return array
 */
function katlist(int $id): string
{
    global $kattree;

    $html = '<a class="menuboxheader" id="katsheader" style="width:' . Config::get('text_width') . 'px;clear:both" onclick="showhidekats(\'kats\',this);">';
    if (@$_COOKIE['hidekats']) {
        $temp = katspath($id);
        $html .= $temp['html'];
    } else {
        $html .= _('Select location:').' ';
    }
    $html .= '</a><div style="width:' . (Config::get('text_width') + 24) . 'px;';
    if (@$_COOKIE['hidekats']) {
        $html .= 'display:none;';
    }
    $html .= '" id="kats"><div>';
    $kattree = kattree($id);
    foreach ($kattree as $i => $value) {
        $kattree[$i] = $value['id'];
    }

    $openkat = explode('<', @$_COOKIE['openkat']);
    if (db()->fetchOne("SELECT id FROM `kat` WHERE bind = -1")) {
        $html .= '<img';
        if (array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
            $html .= ' style="display:none"';
        }
        $html .= ' src="images/+.gif" id="kat-1expand" onclick="kat_expand(-1, true, kat_expand_r);" height="16" width="16" alt="+" title="" /><img';
        if (!array_search(-1, $openkat) && false === array_search('-1', $kattree)) {
            $html .= ' style="display:none"';
        }
        $html .= ' src="images/-.gif" id="kat-1contract" onclick="kat_contract(-1);" height="16" width="16" alt="-" title="" /><a';
    } else {
        $html .= '<a style="margin-left:16px"';
    }
    $html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', -1, 360);"><input name="kat" type="radio" value="-1"';
    if ($kattree[count($kattree)-1] == -1) {
        $html .= ' checked="checked"';
    }
    $html .= ' /><img src="images/folder.png" width="16" height="16" alt="" /> '._('Inactive').'</a><div id="kat-1content" style="margin-left:16px">';
    if (array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
        $temp = kat_expand(-1, true);
        $html .= $temp['html'];
    }
    $html .= '</div></div><div>';
    if (db()->fetchOne("SELECT id FROM `kat` WHERE bind = 0")) {
        $html .= '<img style="';
        if (array_search(0, $openkat) || false !== array_search('0', $kattree)) {
            $html .= 'display:none;';
        }
        $html .= '" src="images/+.gif" id="kat0expand" onclick="kat_expand(0, true, kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="';
        if (!array_search(0, $openkat) && false === array_search('0', $kattree)) {
            $html .= 'display:none;';
        }
        $html .= '" src="images/-.gif" id="kat0contract" onclick="kat_contract(\'0\');" height="16" width="16" alt="-" title="" /><a';
    } else {
        $html .= '<a style="margin-left:16px"';
    }
    $html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', 0, 360);"><input type="radio" name="kat" value="0"';
    if (!$kattree[count($kattree)-1]) {
        $html .= ' checked="checked"';
    }
    $html .= ' /><img src="images/folder.png" width="16" height="16" alt="" /> '._('Frontpage').'</a><div id="kat0content" style="margin-left:16px">';
    if (array_search(0, $openkat) || false !== array_search('0', $kattree)) {
        $temp = kat_expand(0, true);
        $html .= $temp['html'];
    }
    $html .= '</div></div></div>';
    return $html;
}

/**
 * @parram int $id
 *
 * @return string
 */
function siteList(int $id = null): string
{
    global $kattree;

    $html = '<div>';

    $kattree = [];
    if ($id !== null) {
        $kattree = kattree($id);
        foreach ($kattree as $i => $value) {
            $kattree[$i] = $value['id'];
        }
    }

    $openkat = explode('<', @$_COOKIE['openkat']);
    if (db()->fetchOne("SELECT id FROM `kat` WHERE bind = -1") || db()->fetchOne("SELECT id FROM `bind` WHERE kat = -1")) {
        $html .= '<img';
        if (array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
            $html .= ' style="display:none"';
        }
         $html .= ' src="images/+.gif" id="kat-1expand" onclick="siteList_expand(-1, kat_expand_r);" height="16" width="16" alt="+" title="" /><img';
        if (!array_search(-1, $openkat) && false === array_search('-1', $kattree)) {
            $html .= ' style="display:none"';
        }
        $html .= ' src="images/-.gif" id="kat-1contract" onclick="kat_contract(-1);" height="16" width="16" alt="-" title="" /><a';
    } else {
        $html .= '<a style="margin-left:16px"';
    }
    $html .= '><img src="images/folder.png" width="16" height="16" alt="" /> '._('Inactive').'</a><div id="kat-1content" style="margin-left:16px">';
    if (array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
        $temp = siteList_expand(-1);
        $html .= $temp['html'];
    }
    $html .= '</div></div><div>';
    if (db()->fetchOne("SELECT id FROM `kat` WHERE bind = 0") || db()->fetchOne("SELECT id FROM `bind` WHERE kat = 0")) {
        $html .= '<img style="';
        if (array_search(0, $openkat) || false !== array_search('0', $kattree)) {
            $html .= 'display:none;';
        }
        $html .= '" src="images/+.gif" id="kat0expand" onclick="siteList_expand(0, kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="';
        if (!array_search(0, $openkat) && false === array_search('0', $kattree)) {
            $html .= 'display:none;';
        }
        $html .= '" src="images/-.gif" id="kat0contract" onclick="kat_contract(\'0\');" height="16" width="16" alt="-" title="" /><a';
    } else {
        $html .= '<a style="margin-left:16px"';
    }
    $html .= ' href="?side=redigerFrontpage"><img src="images/page.png" width="16" height="16" alt="" /> '._('Frontpage').'</a><div id="kat0content" style="margin-left:16px">';
    if (array_search(0, $openkat) || false !== array_search('0', $kattree)) {
        $temp = siteList_expand(0);
        $html .= $temp['html'];
    }
    $html .= '</div></div>';
    return $html;
}

/**
 * @parram int $id
 *
 * @return array
 */
function pages_expand(int $id): array
{
    $html = kat_expand($id, false)['html'];
    $sider = db()->fetchArray('SELECT sider.id, sider.varenr, bind.id as bind, navn FROM `bind` LEFT JOIN sider on bind.side = sider.id WHERE `kat` = '.$id.' ORDER BY sider.navn');
    foreach ($sider as $side) {
        $html .= '<div id="bind'.$side['bind'].'" class="side'.$side['id'].'"><a style="margin-left:16px" class="side">
        <a class="kat" onclick="this.firstChild.checked=true;"><input name="side" type="radio" value="'.$side['id'].'" />
        <img src="images/page.png" width="16" height="16" alt="" /> ' . strip_tags($side['navn'], '<img>');
        if ($side['varenr']) {
            $html .= ' <em>#:'.$side['varenr'].'</em>';
        }
        $html .= '</a></div>';
    }
    return ['id' => $id, 'html' => $html];
}

/**
 * @parram int $id
 *
 * @return array
 */
function siteList_expand(int $id): array
{
    $html = kat_expand($id, false)['html'];
    $sider = db()->fetchArray('SELECT sider.id, sider.varenr, bind.id as bind, navn FROM `bind` LEFT JOIN sider on bind.side = sider.id WHERE `kat` = '.$id.' ORDER BY sider.navn');
    foreach ($sider as $side) {
        $html .= '<div id="bind'.$side['bind'].'" class="side'.$side['id'].'"><a style="margin-left:16px" class="side" href="?side=redigerside&amp;id='.$side['id'].'"><img src="images/page.png" width="16" height="16" alt="" /> ' . strip_tags($side['navn'], '<img>');
        if ($side['varenr']) {
            $html .= ' <em>#:'.$side['varenr'].'</em>';
        }
        $html .= '</a></div>';
    }
    return ['id' => $id, 'html' => $html];
}

/**
 * @return string
 */
function getSiteTree(): string
{
    $html = '<div id="headline">'._('Overview').'</div><div>';
    $html .= siteList($_COOKIE['activekat'] ?? null);

    $customPages = ORM::getByQuery(
        CustomPage::class,
        "
        SELECT * FROM `special`
        WHERE `id` > 1
        ORDER BY `navn`
        "
    );
    foreach ($customPages as $customPage) {
        $html .= '<div style="margin-left: 16px;"><a href="?side=redigerSpecial&id=' . $customPage->getId()
            . '"><img height="16" width="16" alt="" src="images/page.png"/> ' . $customPage->getTitle . '</a></div>';
    }

    return $html . '</div>';
}

/**
 * @param int $id
 * @param bool $input
 *
 * @return array
 */
function kat_expand(int $id, bool $input = true): array
{
    global $kattree;
    $html = '';

    $kats = db()->fetchArray(
        '
        SELECT *
        FROM `kat`
        WHERE bind = ' . $id . '
        ORDER BY `order`, `navn`
        '
    );
    foreach ($kats as $kat) {
        $katExists = db()->fetchOne(
            '
            SELECT id
            FROM `kat`
            WHERE bind = ' . $kat['id']
        );
        if ($katExists
            || (!$input && db()->fetchOne("SELECT id FROM `bind` WHERE kat = " . $kat['id']))
        ) {
            $openkat = explode('<', @$_COOKIE['openkat']);
            $html .= '<div id="kat'.$kat['id'].'"><img style="display:';
            if (array_search($kat['id'], $openkat)
                || false !== array_search($kat['id'], $kattree)
            ) {
                $html .= 'none';
            }
            $html .= '" src="images/+.gif" id="kat'.$kat['id'].'expand" onclick="';
            if ($input) {
                $html .= 'kat_expand('.$kat['id'].', \'true\'';
            } else {
                $html .= 'siteList_expand('.$kat['id'];
            }
            $html .= ', kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="display:';

            if (!array_search($kat['id'], $openkat)
                && false === array_search($kat['id'], $kattree)
            ) {
                $html .= 'none';
            }
            $html .= '" src="images/-.gif" id="kat'.$kat['id'].'contract" onclick="kat_contract('.$kat['id'].');" height="16" width="16" alt="-" title="" /><a class="kat"';

            if ($input) {
                $html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', '.$kat['id'].', 360);"><input name="kat" type="radio" value="'.$kat['id'].'"';
                if (@$kattree[count($kattree)-1] == $kat['id']) {
                    $html .= ' checked="checked"';
                }
                $html .= ' />';
            } else {
                $html .= ' href="?side=redigerkat&id='.$kat['id'].'">';
            }

            $html .= '<img src="';
            if ($kat['icon']) {
                $html .= $kat['icon'];
            } else {
                $html .= 'images/folder.png';
            }
            $html .= '" alt="" /> ' . strip_tags($kat['navn'], '<img>') . '</a><div id="kat' . $kat['id'] . 'content" style="margin-left:16px">';
            if (array_search($kat['id'], $openkat) || false !== array_search($kat['id'], $kattree)) {
                if ($input) {
                    $temp = kat_expand($kat['id'], true);
                } else {
                    $temp = siteList_expand($kat['id']);
                }
                $html .= $temp['html'];
            }
            $html .= '</div></div>';
        } else {
            $html .= '<div id="kat'.$kat['id'].'"><a class="kat" style="margin-left:16px"';
            if ($input) {
                $html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', '.$kat['id'].', 360);"><input type="radio" name="kat" value="'.$kat['id'].'"';
                if (@$kattree[count($kattree)-1] == $kat['id']) {
                    $html .= ' checked="checked"';
                }
                $html .= ' />';
            } else {
                $html .= ' href="?side=redigerkat&id='.$kat['id'].'">';
            }
            $html .= '<img src="';
            if ($kat['icon']) {
                $html .= $kat['icon'];
            } else {
                $html .= 'images/folder.png';
            }
            $html .= '" alt="" /> ' . strip_tags($kat['navn'], '<img>') . '</a></div>';
        }
    }
    return ['id' => $id, 'html' => $html];
}

/**
 * Check if file is in use
 *
 * @param string $path
 *
 * @return bool
 */
function isinuse(string $path): bool
{
    $result = db()->fetchOne(
        "
        (SELECT id FROM `sider` WHERE `text` LIKE '%$path%' OR `beskrivelse` LIKE '%$path%' OR `billed` LIKE '$path' LIMIT 1)
        UNION (SELECT id FROM `template` WHERE `text` LIKE '%$path%' OR `beskrivelse` LIKE '%$path%' OR `billed` LIKE '$path' LIMIT 1)
        UNION (SELECT id FROM `special` WHERE `text` LIKE '%$path%' LIMIT 1)
        UNION (SELECT id FROM `krav` WHERE `text` LIKE '%$path%' LIMIT 1)
        UNION (SELECT id FROM `maerke` WHERE `ico` LIKE '$path' LIMIT 1)
        UNION (SELECT id FROM `list_rows` WHERE `cells` LIKE '%$path%' LIMIT 1)
        UNION (SELECT id FROM `kat` WHERE `navn` LIKE '%$path%' OR `icon` LIKE '$path' LIMIT 1)
        "
    );

    return $result ? true : false;
}

/**
 * Delete unused file
 *
 * @param int $id
 * @param string $path
 *
 * @return bool
 */
function deletefile(int $id, string $path): array
{
    if (isinuse($path)) {
        return ['error' => _('The file can not be deleted because it is used on a page.')];
    }
    if (@unlink(_ROOT_ . $path)) {
            db()->query("DELETE FROM files WHERE `path` = '".$path."'");
        return ['id' => $id];
    } else {
        return ['error' => _('There was an error deleting the file, the file may be in use.')];
    }
}

if (!function_exists('scandir')) {
    /**
     * Scan folder and get list of files and folders in it
     *
     * @param string $dir
     * @param int $sortorder
     *
     * @return mixed
     */
    function scandir(string $dir, int $sortorder = 0)
    {
        if (is_dir($dir) && $listdirs = @opendir($dir)) {
            while (($file = readdir($listdirs)) !== false) {
                $files[] = $file;
            }
            closedir($listdirs);
            ($sortorder == 0) ? asort($files) : rsort($files); // arsort was replaced with rsort
            return $files;
        } else {
            return false;
        }
    }
}

/**
 * Takes a string and changes it to comply with file name restrictions in windows, linux, mac and urls (UTF8)
 * .|"'´`:%=#&\/+?*<>{}-_
 *
 * @param string $filename
 *
 * @return string
 */
function genfilename(string $filename): string
{
    $search = ['/[.&?\/:*"\'´`<>{}|%\s-_=+#\\\\]+/u', '/^\s+|\s+$/u', '/\s+/u'];
    $replace = [' ', '', '-'];
    return mb_strtolower(preg_replace($search, $replace, $filename), 'UTF-8');
}

/**
 * return true for directorys and false for every thing else
 *
 * @param string $str_file
 *
 * @return bool
 */
function is_dirs(string $str_file): bool
{
    global $temp;
    if (is_file(_ROOT_ . $temp . '/' . $str_file)
        || $str_file == '.'
        || $str_file == '..'
    ) {
        return false;
    }
    return true;
}

/**
 * return list of folders in a folder
 *
 * @param string $dir
 *
 * @return mixed
 */
function sub_dirs(string $dir)
{
    global $temp;
    $temp = $dir;
    if ($dirs = scandir(_ROOT_ . $dir)) {
        $dirs = array_filter($dirs, 'is_dirs');
        natcasesort($dirs);
        $dirs = array_values($dirs);
    }
    return $dirs;
}

//TODO document type does not allow element "input" here; missing one of "p", "h1", "h2", "h3", "h4", "h5", "h6", "div", "pre", "address", "fieldset", "ins", "del" start-tag.
/**
 * Display a list of directorys for the explorer
 *
 * @param string $dir
 * @param int $mode
 *
 * @return array
 */
function listdirs(string $dir, int $mode = 0): array
{
    $subdirs = sub_dirs($dir);
    $html = '';
    foreach ($subdirs as $subdir) {
        $html .= '<div id="dir_' . preg_replace('#/#u', '.', $dir . '/' . $subdir)
        . '">';
        if (sub_dirs($dir.'/'.$subdir)) {
            $html .= '<img';
            if (@$_COOKIE[$dir.'/'.$subdir]) {
                $html .= ' style="display:none"';
            }
            $html .= ' src="images/+.gif"';
            $html .= ' onclick="dir_expand(this,'.$mode.');"';
            $html .= ' height="16" width="16" alt="+" title="" /><img';
            if (empty($_COOKIE[$dir.'/'.$subdir])) {
                $html .= ' style="display:none"';
            }
            $html .= ' src="images/-.gif"';
            $html .= ' onclick="dir_contract(this);"';
            $html .= ' height="16" width="16" alt="-" title="" /><a';
            if ($dir.'/'.$subdir == @$_COOKIE['admin_dir']) {
                $html .= ' class="active"';
            }
            if ($mode == 0) {
                $html .= ' onclick="showfiles(\''.$dir.'/'.$subdir.'\', 0);this.className=\'active\'" ondblclick="showdirname(this)" title="'.$subdir.'"><img src="images/folder.png" height="16" width="16" alt="" /> <span>'.$subdir.'</span></a><form action="" method="get" onsubmit="document.getElementById(\'files\').focus();return false;" style="display:none"><p style="display: inline; margin-left: 3px;"><img width="16" height="16" alt="" src="images/folder.png"/><input style="display:inline;" onblur="renamedir(this);" maxlength="'.(254-mb_strlen($dir, 'UTF-8')).'" value="'.$subdir.'" name="'.$dir.'/'.$subdir.'" /></p></form>';
            } elseif ($mode == 1) {
                $html .= ' onclick="movefile(\''.$dir.'/'.$subdir.'\')" title="'.$subdir.'"><img src="images/folder.png" height="16" width="16" alt="" /> '.$subdir.' </a>';
            }

            $html .= '<div>';
            if (@$_COOKIE[$dir.'/'.$subdir]) {
                $listdirs = listdirs($dir.'/'.$subdir, $mode);
                $html .= $listdirs['html'];
            }
            $html .= '</div></div>';
        } else {
            $html .= '<a style="margin-left:16px"';
            if ($dir.'/'.$subdir == @$_COOKIE['admin_dir']) {
                $html .= ' class="active"';
            }
            if ($mode == 0) {
                $html .= ' onclick="showfiles(\''.$dir.'/'.$subdir.'\', 0);this.className=\'active\'" ondblclick="showdirname(this)" title="'.$subdir.'"><img src="images/folder.png" height="16" width="16" alt="" /> <span>'.$subdir.'</span></a><form action="" method="get" onsubmit="document.getElementById(\'files\').focus();return false;" style="display:none"><p style="display: inline; margin-left: 19px;"><img width="16" height="16" alt="" src="images/folder.png"/><input style="display:inline;" onblur="renamedir(this);" maxlength="'.(254-mb_strlen($dir, 'UTF-8')).'" value="'.$subdir.'" name="'.$dir.'/'.$subdir.'" /></p></form></div>';
            } elseif ($mode == 1) {
                $html .= ' onclick="movefile(\''.$dir.'/'.$subdir.'\')" title="'.$subdir.'"><img src="images/folder.png" height="16" width="16" alt="" /> '.$subdir.' </a></div>';
            }
        }
    }
    return ['id' => $dir, 'html' => $html];
}

/**
 * Update user
 *
 * @param int   $id      User id
 * @param array $updates Array of values to change
 *                       'access' int 0 = no acces, 1 = admin, 3 = priviliged, 4 = clerk
 *                       'password' crypt(string)
 *                       'password_new' string
 *                       'fullname' string
 *                       'name' string
 *                       'lastlogin' MySQL time stamp
 *
 * @return mixed True on update, else ['error' => string]
 */
function updateuser(int $id, array $updates)
{
    if ($_SESSION['_user']['access'] == 1 || $_SESSION['_user']['id'] == $id) {
        //Validate access lavel update
        if ($_SESSION['_user']['id'] == $id && $updates['access'] != $_SESSION['_user']['access']) {
            return ['error' => _('You can\'t change your own access level')];
        }

        //Validate password update
        if (!empty($updates['password_new'])) {
            if ($_SESSION['_user']['access'] == 1 && $_SESSION['_user']['id'] != $id) {
                $updates['password'] = crypt($updates['password_new']);
            } elseif ($_SESSION['_user']['id'] == $id) {
                $user = db()->fetchOne("SELECT `password` FROM `users` WHERE id = ".$id);
                if (mb_substr($user['password'], 0, 13) == mb_substr(crypt($updates['password'], $user['password']), 0, 13)) {
                    $updates['password'] = crypt($updates['password_new']);
                } else {
                    return ['error' => _('Incorrect password.')];
                }
            } else {
                return ['error' => _('You do not have the requred access level to change the password for other users.')];
            }
        } else {
            unset($updates['password']);
        }
        unset($updates['password_new']);

        //Generate SQL command
        $sql = "UPDATE `users` SET";
        foreach ($updates as $key => $value) {
            $sql .= " `".addcslashes($key, '`\\')."` = '".addcslashes($value, "'\\")."',";
        }
        $sql = substr($sql, 0, -1);
        $sql .= ' WHERE `id` = '.$id;

        //Run SQL
        db()->query($sql);

        return true;
    } else {
        return ['error' => _('You do not have the requred access level to change this user.')];
    }
}

/**
 * @param string $path
 * @param int $cropX
 * @param int $cropY
 * @param int $cropW
 * @param int $cropH
 * @param int $maxW
 * @param int $maxH
 * @param int $flip
 * @param int $rotate
 * @param string $filename
 * @param bool $force
 *
 * @return array
 */
function saveImage(string $path, int $cropX, int $cropY, int $cropW, int $cropH, int $maxW, int $maxH, int $flip, int $rotate, string $filename, bool $force): array
{
    $mimeType = get_mime_type($path);

    $output = [];
    if ($mimeType === 'image/jpeg') {
        $output['type'] = 'jpg';
    } else {
        $output['type'] = 'png';
    }

    $output['filename'] = $filename;
    $output['force'] = $force;

    return generateImage($path, $cropX, $cropY, $cropW, $cropH, $maxW, $maxH, $flip, $rotate, $output);
    //TODO close and update image in explorer
}

/**
 * Delete user
 *
 * @param int $id User id
 *
 * @return null
 */
function deleteuser(int $id): bool
{
    if ($_SESSION['_user']['access'] != 1) {
        return false;
    }

    db()->query("DELETE FROM `users` WHERE `id` = ".$id);
    return true;
}

/**
 * @param string $filename
 * @param string $type
 *
 * @return bool
 */
function fileExists(string $filename, string $type = ''): bool
{
    $pathinfo = pathinfo($filename);
    $filePath = _ROOT_ . @$_COOKIE['admin_dir'] . '/' . genfilename($pathinfo['filename']);

    if ($type == 'image') {
        $filePath .= '.jpg';
    } elseif ($type == 'lineimage') {
        $filePath .= '.png';
    } else {
        $filePath .= '.'.$pathinfo['extension'];
    }

    return (bool) is_file($filePath);
}

/**
 * @return int
 */
function newfaktura(): int
{
    db()->query(
        "
        INSERT INTO `fakturas` (`date`, `clerk`)
        VALUES (
            now(),
            '" . addcslashes($_SESSION['_user']['fullname'], '\'\\') . "'
        );
        "
    );
    return db()->insert_id;
}

/**
 * @param int $bind
 * @param string $path_name
 */
function print_kat(int $bind, string $path_name)
{
    $kats = db()->fetchArray("SELECT id, bind, navn FROM `kat` WHERE bind = ".$bind." ORDER BY navn");
    foreach ($kats as $kat) {
        echo "\n".'  <tr class="path"><td colspan="8"><a href="?sort='.@$_GET['sort'].'&amp;kat='.$kat['id'].'"><img src="images/find.png" alt="Vis" title="Vis kun denne kategori" /></a> '.$path_name.' &gt; <a href="/kat'.$kat['id'].'-">'.xhtmlEsc($kat['navn']).'</a></td></tr>';
        print_pages($kat['id']);
        print_kat($kat['id'], $path_name.' &gt; '.xhtmlEsc($kat['navn']));
    }
}

/**
 * @param int $kat
 */
function print_pages(int $kat)
{
    global $maerker;
    global $krav;
    global $sort;
    $sider = db()->fetchArray("SELECT sider.* FROM `bind` JOIN sider ON bind.side = sider.id WHERE bind.kat = ".$kat." ORDER BY " . $sort);
    $altrow = 0;
    foreach ($sider as $side) {
        echo '<tr';
        if ($altrow) {
            echo ' class="altrow"';
            $altrow = 0;
        } else {
            $altrow = 1;
        }

        echo '>
      <td class="tal"><a href="/admin/?side=redigerside&amp;id='.$side['id'].'">'.$side['id'].'</a></td>
      <td><a href="/side'.$side['id'].'-">'.xhtmlEsc($side['navn']).'</a></td>
      <td>'.xhtmlEsc($side['varenr']).'</td>
      <td class="tal">'.number_format($side['for'], 2, ',', '.').'</td>
      <td class="tal">'.number_format($side['pris'], 2, ',', '.').'</td>
      <td class="tal">'.$side['dato'].'</td>
      <td>';
        echo (!empty($side['maerke']) ? $maerker[$side['maerke']] : '') . ' </td><td>' . (!empty($side['krav']) ? $krav[$side['krav']] : '') . '</td></tr>';
    }
}

/**
 * Returns false for files that the users shoudn't see in the files view
 *
 * @param string $str_file
 *
 * @return bool
 */
function is_files(string $str_file): bool
{
    global $dir;
    if ($str_file == '.' || $str_file == '..' || $str_file == '.htaccess' || is_dir(_ROOT_ . $dir . '/' . $str_file)) {
        return false;
    }
    return true;
}

/**
 * display a list of files in the selected folder
 *
 * @param string $temp_dir
 *
 * @return array
 */
function showfiles(string $temp_dir): array
{
    //temp_dir is needed to initialize dir as global
    //$dir needs to be global for other functions like is_files()
    global $dir;
    $dir = $temp_dir;
    unset($temp_dir);
    $html = '';
    $javascript = '';

    if ($files = scandir(_ROOT_ . $dir)) {
        $files = array_filter($files, 'is_files');
        natcasesort($files);
    } else {
        $files = [];
    }

    foreach ($files as $file) {
        $fileinfo = db()->fetchOne(
            "
            SELECT * FROM files
            WHERE path = '" . db()->esc($dir . "/" . $file) . "'"
        );

        if (!$fileinfo) {
            //Save file info to db
            $mime = get_mime_type($dir . '/' . $file);
            $imagesize = @getimagesize(_ROOT_ . $dir . '/' . $file);
            $size = filesize(_ROOT_ . $dir . '/' . $file);
            db()->query('INSERT INTO files (path, mime, width, height, size, aspect) VALUES (\''.$dir.'/'.$file."', '".$mime."', '".$imagesize[0]."', '".$imagesize[1]."', '".$size."', NULL )");
            $fileinfo['path'] = $dir.'/'.$file;
            $fileinfo['mime'] = $mime;
            $fileinfo['width'] = $imagesize[0];
            $fileinfo['height'] = $imagesize[1];
            $fileinfo['size'] = $size;
            $fileinfo['id'] = db()->insert_id;
//          $fileinfo['aspect'] = NULL;
            unset($imagesize);
            unset($mime);
        }

        $html .= filehtml($fileinfo);
        //TODO reduce net to javascript
        $javascript .= filejavascript($fileinfo);
    }
    return ['id' => 'files', 'html' => $html, 'javascript' => $javascript];
}

/**
 * @param array $fileinfo
 *
 * @return string
 */
function filejavascript(array $fileinfo): string
{
    $pathinfo = pathinfo($fileinfo['path']);

    $javascript = '
    files['.$fileinfo['id'].'] = new file('.$fileinfo['id'].', \''.$fileinfo['path'].'\', \''.$pathinfo['filename'].'\'';

    $javascript .= ', \'';
    switch ($fileinfo['mime']) {
        case 'image/jpeg':
        case 'image/png':
        case 'image/gif':
            $javascript .= 'image';
            break;
        case 'video/x-flv':
            $javascript .= 'flv';
            break;
        case 'video/x-shockwave-flash':
        case 'application/x-shockwave-flash':
        case 'application/futuresplash':
            $javascript .= 'swf';
            break;
        case 'video/avi':
        case 'video/x-msvideo':
        case 'video/mpeg':
        case 'audio/mpeg':
        case 'video/quicktime':
        case 'video/x-ms-asf':
        case 'video/x-ms-wmv':
        case 'audio/x-wav':
        case 'audio/midi':
        case 'audio/x-ms-wma':
            $javascript .= 'video';
            break;
        default:
            $javascript .= 'unknown';
            break;
    }
    $javascript .= '\'';

    $javascript .= ', \''.addcslashes(@$fileinfo['alt'], "\\'").'\'';
    $javascript .= ', '.($fileinfo['width'] ? $fileinfo['width'] : '0').'';
    $javascript .= ', '.($fileinfo['height'] ? $fileinfo['height'] : '0').'';
    $javascript .= ');';

    return $javascript;
}

/**
 * @param array $fileinfo
 *
 * @return string
 */
function filehtml(array $fileinfo): string
{
    $pathinfo = pathinfo($fileinfo['path']);

    $html = '';

    switch ($fileinfo['mime']) {
        case 'image/gif':
        case 'image/jpeg':
        case 'image/png':
            $html .= '<div id="tilebox'.$fileinfo['id'].'" class="imagetile"><div class="image"';
            if ($_GET['return']=='rtef') {
                $html .= ' onclick="addimg('.$fileinfo['id'].')"';
            } elseif ($_GET['return']=='thb') {
                if ($fileinfo['width'] <= Config::get('thumb_width') && $fileinfo['height'] <= Config::get('thumb_height')) {
                    $html .= ' onclick="insertThumbnail('.$fileinfo['id'].')"';
                } else {
                    $html .= ' onclick="open_image_thumbnail('.$fileinfo['id'].')"';
                }
            } else {
                $html .= ' onclick="files['.$fileinfo['id'].'].openfile();"';
            }
            break;
        case 'video/x-flv':
            $html .= '<div id="tilebox'.$fileinfo['id'].'" class="flvtile"><div class="image"';
            if ($_GET['return']=='rtef') {
                if ($fileinfo['aspect'] == '4-3') {
                    $html .= ' onclick="addflv('.$fileinfo['id'].', \''.$fileinfo['aspect'].'\', '.max($fileinfo['width'], $fileinfo['height']/3*4).', '.ceil($fileinfo['width']/4*3*1.1975).')"';
                } elseif ($fileinfo['aspect'] == '16-9') {
                    $html .= ' onclick="addflv('.$fileinfo['id'].', \''.$fileinfo['aspect'].'\', '.max($fileinfo['width'], $fileinfo['height']/9*16).', '.ceil($fileinfo['width']/16*9*1.2).')"';
                }
            } else {
                $html .= ' onclick="files['.$fileinfo['id'].'].openfile();"';
            }
            break;
        case 'application/futuresplash':
        case 'application/x-shockwave-flash':
        case 'video/x-shockwave-flash':
            $html .= '<div id="tilebox'.$fileinfo['id'].'" class="swftile"><div class="image"';
            if ($_GET['return']=='rtef') {
                $html .= ' onclick="addswf('.$fileinfo['id'].', '.$fileinfo['width'].', '.$fileinfo['height'].')"';
            } else {
                $html .= ' onclick="files['.$fileinfo['id'].'].openfile();"';
            }
            break;
        case 'audio/midi':
        case 'audio/mpeg':
        case 'audio/x-ms-wma':
        case 'audio/x-wav':
        case 'video/avi':
        case 'video/mpeg':
        case 'video/quicktime':
        case 'video/x-ms-asf':
        case 'video/x-msvideo':
        case 'video/x-ms-wmv':
            $html .= '<div id="tilebox'.$fileinfo['id'].'" class="videotile"><div class="image"';
            //TODO make the actual functions
            if ($_GET['return']=='rtef') {
                $html .= ' onclick="addmedia('.$fileinfo['id'].')"';
            } else {
                $html .= ' onclick="files['.$fileinfo['id'].'].openfile();"';
            }
            break;
        default:
            $html .= '<div id="tilebox'.$fileinfo['id'].'" class="filetile"><div class="image"';
            if ($_GET['return']=='rtef') {
                $html .= ' onclick="addfile('.$fileinfo['id'].')"';
            } else {
                $html .= ' onclick="files['.$fileinfo['id'].'].openfile();"';
            }
            break;
    }

    $html .='> <img src="';

    $type = 'bin';
    switch ($fileinfo['mime']) {
        case 'image/gif':
        case 'image/jpeg':
        case 'image/png':
        case 'image/vnd.wap.wbmp':
            $type = 'image-native';
            break;
        case 'application/pdf':
            $type = 'pdf';
            break;
        case 'application/postscript':
            $type = 'image';
            break;
        case 'application/futuresplash':
        case 'application/vnd.ms-powerpoint':
        case 'application/vnd.rn-realmedia':
        case 'application/x-shockwave-flash':
            $type = 'video';
            break;
        case 'application/msword':
        case 'application/rtf':
        case 'application/vnd.ms-excel':
        case 'application/vnd.ms-works':
            $type = 'text';
            break;
        case 'text/css':
        case 'text/html':
            $type = 'sys';
            break;
        case 'application/mac-binhex40':
        case 'application/x-7z-compressed':
        case 'application/x-bzip2':
        case 'application/x-compressed': //missing
        case 'application/x-compress': //missing
        case 'application/x-gtar':
        case 'application/x-gzip':
        case 'application/x-rar':
        case 'application/x-rar-compressed':
        case 'application/x-stuffit':
        case 'application/x-stuffitx':
        case 'application/x-tar':
        case 'application/x-zip':
        case 'application/zip':
            $type = 'zip';
            break;
        default:
            $type = explode('/', $fileinfo['mime']);
            $type = array_shift($type);
            break;
    }

    switch ($type) {
        case 'image-native':
            $html .= 'image.php?path=' . rawurlencode($pathinfo['dirname'] . '/' . $pathinfo['basename']) . '&amp;maxW=128&amp;maxH=96';
            break;
        case 'pdf':
        case 'image':
        case 'video':
        case 'audio':
        case 'text':
        case 'sys':
        case 'zip':
            $html .= 'images/file-' . $type . '.gif';
            break;
        default:
            $html .= 'images/file-bin.gif';
            break;
    }

    $html .= '" alt="" title="" /> </div><div ondblclick="showfilename('.$fileinfo['id'].')" class="navn" id="navn'.$fileinfo['id'].'div" title="'.$pathinfo['filename'].'"> '.$pathinfo['filename'].'</div><form action="" method="get" onsubmit="document.getElementById(\'files\').focus();return false;" style="display:none" id="navn'.$fileinfo['id'].'form"><p><input onblur="renamefile(\''.$fileinfo['id'].'\');" maxlength="'.(251-mb_strlen($pathinfo['dirname'], 'UTF-8')).'" value="'.$pathinfo['filename'].'" name="" /></p></form>';
    $html .= '</div>';
    return $html;
}

/**
 * @param string $name
 *
 * @return array
 */
function makedir(string $name): array
{
    $name = genfilename($name);
    if (is_dir(_ROOT_ . @$_COOKIE['admin_dir'] . '/' . $name)) {
        return ['error' => _('A file or folder with the same name already exists.')];
    }

    if (!is_dir(_ROOT_ . @$_COOKIE['admin_dir'])
        || !mkdir(_ROOT_ . @$_COOKIE['admin_dir'] . '/' . $name, 0771)
    ) {
        return ['error' => _('Could not create folder, you may not have sufficient rights to this folder.')];
    }

    return ['error' => false];
}

//TODO if force, refresh folder or we might have duplicates displaying in the folder.
//TODO Error out if the files is being moved to it self
//TODO moving two files to the same dire with no reload inbetwean = file exists?????????????
/**
 * Rename or relocate a file/directory
 *
 * @param int $id
 * @param string $path
 * @param string $dir
 * @param string $filename
 * @param bool $force
 *
 * @return array
 */
function renamefile(int $id, string $path, string $dir, string $filename, bool $force = false): array
{
    $pathinfo = pathinfo($path);
    if ($pathinfo['dirname'] == '/') {
        $pathinfo['dirname'] == '';
    }

    if (!$dir) {
        $dir = $pathinfo['dirname'];
    } elseif ($dir == '/') {
        $dir == '';
    }

    if (!is_dir(_ROOT_ . $path)) {
        $mime = get_mime_type($path);
        if ($mime == 'image/jpeg') {
            $pathinfo['extension'] = 'jpg';
        } elseif ($mime == 'image/png') {
            $pathinfo['extension'] = 'png';
        } elseif ($mime == 'image/gif') {
            $pathinfo['extension'] = 'gif';
        } elseif ($mime == 'application/pdf') {
            $pathinfo['extension'] = 'pdf';
        } elseif ($mime == 'video/x-flv') {
            $pathinfo['extension'] = 'flv';
        } elseif ($mime == 'image/vnd.wap.wbmp') {
            $pathinfo['extension'] = 'wbmp';
        }
    } else {
        //a folder with a . will mistakingly be seen as a file with extension
        $pathinfo['filename'] .= '-' . @$pathinfo['extension'];
        $pathinfo['extension'] = '';
    }

    if (!$filename) {
        $filename = $pathinfo['filename'];
    }

    $filename = genfilename($filename);

    if (!$filename) {
        return ['error' => _('The name is invalid.'), 'id' => $id];
    }

    //Destination folder doesn't exist
    if (!is_dir(_ROOT_ . $dir . '/')) {
        return ['error' => _('The file could not be moved because the destination folder does not exist.'), 'id' => $id];
    }
    if ($pathinfo['extension']) {
        //No changes was requested.
        if ($path == $dir.'/'.$filename.'.'.$pathinfo['extension']) {
            return ['id' => $id, 'filename' => $filename, 'path' => $path];
        }

        //if file path more then 255 erturn error
        if (mb_strlen($dir.'/'.$filename.'.'.$pathinfo['extension'], 'UTF-8') > 255) {
            return ['error' => _('The filename is too long.'), 'id' => $id];
        }

        //File already exists, but are we trying to force a overwrite?
        if (is_file(_ROOT_ . $dir . '/' . $filename . '.' . $pathinfo['extension']) && !$force) {
            return ['yesno' => _('A file with the same name already exists.
Would you like to replace the existing file?'), 'id' => $id];
        }

        //Rename/move or give an error
        if (@rename(_ROOT_ . $path, _ROOT_ . $dir . '/' . $filename . '.' . $pathinfo['extension'])) {
            if ($force) {
                db()->query("DELETE FROM files WHERE `path` = '" . $dir . "/" . $filename . "." . $pathinfo['extension'] . "'");
            }

            db()->query("UPDATE `files` SET `path` = '" . $dir . "/" . $filename . "." . $pathinfo['extension'] . "' WHERE `path` = '" . $path . "'");

            db()->query("UPDATE sider SET navn = REPLACE(navn, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), text = REPLACE(text, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), beskrivelse = REPLACE(beskrivelse, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), billed = REPLACE(billed, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            db()->query("UPDATE template SET navn = REPLACE(navn, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), text = REPLACE(text, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), beskrivelse = REPLACE(beskrivelse, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), billed = REPLACE(billed, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            db()->query("UPDATE special SET text = REPLACE(text, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            db()->query("UPDATE krav SET text = REPLACE(text, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            db()->query("UPDATE maerke SET ico = REPLACE(ico, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            db()->query("UPDATE list_rows SET cells = REPLACE(cells, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            db()->query("UPDATE kat SET navn = REPLACE(navn, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), icon = REPLACE(icon, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");

            return ['id' => $id, 'filename' => $filename, 'path' => $dir.'/'.$filename.'.'.$pathinfo['extension']];
        } else {
            return ['error' => _('An error occurred with the file operations.'), 'id' => $id];
        }
    } else {
    //Dir or file with no extension
    //TODO ajax rename folder
        //No changes was requested.
        if ($path == $dir.'/'.$filename) {
            return ['id' => $id, 'filename' => $filename, 'path' => $path];
        }


        //folder already exists
        if (is_dir(_ROOT_ . $dir . '/' . $filename)) {
            return ['error' => _('A folder with the same name already exists.'), 'id' => $id];
        }

        //if file path more then 255 erturn error
        if (mb_strlen($dir.'/'.$filename, 'UTF-8') > 255) {
            return ['error' => _('The filename is too long.'), 'id' => $id];
        }

        //File already exists, but are we trying to force a overwrite?
        if (is_file(_ROOT_ . $path) && !$force) {
            return ['yesno' => _('A file with the same name already exists.
Would you like to replace the existing file?'), 'id' => $id];
        }

        //Rename/move or give an error
        //TODO prepared query
        if (@rename(_ROOT_ . $path, _ROOT_ . $dir . '/' . $filename)) {
            if ($force) {
                db()->query("DELETE FROM files WHERE `path` = '".$dir.'/'.$filename."%'");
                //TODO insert new file data (width, alt, height, aspect)
            }
            db()->query("UPDATE files    SET path = REPLACE(path, '".$path."', '".$dir.'/'.$filename."')");
            db()->query("UPDATE sider    SET navn = REPLACE(navn, '".$path."', '".$dir.'/'.$filename."'), text = REPLACE(text, '$path', '".$dir.'/'.$filename."'), beskrivelse = REPLACE(beskrivelse, '$path', '".$dir.'/'.$filename."'), billed = REPLACE(billed, '$path', '".$dir.'/'.$filename."')");
            db()->query("UPDATE template SET navn = REPLACE(navn, '".$path."', '".$dir.'/'.$filename."'), text = REPLACE(text, '$path', '".$dir.'/'.$filename."'), beskrivelse = REPLACE(beskrivelse, '$path', '".$dir.'/'.$filename."'), billed = REPLACE(billed, '$path', '".$dir.'/'.$filename."')");
            db()->query("UPDATE special  SET text = REPLACE(text, '".$path."', '".$dir.'/'.$filename."')");
            db()->query("UPDATE krav     SET text = REPLACE(text, '".$path."', '".$dir.'/'.$filename."')");
            db()->query("UPDATE maerke   SET ico  = REPLACE( ico, '".$path."', '".$dir.'/'.$filename."')");
            db()->query("UPDATE list_rows  SET cells  = REPLACE(cells, '".$path."', '".$dir.'/'.$filename."')");
            db()->query("UPDATE kat      SET navn = REPLACE(navn, '".$path."', '".$dir.'/'.$filename."'), icon = REPLACE(icon, '$path', '".$dir.'/'.$filename."')");

            if (is_dir(_ROOT_ . $dir . '/' . $filename)) {
                if (@$_COOKIE[@$_COOKIE['admin_dir']]) {
                    @setcookie($dir.'/'.$filename, @$_COOKIE[@$_COOKIE['admin_dir']]);
                }
                @setcookie(@$_COOKIE['admin_dir'], false);
                @setcookie('admin_dir', $dir.'/'.$filename);
            }

            return ['id' => $id, 'filename' => $filename, 'path' => $dir . '/' . $filename];
        } else {
            return ['error' => _('An error occurred with the file operations.'), 'id' => $id];
        }
    }
}

function deletefolder()
{
    /**
     * Rename or relocate a file/directory
     *
     * @param string $dir
     *
     * @return mixed
     */
    function deltree(string $dir)
    {
        $dirlists = scandir(_ROOT_ . $dir);
        foreach ($dirlists as $dirlist) {
            if ($dirlist != '.' && $dirlist != '..') {
                if (is_dir(_ROOT_ . $dir . '/' . $dirlist)) {
                    $deltree = deltree($dir . '/' . $dirlist);
                    if ($deltree) {
                        return $deltree;
                    }
                    @rmdir(_ROOT_ . $dir . '/' . $dirlist);
                    @setcookie($dir . '/' .$dirlist, false);
                } else {
                    if (db()->fetchOne("SELECT id FROM `sider` WHERE `navn` LIKE '%" . $dir . "/" . $dirlist . "%' OR `text` LIKE '%" . $dir . "/" . $dirlist . "%' OR `beskrivelse` LIKE '%" . $dir . "/" . $dirlist . "%' OR `billed` LIKE '%" . $dir . "/" . $dirlist . "%'")
                    || db()->fetchOne("SELECT id FROM `template` WHERE `navn` LIKE '%".$dir."/".$dirlist."%' OR `text` LIKE '%".$dir."/".$dirlist."%' OR `beskrivelse` LIKE '%".$dir."/".$dirlist."%' OR `billed` LIKE '%".$dir."/".$dirlist."%'")
                    || db()->fetchOne("SELECT id FROM `special` WHERE `text` LIKE '%".$dir."/".$dirlist."%'")
                    || db()->fetchOne("SELECT id FROM `krav` WHERE `text` LIKE '%".$dir."/".$dirlist."%'")
                    || db()->fetchOne("SELECT id FROM `maerke` WHERE `ico` LIKE '%".$dir."/".$dirlist."%'")
                    || db()->fetchOne("SELECT id FROM `list_rows` WHERE `cells` LIKE '%".$dir."/".$dirlist."%'")
                    || db()->fetchOne("SELECT id FROM `kat` WHERE `navn` LIKE '%".$dir."/".$dirlist."%' OR `icon` LIKE '%".$dir."/".$dirlist."%'")) {
                        return ['error' => _('A file could not be deleted because it is used on a site.')];
                    }
                    @unlink(_ROOT_ . $dir . '/' . $dirlist);
                }
            }
        }
    }
    $deltree = deltree(@$_COOKIE['admin_dir']);
    if ($deltree) {
        return $deltree;
    }
    if (@rmdir(_ROOT_ . @$_COOKIE['admin_dir'])) {
        @setcookie(@$_COOKIE['admin_dir'], false);
        return true;
    } else {
        return ['error' => _('The folder could not be deleted, you may not have sufficient rights to this folder.')];
    }
}

/**
 * @param string $qpath
 * @param string $qalt
 * @param string $qmime
 *
 * @return array
 */
function searchfiles(string $qpath, string $qalt, string $qmime): array
{
    $qpath = db()->escapeWildcards(db()->esc($qpath));
    $qalt = db()->escapeWildcards(db()->esc($qalt));

    $sql_mime = '';
    switch ($qmime) {
        case 'image':
            $sql_mime = "(mime = 'image/jpeg' OR mime = 'image/png' OR mime = 'image/gif' OR mime = 'image/vnd.wap.wbmp')";
            break;
        case 'imagefile':
            $sql_mime = "(mime = 'application/postscript' OR mime = 'image/x-ms-bmp' OR mime = 'image/x-psd' OR mime = 'image/x-photoshop' OR mime = 'image/tiff' OR mime = 'image/x-eps' OR mime = 'image/bmp')";
            break;
        case 'video':
            $sql_mime = "(mime = 'video/avi' OR mime = 'video/x-msvideo' OR mime = 'video/mpeg' OR mime = 'video/quicktime' OR mime = 'video/x-shockwave-flash' OR mime = 'application/futuresplash' OR mime = 'application/x-shockwave-flash' OR mime = 'video/x-flv' OR mime = 'video/x-ms-asf' OR mime = 'video/x-ms-wmv' OR mime = 'application/vnd.ms-powerpoint' OR mime = 'video/vnd.rn-realvideo' OR mime = 'application/vnd.rn-realmedia')";
            break;
        case 'audio':
            $sql_mime = "(mime = 'audio/vnd.rn-realaudio' OR mime = 'audio/x-wav' OR mime = 'audio/mpeg' OR mime = 'audio/midi' OR mime = 'audio/x-ms-wma')";
            break;
        case 'text':
            $sql_mime = "(mime = 'application/pdf' OR mime = 'text/plain' OR mime = 'application/rtf' OR mime = 'text/rtf' OR mime = 'application/msword' OR mime = 'application/vnd.ms-works' OR mime = 'application/vnd.ms-excel')";
            break;
        case 'sysfile':
            $sql_mime = "(mime = 'text/html' OR mime = 'text/css')";
            break;
        case 'compressed':
            $sql_mime = "(mime = 'application/x-gzip' OR mime = 'application/x-gtar' OR mime = 'application/x-tar' OR mime = 'application/x-stuffit' OR mime = 'application/x-stuffitx' OR mime = 'application/zip' OR mime = 'application/x-zip' OR mime = 'application/x-compressed' OR mime = 'application/x-compress' OR mime = 'application/mac-binhex40' OR mime = 'application/x-rar-compressed' OR mime = 'application/x-rar' OR mime = 'application/x-bzip2' OR mime = 'application/x-7z-compressed')";
            break;
    }

    //Generate search query
    $sql = '';
    $sql .= ' FROM `files`';
    if ($qpath || $qalt || $sql_mime) {
        $sql .= ' WHERE ';
        if ($qpath || $qalt) {
            $sql .= '(';
        }
        if ($qpath) {
            $sql .= "MATCH(path) AGAINST('".$qpath."')>0";
        }
        if ($qpath && $qalt) {
            $sql .= " OR ";
        }
        if ($qalt) {
            $sql .= "MATCH(alt) AGAINST('".$qalt."')>0";
        }
        if ($qpath) {
            $sql .= " OR `path` LIKE '%".$qpath."%' ";
        }
        if ($qalt) {
            $sql .= " OR `alt` LIKE '%".$qalt."%'";
        }
        if ($qpath || $qalt) {
            $sql .= ")";
        }
        if (($qpath || $qalt) && !empty($sql_mime)) {
            $sql .= " AND ";
        }
        if (!empty($sql_mime)) {
            $sql .= $sql_mime;
        }
    }

    $filecount = db()->fetchOne("SELECT count(id) AS count" . $sql);
    $filecount = $filecount['count'];

    $sql_select = '';
    if ($qpath || $qalt) {
        $sql_select .= ', ';
        if ($qpath && $qalt) {
            $sql_select .= '(';
        }
        if ($qpath) {
            $sql_select .= 'MATCH(path) AGAINST(\''.$qpath.'\')';
        }
        if ($qpath && $qalt) {
            $sql_select .= ' + ';
        }
        if ($qalt) {
            $sql_select .= 'MATCH(alt) AGAINST(\''.$qalt.'\')';
        }
        if ($qpath && $qalt) {
            $sql_select .= ')';
        }
        $sql_select .= ' AS score';
        $sql = $sql_select.$sql;
        $sql .= ' ORDER BY `score` DESC';
    }


    $filenumber = 0;
    $html = '';
    $javascript = '';
    while ($filenumber < $filecount) {
        $limit = 250;
        if ($filecount-$filenumber<250) {
            $limit = $filecount-$filenumber;
        }
        //TODO return error if befor time out or mem exceded
        //TODO set header() to internal error at the start of all ajax request and 200 (OK) at the end and make javascript display an error if the returned isn't 200;
        $files = db()->fetchArray('SELECT *'.$sql.' LIMIT '.$filenumber.', '.$limit);
        $filenumber += 250;

        foreach ($files as $key => $file) {
            if ($qmime != 'unused' || !isinuse($file['path'])) {
                $html .= filehtml($file);
                $javascript .= filejavascript($file);
            }
            unset($files[$key]);
        }
    }

    return ['id' => 'files', 'html' => $html, 'javascript' => $javascript];
}

/**
 * @param int $qpath
 * @param string $alt
 *
 * @return array
 */
function edit_alt(int $id, string $alt): array
{
    db()->query("UPDATE `files` SET `alt` = '" . db()->esc($alt) . "' WHERE `id` = " . $id);

    //Update html with new alt...
    $file = db()->fetchOne("SELECT path FROM `files` WHERE `id` = " . $id);
    $sider = db()->fetchArray("SELECT id, text FROM `sider` WHERE `text` LIKE '%" . $file['path'] . "%'");

    foreach ($sider as $value) {
        //TODO move this to db fixer to test for missing alt="" in img
        /*preg_match_all('/<img[^>]+/?>/ui', $value, $matches);*/
        $value['text'] = preg_replace('/(<img[^>]+src="'.addcslashes(str_replace('.', '[.]', $file['path']), '/').'"[^>]+alt=)"[^"]*"([^>]*>)/iu', '\1"'.xhtmlEsc($alt).'"\2', $value['text']);
        $value['text'] = preg_replace('/(<img[^>]+alt=)"[^"]*"([^>]+src="'.addcslashes(str_replace('.', '[.]', $file['path']), '/').'"[^>]*>)/iu', '\1"'.xhtmlEsc($alt).'"\2', $value['text']);
        db()->query("UPDATE `sider` SET `text` = '" . $value['text'] . "' WHERE `id` = " . $value['id']);
    }
    return ['id' => $id, 'alt' => $alt];
}

/**
 * Use HTMLPurifier to clean HTML-code, preserves youtube videos
 *
 * @param string $string Sting to clean
 *
 * @return string Cleaned stirng
 **/
function purifyHTML(string $string): string
{
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^http://www.youtube.com/embed/%u');
    $config->set('HTML.SafeObject', true);
    $config->set('Output.FlashCompat', true);
    $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
    $purifier = new HTMLPurifier($config);

    return $purifier->purify($string);
}

function rtefsafe(string $text): string
{
    return str_replace(
        ["'", chr(10), chr(13), '?', '?'],
        ["&#39;", ' ', ' ', ' ', ' '],
        $text
    );
}

function search(string $text): array
{
    if (!$text) {
        return ['error' => _('You must enter a search word.')];
    }

    $sider = db()->fetchArray("SELECT id, navn, MATCH(navn, text, beskrivelse) AGAINST ('".$text."') AS score FROM sider WHERE MATCH (navn, text, beskrivelse) AGAINST('".$text."') > 0 ORDER BY `score` DESC");

    //fulltext search dosn't catch things like 3 letter words and some other combos
    $qsearch = ['/\s+/u', "/'/u", '/´/u', '/`/u'];
    $qreplace = ['%', '_', '_', '_'];
    $simpleq = preg_replace($qsearch, $qreplace, $text);
    $sidersimple = db()->fetchArray("SELECT id, navn FROM `sider` WHERE (`navn` LIKE '%".$simpleq."%' OR `text` LIKE '%".$simpleq."%' OR `beskrivelse` LIKE '%".$simpleq."%')");

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

    $html = '<div id="headline">Søgning</div><div><div><span style="margin-left: 16px;"><img src="images/folder.png" width="16" height="16" alt="" /> &quot;'.$text.'&quot;</span><div style="margin-left:16px">';
    foreach ($sider as $value) {
        $html .= '<div class="side'.$value['id'].'"><a style="margin-left:16px" class="side" href="?side=redigerside&amp;id='.$value['id'].'"><img src="images/page.png" width="16" height="16" alt="" /> '.$value['navn'].'</a></div>';
    }
    $html .= '</div></div></div>';

    return ['id' => 'canvas', 'html' => $html];
}

function redigerkat(int $id): string
{
    if ($id) {
        $kat = db()->fetchOne("SELECT * FROM `kat` WHERE id = " . $id);
    }

    $html = '<div id="headline">'.('Rediger kategori').'</div><form action="" onsubmit="return updateKat('.$id.')"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />
    <div>'._('Name:').' <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" src="';

    if (empty($kat['icon'])) {
        $html .= 'images/folder.png';
    } else {
        $html .= $kat['icon'];
    }

    $html .= '" title="" alt="Billeder" id="iconthb" /> <input id="navn" style="width:256px;" maxlength="64" value="'. $kat['navn'] . '" /> <br /> '._('Icon:').' <input id="icon" style="width:247px;" maxlength="128" type="hidden" value="'.$kat['icon'].'" /> <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" width="16" height="16" src="images/folder_image.png" title="'._('Find pictures').'" alt="'._('Pictures').'" /> <img style="cursor:pointer;vertical-align:bottom" onclick="setThb(\'icon\',\'\',\'images/folder.png\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" height="16" width="16" /><br /><br />';

    if ($subkats = db()->fetchArray('SELECT id, navn, icon FROM `kat` WHERE bind = '.$id.' ORDER BY `order`, `navn`')) {
        $html .= _('Sort subcategories:').'<select id="custom_sort_subs" onchange="displaySubMenus(this.value);" onblur="displaySubMenus(this.value);"><option value="0">'._('Alphabetically').'</option><option value="1"';
        if ($kat['custom_sort_subs']) {
            $html .= ' selected="selected"';
        }
        $html .= '>'._('Manually').'</option></select><br /><ul id="subMenus" style="width:' . Config::get('text_width') . 'px;';
        if (!$kat['custom_sort_subs']) {
            $html .= 'display:none;';
        }
        $html .= '">';

        foreach ($subkats as $value) {
            $html .= '<li id="item_'.$value['id'].'"><img src="';
            if ($value['icon']) {
                $html .= $value['icon'];
            } else {
                $html .= 'images/folder.png';
            }
            $html .= '" alt=""> '.$value['navn'].'</li>';
        }

        $html .= '</ul><input type="hidden" id="subMenusOrder" value="" /><script type="text/javascript"><!--
Sortable.create(\'subMenus\',{ghosting:false,constraint:false,hoverclass:\'over\',
onChange:function(element){
var newOrder = Sortable.serialize(element.parentNode);
newOrder = newOrder.replace(/subMenus\\[\\]=/g,"");
newOrder = newOrder.replace(/&/g,",");
$(\'subMenusOrder\').value = newOrder;
}
});
var newOrder = Sortable.serialize($(\'subMenus\'));
newOrder = newOrder.replace(/subMenus\\[\\]=/g,"");
newOrder = newOrder.replace(/&/g,",");
$(\'subMenusOrder\').value = newOrder;
--></script>';
    } else {
        $html .= '<input type="hidden" id="subMenusOrder" /><input type="hidden" id="custom_sort_subs" />';
    }

    //Email
    $html .= _('Contact:').' <select id="email">';
    foreach (Config::get('emails', []) as $value => $dummy) {
        $html .= '<option value="'.$value.'"';
        if ($kat['email'] == $value) {
            $html .= ' selected="selected"';
        }
        $html .= '>'.$value.'</option>';
    }
    $html .= '</select>';

    //Visning
    $html .= '<br />'._('Display:').' <select id="vis"><option value="0"';
    if ($kat['vis'] == 0) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('Hide').'</option><option value="1"';
    if ($kat['vis'] == 1) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('Gallery').'</option><option value="2"';
    if ($kat['vis'] == 2) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('List').'</option></select>';

    //Binding
    //TODO init error, vælger fra cookie i stedet for $kat['bind']
    $kat = $kat['bind'];
    $html .= katlist($kat);

    $html .= '<br /></div><p style="display:none;"></p></form>';
    return $html;
}

function redigerside(int $id): string
{
    if ($id) {
        $page = db()->fetchOne("SELECT * FROM `sider` WHERE id = " . $id);
    }
    if (!$page) {
        return '<div id="headline">'._('The page does not exist').'</div>';
    }

    $html = '<div id="headline">'._('Edit page #').$id.'</div><form action="" method="post" onsubmit="return updateSide('.$id.');"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><div><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
//--></script><input type="hidden" name="id" id="id" value="'.$id.'" /><input class="admin_name" type="text" name="navn" id="navn" value="'.xhtmlEsc($page['navn']).'" maxlength="127" size="127" style="width:' . Config::get('text_width') . 'px" /><script type="text/javascript"><!--
writeRichText("text", \'' . rtefsafe($page['text']) . '\', "", ' . (Config::get('text_width') + 32) . ', 420, true, false, false);
//--></script>';
    $html .= _('Search word (separate search words with a comma \'Emergency Blanket, Emergency Blanket\'):').'<br /><textarea name="keywords" id="keywords" style="width:' . Config::get('text_width') . 'px;max-width:' . Config::get('text_width') . 'px" rows="2" cols="">'.xhtmlEsc($page['keywords']).'</textarea>';
    //Beskrivelse start
    $html .= '<div class="toolbox"><a class="menuboxheader" id="beskrivelseboxheader" style="width:'.(Config::get('thumb_width') + 14).'px" onclick="showhide(\'beskrivelsebox\',this);">'._('Description:').' </a><div style="text-align:center;width:'.(Config::get('thumb_width') + 34).'px" id="beskrivelsebox"><br /><input type="hidden" value="';
    if ($page['billed']) {
        $html .= $page['billed'];
    } else {
        $html .= _('/images/web/intet-foto.jpg');
    }
    $html .= '" id="billed" name="billed" /><img id="billedthb" src="';

    if ($page['billed']) {
        $html .= $page['billed'];
    } else {
        $html .= _('/images/web/intet-foto.jpg');
    }
    $html .= '" alt="" onclick="explorer(\'thb\', \'billed\')" /><br /><img onclick="explorer(\'thb\', \'billed\')" src="images/folder_image.png" width="16" height="16" alt="'._('Pictures').'" title="'._('Find image').'" /><a onclick="setThb(\'billed\',\'\',\''._('/images/web/intet-foto.jpg').'\')"><img src="images/cross.png" alt="X" title="'._('Remove picture').'" width="16" height="16" /></a>';
    $html .= '<script type="text/javascript"><!--
writeRichText("beskrivelse", \''.rtefsafe($page['beskrivelse']).'\', "", '.(Config::get('thumb_width') + 32).', 115, false, false, false);
//--></script>';
    $html .= '</div></div>';
    //Beskrivelse end
    //Pris start
    $html .= '<div class="toolbox"><a class="menuboxheader" id="priserheader" style="width:230px" onclick="showhide(\'priser\',this);">'._('Price:').' </a><div style="width:250px;" id="priser"><table style="width:100%"><tr><td><select name="burde" id="burde">
    <option value="0"';
    if ($page['burde'] == 0) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('Before').'</option>
    <option value="1"';
    if ($page['burde'] == 1) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('Indicative price').'</option>
    <option value="2"';
    if ($page['burde'] == 2) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('Should cost').'</option>
    </select></td><td style="text-align:right"><input class="XPris" onkeypress="return checkForInt(event)" onchange="prisHighlight()" value="'.$page['for'].'" name="for" id="for" size="11" maxlength="11" style="width:100px;text-align:right" />,-</td></tr>';
    $html .= '<tr><td><select name="fra" id="fra">
    <option value="0"';
    if ($page['fra'] == 0) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('Price').'</option>
    <option value="1"';
    if ($page['fra'] == 1) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('From').'</option>
    <option value="2"';
    if ($page['fra'] == 2) {
        $html .= ' selected="selected"';
    }
    $html .= '>'._('Used').'</option></select></td><td style="text-align:right"><input value="'.$page['pris'].'" class="';
    if ($page['for']) {
        $html .= 'NyPris';
    } else {
        $html .= 'Pris';
    }
    $html .= '" name="pris" id="pris" size="11" maxlength="11" style="width:100px;text-align:right" onkeypress="return checkForInt(event)" onchange="prisHighlight()" />,-';
    $html .= '</td></tr></table></div></div>';
    //Pris end
    //misc start
    $html .= '<div class="toolbox"><a class="menuboxheader" id="miscboxheader" style="width:201px" onclick="showhide(\'miscbox\',this);">'._('Other:').' </a><div style="width:221px" id="miscbox">'._('SKU:').' <input type="text" name="varenr" id="varenr" maxlength="63" style="text-align:right;width:128px" value="'.xhtmlEsc($page['varenr']).'" /><br /><img src="images/page_white_key.png" width="16" height="16" alt="" /><select id="krav" name="krav"><option value="0">'._('None').'</option>';
    $kravs = db()->fetchArray('SELECT id, navn FROM `krav` ORDER BY navn');
    foreach ($kravs as $krav) {
        $html .= '<option value="'.$krav['id'].'"';
        if ($page['krav'] == $krav['id']) {
            $html .= ' selected="selected"';
        }
        $html .= '>'.xhtmlEsc($krav['navn']).'</option>';
    }
    $html .= '</select><br /><img width="16" height="16" alt="" src="images/page_white_medal.png"/><select id="maerke" name="maerke" size="15"><option' . (!$page['maerke'] ? ' selected="selected"' : '') . ' value="0">'._('All others').'</option>';

    $maerker = db()->fetchArray('SELECT id, navn FROM `maerke` ORDER BY navn');
    foreach ($maerker as $maerke) {
        $html .= '<option value="' . $maerke['id'].'"';
        if ($maerke['id'] == $page['maerke']) {
            $html .= ' selected="selected"';
        }
        $html .= '>'.xhtmlEsc($maerke['navn']).'</option>';
    }
    $html .= '</select></div></div>';
    //misc end
    //list start
    $html .= '<div class="toolbox"><a class="menuboxheader" id="listboxheader" style="width:'.(Config::get('text_width') - 20 + 32).'px" onclick="showhide(\'listbox\',this);">'._('Lists:').' </a><div style="width:'.(Config::get('text_width') + 32).'px" id="listbox">';
    $lists = db()->fetchArray('SELECT * FROM `lists` WHERE page_id = ' . $id);
    $firstRow = reset($lists);
    $options = [];
    foreach ($lists as $list) {
        $html .= '<table>';

        $list['cells'] = explode('<', $list['cells']);
        $list['cell_names'] = explode('<', $list['cell_names']);
        $list['sorts'] = explode('<', $list['sorts']);


        $html .= '<thead><tr>';
        foreach ($list['cell_names'] as $name) {
            $html .= '<td>'.$name.'</td>';
        }
        if ($list['link']) {
            $html .= '<td><img src="images/link.png" alt="'._('Link').'" title="" width="16" height="16" /></td>';
        }
        $html .= '<td style="width:32px;"></td>';
        $html .= '</tr></thead><tfoot><tr id="list'.$list['id'].'footer">';
        foreach ($list['cells'] as $key => $type) {
            if ($list['sorts'][$key] == 0) {
                if ($type != 0) {
                    $html .= '<td><input style="display:none;text-align:right;" /></td>';
                } else {
                    $html .= '<td><input style="display:none;" /></td>';
                }
            } else {
                if (empty($options[$list['sorts'][$key]])) {
                    $temp = db()->fetchOne("SELECT `text` FROM `tablesort` WHERE id = " . $list['sorts'][$key]);
                    $options[$list['sorts'][$key]] = explode('<', $temp['text']);
                }

                $html .= '<td><select style="display:none;"><option value=""></option>';
                foreach ($options[$list['sorts'][$key]] as $option) {
                    $html .= '<option value="'.$option.'">'.$option.'</option>';
                }
                $html .= '</select></td>';
            }
        }
        if ($list['link']) {
            $html .= '<td><input style="display:none;text-align:right;" /></td>';
        }
        $html .= '<td><img onclick="listInsertRow('.$list['id'].');" src="images/disk.png" alt="'._('Edit').'" title="'._('Edit').'" width="16" height="16" /></td>';
        $html .= '</tr></tfoot>';
        $html .= '<tbody id="list'.$list['id'].'rows">';

        if ($rows = db()->fetchArray('SELECT * FROM `list_rows` WHERE list_id = '.$list['id'])) {
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
            if (empty($bycell) || $firstRow['sorts'][$bycell] < 1) {
                $rows = arrayNatsort($rows, 'id', $firstRow['sort']);
            } else {
                $rows = arrayListsort($rows, 'id', $firstRow['sort'], $firstRow['sorts'][$firstRow['sort']]);
            }

            foreach ($rows as $i => $row) {
                if ($i % 2) {
                    $html .= '<tr id="list_row'.$row['id'].'" class="altrow">';
                } else {
                    $html .= '<tr id="list_row'.$row['id'].'">';
                }
                foreach ($list['cells'] as $key => $type) {
                    if ($list['sorts'][$key] == 0) {
                        if ($type != 0) {
                            $html .= '<td style="text-align:right;"><input value="'.$row[$key].'" style="display:none;text-align:right;" /><span>'.$row[$key].'</span></td>';
                        } else {
                            $html .= '<td><input value="'.$row[$key].'" style="display:none;" /><span>'.$row[$key].'</span></td>';
                        }
                    } else {
                        if (empty($options[$list['sorts'][$key]])) {
                            $temp = db()->fetchOne("SELECT `text` FROM `tablesort` WHERE id = " . $list['sorts'][$key]);
                            $options[$list['sorts'][$key]] = explode('<', $temp['text']);
                        }

                        $html .= '<td><select style="display:none"><option value=""></option>';
                        foreach ($options[$list['sorts'][$key]] as $option) {
                            $html .= '<option value="'.$option.'"';
                            if ($row[$key] == $option) {
                                $html .= ' selected="selected"';
                            }
                            $html .= '>'.$option.'</option>';
                        }
                        $html .= '</select><span>'.$row[$key].'</span></td>';
                    }
                }
                if ($list['link']) {
                    $html .= '<td style="text-align:right;"><input value="'.$row['link'].'" style="display:none;text-align:right;" /><span>'.$row['link'].'</span></td>';
                }
                //TODO change to right click
                $html .= '<td><img onclick="listEditRow('.$list['id'].', '.$row['id'].');" src="images/application_edit.png" alt="'._('Edit').'" title="'._('Edit').'" width="16" height="16" /><img onclick="listUpdateRow('.$list['id'].', '.$row['id'].');" style="display:none" src="images/disk.png" alt="'._('Edit').'" title="'._('Edit').'" width="16" height="16" /><img src="images/cross.png" alt="X" title="'._('Delete row').'" onclick="listRemoveRow('.$list['id'].', '.$row['id'].')" /></td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table><script type="text/javascript"><!--

Event.observe(window, \'load\', function() { listSizeFooter('.$list['id'].'); });
listlink['.$list['id'].'] = '.$list['link'].';
--></script>';
    }
    $html .= '</div>';
    $html .= '<input type="button" onclick="window.open(\'addlist.php?id='.$id.'\', \'addlist\',\'status=1,resizable=1,toolbar=0,menubar=0,location=0,scrollbars=0,height=250\');" value="'._('Add list').'"></div>';
    //list end

    $html .= '</div></form>';

    //bind start
        $html .= '<form action="" method="post" onsubmit="return bind('.$id.');">
    <div class="toolbox"><a class="menuboxheader" id="bindingheader" style="width:593px;" onclick="showhide(\'binding\',this);">Bindinger: </a><div style="width:613pxpx;" id="binding"><div id="bindinger"><br />';
    $binds = db()->fetchArray('SELECT id, kat FROM `bind` WHERE `side` = '.$id);
    $kattree = [];
    foreach ($binds as $bind) {
        if ($bind['id'] != -1) {
            $kattree_html = '';
            foreach (kattree($bind['kat']) as $kattree) {
                $kattree_html .= '/'.trim($kattree['navn']);
            }
            $kattree_html .= '/';

            $html .= '<p id="bind'.$bind['id'].'"> <img onclick="slet(\'bind\', \''.addslashes($kattree_html).'\', '.$bind['id'].')" src="images/cross.png" alt="X" title="'._('Remove binding').'" width="16" height="16" /> ';
            $html .= $kattree_html.'</p>';
        }
    }
    $html .= '</div>';

    if (@$_COOKIE['activekat'] >= -1) {
        $html .= katlist(@$_COOKIE['activekat']);
    } else {
        $html .= katlist(-1);
    }
    $html .= '<br /><input type="submit" value="'._('Create binding').'" accesskey="b" />';

    $html .= '</div></div></form>';
    //bind end

    //tilbehor start
    $html .= '<form action="" method="post" onsubmit="return tilbehor('.$id.');">
<div class="toolbox"><a class="menuboxheader" id="tilbehorsheader" style="width:593px;" onclick="showhide(\'tilbehor\',this);">'._('Accessories:').' </a><div style="width:613pxpx;" id="tilbehor"><div id="tilbehore"><br />';
    $tilbehors = db()->fetchArray('SELECT id, tilbehor FROM `tilbehor` WHERE `side` = '.$id);
    foreach ($tilbehors as $tilbehor) {
        if ($tilbehor['id'] != null && $tilbehor['id'] != -1) {
            $kattree_html = '';
            foreach (kattree($tilbehor['kat']) as $kattree) {
                $kattree_html .= '/'.trim($kattree['navn']);
            }
            $kattree_html .= '/';

            $html .= '<p id="tilbehor'.$tilbehor['id'].'"> <img onclick="slet(\'tilbehor\', \''.addslashes($kattree_html).'\', '.$tilbehor['id'].')" src="images/cross.png" alt="X" title="'._('Remove binding').'" width="16" height="16" /> ';
            $html .= $kattree_html.'</p>';
        }
    }
    $html .= '</div>';
    $html .= '<div><iframe src="pagelist.php" width="100%" height="300"></iframe></div>';

    $html .= '<br /><input type="submit" value="'._('Add accessories').'" accesskey="a" />';

    $html .= '</div></div></form>';
    //tilbehor end

    return $html;
}

function listRemoveRow(int $list_id, int $row_id): array
{
    db()->query("DELETE FROM `list_rows` WHERE `id` = " . $row_id);

    return ['listid' => $list_id, 'rowid' => $row_id];
}

function listSavetRow(int $list_id, string $cells, string $link, int $row_id): array
{
    if (!$row_id) {
        db()->query('INSERT INTO `list_rows`(`list_id`, `cells`, `link`) VALUES ('.$list_id.', \''.addcslashes($cells, "'\\").'\', \''.$link.'\')');
        $row_id = db()->insert_id;
    } else {
        db()->query('UPDATE `list_rows` SET `list_id` = \''.$list_id.'\', `cells` = \''.addcslashes($cells, "'\\").'\', `link` = \''.$link.'\' WHERE id = '.$row_id);
    }

    return ['listid' => $list_id, 'rowid' => $row_id];
}

function redigerFrontpage(): string
{
    $customPage = ORM::getOne(CustomPage::class, 1);
    if (!$customPage) {
        return '<div id="headline">'._('The page does not exist').'</div>';
    }

    $html = '';
    $html .= '<div id="headline">'._('Edit frontpage').'</div><form action="" method="post" onsubmit="return updateForside();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />';

    $subkats = db()->fetchArray('SELECT id, navn, icon FROM `kat` WHERE bind = 0 ORDER BY `order`, `navn`');

    $html .= _('Sort maincategories:');
    $html .= '<ul id="subMenus" style="width:' . Config::get('text_width') .'px;">';

    foreach ($subkats as $value) {
        $html .= '<li id="item_'.$value['id'].'"><img src="';
        if ($value['icon']) {
            $html .= $value['icon'];
        } else {
            $html .= 'images/folder.png';
        }
        $html .= '" alt=""> '.$value['navn'].'</li>';
    }

    $html .= '</ul><input type="hidden" id="subMenusOrder" /><script type="text/javascript"><!--
Sortable.create(\'subMenus\',{ghosting:false,constraint:false,hoverclass:\'over\',
onChange:function(element){
var newOrder = Sortable.serialize(element.parentNode);
newOrder = newOrder.replace(/subMenus\\[\\]=/g,"");
newOrder = newOrder.replace(/&/g,",");
$(\'subMenusOrder\').value = newOrder;
}
});
var newOrder = Sortable.serialize($(\'subMenus\'));
newOrder = newOrder.replace(/subMenus\\[\\]=/g,"");
newOrder = newOrder.replace(/&/g,",");
$(\'subMenusOrder\').value = newOrder;
--></script><br />';

    $html .= '<script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", \''.rtefsafe($customPage->getHtml()).'\', "", '.(Config::get('frontpage_width') + 32).', 572, true, false, false);
//--></script></form>';

    return $html;
}

function redigerSpecial(int $id): string
{
    $customPage = ORM::getOne(CustomPage::class, $id);
    if (!$customPage) {
        return '<div id="headline">' . _('The page does not exist') . '</div>';
    }

    return '<div id="headline">' . sprintf(_('Edit %s'), $customPage->getTitle())
        . '</div><form action="" method="post" onsubmit="return updateSpecial(' . $customPage->getId()
        . ');"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />'
        . '<input type="hidden" id="id" /><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", \''
        . rtefsafe($customPage->getHtml()) . '\', "", ' . (Config::get('text_width') + 32)
        . ', 572, true, false, false);
//--></script></form>';
}

function getnykrav()
{
    $html = '<div id="headline">'._('Create new requirement').'</div><form action="" method="post" onsubmit="return savekrav();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><input type="hidden" name="id" id="id" value="" /><input class="admin_name" type="text" name="navn" id="navn" value="" maxlength="127" size="127" style="width:' . Config::get('text_width') . 'px" /><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", "", "", ' . Config::get('text_width') . ', 420, true, false, false);
//--></script></form>';

    return $html;
}

function listsort(int $id = null): string
{
    if ($id) {
        $liste = db()->fetchOne("SELECT * FROM `tablesort` WHERE `id` = " . $id);

        $html = '<div id="headline">'.sprintf(_('Edit %s sorting'), $liste['navn']) . '</div><div>';

        $html .= _('Name:').' <input id="listOrderNavn" value="' . $liste['navn'] . '"><form action="" method="post" onsubmit="addNewItem(); return false;">'._('New Item:').' <input id="newItem"> <input type="submit" value="tilføj" accesskey="t"></form>';

        $html .= '<ul id="listOrder" style="width:' . Config::get('text_width') . 'px;">';
        $liste['text'] = explode('<', $liste['text']);

        foreach ($liste['text'] as $key => $value) {
            $html .= '<li id="item_'.$key.'">'.$value.'</li>';
        }

        $html .= '</ul><input type="hidden" id="listOrderValue" value="" /><script type="text/javascript"><!--
var items = ' . count($liste['text']) . ';
Sortable.create(\'listOrder\',{ghosting:false,constraint:false,hoverclass:\'over\'});
--></script></div>';
    } else {
        $html = '<div id="headline">'._('List sorting').'</div><div>';
        $html .= '<a href="#" onclick="makeNewList(); return false;">'._('Create new sorting').'</a><br /><br />';

        $lists = db()->fetchArray('SELECT id, navn FROM `tablesort`');

        foreach ($lists as $value) {
            $html .= '<a href="?side=listsort&amp;id='.$value['id'].'"><img src="images/shape_align_left.png" width="16" height="16" alt="" /> '.$value['navn'].'</a><br />';
        }
        $html .= '</div>';
    }

    return $html;
}

function getaddressbook(): string
{
    $addresses = db()->fetchArray('SELECT * FROM `email` ORDER BY `navn`');

    $html = '<div id="headline">'._('Address Book').'</div><div>';
    $html .= '<table id="addressbook"><thead><tr><td></td><td>'._('Name').'</td><td>'._('E-mail').'</td><td>'._('Phone').'</td></tr></thead><tbody>';

    foreach ($addresses as $i => $addres) {
        if (!$addres['tlf1'] && $addres['tlf2']) {
            $addres['tlf1'] = $addres['tlf2'];
        }

        $html .= '<tr id="contact'.$addres['id'].'"';

        if ($i % 2) {
            $html .= ' class="altrow"';
        }

        $html .= '><td><a href="?side=editContact&id='.$addres['id'].'"><img width="16" height="16" src="images/vcard_edit.png" alt="R" title="'._('Edit').'" /></a><img onclick="x_deleteContact('.$addres['id'].', removeTagById)" width="16" height="16" src="images/cross.png" alt="X" title="'._('Delete').'" /></td>';
        $html .= '<td>'.$addres['navn'].'</td><td>'.$addres['email'].'</td><td>'.$addres['tlf1'].'</td></tr>';
    }

    $html .= '</tbody></table></div>';

    return $html;
}

function editContact(int $id): string
{
    $address = db()->fetchOne('SELECT * FROM `email` WHERE `id` = ' . $id);

    $html = '<div id="headline">' ._('Edit contact person') .'</div>';
    $html .= '<form method="post" action="" onsubmit="updateContact(' .$id .'); return false;"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><table border="0" cellspacing="0"><tbody><tr><td>'._('Name:').'</td><td colspan="2"><input value="'.
    $address['navn'].'" id="navn" /></td></tr><tr><td>'._('E-mail:').'</td><td colspan="2"><input value="'.
    $address['email'].'" id="email" /></td></tr><tr><td>'._('Address:').'</td><td colspan="2"><input value="'.
    $address['adresse'].'" id="adresse" /></td></tr><tr><td>'._('Country:').'</td><td colspan="2"><input value="'.
    $address['land'].'" id="land" /></td></tr><tr><td width="1%">'._('Postal Code:').'</td><td width="1%"><input maxlength="8" size="8" id="post" value="'.
    $address['post'].'" /></td><td align="left" nowrap="nowrap">'._('City:').'<input size="8" id="by" value="'.
    $address['by'].'" /></td></tr><tr><td nowrap="nowrap">'._('Private phone:').'</td><td colspan="2"><input maxlength="11" size="15" id="tlf1" value="'.
    $address['tlf1'].'" /></td></tr><tr><td nowrap="nowrap">'._('Mobile phone:').'</td><td colspan="2"><input maxlength="11" size="15" id="tlf2" value="'.
    $address['tlf2'].'" /></td></tr><tr><td colspan="5"><br /><label for="kartotek"><input value="1" id="kartotek" type="checkbox"';
    if ($address['kartotek']) {
        $html .= ' checked="checked"';
    }
    $html .= ' />'._('Receive newsletters.').'</label><br />
    <strong>'._('Interests:').'</strong>';
    $html .= '<div id="interests">';
    $address['interests_array'] = explode('<', $address['interests']);
    foreach (Config::get('interests', []) as $interest) {
        $html .= '<label for="'.$interest.'"><input';
        if (false !== array_search($interest, $address['interests_array'])) {
            $html .= ' checked="checked"';
        }
        $html .= ' type="checkbox" value="'.$interest.'" id="'.$interest.'" /> '.$interest.'</label> ';
    }
    $html .= '</div></td></tr></tbody></table></form>';

    return $html;
}

function updateContact(int $id, string $navn, string $email, string $adresse, string $land, string $post, string $by, string $tlf1, string $tlf2, string $kartotek, string $interests): bool
{
    db()->query("UPDATE `email` SET `navn` = '".$navn."', `email` = '".$email."', `adresse` = '".$adresse."', `land` = '".$land."', `post` = '".$post."', `by` = '".$by."', `tlf1` = '".$tlf1."', `tlf2` = '".$tlf2."', `kartotek` = '".$kartotek."', `interests` = '".$interests."' WHERE id = ".$id);
    return true;
}

function deleteContact(int $id): string
{
    db()->query('DELETE FROM `email` WHERE `id` = '.$id);
    return 'contact'.$id;
}

function makeNewList(string $navn): array
{
    db()->query('INSERT INTO `tablesort` (`navn`) VALUES (\''.$navn.'\')');
    return ['id' => db()->insert_id, 'name' => $navn];
}

function saveListOrder(int $id, string $navn, string $text): bool
{
    db()->query('UPDATE `tablesort` SET navn = \''.$navn.'\', text = \''.$text.'\' WHERE id = '.$id);
    return true;
}

function get_db_error(): string
{
    $html = '<div id="headline">'._('Maintenance').'</div><div>
    <div>';
        $html .= '<script type=""><!--
        function set_db_errors(result)
        {
            if (result != \'\')
                $(\'errors\').innerHTML = $(\'errors\').innerHTML+result;
        }

        function scan_db()
        {
            $(\'loading\').style.visibility = \'\';
            $(\'errors\').innerHTML = \'\';

            var starttime = new Date().getTime();

            $(\'status\').innerHTML = \''._('Removing news subscribers without contact information').'\';
            x_removeBadSubmisions(set_db_errors);

            $(\'status\').innerHTML = \''._('Removing bindings to pages that do not exist').'\';
            x_removeBadBindings(set_db_errors);

            $(\'status\').innerHTML = \''._('Removing accessories that do not exist').'\';
            x_removeBadAccessories(set_db_errors);

            $(\'status\').innerHTML = \''._('Searching for pages without bindings').'\';
            x_get_orphan_pages(set_db_errors);

            $(\'status\').innerHTML = \''._('Searching for pages with illegal bindings').'\';
            x_get_pages_with_mismatch_bindings(set_db_errors);

            $(\'status\').innerHTML = \''._('Searching for orphaned lists').'\';
            x_get_orphan_lists(set_db_errors);

            $(\'status\').innerHTML = \''._('Searching for orphaned rows').'\';
            x_get_orphan_rows(set_db_errors);

            $(\'status\').innerHTML = \''._('Searching for orphaned categories').'\';
            x_get_orphan_cats(set_db_errors);

            $(\'status\').innerHTML = \''._('Searching for cirkalur linked categories').'\';
            x_get_looping_cats(set_db_errors);

            $(\'status\').innerHTML = \''._('Checking the file names').'\';
            x_check_file_names(set_db_errors);

            $(\'status\').innerHTML = \''._('Checking the folder names').'\';
            x_check_file_paths(set_db_errors);

            $(\'status\').innerHTML = \''._('Deleting temporary files').'\';
            x_deleteTempfiles(set_db_errors);

            $(\'status\').innerHTML = \''._('Retrieving the size of the files').'\';
            x_get_size_of_files(function(){});

            $(\'status\').innerHTML = \''._('Optimizing the database').'\';
            x_optimizeTables(set_db_errors);

            $(\'status\').innerHTML = \''._('Sending delayed emails').'\';
            x_sendDelayedEmail(set_db_errors);

            $(\'status\').innerHTML = \'\';
            $(\'loading\').style.visibility = \'hidden\';
            $(\'errors\').innerHTML = $(\'errors\').innerHTML+\'<br />\'+(\''._('The scan took %d seconds.').'\'.replace(/[%]d/g, Math.round((new Date().getTime()-starttime)/1000).toString()));
        }

        function get_subscriptions_with_bad_emails()
        {
            $(\'loading\').style.visibility = \'\';
            $(\'errors\').innerHTML = \'\';

            var starttime = new Date().getTime();

            $(\'status\').innerHTML = \''._('Searching for illegal e-mail adresses').'\';
            x_get_subscriptions_with_bad_emails(set_db_errors);

            $(\'status\').innerHTML = \'\';
            $(\'loading\').style.visibility = \'hidden\';
            $(\'errors\').innerHTML = $(\'errors\').innerHTML+\'<br />\'+(\''._('The scan took %d seconds.').'\'.replace(/[%]d/g, Math.round((new Date().getTime()-starttime)/1000).toString()));
        }

        function removeNoneExistingFiles()
        {
            $(\'loading\').style.visibility = \'\';
            $(\'status\').innerHTML = \''._('Removes not existing files from the database').'\';
            x_removeNoneExistingFiles(function (dummy) {});
            $(\'status\').innerHTML = \''._('Getting Database Size').'\';
            x_get_db_size(get_db_size_r);
            $(\'status\').innerHTML = \'\';
            $(\'loading\').style.visibility = \'hidden\';
        }

        function get_mail_size_r(size)
        {
            $(\'mailboxsize\').innerHTML = Math.round(size/1024/1024)+\''._('MB').'\';
            $(\'status\').innerHTML = \'\';
            $(\'loading\').style.visibility = \'hidden\';
        }

        function get_db_size_r(size)
        {
            $(\'dbsize\').innerHTML = Math.round(size/1024/1024)+\''._('MB').'\';
        }

        --></script><div><b>'._('Server consumption').'</b> - '._('E-mail:').' <span id="mailboxsize"><button onclick="$(\'loading\').style.visibility = \'\'; x_get_mail_size(get_mail_size_r);">'._('Get e-mail consumption').'</button></span> '._('DB:').' <span id="dbsize">'.number_format(get_db_size(), 1, ',', '')._('MB').'</span> '._('WWW').': <span id="wwwsize">'.number_format(get_size_of_files(), 1, ',', '')._('MB').'</span></div><div id="status"></div><button onclick="scan_db();">'._('Scan database').'</button> <button onclick="get_subscriptions_with_bad_emails();">'._('Check emails in the address book').'</button> <button onclick="removeNoneExistingFiles();">'._('Clean up files').'</button><div id="errors"></div>';

    $emailsCount = db()->fetchOne("SELECT count(*) as 'count' FROM `emails`");
    $emails = db()->fetchArray("SHOW TABLE STATUS LIKE 'emails'");
    $emails = reset($emails);

    return $html . '<div>' . sprintf(_('Delayed e-mails %d/%d'), $emailsCount['count'], $emails['Auto_increment'] - 1)
        . '</div><div>'
        . sprintf(_('Cron last run at %s'), date('d/m/Y', ORM::getOne(CustomPage::class, 0)->getTimestamp()))
        . '</div></div></div>';
}

function get_subscriptions_with_bad_emails(): string
{
    $html = '';
    $errors = 0;
    $emails = db()->fetchArray("SELECT `id`, `email` FROM `email` WHERE `email` != ''");
    foreach ($emails as $email) {
        if (!valideMail($email['email'])) {
            $html .= '<a href="?side=editContact&id='.$email['id'].'">'.sprintf(_('E-mail: %s #%d is not valid'), $email['email'], $email['id']).'</a><br />';
        }
    }
    if ($html) {
        $html = '<b>'._('The following e-mail addresses are not valid').'</b><br />'.$html;
    }
    return $html;
}

function get_orphan_rows(): string
{
    $html = '';
    $error = db()->fetchArray('SELECT * FROM `list_rows` WHERE list_id NOT IN (SELECT id FROM lists);');
    if ($error) {
        $html .= '<br /><b>'._('The following rows have no lists:').'</b><br />';
        foreach ($error as $value) {
            $html .= $value['id'].': '.$value['cells'].' '.$value['link'].'<br />';
        }
    }
    if ($html) {
        $html = '<b>'._('The following pages have no binding').'</b><br />'.$html;
    }
    return $html;
}

function get_orphan_cats(): string
{
    $html = '';
    $error = db()->fetchArray('SELECT `id`, `navn` FROM `kat` WHERE `bind` != 0 AND `bind` != -1 AND `bind` NOT IN (SELECT `id` FROM `kat`);');
    if ($error) {
        $html .= '<br /><b>'._('The following categories are orphans:').'</b><br />';
        foreach ($error as $value) {
            $html .= '<a href="?side=redigerkat&id='.$value['id'].'">'.$value['id'].': '.$value['navn'].'</a><br />';
        }
    }
    if ($html) {
        $html = '<b>'._('The following categories have no binding').'</b><br />'.$html;
    }
    return $html;
}

function get_looping_cats(): string
{
    $error = db()->fetchArray("SELECT id, bind, navn FROM `kat` WHERE bind != 0 AND bind != -1");

    $html = '';
    $temp_html = '';
    foreach ($error as $kat) {
        $bindtree = kattree($kat['bind']);
        foreach ($bindtree as $bindbranch) {
            if ($kat['id'] == $bindbranch['id']) {
                $temp_html .= '<a href="?side=redigerkat&id='.$kat['id'].'">'.$kat['id'].': '.$kat['navn'].'</a><br />';
                continue;
            }
        }
    }
    if ($temp_html) {
        $html .= '<br /><b>'._('The following categories are tied in itself:').'</b><br />'.$temp_html;
    }
    if ($html) {
        $html = '<b>'._('The following categories are tied in itself:').'</b><br />'.$html;
    }
    return $html;
}

function check_file_names(): string
{
    $html = '';
    $error = db()->fetchArray('SELECT path FROM `files` WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+[^/]+$\' ORDER BY `path` ASC');
    if ($error) {
        if (db()->affected_rows > 1) {
            $html .= '<br /><b>'.sprintf(_('The following %d files must be renamed:'), db()->affected_rows).'</b><br /><a onclick="explorer(\'\',\'\');">';
        } else {
            $html .= '<br /><br /><a onclick="explorer(\'\',\'\');">';
        }
        foreach ($error as $value) {
            $html .= $value['path'].'<br />';
        }
        $html .= '</a>';
    }
    if ($html) {
        $html = '<b>'._('The following files must be renamed').'</b><br />'.$html;
    }
    return $html;
}

function check_file_paths(): string
{
    $html = '';
    $error = db()->fetchArray('SELECT path FROM `files` WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+.*[/]+\' ORDER BY `path` ASC');
    if ($error) {
        if (db()->affected_rows > 1) {
            $html .= '<br /><b>'.sprintf(_('The following %d files are in a folder that needs to be renamed:'), db()->affected_rows).'</b><br /><a onclick="explorer(\'\',\'\');">';
        } else {
            $html .= '<br /><br /><a onclick="explorer(\'\',\'\');">';
        }
        //TODO only repport one error per folder
        foreach ($error as $value) {
            $html .= $value['path'].'<br />';
        }
        $html .= '</a>';
    }
    if ($html) {
        $html = '<b>'._('The following folders must be renamed').'</b><br />'.$html;
    }
    return $html;
}

function get_size_of_files(): int
{
    $files = db()->fetchOne("SELECT count( * ) AS `count`, sum( `size` ) /1024 /1024 AS `filesize` FROM `files`");

    return $files['filesize'];
}

function get_mail_size(): int
{
    $mailboxes = [];

    $size = 0;

    foreach (Config::get('emails', []) as $email) {
        $imap = new AJenbo\Imap(
            $email['address'],
            $email['password'],
            $email['imapHost'],
            $email['imapPort']
        );

        foreach ($imap->listMailboxes() as $mailbox) {
            try {
                $mailboxStatus = $imap->select($mailbox['name'], true);
                if (!$mailboxStatus['exists']) {
                    continue;
                }

                $mails = $imap->fetch('1:*', 'RFC822.SIZE');
                preg_match_all('/RFC822.SIZE\s([0-9]+)/', $mails['data'], $mailSizes);
                $size += array_sum($mailSizes[1]);
            } catch (Exception $e) {
            }
        }
    }

    return $size;
}

//todo remove missing maerke from sider->maerke
/*
TODO test for missing alt="" in img under sider
preg_match_all('/<img[^>]+/?>/ui', $value, $matches);
*/


function get_orphan_lists(): string
{
    $error = db()->fetchArray('SELECT id FROM `lists` WHERE page_id NOT IN (SELECT id FROM sider);');
    $html = '';
    if ($error) {
        $html .= '<br /><b>'._('The following lists are orphans:').'</b><br />';
        foreach ($error as $value) {
            $html .= $value['id'].': '.$value['navn'].' '.$value['cell1'].' '.$value['cell2'].' '.$value['cell3'].' '.$value['cell4'].' '.$value['cell5'].' '.$value['cell6'].' '.$value['cell7'].' '.$value['cell8'].' '.$value['cell9'].' '.$value['img'].' '.$value['link'].'<br />';
        }
    }
    if ($html) {
        $html = '<b>'._('The following lists are not tied to any page').'</b><br />'.$html;
    }
    return $html;
}

function get_db_size(): float
{
    $tabels = db()->fetchArray("SHOW TABLE STATUS");
    $dbsize = 0;
    foreach ($tabels as $tabel) {
        $dbsize += $tabel['Data_length'];
        $dbsize += $tabel['Index_length'];
    }
    return $dbsize/1024/1024;
}

function get_orphan_pages(): string
{
    $html = '';
    $sider = db()->fetchArray("SELECT `id`, `navn`, `varenr` FROM `sider` WHERE `id` NOT IN(SELECT `side` FROM `bind`);");
    foreach ($sider as $side) {
        $html .= '<a href="?side=redigerside&amp;id='.$side['id'].'">'.$side['id'].': '.$side['navn'].'</a><br />';
    }

    if ($html) {
        $html = '<b>'._('The following pages have no binding').'</b><br />'.$html;
    }
    return $html;
}

function get_pages_with_mismatch_bindings(): string
{
    $html = '';

    // Map out active / inactive
    $categoryActiveMaps = [[0], [-1]];
    $categories = ORM::getByQuery(Category::class, "SELECT * FROM `kat`");
    foreach ($categories as $category) {
        $categoryActiveMaps[(int) $category->isInactive()][] = $category->getId();
    }

    $pages = ORM::getByQuery(
        Page::class,
        "
        SELECT * FROM `sider`
        WHERE EXISTS (
            SELECT * FROM bind
            WHERE side = sider.id
            AND kat IN (" . implode(",", $categoryActiveMaps[0]) . ")
        )
        AND EXISTS (
            SELECT * FROM bind
            WHERE side = sider.id
            AND kat IN (" . implode(",", $categoryActiveMaps[1]) . ")
        )
        ORDER BY id
        "
    );
    if ($pages) {
        $html .= '<b>'._('The following pages are both active and inactive').'</b><br />';
        foreach ($pages as $page) {
            $html .= '<a href="?side=redigerside&amp;id=' . $page->getId() . '">' . $page->getId() . ': ' . $page->getTitle() . '</a><br />';
        }
    }

    //Add active pages that has a list that links to this page
    $pages = db()->fetchArray(
        "
        SELECT `sider`.*, `lists`.`page_id`
        FROM `list_rows`
        JOIN `lists` ON `list_rows`.`list_id` = `lists`.`id`
        JOIN `sider` ON `list_rows`.`link` = `sider`.id
        WHERE EXISTS (
            SELECT * FROM bind
            WHERE side = `lists`.`page_id`
            AND kat IN (" . implode(",", $categoryActiveMaps[0]) . ")
        )
        AND EXISTS (
            SELECT * FROM bind
            WHERE side = sider.id
            AND kat IN (" . implode(",", $categoryActiveMaps[1]) . ")
        )
        ORDER BY `lists`.`page_id`
        "
    );
    if ($pages) {
        $html .= '<b>'._('The following inactive pages appears in list on active pages').'</b><br />';
        foreach ($pages as $page) {
            $listPage = ORM::getOne(Page::class, $page['page_id']);
            $page = new Page(Page::mapFromDB($page));
            $html .= '<a href="?side=redigerside&amp;id=' . $listPage->getId() . '">' . $listPage->getId() . ': ' . $listPage->getTitle() . '</a> -&gt; <a href="?side=redigerside&amp;id=' . $page->getId() . '">' . $page->getId() . ': ' . $page->getTitle() . '</a><br />';
        }
    }

    return $html;
}

function getnyside(): string
{
    $html = '<div id="headline">Opret ny side</div><form action="" method="post" onsubmit="return opretSide();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><div><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
//--></script><input type="hidden" name="id" id="id" value="" /><input class="admin_name" type="text" name="navn" id="navn" value="" maxlength="127" size="127" style="width:' . Config::get('text_width') . 'px" /><script type="text/javascript"><!--
writeRichText("text", \'\', "", ' . (Config::get('text_width') + 32) .', 420, true, false, false);
//--></script>';
    //Søge ord (separere søge ord med et komma "Emergency Blanket, Redningstæppe"):
    $html .= _('Search word (separate search words with a comma \'Emergency Blanket, Rescue Blanket\'):').'<br /><textarea name="keywords" id="keywords" style="width:' . Config::get('text_width') . 'px;max-width:' . Config::get('text_width') . 'px" rows="2" cols=""></textarea>';
    //Beskrivelse start
    $html .= '<div class="toolbox"><a class="menuboxheader" id="beskrivelseboxheader" style="width:' . (Config::get('thumb_width') + 14).'px" onclick="showhide(\'beskrivelsebox\',this);">'._('Description:').' </a><div style="text-align:center;width:' . (Config::get('thumb_width') + 34) . 'px" id="beskrivelsebox"><br /><input type="hidden" value="'._('/images/web/intet-foto.jpg').'" id="billed" name="billed" /><img id="billedthb" src="'._('/images/web/intet-foto.jpg').'" alt="" onclick="explorer(\'thb\', \'billed\')" /><br /><img onclick="explorer(\'thb\', \'billed\')" src="images/folder_image.png" width="16" height="16" alt="'._('Pictures').'" title="'._('Find image').'" /><img onclick="setThb(\'billed\', \'\', \''._('/images/web/intet-foto.jpg').'\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" width="16" height="16" /><script type="text/javascript"><!--
writeRichText("beskrivelse", \'\', "", ' . (Config::get('thumb_width') + 32) . ', 115, false, false, false);
//--></script></div></div>';
    //Beskrivelse end
    //Pris start
    $html .= '<div class="toolbox"><a class="menuboxheader" id="priserheader" style="width:230px" onclick="showhide(\'priser\',this);">'._('Price:').' </a><div style="width:250px;" id="priser"><table style="width:100%"><tr><td><select name="burde" id="burde"><option value="0">'._('Before').'</option><option value="1">'._('Indicative price').'</option></select></td><td style="text-align:right"><input class="XPris" onkeypress="return checkForInt(event)" onchange="prisHighlight()" value="" name="for" id="for" size="11" maxlength="11" style="width:100px;text-align:right" />,-</td></tr><tr><td><select name="fra" id="fra"><option value="0">'._('Price').'</option><option value="1">'._('From').'</option></select></td><td style="text-align:right"><input value="" class="Pris" name="pris" id="pris" size="11" maxlength="11" style="width:100px;text-align:right" onkeypress="return checkForInt(event)" onchange="prisHighlight()" />,-</td></tr></table></div></div>';
    //Pris end
    //misc start
    $html .= '<div class="toolbox"><a class="menuboxheader" id="miscboxheader" style="width:201px" onclick="showhide(\'miscbox\',this);">'._('Other:').' </a><div style="width:221px" id="miscbox">'._('SKU:').' <input type="text" name="varenr" id="varenr" maxlength="63" style="text-align:right;width:128px" value="" /><br /><img src="images/page_white_key.png" width="16" height="16" alt="" /><select id="krav" name="krav"><option value="0">'._('None').'</option>';
    $kravs = db()->fetchArray('SELECT id, navn FROM `krav`');
    foreach ($kravs as $krav) {
        $html .= '<option value="'.$krav['id'].'"';
        $html .= '>'.xhtmlEsc($krav['navn']).'</option>';
    }
    $html .= '</select><br /><img width="16" height="16" alt="" src="images/page_white_medal.png"/><select id="maerke" name="maerke" size="10"><option selected="selected" value="0">'._('All others').'</option>';
    $maerker = db()->fetchArray('SELECT id, navn FROM `maerke` ORDER BY navn');
    foreach ($maerker as $maerke) {
        $html .= '<option value="' . $maerke['id'] . '"';
        $html .= '>' . xhtmlEsc($maerke['navn']) . '</option>';
    }
    $html .= '</select></div></div></div>';
    //misc end
    //bind start
    if (@$_COOKIE['activekat'] >= -1) {
        $html .= katlist(@$_COOKIE['activekat']);
    } else {
        $html .= katlist(-1);
    }

    $html .= '</form>';
    return $html;
}

function getnykat(): array
{
    $html = '<div id="headline">'._('Create category').'</div><form action="" onsubmit="return save_ny_kat()"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><div>'._('Name:').' <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" src="images/folder.png" title="" alt="'._('Pictures').'" id="iconthb" /> <input id="navn" style="width:256px;" maxlength="64" /> <br /> '._('Icon:').' <input id="icon" style="width:247px;" maxlength="128" type="hidden" /> <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" width="16" height="16" src="images/folder_image.png" title="'._('Find pictures').'" alt="'._('Pictures').'" /> <img style="cursor:pointer;vertical-align:bottom" onclick="setThb(\'icon\',\'\',\'images/folder.png\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" height="16" width="16" /><br /><br />';

    //Email
    $html .= _('Contact:').' <select id="email">';
    foreach (Config::get('emails', []) as $email => $dummy) {
        $html .= '<option value="' . $email . '">' . $email . '</option>';
    }
    $html .= '</select>';

    //Visning
    $html .= '<br />'._('Display:').' <select id="vis"><option value="0">'._('Hide').'</option><option value="1" selected="selected">'._('Gallery').'</option><option value="2">'._('List').'</option></select>';

    //binding
    if (@$_COOKIE['activekat'] >= -1) {
        $html .= katlist(@$_COOKIE['activekat']);
    } else {
        $html .= katlist(-1);
    }

    $html .= '<br /></div></form>';
    return ['id' => 'canvas', 'html' => $html];
}

function save_ny_kat(string $navn, string $kat, string $icon, string $vis, string $email)
{
    if ($navn != '' && $kat != '') {
        db()->query('INSERT INTO `kat` (`navn`, `bind`, `icon`, `vis`, `email`) VALUES (\''.$navn.'\', \''.$kat.'\', \''.$icon.'\', \''.$vis.'\', \''.$email.'\')');

        //$html = "INSERT INTO `kat` (`navn`, `bind`, `icon` ) VALUES ('$navn', '$kat', '$icon')".'side funktion';
        return true;
    } else {
        return ['error' => _('You must enter a name and choose a location for the new category.')];
    }
}

function savekrav(int $id, string $navn, string $text): array
{
    $text = purifyHTML($text);
    $text = htmlUrlDecode($text);

    if ($navn != '' && $text != '') {
        if (!$id) {
            db()->query('INSERT INTO `krav` (`navn`, `text` ) VALUES (\''.addcslashes($navn, "'\\").'\', \''.addcslashes($text, "'\\").'\')');
        } else {
            db()->query('UPDATE krav SET navn = \''.addcslashes($navn, "'\\").'\', text = \''.addcslashes($text, "'\\").'\' WHERE id = '.$id);
        }

        return ['id' => 'canvas', 'html' => getkrav()];
    } else {
        return ['error' => _('You must enter a name and a text of the requirement.')];
    }
}

function getsogogerstat()
{
    echo '<div id="headline">'._('Find and replace').'</div><form onsubmit="sogogerstat(document.getElementById(\'sog\').value,document.getElementById(\'erstat\').value,inject_html); return false;"><img src="images/error.png" width="16" height="16" alt="" > '._('This function affects all pages.').'<table cellspacing="0"><tr><td>'._('Find:').' </td><td><input id="sog" style="width:256px;" maxlength="64" /></td></tr><tr><td>'._('Replace:').' </td><td><input id="erstat" style="width:256px;" maxlength="64" /></td></tr></table><br /><br /><input value="'._('Find and replace').'" type="submit" accesskey="r" /></form>';
}

function sogogerstat(string $sog, string $erstat): int
{
    db()->query('UPDATE sider SET text = REPLACE(text,\''.$sog.'\',\''.$erstat.'\')');

    return db()->affected_rows;
}

function getmaerker(): string
{
    $html = '<div id="headline">'._('List of brands').'</div><form action="" id="maerkerform" onsubmit="x_save_ny_maerke(document.getElementById(\'navn\').value,document.getElementById(\'link\').value,document.getElementById(\'ico\').value,inject_html); return false;"><table cellspacing="0"><tr style="height:21px"><td>'._('Name:').' </td><td><input id="navn" style="width:256px;" maxlength="64" /></td><td rowspan="4"><img id="icoimage" src="" style="display:none" alt="" /></td></tr><tr style="height:21px"><td>'._('Link:').' </td><td><input id="link" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>'._('Logo:').' </td>
    <td style="text-align:center"><input type="hidden" value="'._('/images/web/intet-foto.jpg').'" id="ico" name="ico" /><img id="icothb" src="'._('/images/web/intet-foto.jpg').'" alt="" onclick="explorer(\'thb\', \'ico\')" /><br /><img onclick="explorer(\'thb\', \'ico\')" src="images/folder_image.png" width="16" height="16" alt="'._('Pictures').'" title="'._('Find image').'" /><img onclick="setThb(\'ico\', \'\', \''._('/images/web/intet-foto.jpg').'\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" width="16" height="16" /></td>
    </tr><tr><td></td></tr></table><p><input value="'._('Add brand').'e" type="submit" accesskey="s" /><br /><br /></p><div id="imagelogo" style="display:none; position:absolute;"></div>';
    $brands = db()->fetchArray('SELECT * FROM `maerke` ORDER BY navn');
    foreach ($brands as $brand) {
        $html .= '<div id="maerke'.$brand['id'].'"><a href="" onclick="slet(\'maerke\',\''.addslashes($brand['navn']).'\','.$brand['id'].');"><img src="images/cross.png" alt="X" title="'._('Delete').' '.xhtmlEsc($brand['navn']).'!" width="16" height="16"';
        if (!$brand['link'] && !$brand['ico']) {
            $html .= ' style="margin-right:32px"';
        } elseif (!$brand['link']) {
            $html .= ' style="margin-right:16px"';
        }
        $html .= ' /></a><a href="?side=updatemaerke&amp;id='.$brand['id'].'">';
        if ($brand['link']) {
            $html .= '<img src="images/link.png" alt="W" width="16" height="16" title="'.xhtmlEsc($brand['link']).'"';
            if (!$brand['ico']) {
                $html .= ' style="margin-right:16px"';
            }
            $html .= ' />';
        }
        if ($brand['ico']) {
            $html .= '<img alt="icon" title="" src="images/picture.png" width="16" height="16" onmouseout="document.getElementById(\'imagelogo\').style.display = \'none\'" onmouseover="showimage(this,\''.addslashes($brand['ico']).'\')" />';
        }
        $html .= ' '.xhtmlEsc($brand['navn']).'</a></div>';
    }
    $html .= '</form>';
    return $html;
}

function getupdatemaerke(int $id): string
{
    $brand = db()->fetchOne("SELECT navn, link, ico FROM `maerke` WHERE id = " . $id);

    $html = '<div id="headline">'.sprintf(_('Edit the brand %d'), $brand['navn']).'</div><form onsubmit="x_updatemaerke('.$id.',document.getElementById(\'navn\').value,document.getElementById(\'link\').value,document.getElementById(\'ico\').value,inject_html); return false;"><table cellspacing="0"><tr style="height:21px"><td>'._('Name:').' </td><td><input value="'.xhtmlEsc($brand['navn']).'" id="navn" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>Link: </td><td><input value="'.xhtmlEsc($brand['link']).'" id="link" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>'._('Logo:').' </td>
    <td style="text-align:center"><input type="hidden" value="'.xhtmlEsc($brand['ico']).'" id="ico" name="ico" /><img id="icothb" src="';
    if ($brand['ico']) {
        $html .= $brand['ico'];
    } else {
        $html .= _('/images/web/intet-foto.jpg');
    }
    $html .= '" alt="" onclick="explorer(\'thb\', \'ico\')" /><br /><img onclick="explorer(\'thb\', \'ico\')" src="images/folder_image.png" width="16" height="16" alt="'._('Pictures').'" title="'._('Find image').'" /><img onclick="setThb(\'ico\', \'\', \''._('/images/web/intet-foto.jpg').'\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" width="16" height="16" /></td>
    </tr><tr><td></td></tr></table><br /><br /><input value="'._('Save brand').'" type="submit" accesskey="s" /><br /><br /><div id="imagelogo" style="display:none; position:absolute;"></div></form>';
    return $html;
}

function updatemaerke(int $id, string $navn, string $link, string $ico): array
{
    if ($navn) {
        db()->query('UPDATE maerke SET navn = \''.$navn.'\', link = \''.$link.'\', ico = \''.$ico.'\' WHERE id = '.$id);
        return ['id' => 'canvas', 'html' => getmaerker()];
    } else {
        return ['error' => _('You must enter a name.')];
    }
}

function save_ny_maerke(string $navn, string $link, string $ico): array
{
    if ($navn) {
        db()->query('INSERT INTO `maerke` (`navn` , `link` , `ico` ) VALUES (\''.$navn.'\', \''.$link.'\', \''.$ico.'\')');
        return ['id' => 'canvas', 'html' => getmaerker()];
    } else {
        return ['error' => _('You must enter a name.')];
    }
}

function getkrav(): string
{
    $html = '<div id="headline">'._('Requirements list').'</div><div style="margin:16px;"><a href="?side=nykrav">Tilføj krav</a>';
    $kravs = db()->fetchArray('SELECT id, navn FROM `krav` ORDER BY navn');
    foreach ($kravs as $krav) {
        $html .= '<div id="krav'.$krav['id'].'"><a href="" onclick="slet(\'krav\',\''.addslashes($krav['navn']).'\','.$krav['id'].');"><img src="images/cross.png" title="Slet '.$krav['navn'].'!" width="16" height="16" /></a><a href="?side=editkrav&amp;id='.$krav['id'].'">'.$krav['navn'].'</a></div>';
    }
    $html .= '</div>';
    return $html;
}

function editkrav(int $id): string
{
    $krav = db()->fetchOne('SELECT navn, text FROM `krav` WHERE id = '.$id);

    $html = '<div id="headline">'.sprintf(_('Edit %s'), $krav['navn']).'</div><form action="" method="post" onsubmit="return savekrav();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><input type="hidden" name="id" id="id" value="'.$id.'" /><input class="admin_name" type="text" name="navn" id="navn" value="'.$krav['navn'].'" maxlength="127" size="127" style="width:587px" /><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", \''.rtefsafe($krav['text']).'\', "", ' . Config::get('text_width') . ', 420, true, false, false);
//--></script></form>';

    return $html;
}

function sletmaerke(int $id): array
{
    db()->query("DELETE FROM `maerke` WHERE `id` = " . $id);
    return ['node' => 'maerke' . $id];
}

function sletkrav(int $id): array
{
    db()->query("DELETE FROM `krav` WHERE `id` = " . $id);
    return ['id' => 'krav' . $id];
}

function sletkat(int $id): array
{
    db()->query("DELETE FROM `kat` WHERE `id` = " . $id);
    if ($kats = db()->fetchArray('SELECT id FROM `kat` WHERE `bind` = '.$id)) {
        foreach ($kats as $kat) {
            sletkat($kat['id']);
        }
    }
    if ($bind = db()->fetchArray('SELECT side FROM `bind` WHERE `kat` = '.$id)) {
        db()->query('DELETE FROM `bind` WHERE `kat` = '.$id);
        foreach ($bind as $side) {
            if (!db()->fetchOne("SELECT id FROM `bind` WHERE `side` = " . $side['side'])) {
                sletSide($side['side']);
            }
        }
    }
    return ['id' => 'kat' . $id];
}

function movekat(int $id, int $toId)
{
    db()->query("UPDATE `kat` SET `bind` = " . $toId . " WHERE `id` = " . $id);

    if (db()->affected_rows) {
        return ['id' => 'kat'.$id, 'update' => $toId];
    } else {
        return false;
    }
}

function renamekat(int $id, string $name): array
{
    db()->query("UPDATE `kat` SET `navn` = '" . $name . "' WHERE `id` = " . $id);
    return ['id' => 'kat' . $id, 'name' => $name];
}

function sletbind(string $id)
{
    if (!$bind = db()->fetchOne("SELECT side FROM `bind` WHERE `id` = " . $id)) {
        return ['error' => _('The binding does not exist.')];
    }
    db()->query("DELETE FROM `bind` WHERE `id` = " . $id);
    $delete[0]['id'] = $id;
    if (!db()->fetchOne("SELECT id FROM `bind` WHERE `side` = " . $bind['side'])) {
        db()->query('INSERT INTO `bind` (`side` ,`kat`) VALUES (\''.$bind['side'].'\', \'-1\')');

        $added['id'] = db()->insert_id;
        $added['path'] = '/'._('Inactive').'/';
        $added['kat'] = -1;
        $added['side'] = $bind['side'];
    } else {
        $added = false;
    }
    return ['deleted' => $delete, 'added' => $added];
}

function bind(int $id, int $kat): array
{
    if (db()->fetchOne("SELECT id FROM `bind` WHERE `side` = " . $id . " AND `kat` = " . $kat)) {
        return ['error' => _('The binding already exists.')];
    }

    $katRoot = $kat;
    while ($katRoot > 0) {
        $katRoot = db()->fetchOne("SELECT bind FROM `kat` WHERE id = '" . $katRoot . "'");
        $katRoot = $katRoot['bind'];
    }

    //Delete any binding not under $katRoot
    $delete = [];
    $binds = db()->fetchArray('SELECT id, kat FROM `bind` WHERE `side` = '.$id);
    foreach ($binds as $bind) {
        $bindRoot = $bind['kat'];
        while ($bindRoot > 0) {
            $bindRoot = db()->fetchOne("SELECT bind FROM `kat` WHERE id = '" . $bindRoot ."'");
            $bindRoot = $bindRoot['bind'];
        }
        if ($bindRoot != $katRoot) {
            db()->query("DELETE FROM `bind` WHERE `id` = " . $bind['id']);
            $delete[] = $bind['id'];
        }
    }

    db()->query('INSERT INTO `bind` (`side` ,`kat`) VALUES (\''.$id.'\', \''.$kat.'\')');

    $added = [
        'id' => db()->insert_id,
        'kat' => $kat,
        'side' => $id,
        'path' => '',
    ];

    foreach (kattree($kat) as $kat) {
        $added['path'] .= '/'.trim($kat['navn']);
    }
    $added['path'] .= '/';

    return ['deleted' => $delete, 'added' => $added];
}

function htmlUrlDecode(string $text): string
{
    $text = trim($text);

    //Double encode importand encodings, to survive next step and remove white space
    $text = preg_replace(
        ['/&lt;/u',  '/&gt;/u',  '/&amp;/u', '/\s+/u'],
        ['&amp;lt;', '&amp;gt;', '&amp;amp;', ' '],
        $text
    );

    //Decode IE style urls
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

    //Decode Firefox style urls
    $text = rawurldecode($text);

    //atempt to make relative paths (generated by Firefox when copy pasting) in to absolute
    $text = preg_replace('/="[.]{2}\//iu', '="/', $text);

    return $text;
}

function updateSide(
    int $id,
    string $navn,
    string $keywords,
    int $pris,
    string $billed,
    string $beskrivelse,
    int $for,
    string $text,
    string $varenr,
    int $burde,
    int $fra,
    int $krav,
    int $maerke
): bool {
    $beskrivelse = purifyHTML($beskrivelse);
    $beskrivelse = htmlUrlDecode($beskrivelse);
    $text = purifyHTML($text);
    $text = htmlUrlDecode($text);

    db()->query(
        "
        UPDATE `sider`
        SET
            `dato` = now(),
            `navn` = '" . addcslashes($navn, "'\\") . "',
            `keywords` = '" . addcslashes($keywords, "'\\") . "',
            `pris` = " . $pris . ",
            `text` = '" . addcslashes($text, "'\\") . "',
            `varenr` = '" . addcslashes($varenr, "'\\") . "',
            `for` = " . $for . ",
            `beskrivelse` = '" . addcslashes($beskrivelse, "'\\") . "',
            `krav` = ". $krav .",
            `maerke` = " . $maerke . ",
            `billed` = '" . addcslashes($billed, "'\\") . "',
            `fra` = " . $fra . ",
            `burde` = " . $burde . "

        WHERE `id` = " . $id
    );
    return true;
}

function updateKat(int $id, string $navn, string $bind, string $icon, string $vis, string $email, string $custom_sort_subs, string $subsorder): bool
{
    $bindtree = kattree($bind);
    foreach ($bindtree as $bindbranch) {
        if ($id == $bindbranch['id']) {
            return ['error' => _('The category can not be placed under itself.')];
        }
    }

    //Set the order of the subs
    if ($custom_sort_subs) {
        updateKatOrder($subsorder);
    }

    //Update kat
    db()->query(
        "
        UPDATE `kat`
        SET `navn` = '" . $navn
        . "', `bind` = '" . $bind
        . "', `icon` = '" . $icon
        . "', `vis` = '" . $vis
        . "', `email` = '" . $email
        . "', `custom_sort_subs` = '" . $custom_sort_subs
        . "' WHERE `id` = " .$id
    );
    return true;
}

function updateKatOrder(string $subsorder)
{
    $orderquery = db()->prepare('UPDATE `kat` SET `order` = ? WHERE `id` = ?');
    $orderquery->bind_param('ii', $key, $value);

    $subsorder = explode(',', $subsorder);

    foreach ($subsorder as $key => $value) {
        $orderquery->execute();
    }

    $orderquery->close();
}

function updateForside(int $id, string $text, string $subsorder): bool
{
    updateSpecial($id, $text);
    updateKatOrder($subsorder);
    return true;
}

function updateSpecial(int $id, string $html): bool
{
    $html = purifyHTML($html);
    $html = htmlUrlDecode($html);
    ORM::getOne(CustomPage::class, $id)->setHtml($html)->save();
    return true;
}

function opretSide(int $kat, string $navn, string $keywords, int $pris, string $billed, string $beskrivelse, int $for, string $text, string $varenr, int $burde, int $fra, int $krav, int $maerke): array
{
    $beskrivelse = purifyHTML($beskrivelse);
    $beskrivelse = htmlUrlDecode($beskrivelse);
    $text = purifyHTML($text);
    $text = htmlUrlDecode($text);

    db()->query(
        'INSERT INTO `sider` (
            `dato`,
            `navn`,
            `keywords`,
            `pris`,
            `text`,
            `varenr`,
            `for`,
            `beskrivelse`,
            `krav`,
            `maerke`,
            `billed`,
            `fra`,
            `burde`
        ) VALUES (
            now(),
            \''.addcslashes($navn, "'\\").'\',
            \''.addcslashes($keywords, "'\\").'\',
            ' . $pris . ',
            \''.addcslashes($text, "'\\").'\',
            \''.addcslashes($varenr, "'\\").'\',
            ' . $for . ',
            \''.addcslashes($beskrivelse, "'\\").'\',
            ' . $krav . ',
            ' . $maerke . ',
            \''.addcslashes($billed, "'\\").'\',
            ' . $fra . ',
            ' . $burde . '
        )'
    );

    $id = db()->insert_id;
    db()->query('INSERT INTO `bind` (`side` ,`kat` ) VALUES ('.$id.', '.$kat.')');
    return ['id' => $id];
}

//Delete a page and all it's relations from the database
function sletSide(int $sideId): array
{
    $lists = db()->fetchArray('SELECT id FROM `lists` WHERE `page_id` = '.$sideId);
    if ($lists) {
        $listIds = [];
        foreach ($lists as $list) {
            $listIds[] = $list['id'];
        }

        db()->query('DELETE FROM `list_rows` WHERE list_id IN(' . implode('', $listIds) . ')');
        db()->query('DELETE FROM `lists` WHERE `sideId` = ' . $sideId);
    }
    db()->query('DELETE FROM `list_rows` WHERE `link` = '.$sideId);
    db()->query('DELETE FROM `bind` WHERE side = '.$sideId);
    db()->query('DELETE FROM `tilbehor` WHERE side = '.$sideId.' OR tilbehor ='.$sideId);
    db()->query('DELETE FROM `sider` WHERE id = '.$sideId);

    return ['class' => 'side' . $sideId];
}

/**
 * @param int $id
 *
 * @return int
 */
function copytonew(int $id): int
{
    $faktura = db()->fetchOne("SELECT * FROM `fakturas` WHERE `id` = ".$id);

    unset($faktura['id']);
    unset($faktura['status']);
    unset($faktura['date']);
    unset($faktura['paydate']);
    unset($faktura['sendt']);
    unset($faktura['transferred']);
    $faktura['clerk'] = $_SESSION['_user']['fullname'];

    $sql = "INSERT INTO `fakturas` SET";
    foreach ($faktura as $key => $value) {
        $sql .= " `".addcslashes($key, '`\\')."` = '".addcslashes($value, "'\\")."',";
    }
    $sql .= " `date` = NOW();";

    db()->query($sql);

    return db()->insert_id;
}

/**
 * @param int $id
 * @param string $type
 * @param array $updates
 *
 * @return array
 */
function save(int $id, string $type, array $updates): array
{
    if (empty($updates['department'])) {
        $email = first(Config::get('emails'))['address'];
        $updates['department'] = $email;
    }

    if (!empty($updates['date'])) {
        $date = "STR_TO_DATE('".$updates['date']."', '%d/%m/%Y')";
        unset($updates['date']);
    }

    if (!empty($updates['paydate']) && ($type == 'giro' || $type == 'cash')) {
        $paydate = "STR_TO_DATE('".$updates['paydate']."', '%d/%m/%Y')";
    } elseif ($type == 'lock' || $type == 'cancel') {
        $paydate = 'NOW()';
    }
    unset($updates['paydate']);

    $faktura = db()->fetchOne("SELECT `status`, `note` FROM `fakturas` WHERE `id` = ".$id);

    if (in_array($faktura['status'], ['locked', 'pbsok', 'rejected'])) {
        $updates = [
            'note' => $updates['note'] ? trim($faktura['note'] . "\n" . $updates['note']) : $faktura['note'],
            'clerk' => isset($updates['clerk']) ? $updates['clerk'] : '',
            'department' => $updates['department'],
        ];
        if ($faktura['status'] != 'pbsok') {
            if ($type == 'giro') {
                $updates['status'] = 'giro';
            }
            if ($type == 'cash') {
                $updates['status'] = 'cash';
            }
        }
    } elseif (in_array($faktura['status'], ['accepted', 'giro', 'cash', 'canceled'])) {
        if ($updates['note']) {
            $updates = ['note' => $faktura['note']."\n".$updates['note']];
        } else {
            $updates = [];
        }
    } elseif ($faktura['status'] == 'new') {
        unset($updates['id']);
        unset($updates['status']);
        if ($type == 'lock') {
            $updates['status'] = 'locked';
        } elseif ($type == 'giro') {
            $updates['status'] = 'giro';
        } elseif ($type == 'cash') {
            $updates['status'] = 'cash';
        }
    }

    if ($type == 'cancel'
        && !in_array($faktura['status'], ['pbsok', 'accepted', 'giro', 'cash'])
    ) {
        $updates['status'] = 'canceled';
    }

    if ($_SESSION['_user']['access'] != 1) {
        unset($updates['clerk']);
    }

    if (count($updates) || !empty($date) || !empty($paydate)) {
        $sql = "UPDATE `fakturas` SET";
        foreach ($updates as $key => $value) {
            $sql .= " `".addcslashes($key, '`\\')."` = '".addcslashes($value, "'\\")."',";
        }
        $sql = substr($sql, 0, -1);

        if (!empty($date)) {
            $sql .= ", `date` = ".$date;
        }
        if (!empty($paydate)) {
            $sql .= ", `paydate` = ".$paydate;
        }

        $sql .= ' WHERE `id` = '.$id;

        db()->query($sql);
    }

    $faktura = db()->fetchOne("SELECT * FROM `fakturas` WHERE `id` = ".$id);

    if (empty($faktura['clerk'])) {
        db()->query("UPDATE `fakturas` SET `clerk` = '".addcslashes($_SESSION['_user']['fullname'], '\'\\')."' WHERE `id` = ".$faktura['id']);
        $faktura['clerk'] = $_SESSION['_user']['fullname'];
    }

    if ($type == 'email') {
        if (!valideMail($faktura['email'])) {
            return ['error' => _('E-mail address is not valid!')];
        }
        if (!$faktura['department'] && count(Config::get('emails')) > 1) {
            return ['error' => _('You have not selected a sender!')];
        } elseif (!$faktura['department']) {
            $email = first(Config::get('emails'))['address'];
            $updates['department'] = $email;
        }
        if ($faktura['amount'] < 1) {
            return ['error' => _('The invoice must be of at at least 1 krone!')];
        }

        $msg = _(
            '<p>Thank you for your order.</p>

<p>your online invoice no %d is approved and ready for shipment once the payment is complete.</p>

<p>Payment with credit card, is performed by clicking on the link below.</p>

<p>Link to payment:<br />
<a href="%s/betaling/?id=%d&amp;checkid=%s">%s/betaling/?id=%d&amp;checkid=%s</a></p>
<p>Do you have questions about your order, do not hesitate to contact us.</p>

<p>Sincerely,</p>

<p>%s<br />
<br />%s
<br />%s
%s %s<br />
Tel. %s</p>'
        );
        $msg = sprintf(
            $msg,
            $faktura['id'],
            Config::get('base_url'),
            $faktura['id'],
            getCheckid($faktura['id']),
            Config::get('base_url'),
            $faktura['id'],
            getCheckid($faktura['id']),
            $faktura['clerk'],
            Config::get('site_name'),
            Config::get('address'),
            Config::get('postcode'),
            Config::get('city'),
            Config::get('phone')
        );

        $emailBody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>' . sprintf(_('Online payment to %s'), Config::get('site_name')) . '</title>
</head><body>' .$msg .'</body></html>';

        $success = sendEmails(
            _('Online payment for ').Config::get('site_name'),
            $emailBody,
            $faktura['department'],
            '',
            $faktura['email'],
            $faktura['navn'],
            false
        );
        if (!$success) {
            return ['error' => _('Unable to sendt e-mail!')."\n".$mail->ErrorInfo];
        }
        db()->query("UPDATE `fakturas` SET `status` = 'locked' WHERE `status` = 'new' && `id` = ".$faktura['id']);
        db()->query("UPDATE `fakturas` SET `sendt` = 1, `department` = '".$faktura['department']."' WHERE `id` = ".$faktura['id']);

        //Forece reload
        $faktura['status'] = 'sendt';
    }

    return ['type' => $type, 'status' => $faktura['status']];
}

/**
 * @param int $id
 *
 * @return array
 */
function sendReminder(int $id): array
{
    $error = '';

    $faktura = db()->fetchOne("SELECT * FROM `fakturas` WHERE `id` = ".$id);

    if (!$faktura['status']) {
        return ['error' => _('You can not send a reminder until the invoice is sent!')];
    }

    if (!valideMail($faktura['email'])) {
        return ['error' => _('E-mail address is not valid!')];
    }

    if (empty($faktura['department'])) {
        $email = first(Config::get('emails'))['address'];
        $faktura['department'] = $email;
    }

    $msg = _(
        '<hr />

<p style="text-align:center;"> <img src="/images/logoer/jagt-og-fiskermagasinet.png" alt="%s" /> </p>

<hr />

<p>This is an automatically generated email reminder:</p>

<p>Your goods are ready for delivery / pick-up - but we have not yet <br />
registred, that the payment can be accepted - therefore we are <br />
sending a you a new link to the credit card invoice system <br />
<br />
<a href="%s/betaling/?id=%d&amp;checkid=%s">%s/betaling/?id=%d&amp;checkid=%s</a><br />
</p>

<p>When entering your credit card information, - errors may occure <br />
 preventing us from noticing the payment - thus causing unnecessary <br />
 delays - therefore we include the following notice. </p>

 <p>It is very helpful and results in a shorter expedite time - if you could <br />
 please send us an email when the payment is made. </p>n
 <p>We would also welcome an email or phone call - if you: <br />
 * Experiencing problems with our payment system <br />
 * Wish to cancel the order <br />
 * Wish to change the order <br />
 * Wish to pay by other means - for example, by transfering the amount via home banking. </p>

 <p>Kind regards <br />
<br />
%s<br />
%s<br />
%s %s<br />
Tel: %s<br />
Fax: %s<br />
<a href="mailto:%s">%s</a></p>'
    );

    $msg = sprintf(
        $msg,
        Config::get('site_name'),
        Config::get('base_url'),
        $faktura['id'],
        getCheckid($faktura['id']),
        Config::get('base_url'),
        $faktura['id'],
        getCheckid($faktura['id']),
        Config::get('site_name'),
        Config::get('address'),
        Config::get('postcode'),
        Config::get('city'),
        Config::get('phone'),
        Config::get('fax'),
        $faktura['department'],
        $faktura['department']
    );

    $emailBody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'._('Electronic Invoice concerning order #').$faktura['id'].'</title>
</head><body>' .$msg .'</body></html>';


    $success = sendEmails(
        'Elektronisk faktura vedr. ordre',
        $emailBody,
        $faktura['department'],
        '',
        $faktura['email'],
        $faktura['navn'],
        false
    );

    if (!$success) {
        return ['error' => 'Mailen kunde ikke sendes!' . "\n" . $mail->ErrorInfo];
    }
    $error .= "\n\n"._('A Reminder was sent to the customer.');

    return ['error' => trim($error)];
}

/**
 * @param int $id
 */
function pbsconfirm(int $id)
{
    global $epayment;

    try {
        $success = $epayment->confirm();
    } catch (SoapFault $e) {
        return ['error' => $e->faultstring];
    }

    if (!$epayment->hasError() || !$success) {
        db()->query(
            "
            UPDATE `fakturas`
            SET `status` = 'accepted', `paydate` = NOW()
            WHERE `id` = " . $id
        );
        return true;
    } else {
        return ['error' => _('An error occurred')];
    }
}

/**
 * @param int $id
 */
function annul(int $id)
{
    global $epayment;

    try {
        $success = $epayment->annul();
    } catch (SoapFault $e) {
        return ['error' => $e->faultstring];
    }

    if (!$epayment->hasError() || !$success) {
        db()->query(
            "
            UPDATE `fakturas`
            SET `status`  = 'rejected',
                `paydate` = NOW()
            WHERE `id` = 'pbsok'
              AND `id` = " . $id
        );
        return true;
    } else {
        return ['error' => _('An error occurred')];
    }
}

/**
 * @param string $path
 * @param int $cropX
 * @param int $cropY
 * @param int $cropW
 * @param int $cropH
 * @param int $maxW
 * @param int $maxH
 * @param int $flip
 * @param int $rotate
 * @param array $output
 *
 * @return array
 */
function generateImage(
    string $path,
    int $cropX,
    int $cropY,
    int $cropW,
    int $cropH,
    int $maxW,
    int $maxH,
    int $flip,
    int $rotate,
    array $output = []
): array {
    $outputPath = $path;
    if (!empty($output['type'])) {
        $pathinfo = pathinfo($path);
        if (empty($output['filename'])) {
            $output['filename'] = $pathinfo['filename'];
        }

        $outputPath = $pathinfo['dirname'] . '/' . $output['filename'];
        $outputPath .= !empty($output['type']) && $output['type'] === 'png' ? '.png' : '.jpg';

        if (!empty($output['type']) && empty($output['force']) && file_exists(_ROOT_ . $outputPath)) {
            return ['yesno' => _('A file with the same name already exists.'."\n".'Would you like to replace the existing file?'), 'filename' => $output['filename']];
        }
    }

    $image = new AJenbo\Image(_ROOT_ . $path);
    $orginalWidth = $image->getWidth();
    $orginalHeight = $image->getHeight();

    //Config::get('bgcolorR'), Config::get('bgcolorG'), Config::get('bgcolorB')

    // Crop image
    $cropW = $cropW ?: $image->getWidth();
    $cropH = $cropH ?: $image->getHeight();
    $cropW = min($image->getWidth(), $cropW);
    $cropH = min($image->getHeight(), $cropH);
    $cropX = $cropW !== $image->getWidth() ? $cropX : 0;
    $cropY = $cropH !== $image->getHeight() ? $cropY : 0;
    $image->crop($cropX, $cropY, $cropW, $cropH);

    // Trim image whitespace
    $imageContent = $image->findContent(0);

    $maxW = min($maxW, $imageContent['width']);
    $maxH = min($maxH, $imageContent['height']);

    if (empty($output['type'])
        && !$flip
        && !$rotate
        && $maxW === $orginalWidth
        && $maxH === $orginalHeight
    ) {
        redirect($path, 301);
    }

    $image->crop(
        $imageContent['x'],
        $imageContent['y'],
        $imageContent['width'],
        $imageContent['height']
    );

    // Resize
    $image->resize($maxW, $maxH);

    // Flip / mirror
    if ($flip) {
        $image->flip($flip === 1 ? 'x' : 'y');
    }

    $image->rotate($rotate);

    // Output image or save
    if (empty($output['type'])) {
        $mimeType = get_mime_type($path);
        if ($mimeType !== 'image/png') {
            $mimeType = 'image/jpeg';
        }
        header('Content-Type: ' . $mimeType);
        $image->save(null, $mimeType === 'image/png' ? 'png' : 'jpeg');
        die();
    } elseif ($output['type'] === 'png') {
        $mimeType = 'image/png';
        $image->save(_ROOT_ . $outputPath, 'png');
    } else {
        $mimeType = 'image/jpeg';
        $image->save(_ROOT_ . $outputPath, 'jpeg');
    }

    $width = $image->getWidth();
    $height = $image->getHeight();
    unset($image);

    $filesize = filesize(_ROOT_ . $outputPath);

    $id = null;
    if ($output['filename'] === $pathinfo['filename'] && $outputPath !== $path) {
        @unlink(_ROOT_ . $path);
        db()->query("DELETE FROM files WHERE path = '" . db()->esc($outputPath) . "'");
    } else {
        $id = db()->fetchOne("SELECT id FROM files WHERE path = '" . db()->esc($outputPath) . "'");
        $id = $id ? (int) $id['id'] : null;
    }

    if ($id) {
        db()->query(
            "
            UPDATE files SET
            path = '" . db()->esc($outputPath) . "',
            mime = '" . db()->esc($mimeType) . "',
            width = '" . $width . "',
            height = '" . $height . "'
            size = " . $filesize . ",
            WHERE id = " . $id
        );
    } else {
        db()->query(
            "
            INSERT INTO files (
                path,
                mime,
                width,
                height,
                size,
                aspect
            ) VALUES (
                '" . db()->esc($outputPath) . "',
                '" . db()->esc($mimeType) . "',
                '" . $width . "',
                '" . $height . "',
                '" . $filesize . "',
                NULL
            )
            "
        );
        $id = db()->insert_id;
    }

    return ['id' => $id, 'path' => $outputPath, 'width' => $width, 'height' => $height];
}
