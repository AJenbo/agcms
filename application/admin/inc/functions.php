<?php

use AGCMS\Config;
use AGCMS\Entity\Brand;
use AGCMS\Entity\Category;
use AGCMS\Entity\Contact;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\File;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\Page;
use AGCMS\Entity\Requirement;
use AGCMS\Entity\Table;
use AGCMS\Entity\User;
use AGCMS\EpaymentAdminService;
use AGCMS\ORM;
use AGCMS\Render;
use AJenbo\Image;
use Symfony\Component\HttpFoundation\Response;

function checkUserLoggedIn(): void
{
    session_start();
    if (curentUser()) {
        return;
    }

    if (!request()->get('username')) {
        sleep(1);
        header('HTTP/1.0 401 Unauthorized', true, 401);

        if (request()->get('rs')) { // Sajax call
            exit(_('Your login has expired, please reload the page and login again.'));
        }

        Render::output('admin-login');
        exit;
    }

    $user = ORM::getOneByQuery(
        User::class,
        'SELECT * FROM `users` WHERE `name` = ' . db()->eandq(request()->get('username'))
    );
    assert($user instanceof User);
    if ($user && $user->getAccessLevel() && $user->validatePassword(request()->get('password', ''))) {
        $_SESSION['curentUser'] = $user;
    }
    session_write_close();

    redirect(request()->getRequestUri());
}

/**
 * Declare common functions.
 */
function curentUser(): ?User
{
    return $_SESSION['curentUser'] ?? null;
}

/**
 * Optimize all tables.
 *
 * @return string Always empty
 */
function optimizeTables(): string
{
    $tables = db()->fetchArray('SHOW TABLE STATUS');
    foreach ($tables as $table) {
        db()->query('OPTIMIZE TABLE `' . $table['Name'] . '`');
    }

    return '';
}

/**
 * Remove newletter submissions that are missing vital information.
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
 * Remove enteries for files that do no longer exist.
 *
 * @return string Always empty
 */
function removeNoneExistingFiles(): string
{
    $files = db()->fetchArray('SELECT id, path FROM `files`');

    $missing = [];
    foreach ($files as $files) {
        if (!is_file(_ROOT_ . $files['path'])) {
            $missing[] = (int) $files['id'];
        }
    }
    if ($missing) {
        db()->query('DELETE FROM `files` WHERE `id` IN(' . implode(',', $missing) . ')');
    }

    return '';
}

function sendDelayedEmail(): string
{
    //Get emails that needs sending
    $emails = db()->fetchArray('SELECT * FROM `emails`');
    $cronStatus = ORM::getOne(CustomPage::class, 0);
    assert($cronStatus instanceof CustomPage);
    if (!$emails) {
        $cronStatus->save();

        return '';
    }

    $emailsSendt = 0;
    $emailCount = count($emails);
    foreach ($emails as $email) {
        $email['from'] = explode('<', $email['from']);
        $email['from'][1] = mb_substr($email['from'][1], 0, -1);
        $email['to'] = explode('<', $email['to']);
        $email['to'][1] = mb_substr($email['to'][1], 0, -1);

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

        ++$emailsSendt;

        db()->query('DELETE FROM `emails` WHERE `id` = ' . (int) $email['id']);
    }

    $cronStatus->save();

    $msg = ngettext(
        '%d of %d e-mail was sent.',
        '%d of %d e-mails was sent.',
        $emailsSendt
    );

    return sprintf($msg, $emailsSendt, $emailCount);
}

/**
 * Convert PHP size string to bytes.
 *
 * @param string $val PHP size string (eg. '2M')
 *
 * @return int Byte size
 */
function returnBytes(string $val): int
{
    $last = mb_substr($val, -1);
    $last = mb_strtolower($last);
    $val = (int) mb_substr($val, 0, -1);
    switch ($last) {
        case 'g':
            $val *= 1024;
            // no break
        case 'm':
            $val *= 1024;
            // no break
        case 'k':
            $val *= 1024;
    }

    return $val;
}

function get_mime_type(string $filepath): string
{
    $mime = '';
    if (function_exists('finfo_file')) {
        $mime = finfo_file($finfo = finfo_open(FILEINFO_MIME), $filepath);
        finfo_close($finfo);
    }
    if (!$mime && function_exists('mime_content_type')) {
        $mime = mime_content_type($filepath);
    }

    //Some types can't be trusted, and finding them via extension seams to give better resutls.
    $unknown = ['text/plain', 'application/msword', 'application/octet-stream'];
    if (!$mime || in_array($mime, $unknown, true)) {
        $mimes = [
            'doc'   => 'application/msword',
            'pdf'   => 'application/pdf',
            'xls'   => 'application/vnd.ms-excel',
            'zip'   => 'application/zip',
            'm4a'   => 'audio/mpeg',
            'mp3'   => 'audio/mpeg',
            'wav'   => 'audio/x-wav',
            'bmp'   => 'image/x-ms-bmp',
            'txt'   => 'text/plain',
            'mov'   => 'video/quicktime',
        ];
        $mime = 'application/octet-stream';
        $extension = mb_strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        if (isset($mimes[$extension])) {
            $mime = $mimes[$extension];
        }
    }

    $mime = explode(';', $mime);

    return array_shift($mime);
}

/**
 * @return string[]|true
 */
function sendEmail(
    int $id,
    string $from,
    string $interests,
    string $subject,
    string $html
) {
    if (!db()->fetchArray('SELECT `id` FROM `newsmails` WHERE `sendt` = 0')) {
        //Nyhedsbrevet er allerede afsendt!
        return ['error' => _('The newsletter has already been sent!')];
    }

    saveEmail($from, $interests, $subject, $html, $id);

    $html = purifyHTML($html);

    //Colect interests
    $andwhere = '';
    if ($interests) {
        $interests = explode('<', $interests);
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
        $andwhere = ' AND (' . $andwhere;
        $andwhere .= ')';
    }

    $emails = db()->fetchArray(
        'SELECT navn, email
        FROM `email`
        WHERE `email` NOT LIKE \'\'
          AND `kartotek` = \'1\' ' . $andwhere . '
        GROUP BY `email`'
    );
    $totalEmails = count($emails);
    $emailsGroup = [];
    foreach ($emails as $x => $email) {
        $emailsGroup[(int) floor($x / 99) + 1][] = $email;
    }

    $data = [
        'siteName' => Config::get('site_name'),
        'css'      => file_get_contents(_ROOT_ . '/theme/' . Config::get('theme', 'default') . '/style/email.css'),
        'body'     => str_replace(' href="/', ' href="' . Config::get('base_url') . '/', $html),
    ];

    $failedCount = 0;
    foreach ($emailsGroup as $of => $emails) {
        $success = sendEmails(
            $subject,
            Render::render('email/newsletter', $data),
            $from,
            '',
            '',
            '',
            true,
            $emails
        );

        if (!$success) {
            $failedCount += count($emails);
        }
    }
    if ($failedCount) {
        return ['error' => 'Email ' . $failedCount . '/' . $totalEmails . ' failed to be sent.'];
    }

    db()->query('UPDATE `newsmails` SET `sendt` = 1 WHERE `id` = ' . $id);

    return true;
}

function countEmailTo(array $interests): int
{
    //Colect interests
    $andwhere = '';
    if ($interests) {
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
        $andwhere = ' AND (' . $andwhere . ')';
    }

    $emails = db()->fetchOne(
        "
        SELECT count(DISTINCT email) as 'count'
        FROM `email`
        WHERE `email` NOT LIKE '' AND `kartotek` = '1'
        " . $andwhere
    );

    return $emails['count'];
}

function saveEmail(string $from, string $interests, string $subject, string $html, int $id = null): bool
{
    $html = purifyHTML($html);

    if (null === $id) {
        db()->query(
            "
            INSERT INTO `newsmails` (`from`, `interests`, `subject`, `text`)
            VALUES (
                '" . db()->esc($from) . "',
                '" . db()->esc($interests) . "',
                '" . db()->esc($subject) . "',
                '" . db()->esc($html) . "'
            )
            "
        );

        return true;
    }

    db()->query(
        "UPDATE `newsmails`
        SET `from` = '" . db()->esc($from) . "',
        `interests` = '" . db()->esc($interests) . "',
        `subject` = '" . db()->esc($subject) . "',
        `text` = '" . db()->esc($html) . "'
        WHERE `id` = " . $id
    );

    return true;
}

function expandCategory(int $categoryId, string $inputType = ''): array
{
    $data = [
        'openCategories' => getOpenCategories(),
        'includePages'   => (!$inputType || 'pages' === $inputType),
        'inputType'      => $inputType,
        'node'           => ORM::getOne(Category::class, $categoryId),
    ];

    return ['id' => $categoryId, 'html' => Render::render('partial-admin-kat_expand', $data)];
}

/**
 * Delete unused file.
 *
 * @return string[]|int[]
 */
function deletefile(int $id, string $path): array
{
    if (isinuse($path)) {
        return ['error' => _('The file can not be deleted because it is used on a page.')];
    }
    $file = File::getByPath($path);
    if ($file && $file->delete()) {
        return ['id' => $id];
    }

    return ['error' => _('There was an error deleting the file, the file may be in use.')];
}

//TODO document type doesn't allow element "input" here; missing one of "p", "h1", "h2", "h3", "h4", "h5", "h6", "div", "pre", "address", "fieldset", "ins", "del" start-tag.

/**
 * Update user.
 *
 * @param int   $id      User id
 * @param array $updates Array of values to change
 *                       'access' int
 *                       'fullname' string
 *                       'password' string
 *                       'password_new' string
 *
 * @return string[]|true True on update, else ['error' => string]
 */
function updateuser(int $id, array $updates)
{
    if (!curentUser()->hasAccess(User::ADMINISTRATOR) && curentUser()->getId() != $id) {
        return ['error' => _('You do not have the requred access level to change other users.')];
    }

    // Validate access lavel update
    if (curentUser()->getId() == $id
        && isset($updates['access'])
        && $updates['access'] != curentUser()->getAccessLevel()
    ) {
        return ['error' => _('You can\'t change your own access level')];
    }

    /** @var User */
    $user = ORM::getOne(User::class, $id);
    assert($user instanceof User);

    //Validate password update
    if (!empty($updates['password_new'])) {
        if (!curentUser()->hasAccess(User::ADMINISTRATOR)
            && curentUser()->getId() != $id
        ) {
            return ['error' => _('You do not have the requred access level to change the password for this users.')];
        }

        if (curentUser()->getId() == $id && !$user->validatePassword($updates['password'])) {
            return ['error' => _('Incorrect password.')];
        }

        $user->setPassword($updates['password_new']);
    }

    if (isset($updates['access'])) {
        $user->setAccessLevel($updates['access']);
    }

    if (!empty($updates['fullname'])) {
        $user->setFullName($updates['fullname']);
    }

    $user->save();

    return true;
}

function saveImage(
    string $path,
    int $cropX,
    int $cropY,
    int $cropW,
    int $cropH,
    int $maxW,
    int $maxH,
    int $flip,
    int $rotate,
    string $filename,
    bool $force
): array {
    $mimeType = get_mime_type(_ROOT_ . $path);

    $output = ['type' => 'png'];
    if ('image/jpeg' === $mimeType) {
        $output['type'] = 'jpg';
    }

    $output['filename'] = $filename;
    $output['force'] = $force;

    return generateImage(_ROOT_ . $path, $cropX, $cropY, $cropW, $cropH, $maxW, $maxH, $flip, $rotate, $output);
    //TODO close and update image in explorer
}

/**
 * Delete user.
 */
function deleteuser(int $id): bool
{
    if (!curentUser()->hasAccess(User::ADMINISTRATOR) || curentUser()->getId() == $id) {
        return false;
    }

    db()->query('DELETE FROM `users` WHERE `id` = ' . $id);

    return true;
}

function fileExists(string $dir, string $filename, string $type = ''): array
{
    $pathinfo = pathinfo($filename);
    $filePath = _ROOT_ . $dir . '/' . cleanFileName($pathinfo['filename']);

    if ('image' == $type) {
        $filePath .= '.jpg';
    } elseif ('lineimage' == $type) {
        $filePath .= '.png';
    } else {
        $filePath .= '.' . $pathinfo['extension'];
    }

    return ['exists' => (bool) is_file($filePath), 'name' => basename($filePath)];
}

function newfaktura(): int
{
    db()->query(
        'INSERT INTO `fakturas` (`date`, `clerk`) VALUES (NOW(), ' . db()->eandq(curentUser()->getFullName()) . ')'
    );

    return db()->insert_id;
}

//TODO if force, refresh folder or we might have duplicates displaying in the folder.
//TODO Error out if the files is being moved to it self
//TODO moving two files to the same dire with no reload inbetwean = file exists?????????????
/**
 * Rename or relocate a file/directory.
 *
 * @param int|string $id Int for file renaming, string on folder renaming
 */
function renamefile($id, string $path, string $dir, string $filename, bool $force = false): array
{
    $pathinfo = pathinfo($path);
    if ('/' == $pathinfo['dirname']) {
        '' == $pathinfo['dirname'];
    }

    if (!$dir) {
        $dir = $pathinfo['dirname'];
    } elseif ('/' == $dir) {
        '' == $dir;
    }

    $pathinfo['extension'] = '';
    if (!is_dir(_ROOT_ . $path)) {
        $mime = get_mime_type(_ROOT_ . $path);
        if ('image/jpeg' == $mime) {
            $pathinfo['extension'] = 'jpg';
        } elseif ('image/png' == $mime) {
            $pathinfo['extension'] = 'png';
        } elseif ('image/gif' == $mime) {
            $pathinfo['extension'] = 'gif';
        } elseif ('application/pdf' == $mime) {
            $pathinfo['extension'] = 'pdf';
        } elseif ('image/vnd.wap.wbmp' == $mime) {
            $pathinfo['extension'] = 'wbmp';
        }
    } else {
        //a folder with a . will mistakingly be seen as a file with extension
        $pathinfo['filename'] .= '-' . @$pathinfo['extension'];
    }

    if (!$filename) {
        $filename = $pathinfo['filename'];
    }

    $filename = cleanFileName($filename);

    if (!$filename) {
        return ['error' => _('The name is invalid.'), 'id' => $id];
    }

    //Destination folder doesn't exist
    if (!is_dir(_ROOT_ . $dir . '/')) {
        return [
            'error' => _('The file could not be moved because the destination folder doesn\'t exist.'),
            'id' => $id,
        ];
    }
    if ($pathinfo['extension']) {
        //No changes was requested.
        $newPath = $dir . '/' . $filename . '.' . $pathinfo['extension'];
        if ($path === $newPath) {
            return ['id' => $id, 'filename' => $filename, 'path' => $path];
        }

        //if file path more then 255 erturn error
        if (mb_strlen($newPath, 'UTF-8') > 255) {
            return ['error' => _('The filename is too long.'), 'id' => $id];
        }

        //File already exists, but are we trying to force a overwrite?
        if (is_file(_ROOT_ . $newPath) && !$force) {
            return ['yesno' => _('A file with the same name already exists.
Would you like to replace the existing file?'), 'id' => $id];
        }
        if ($force) {
            $oldFile = File::getByPath($newPath);
            if ($oldFile) {
                $oldFile->delete();
            }
        }
        if (File::getByPath($path)->move($newPath)) {
            return ['id' => $id, 'filename' => $filename, 'path' => $newPath];
        }

        return ['error' => _('An error occurred with the file operations.'), 'id' => $id];
    }

    //Dir or file with no extension
    //TODO ajax rename folder
    $newPath = $dir . '/' . $filename . '.' . $pathinfo['extension'];
    //No changes was requested.
    if ($path == $newPath) {
        return ['id' => $id, 'filename' => $filename, 'path' => $path];
    }

    //folder already exists
    if (is_dir(_ROOT_ . $dir . '/' . $filename)) {
        return ['error' => _('A folder with the same name already exists.'), 'id' => $id];
    }

    //if file path more then 255 erturn error
    if (mb_strlen($newPath, 'UTF-8') > 255) {
        return ['error' => _('The filename is too long.'), 'id' => $id];
    }

    //File already exists, but are we trying to force a overwrite?
    if (is_file(_ROOT_ . $path) && !$force) {
        return ['yesno' => _('A file with the same name already exists.
Would you like to replace the existing file?'), 'id' => $id];
    }

    //Rename/move or give an error
    //TODO prepared query
    if (rename(_ROOT_ . $path, _ROOT_ . $dir . '/' . $filename)) {
        if ($force) {
            db()->query("DELETE FROM files WHERE `path` = '" . db()->esc($newPath) . "%'");
            //TODO insert new file data (width, alt, height)
        }

        db()->query("UPDATE files SET path = REPLACE(path, '" . db()->esc($path) . "', '" . db()->esc($newPath) . "')");
        replacePaths($path, $newPath);

        return ['id' => $id, 'filename' => $filename, 'path' => $dir . '/' . $filename];
    }

    return ['error' => _('An error occurred with the file operations.'), 'id' => $id];
}

function replacePaths(string $path, string $newPath): void
{
    $newPathEsc = db()->esc($newPath);
    $pathEsc = db()->esc($path);
    db()->query("UPDATE sider     SET navn  = REPLACE(navn, '" . $pathEsc . "', '" . $newPathEsc . "'), text = REPLACE(text, '" . $pathEsc . "', '" . $newPathEsc . "'), beskrivelse = REPLACE(beskrivelse, '" . $pathEsc . "', '" . $newPathEsc . "'), billed = REPLACE(billed, '" . $pathEsc . "', '" . $newPathEsc . "')");
    db()->query("UPDATE template  SET navn  = REPLACE(navn, '" . $pathEsc . "', '" . $newPathEsc . "'), text = REPLACE(text, '" . $pathEsc . "', '" . $newPathEsc . "'), beskrivelse = REPLACE(beskrivelse, '" . $pathEsc . "', '" . $newPathEsc . "'), billed = REPLACE(billed, '" . $pathEsc . "', '" . $newPathEsc . "')");
    db()->query("UPDATE special   SET text  = REPLACE(text, '" . $pathEsc . "', '" . $newPathEsc . "')");
    db()->query("UPDATE krav      SET text  = REPLACE(text, '" . $pathEsc . "', '" . $newPathEsc . "')");
    db()->query("UPDATE maerke    SET ico   = REPLACE(ico, '" . $pathEsc . "', '" . $newPathEsc . "')");
    db()->query("UPDATE list_rows SET cells = REPLACE(cells, '" . $pathEsc . "', '" . $newPathEsc . "')");
    db()->query("UPDATE kat       SET navn  = REPLACE(navn, '" . $pathEsc . "', '" . $newPathEsc . "'), icon = REPLACE(icon, '" . $pathEsc . "', '" . $newPathEsc . "')");
}

function edit_alt(int $id, string $description): array
{
    $file = ORM::getOne(File::class, $id);
    assert($file instanceof File);
    $file->setDescription($description)->save();

    //Update html with new alt...
    $pages = ORM::getByQuery(
        Page::class,
        "SELECT * FROM `sider` WHERE `text` LIKE '%" . db()->esc($file->getPath()) . "%'"
    );
    foreach ($pages as $page) {
        assert($page instanceof Page);
        //TODO move this to db fixer to test for missing alt="" in img
        /*preg_match_all('/<img[^>]+/?>/ui', $value, $matches);*/
        $html = $page->getHtml();
        $html = preg_replace(
            '/(<img[^>]+src="' . addcslashes(str_replace('.', '[.]', $file->getPath()), '/')
                . '"[^>]+alt=)"[^"]*"([^>]*>)/iu',
            '\1"' . xhtmlEsc($description) . '"\2',
            $html
        );
        $html = preg_replace(
            '/(<img[^>]+alt=)"[^"]*"([^>]+src="' . addcslashes(str_replace(
                '.',
                '[.]',
                $file->getPath()
            ), '/') . '"[^>]*>)/iu',
            '\1"' . xhtmlEsc($description) . '"\2',
            $html
        );
        $page->setHtml($html)->save();
    }

    return ['id' => $id, 'alt' => $description];
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
 * @return string[]
 */
function search(string $text): array
{
    if (!$text) {
        return ['error' => _('You must enter a search word.')];
    }

    $pages = findPages($text);

    $html = Render::render('partial-admin-search', ['text' => $text, 'pages' => $pages]);

    return ['id' => 'canvas', 'html' => $html];
}

/**
 * @return Page[]
 */
function findPages(string $text): array
{
    //fulltext search dosn't catch things like 3 letter words and some other combos
    $simpleq = preg_replace(
        ['/\s+/u', "/'/u", '/´/u', '/`/u'],
        ['%', '_', '_', '_'],
        $text
    );

    return ORM::getByQuery(
        Page::class,
        "
        SELECT * FROM sider
        WHERE MATCH (navn, text, beskrivelse) AGAINST('" . $text . "') > 0
            OR `navn` LIKE '%" . $simpleq . "%'
            OR `text` LIKE '%" . $simpleq . "%'
            OR `beskrivelse` LIKE '%" . $simpleq . "%'
        ORDER BY MATCH (navn, text, beskrivelse) AGAINST('" . $text . "') DESC
        "
    );
}

/**
 * @return int[]
 */
function listRemoveRow(int $tableId, int $rowId): array
{
    $table = ORM::getOne(Table::class, $tableId);
    assert($table instanceof Table);
    $table->removeRow($rowId);

    return ['listid' => $tableId, 'rowid' => $rowId];
}

function listSavetRow(int $tableId, array $cells, int $link = null, int $rowId = null): array
{
    /** @var Table */
    $table = ORM::getOne(Table::class, $tableId);
    assert($table instanceof Table);

    if (!$rowId) {
        $rowId = $table->addRow($cells, $link);
    } else {
        $table->updateRow($rowId, $cells, $link);
    }

    return ['listid' => $tableId, 'rowid' => $rowId];
}

function updateContact(
    string $navn,
    string $email,
    string $adresse,
    string $land,
    string $post,
    string $city,
    string $tlf1,
    string $tlf2,
    bool $kartotek,
    string $interests,
    int $id = null
): bool {
    if (!$id) {
        $contact = new Contact([
            'name'       => $navn,
            'email'      => $email,
            'address'    => $adresse,
            'country'    => $land,
            'postcode'   => $post,
            'city'       => $city,
            'phone1'     => $tlf1,
            'phone2'     => $tlf2,
            'newsletter' => $kartotek,
            'interests'  => $interests,
            'ip'         => request()->getClientIp(),
        ]);
        $contact->save();

        return true;
    }

    $contact = ORM::getOne(Contact::class, $id);
    assert($contact instanceof Contact);
    $contact->setName($navn)
        ->setEmail($email)
        ->setAddress($adresse)
        ->setCountry($land)
        ->setPostcode($post)
        ->setCity($city)
        ->setPhone1($tlf1)
        ->setPhone2($tlf2)
        ->setNewsletter($kartotek)
        ->setInterests($interests)
        ->setIp(request()->getClientIp())
        ->save();

    return true;
}

function deleteContact(int $id): string
{
    db()->query('DELETE FROM `email` WHERE `id` = ' . $id);

    return 'contact' . $id;
}

function makeNewList(string $navn): array
{
    db()->query('INSERT INTO `tablesort` (`navn`) VALUES (\'' . db()->esc($navn) . '\')');

    return ['id' => db()->insert_id, 'name' => $navn];
}

function saveListOrder(int $id, string $navn, string $text): bool
{
    db()->query(
        'UPDATE `tablesort` SET navn = \'' . db()->esc($navn) . '\', text = \'' . db()->esc($text) . '\'
        WHERE id = ' . $id
    );

    return true;
}

function get_subscriptions_with_bad_emails(): string
{
    $contacts = ORM::getByQuery(Contact::class, "SELECT * FROM `email` WHERE `email` != ''");
    foreach ($contacts as $key => $contact) {
        assert($contact instanceof Contact);
        if (!$contact->isEmailValide()) {
            unset($contacts[$key]);
        }
    }

    return Render::render('partial-admin-subscriptions_with_bad_emails', ['contacts' => $contacts]);
}

function get_looping_cats(): string
{
    $html = '';
    $categories = ORM::getByQuery(Category::class, 'SELECT * FROM `kat` WHERE bind != 0 AND bind != -1');
    foreach ($categories as $category) {
        assert($category instanceof Category);
        $branchIds = [$category->getId() => true];
        while ($category = $category->getParent()) {
            if (isset($branchIds[$category->getId()])) {
                $html .= '<a href="?side=redigerkat&id=' . $category->getId() . '">' . $category->getId()
                    . ': ' . $category->getTitle() . '</a><br />';
                break;
            }
            $branchIds[$category->getId()] = true;
        }
    }
    if ($html) {
        $html = '<b>' . _('The following categories have circular references:') . '</b><br />' . $html;
    }

    return $html;
}

function check_file_names(): string
{
    $html = '';
    $error = db()->fetchArray(
        '
        SELECT path FROM `files`
        WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+[^/]+$\'
        ORDER BY `path` ASC
        '
    );
    if ($error) {
        if (db()->affected_rows > 1) {
            $html .= '<br /><b>'
                . sprintf(_('The following %d files must be renamed:'), db()->affected_rows)
                . '</b><br /><a onclick="explorer(\'\',\'\');">';
        } else {
            $html .= '<br /><br /><a onclick="explorer(\'\',\'\');">';
        }
        foreach ($error as $value) {
            $html .= $value['path'] . '<br />';
        }
        $html .= '</a>';
    }
    if ($html) {
        $html = '<b>' . _('The following files must be renamed') . '</b><br />' . $html;
    }

    return $html;
}

function check_file_paths(): string
{
    $html = '';
    $error = db()->fetchArray(
        '
        SELECT path FROM `files`
        WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+.*[/]+\'
        ORDER BY `path` ASC
        '
    );
    if ($error) {
        if (db()->affected_rows > 1) {
            $html .= '<br /><b>'
                . sprintf(_('The following %d files are in a folder that needs to be renamed:'), db()->affected_rows)
                . '</b><br /><a onclick="explorer(\'\',\'\');">';
        } else {
            $html .= '<br /><br /><a onclick="explorer(\'\',\'\');">';
        }
        //TODO only repport one error per folder
        foreach ($error as $value) {
            $html .= $value['path'] . '<br />';
        }
        $html .= '</a>';
    }
    if ($html) {
        $html = '<b>' . _('The following folders must be renamed') . '</b><br />' . $html;
    }

    return $html;
}

function get_size_of_files(): int
{
    $files = db()->fetchOne('SELECT sum(`size`) / 1024 / 1024 AS `filesize` FROM `files`');

    return $files['filesize'] ?? 0;
}

function get_mail_size(): int
{
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

function get_db_size(): float
{
    $tabels = db()->fetchArray('SHOW TABLE STATUS');
    $dbsize = 0;
    foreach ($tabels as $tabel) {
        $dbsize += $tabel['Data_length'];
        $dbsize += $tabel['Index_length'];
    }

    return $dbsize / 1024 / 1024;
}

function get_orphan_pages(): string
{
    $html = '';
    /** @var Page */
    $pages = ORM::getByQuery(Page::class, 'SELECT * FROM `sider` WHERE `id` NOT IN(SELECT `side` FROM `bind`)');
    foreach ($pages as $page) {
        assert($page instanceof Page);
        $html .= '<a href="?side=redigerside&amp;id=' . $page->getId() . '">' . $page->getId()
            . ': ' . $page->getTitle() . '</a><br />';
    }

    if ($html) {
        $html = '<b>' . _('The following pages have no binding') . '</b><br />' . $html;
    }

    return $html;
}

function get_pages_with_mismatch_bindings(): string
{
    $html = '';

    // Map out active / inactive
    $categoryActiveMaps = [[0], [-1]];
    $categories = ORM::getByQuery(Category::class, 'SELECT * FROM `kat`');
    foreach ($categories as $category) {
        assert($category instanceof Category);
        $categoryActiveMaps[(int) $category->isInactive()][] = $category->getId();
    }

    $pages = ORM::getByQuery(
        Page::class,
        '
        SELECT * FROM `sider`
        WHERE EXISTS (
            SELECT * FROM bind
            WHERE side = sider.id
            AND kat IN (' . implode(',', $categoryActiveMaps[0]) . ')
        )
        AND EXISTS (
            SELECT * FROM bind
            WHERE side = sider.id
            AND kat IN (' . implode(',', $categoryActiveMaps[1]) . ')
        )
        ORDER BY id
        '
    );
    if ($pages) {
        $html .= '<b>' . _('The following pages are both active and inactive') . '</b><br />';
        foreach ($pages as $page) {
            assert($page instanceof Page);
            $html .= '<a href="?side=redigerside&amp;id=' . $page->getId() . '">' . $page->getId() . ': '
                . $page->getTitle() . '</a><br />';
        }
    }

    //Add active pages that has a list that links to this page
    $pages = db()->fetchArray(
        '
        SELECT `sider`.*, `lists`.`page_id`
        FROM `list_rows`
        JOIN `lists` ON `list_rows`.`list_id` = `lists`.`id`
        JOIN `sider` ON `list_rows`.`link` = `sider`.id
        WHERE EXISTS (
            SELECT * FROM bind
            WHERE side = `lists`.`page_id`
            AND kat IN (' . implode(',', $categoryActiveMaps[0]) . ')
        )
        AND EXISTS (
            SELECT * FROM bind
            WHERE side = sider.id
            AND kat IN (' . implode(',', $categoryActiveMaps[1]) . ')
        )
        ORDER BY `lists`.`page_id`
        '
    );
    if ($pages) {
        $html .= '<b>' . _('The following inactive pages appears in list on active pages') . '</b><br />';
        foreach ($pages as $page) {
            $listPage = ORM::getOne(Page::class, $page['page_id']);
            assert($listPage instanceof Page);
            $page = new Page(Page::mapFromDB($page));
            $html .= '<a href="?side=redigerside&amp;id=' . $listPage->getId() . '">' . $listPage->getId() . ': '
                . $listPage->getTitle() . '</a> -&gt; <a href="?side=redigerside&amp;id=' . $page->getId() . '">'
                . $page->getId() . ': ' . $page->getTitle() . '</a><br />';
        }
    }

    return $html;
}

/**
 * @return string[]|true
 */
function save_ny_kat(string $navn, int $kat, int $vis, string $email, string $icon = null)
{
    if (!$navn) {
        return ['error' => _('You must enter a name and choose a location for the new category.')];
    }

    $category = new Category([
        'title'             => $navn,
        'parent_id'         => $kat,
        'icon_path'         => $icon,
        'render_mode'       => $vis,
        'email'             => $email,
        'weighted_children' => 0,
        'weight'            => 0,
    ]);
    $category->save();

    return true;
}

/**
 * @return string[]|int[]
 */
function savekrav(string $navn, string $html, int $id = null): array
{
    $html = purifyHTML($html);

    if ('' === $navn || '' === $html) {
        return ['error' => _('You must enter a name and a text of the requirement.')];
    }

    $requirement = new Requirement(['title' => $navn, 'html' => $html]);
    if (null !== $id) {
        $requirement = ORM::getOne(Requirement::class, $id);
    }
    assert($requirement instanceof Requirement);
    $requirement->setHtml($html)->setTitle($navn)->save();

    return ['id' => $requirement->getId()];
}

function sogogerstat(string $sog, string $erstat): int
{
    db()->query('UPDATE sider SET text = REPLACE(text,\'' . db()->esc($sog) . '\',\'' . db()->esc($erstat) . '\')');

    return db()->affected_rows;
}

function updatemaerke(?int $id, string $navn, string $link = '', string $ico = null): array
{
    if (!$navn) {
        return ['error' => _('You must enter a name.')];
    }

    $brand = new Brand(['title' => $navn, 'link' => $link, 'icon_path' => $ico]);
    if (null !== $id) {
        $brand = ORM::getOne(Brand::class, $id);
    }
    assert($brand instanceof Brand);
    $brand->setLink($link)
        ->setIconPath($ico)
        ->setTitle($navn)
        ->save();

    return ['id' => $brand->getId()];
}

/**
 * @return string[]
 */
function sletmaerke(int $id): array
{
    db()->query('DELETE FROM `maerke` WHERE `id` = ' . $id);

    return ['node' => 'maerke' . $id];
}

/**
 * @return string[]
 */
function sletkrav(int $id): array
{
    db()->query('DELETE FROM `krav` WHERE `id` = ' . $id);

    return ['id' => 'krav' . $id];
}

/**
 * @return string[]
 */
function removeAccessory(int $pageId, int $accessoryId): array
{
    $accessory = ORM::getOne(Page::class, $accessoryId);
    assert($accessory instanceof Page);
    $page = ORM::getOne(Page::class, $pageId);
    assert($page instanceof Page);
    $page->removeAccessory($accessory);

    return ['id' => 'accessory' . $accessory->getId()];
}

function addAccessory(int $pageId, int $accessoryId): array
{
    $accessory = ORM::getOne(Page::class, $accessoryId);
    assert($accessory instanceof Page);
    $page = ORM::getOne(Page::class, $pageId);
    assert($page instanceof Page);
    $page->addAccessory($accessory);

    return ['pageId' => $page->getId(), 'accessoryId' => $accessory->getId(), 'title' => $accessory->getTitle()];
}

/**
 * @return string[]
 */
function sletkat(int $id): array
{
    ORM::getOne(Category::class, $id)->delete();

    return ['id' => 'kat' . $id];
}

function movekat(int $id, int $parentId): array
{
    $category = ORM::getOne(Category::class, $id);
    assert($category instanceof Category);
    $category->setParentId($parentId)->save();

    return ['id' => 'kat' . $id, 'update' => $parentId];
}

/**
 * @return string[]
 */
function renamekat(int $id, string $title): array
{
    $category = ORM::getOne(Category::class, $id);
    assert($category instanceof Category);
    $category->setTitle($title)->save();

    return ['id' => 'kat' . $id, 'name' => $title];
}

function sletbind(int $pageId, int $categoryId): array
{
    /** @var Page */
    $page = ORM::getOne(Page::class, $pageId);
    assert($page instanceof Page);

    /** @var Category */
    $category = ORM::getOne(Category::class, $categoryId);
    if (!$category) {
        return ['error' => _('The category doesn\'t exist.')];
    }
    assert($category instanceof Category);

    $result = ['pageId' => $page->getId(), 'deleted' => [], 'added' => null];
    if (($category->getId() === -1 && 1 === count($page->getCategories()))
        || !$page->isInCategory($category)
    ) {
        return $result;
    }

    if (1 === count($page->getCategories())) {
        $inactiveCategory = ORM::getOne(Category::class, -1);
        assert($inactiveCategory instanceof Category);
        $page->addToCategory($inactiveCategory);
        $result['added'] = ['categoryId' => -1, 'path' => '/' . _('Inactive') . '/'];
    }

    $page->removeFromCategory($category);
    $result['deleted'][] = $category->getId();

    return $result;
}

function bind(int $pageId, int $categoryId): array
{
    /** @var Page */
    $page = ORM::getOne(Page::class, $pageId);
    assert($page instanceof Page);

    /** @var Category */
    $category = ORM::getOne(Category::class, $categoryId);
    if (!$category) {
        return ['error' => _('The category doesn\'t exist.')];
    }
    assert($category instanceof Category);

    $result = ['pageId' => $page->getId(), 'deleted' => [], 'added' => null];

    if ($page->isInCategory($category)) {
        return $result;
    }

    $page->addToCategory($category);
    $result['added'] = ['categoryId' => $category->getId(), 'path' => $category->getPath()];

    $rootCategory = $category->getRoot();
    foreach ($page->getCategories() as $node) {
        if ($node->getRoot() === $rootCategory) {
            continue;
        }

        $page->removeFromCategory($node);
        $result['deleted'][] = $node->getId();
    }

    return $result;
}

/**
 * @return string[]|true
 */
function updateKat(
    int $id,
    string $navn,
    int $vis,
    string $email,
    bool $customSortSubs,
    string $subsorder,
    int $parentId = null,
    string $icon = null
) {
    $category = ORM::getOne(Category::class, $id);
    assert($category instanceof Category);
    if ($category->getParent() && null === $parentId) {
        return ['error' => _('You must select a parent category')];
    }

    if (null !== $parentId) {
        $parent = ORM::getOne(Category::class, $parentId);
        assert($parent instanceof Category);
        foreach ($parent->getBranch() as $node) {
            if ($node->getId() === $category->getId()) {
                return ['error' => _('The category can not be placed under itself.')];
            }
        }
        $category->setParentId($parentId);
    }

    //Set the order of the subs
    if ($customSortSubs) {
        updateKatOrder($subsorder);
    }

    //Update kat
    $category->setRenderMode($vis)
        ->setEmail($email)
        ->setWeightedChildren($customSortSubs)
        ->setIconPath($icon)
        ->setTitle($navn)
        ->save();

    return true;
}

function updateKatOrder(string $order): void
{
    foreach (explode(',', $order) as $weight => $id) {
        $category = ORM::getOne(Category::class, $id);
        assert($category instanceof Category);
        $category->setWeight($weight)->save();
    }
}

function updateSpecial(int $id, string $html, string $title = ''): bool
{
    $html = purifyHTML($html);

    $page = ORM::getOne(CustomPage::class, $id);
    assert($page instanceof CustomPage);
    if ($title) {
        $page->setTitle($title);
    }
    $page->setHtml($html)->save();

    if (1 === $id) {
        $category = ORM::getOne(Category::class, 0);
        assert($category instanceof Category);
        $category->setTitle($title)->save();
    }

    return true;
}

/**
 * Delete a page and all it's relations from the database.
 *
 * @return string[]
 */
function sletSide(int $pageId): array
{
    $page = ORM::getOne(Page::class, $pageId);
    if ($page) {
        $page->delete();
    }

    return ['class' => 'side' . $pageId];
}

function copytonew(int $id): int
{
    $faktura = db()->fetchOne('SELECT * FROM `fakturas` WHERE `id` = ' . $id);

    unset(
        $faktura['id'],
        $faktura['status'],
        $faktura['date'],
        $faktura['paydate'],
        $faktura['sendt'],
        $faktura['transferred']
    );
    $faktura['clerk'] = curentUser()->getFullName();

    $sql = 'INSERT INTO `fakturas` SET';
    foreach ($faktura as $key => $value) {
        $sql .= ' `' . addcslashes($key, '`\\') . "` = '" . db()->esc($value) . "',";
    }
    $sql .= ' `date` = NOW();';

    db()->query($sql);

    return db()->insert_id;
}

/**
 * @return string[]
 */
function save(int $id, string $action, array $updates): array
{
    /** @var Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    assert($invoice instanceof Invoice);

    invoiceBasicUpdate($invoice, $action, $updates);

    if ('email' === $action) {
        try {
            sendInvoice($invoice);
        } catch (Exception $exception) {
            return ['error' => $exception->getMessage()];
        }
    }

    return ['type' => $action, 'status' => $invoice->getStatus()];
}

function invoiceBasicUpdate(Invoice $invoice, string $action, array $updates): void
{
    $status = $invoice->getStatus();

    if ('new' === $invoice->getStatus()) {
        if ('lock' === $action) {
            $status = 'locked';
        }
        $invoice->setTimeStamp(strtotime($updates['date']));
        $invoice->setShipping($updates['shipping']);
        $invoice->setAmount($updates['amount']);
        $invoice->setVat($updates['vat']);
        $invoice->setPreVat($updates['preVat']);
        $invoice->setIref($updates['iref']);
        $invoice->setEref($updates['eref']);
        $invoice->setName($updates['name']);
        $invoice->setAttn($updates['attn']);
        $invoice->setAddress($updates['address']);
        $invoice->setPostbox($updates['postbox']);
        $invoice->setPostcode($updates['postcode']);
        $invoice->setCity($updates['city']);
        $invoice->setCountry($updates['country']);
        $invoice->setEmail($updates['email']);
        $invoice->setPhone1($updates['phone1']);
        $invoice->setPhone2($updates['phone2']);
        $invoice->setHasShippingAddress($updates['hasShippingAddress']);
        if ($updates['hasShippingAddress']) {
            $invoice->setShippingPhone($updates['shippingPhone']);
            $invoice->setShippingName($updates['shippingName']);
            $invoice->setShippingAttn($updates['shippingAttn']);
            $invoice->setShippingAddress($updates['shippingAddress']);
            $invoice->setShippingAddress2($updates['shippingAddress2']);
            $invoice->setShippingPostbox($updates['shippingPostbox']);
            $invoice->setShippingPostcode($updates['shippingPostcode']);
            $invoice->setShippingCity($updates['shippingCity']);
            $invoice->setShippingCountry($updates['shippingCountry']);
        }
        $invoice->setItemData(json_encode($updates['lines']));
    }

    if (isset($updates['note'])) {
        if ('new' !== $invoice->getStatus()) {
            $updates['note'] = trim($invoice->getNote() . "\n" . $updates['note']);
        }
        $invoice->setNote($updates['note']);
    }

    if (!$invoice->getDepartment() && 1 === count(Config::get('emails'))) {
        $email = first(Config::get('emails'))['address'];
        $invoice->setDepartment($email);
    } elseif (!empty($updates['department'])) {
        $invoice->setDepartment($updates['department']);
    }

    if (!$invoice->getClerk()) {
        $invoice->setClerk(curentUser()->getFullName());
    }

    if (('giro' === $action || 'cash' === $action)
        && in_array($invoice->getStatus(), ['new', 'locked', 'rejected'], true)
    ) {
        $status = $action;
    }

    if (!$invoice->isFinalized()) {
        if (in_array($action, ['cancel', 'giro', 'cash'], true)
            || ('lock' === $action && 'locked' !== $invoice->getStatus())
        ) {
            $invoice->setTimeStampPay(strtotime($updates['paydate'] ?? '') ?: time());
        }

        if ('cancel' === $action) {
            if ('pbsok' === $invoice->getStatus() && !annul($invoice->getId())) {
                throw new Exception(_('Failed to cancel payment!'));
            }
            $status = 'canceled';
        }

        if (isset($updates['clerk']) && curentUser()->hasAccess(User::ADMINISTRATOR)) {
            $invoice->setClerk($updates['clerk']);
        }
    }

    $invoice->setStatus($status)->save();
}

function sendInvoice(Invoice $invoice): void
{
    if (!$invoice->hasValidEmail()) {
        throw new Exception(_('Email is not valid!'));
    }

    if (!$invoice->getDepartment() && 1 === count(Config::get('emails'))) {
        $email = first(Config::get('emails'))['address'];
        $invoice->setDepartment($email);
    } elseif (!$invoice->getDepartment()) {
        throw new Exception(_('You have not selected a sender!'));
    }
    if ($invoice->getAmount() < 0.01) {
        throw new Exception(_('The invoice must be of at at least 0.01 krone!'));
    }

    $subject = _('Online payment for ') . Config::get('site_name');
    $emailTemplate = 'email/invoice';
    if ($invoice->isSent()) {
        $subject = 'Elektronisk faktura vedr. ordre';
        $emailTemplate = 'email/invoice-reminder';
    }

    $emailBody = Render::render(
        $emailTemplate,
        [
            'invoice' => $invoice,
            'siteName' => Config::get('site_name'),
            'address' => Config::get('address'),
            'postcode' => Config::get('postcode'),
            'city' => Config::get('city'),
            'phone' => Config::get('phone'),
        ]
    );

    $success = sendEmails(
        $subject,
        $emailBody,
        $invoice->getDepartment(),
        '',
        $invoice->getEmail(),
        $invoice->getName(),
        false
    );
    if (!$success) {
        throw new Exception(_('Unable to sendt e-mail!'));
    }

    if ('new' === $invoice->getStatus()) {
        $invoice->setStatus('locked');
    }

    $invoice->setSent(true)
        ->save();
}

/**
 * @return string[]
 */
function sendReminder(int $id): array
{
    /** @var Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    assert($invoice instanceof Invoice);
    try {
        sendInvoice($invoice);
    } catch (Exception $exception) {
        return ['error' => $exception->getMessage()];
    }

    return ['error' => _('A Reminder was sent to the customer.')];
}

/**
 * @return string[]|true
 */
function pbsconfirm(int $id)
{
    /** @var Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    assert($invoice instanceof Invoice);

    try {
        $epaymentService = new EpaymentAdminService(Config::get('pbsid'), Config::get('pbspwd'));
        $epayment = $epaymentService->getPayment(Config::get('pbsfix') . $invoice->getId());
        if (!$epayment->confirm()) {
            return ['error' => _('An error occurred')];
        }
    } catch (SoapFault $e) {
        return ['error' => $e->getMessage()];
    }

    $invoice->setStatus('accepted')
        ->setTimeStampPay(time())
        ->save();

    return true;
}

/**
 * @return string[]|true
 */
function annul(int $id)
{
    /** @var Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);
    assert($invoice instanceof Invoice);

    try {
        $epaymentService = new EpaymentAdminService(Config::get('pbsid'), Config::get('pbspwd'));
        $epayment = $epaymentService->getPayment(Config::get('pbsfix') . $invoice->getId());
        if (!$epayment->annul()) {
            return ['error' => _('An error occurred')];
        }
    } catch (SoapFault $e) {
        return ['error' => $e->getMessage()];
    }

    if ('pbsok' === $invoice->getStatus()) {
        $invoice->setStatus('rejected')->save();
    }

    return true;
}

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
    $pathinfo = pathinfo($path);
    if (!empty($output['type']) && empty($output['overwrite'])) {
        if (empty($output['filename'])) {
            $output['filename'] = $pathinfo['filename'];
        }

        $outputPath = $pathinfo['dirname'] . '/' . $output['filename'];
        $outputPath .= !empty($output['type']) && 'png' === $output['type'] ? '.png' : '.jpg';

        if (!empty($output['type']) && empty($output['force']) && file_exists($outputPath)) {
            return [
                'yesno' => _(
                    'A file with the same name already exists.' . "\n"
                    . 'Would you like to replace the existing file?'
                ),
                'filename' => $output['filename'],
            ];
        }
    }

    $image = new AJenbo\Image($path);
    $orginalWidth = $image->getWidth();
    $orginalHeight = $image->getHeight();

    // Crop image
    $cropW = $cropW ?: $image->getWidth();
    $cropH = $cropH ?: $image->getHeight();
    $cropW = min($image->getWidth(), $cropW);
    $cropH = min($image->getHeight(), $cropH);
    $cropX = $cropW !== $image->getWidth() ? $cropX : 0;
    $cropY = $cropH !== $image->getHeight() ? $cropY : 0;
    $image->crop($cropX, $cropY, $cropW, $cropH);

    // Trim image whitespace
    $imageContent = $image->findContent();

    $maxW = min($maxW, $imageContent['width']);
    $maxH = min($maxH, $imageContent['height']);

    if (empty($output['type'])
        && !$flip
        && !$rotate
        && $maxW === $orginalWidth
        && $maxH === $orginalHeight
        && 0 === mb_strpos($path, _ROOT_)
    ) {
        redirect(mb_substr($path, mb_strlen(_ROOT_)), Response::HTTP_MOVED_PERMANENTLY);
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
        $image->flip(1 === $flip ? 'x' : 'y');
    }

    $image->rotate($rotate);

    // Output image or save
    $mimeType = 'image/jpeg';
    $type = 'jpeg';
    if (empty($output['type'])) {
        $mimeType = get_mime_type($path);
        if ('image/png' !== $mimeType) {
            $mimeType = 'image/jpeg';
        }
        header('Content-Type: ' . $mimeType);
        $image->save(null, 'image/png' === $mimeType ? 'png' : 'jpeg');
        die();
    } elseif ('png' === $output['type']) {
        $mimeType = 'image/png';
        $type = 'png';
    }
    $image->save($outputPath, $type);

    $width = $image->getWidth();
    $height = $image->getHeight();
    unset($image);

    $file = null;
    $localFile = $outputPath;
    if (0 === mb_strpos($outputPath, _ROOT_)) {
        $localFile = mb_substr($outputPath, mb_strlen(_ROOT_));
        $file = File::getByPath($localFile);
        if ($file && $output['filename'] === $pathinfo['filename'] && $outputPath !== $path) {
            $file->delete();
            $file = null;
        }
        if (!$file) {
            $file = File::fromPath($localFile);
        }

        $file->setMime($mimeType)
            ->setWidth($width)
            ->setHeight($height)
            ->setSize(filesize($outputPath))
            ->save();
    }

    return ['id' => $file ? $file->getId() : null, 'path' => $localFile, 'width' => $width, 'height' => $height];
}
