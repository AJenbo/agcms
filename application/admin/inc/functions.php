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
            Render::render('email-newsletter', $data),
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

    db()->query('UPDATE `newsmails` SET `sendt` = 1 WHERE `id` = ' . (int) $id);

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

/**
 * @return string[]
 */
function katspath(int $id): array
{
    $category = ORM::getOne(Category::class, $id);
    assert($category instanceof Category);

    return [
        'id'   => 'katsheader',
        'html' => _('Select location:') . ' ' . $category->getPath(),
    ];
}

/**
 * @return int[]
 */
function getOpenCategories(int $selectedId = null): array
{
    $openCategories = explode('<', request()->cookies->get('openkat', ''));
    $openCategories = array_map('intval', $openCategories);

    if (null !== $selectedId) {
        $category = ORM::getOne(Category::class, $selectedId);
        if ($category) {
            assert($category instanceof Category);
            foreach ($category->getBranch() as $category) {
                $openCategories[] = $category->getId();
            }
        }
    }

    return $openCategories;
}

function getSiteTreeData(string $inputType = '', int $selectedId = null): array
{
    $category = null;
    if (null !== $selectedId) {
        $category = ORM::getOne(Category::class, $selectedId);
    }

    return [
        'selectedCategory' => $category,
        'openCategories'   => getOpenCategories($selectedId),
        'includePages'     => (!$inputType || 'pages' === $inputType),
        'inputType'        => $inputType,
        'node'             => ['children' => ORM::getByQuery(Category::class, 'SELECT * FROM kat WHERE bind IS NULL')],
        'customPages'      => !$inputType ? ORM::getByQuery(CustomPage::class, 'SELECT * FROM `special` WHERE `id` > 1 ORDER BY `navn`') : [],
    ];
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
 * Check if file is in use.
 */
function isinuse(string $path): bool
{
    return (bool) db()->fetchOne(
        "
        (
            SELECT id FROM `sider`
            WHERE `text` LIKE '%$path%' OR `beskrivelse` LIKE '%$path%' OR `billed`
            LIKE '$path' LIMIT 1
        )
        UNION (
            SELECT id FROM `template`
            WHERE `text` LIKE '%$path%' OR `beskrivelse` LIKE '%$path%' OR `billed`
            LIKE '$path' LIMIT 1
        )
        UNION (SELECT id FROM `special` WHERE `text` LIKE '%$path%' LIMIT 1)
        UNION (SELECT id FROM `krav` WHERE `text` LIKE '%$path%' LIMIT 1)
        UNION (SELECT id FROM `maerke` WHERE `ico` LIKE '$path' LIMIT 1)
        UNION (SELECT id FROM `list_rows` WHERE `cells` LIKE '%$path%' LIMIT 1)
        UNION (SELECT id FROM `kat` WHERE `navn` LIKE '%$path%' OR `icon` LIKE '$path' LIMIT 1)
        "
    );
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

/**
 * Takes a string and changes it to comply with file name restrictions in windows, linux, mac and urls (UTF8)
 * .|"'´`:%=#&\/+?*<>{}-_.
 */
function genfilename(string $filename): string
{
    $search = ['/[.&?\/:*"\'´`<>{}|%\s-_=+#\\\\]+/u', '/^\s+|\s+$/u', '/\s+/u'];
    $replace = [' ', '', '-'];

    return mb_strtolower(preg_replace($search, $replace, $filename), 'UTF-8');
}

/**
 * return true for directorys and false for every thing else.
 */
function is_dirs(string $path): bool
{
    if (is_file(_ROOT_ . $path)
        || '.' == $path
        || '..' == $path
    ) {
        return false;
    }

    return true;
}

/**
 * return list of folders in a folder.
 *
 * @return array[]
 */
function getSubDirs(string $path): array
{
    $dirs = [];
    $iterator = new DirectoryIterator(_ROOT_ . $path);
    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isDot() || !$fileinfo->isDir()) {
            continue;
        }
        $dirs[] = $fileinfo->getFilename();
    }

    natcasesort($dirs);
    $dirs = array_values($dirs);

    foreach ($dirs as $index => $dir) {
        $dirs[$index] = formatDir($path . '/' . $dir, $dir);
    }

    return $dirs;
}

function hasSubsDirs(string $path): bool
{
    $iterator = new DirectoryIterator(_ROOT_ . $path);
    foreach ($iterator as $fileinfo) {
        if (!$fileinfo->isDot() && $fileinfo->isDir()) {
            return true;
        }
    }

    return false;
}

/**
 * @return array[]
 */
function getRootDirs(): array
{
    $dirs = [];
    foreach (['/images' => _('Images'), '/files' => _('Files')] as $path => $name) {
        $dirs[] = formatDir($path, $name);
    }

    return $dirs;
}

function formatDir(string $path, string $name): array
{
    $subs = [];
    if (0 === mb_strpos(request()->cookies->get('admin_dir'), $path)) {
        $subs = getSubDirs($path);
        $hassubs = (bool) $subs;
    } else {
        $hassubs = hasSubsDirs($path);
    }

    return [
        'id'      => preg_replace('#/#u', '.', $path),
        'path'    => $path,
        'name'    => $name,
        'hassubs' => $hassubs,
        'subs'    => $subs,
    ];
}

//TODO document type doesn't allow element "input" here; missing one of "p", "h1", "h2", "h3", "h4", "h5", "h6", "div", "pre", "address", "fieldset", "ins", "del" start-tag.
/**
 * Display a list of directorys for the explorer.
 *
 * @return string[]
 */
function listdirs(string $path, bool $move = false): array
{
    $html = Render::render(
        'partial-admin-listDirs',
        [
            'dirs' => getSubDirs($path),
            'move' => $move,
        ]
    );

    return ['id' => $path, 'html' => $html];
}

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

    db()->query('DELETE FROM `users` WHERE `id` = ' . (int) $id);

    return true;
}

function fileExists(string $dir, string $filename, string $type = ''): array
{
    $pathinfo = pathinfo($filename);
    $filePath = _ROOT_ . $dir . '/' . genfilename($pathinfo['filename']);

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

/**
 * display a list of files in the selected folder.
 *
 * @return string[]
 */
function showfiles(string $dir): array
{
    $html = '';
    $javascript = '';

    $files = scandir(_ROOT_ . $dir);
    natcasesort($files);

    foreach ($files as $fileName) {
        if ('.' === mb_substr($fileName, 0, 1) || is_dir(_ROOT_ . $dir . '/' . $fileName)) {
            continue;
        }

        $filePath = $dir . '/' . $fileName;
        $file = File::getByPath($filePath);
        if (!$file) {
            $file = File::fromPath($filePath)->save();
        }

        $html .= filehtml($file);
        //TODO reduce net to javascript
        $javascript .= filejavascript($file);
    }

    return ['id' => 'files', 'html' => $html, 'javascript' => $javascript];
}

function filejavascript(File $file): string
{
    $data = [
        'id'          => $file->getId(),
        'path'        => $file->getPath(),
        'mime'        => $file->getMime(),
        'name'        => pathinfo($file->getPath(), PATHINFO_FILENAME),
        'width'       => $file->getWidth(),
        'height'      => $file->getHeight(),
        'description' => $file->getDescription(),
    ];

    return 'files[' . $file->getId() . '] = new file(' . json_encode($data) . ');';
}

function filehtml(File $file): string
{
    $html = '';

    $menuType = 'filetile';
    $type = explode('/', $file->getMime());
    $type = array_shift($type);
    if (in_array($file->getMime(), ['image/gif', 'image/jpeg', 'image/png'], true)) {
        $menuType = 'imagetile';
    }
    $html .= '<div id="tilebox' . $file->getId() . '" class="' . $menuType . '"><div class="image"';

    $returnType = request()->get('return');
    if ('ckeditor' === $returnType) {
        $html .= ' onclick="files[' . $file->getId() . '].addToEditor()"';
    } elseif ('thb' === $returnType && in_array($file->getMime(), ['image/gif', 'image/jpeg', 'image/png'], true)) {
        if ($file->getWidth() <= Config::get('thumb_width')
            && $file->getHeight() <= Config::get('thumb_height')
        ) {
            $html .= ' onclick="insertThumbnail(' . $file->getId() . ')"';
        } else {
            $html .= ' onclick="openImageThumbnail(' . $file->getId() . ')"';
        }
    } else {
        $html .= ' onclick="files[' . $file->getId() . '].openfile();"';
    }

    $html .= '> <img src="';

    $type = explode('/', $file->getMime());
    $type = array_shift($type);
    switch ($file->getMime()) {
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
    }

    switch ($type) {
        case 'image-native':
            $html .= '/admin/image.php?path=' . rawurlencode($file->getPath()) . '&amp;maxW=128&amp;maxH=96';
            break;
        case 'pdf':
        case 'image':
        case 'video':
        case 'audio':
        case 'text':
        case 'sys':
        case 'zip':
            $html .= '/admin/images/file-' . $type . '.gif';
            break;
        default:
            $html .= '/admin/images/file-bin.gif';
            break;
    }

    $pathinfo = pathinfo($file->getPath());
    $html .= '" alt="" title="" /> </div><div ondblclick="showfilename(' . $file->getId() . ')" class="navn" id="navn'
    . $file->getId() . 'div" title="' . $pathinfo['filename'] . '"> ' . $pathinfo['filename']
    . '</div><form action="" method="get" onsubmit="document.getElementById(\'files\').focus();return false;" style="display:none" id="navn'
    . $file->getId() . 'form"><p><input onblur="renamefile(\'' . $file->getId() . '\');" maxlength="'
    . (251 - mb_strlen($pathinfo['dirname'], 'UTF-8')) . '" value="' . $pathinfo['filename']
    . '" name="" /></p></form></div>';

    return $html;
}

function makedir(string $adminDir, string $name): array
{
    $name = genfilename($name);
    if (is_dir(_ROOT_ . $adminDir . '/' . $name)) {
        return ['error' => _('A file or folder with the same name already exists.')];
    }

    if (!is_dir(_ROOT_ . $adminDir)
        || !mkdir(_ROOT_ . $adminDir . '/' . $name, 0771)
    ) {
        return ['error' => _('Could not create folder, you may not have sufficient rights to this folder.')];
    }

    return ['error' => false];
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

    $filename = genfilename($filename);

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

/**
 * Rename or relocate a file/directory.
 *
 * return bool False if one or more files coud not be deleted
 */
function deltree(string $dir): bool
{
    $success = true;

    $nodes = scandir(_ROOT_ . $dir);
    foreach ($nodes as $node) {
        if ('.' === $node || '..' === $node) {
            continue;
        }

        if (is_dir(_ROOT_ . $dir . '/' . $node)) {
            $success = $success && deltree($dir . '/' . $node);
            continue;
        }

        if (db()->fetchOne("SELECT id FROM `sider` WHERE `navn` LIKE '%" . $dir . '/' . $node . "%' OR `text` LIKE '%" . $dir . '/' . $node . "%' OR `beskrivelse` LIKE '%" . $dir . '/' . $node . "%' OR `billed` LIKE '%" . $dir . '/' . $node . "%'")
            || db()->fetchOne("SELECT id FROM `template` WHERE `navn` LIKE '%" . $dir . '/' . $node . "%' OR `text` LIKE '%" . $dir . '/' . $node . "%' OR `beskrivelse` LIKE '%" . $dir . '/' . $node . "%' OR `billed` LIKE '%" . $dir . '/' . $node . "%'")
            || db()->fetchOne("SELECT id FROM `special` WHERE `text` LIKE '%" . $dir . '/' . $node . "%'")
            || db()->fetchOne("SELECT id FROM `krav` WHERE `text` LIKE '%" . $dir . '/' . $node . "%'")
            || db()->fetchOne("SELECT id FROM `maerke` WHERE `ico` LIKE '%" . $dir . '/' . $node . "%'")
            || db()->fetchOne("SELECT id FROM `list_rows` WHERE `cells` LIKE '%" . $dir . '/' . $node . "%'")
            || db()->fetchOne("SELECT id FROM `kat` WHERE `navn` LIKE '%" . $dir . '/' . $node . "%' OR `icon` LIKE '%" . $dir . '/' . $node . "%'")
        ) {
            $success = false;
            continue;
        }

        unlink(_ROOT_ . $dir . '/' . $node);
    }
    unlink(_ROOT_ . $dir);

    return $success;
}

/**
 * @return string[]|true
 */
function deletefolder(string $dir)
{
    if (!deltree($dir)) {
        return ['error' => _('A file could not be deleted because it is used on a site.')];
    }

    return true;
}

/**
 * @return string[]
 */
function searchfiles(string $qpath, string $qalt, string $qmime): array
{
    $qpath = db()->escapeWildcards(db()->esc($qpath));
    $qalt = db()->escapeWildcards(db()->esc($qalt));

    $sqlMime = '';
    switch ($qmime) {
        case 'image':
            $sqlMime = "(mime = 'image/jpeg' OR mime = 'image/png' OR mime = 'image/gif' OR mime = 'image/vnd.wap.wbmp')";
            break;
        case 'imagefile':
            $sqlMime = "(mime = 'application/postscript' OR mime = 'image/x-ms-bmp' OR mime = 'image/x-psd' OR mime = 'image/x-photoshop' OR mime = 'image/tiff' OR mime = 'image/x-eps' OR mime = 'image/bmp')";
            break;
        case 'video':
            $sqlMime = "mime LIKE 'video/%'";
            break;
        case 'audio':
            $sqlMime = "(mime = 'audio/vnd.rn-realaudio' OR mime = 'audio/x-wav' OR mime = 'audio/mpeg' OR mime = 'audio/midi' OR mime = 'audio/x-ms-wma')";
            break;
        case 'text':
            $sqlMime = "(mime = 'application/pdf' OR mime = 'text/plain' OR mime = 'application/rtf' OR mime = 'text/rtf' OR mime = 'application/msword' OR mime = 'application/vnd.ms-works' OR mime = 'application/vnd.ms-excel')";
            break;
        case 'sysfile':
            $sqlMime = "(mime = 'text/html' OR mime = 'text/css')";
            break;
        case 'compressed':
            $sqlMime = "(mime = 'application/x-gzip' OR mime = 'application/x-gtar' OR mime = 'application/x-tar' OR mime = 'application/x-stuffit' OR mime = 'application/x-stuffitx' OR mime = 'application/zip' OR mime = 'application/x-zip' OR mime = 'application/x-compressed' OR mime = 'application/x-compress' OR mime = 'application/mac-binhex40' OR mime = 'application/x-rar-compressed' OR mime = 'application/x-rar' OR mime = 'application/x-bzip2' OR mime = 'application/x-7z-compressed')";
            break;
    }

    //Generate search query
    $sql = ' FROM `files`';
    if ($qpath || $qalt || $sqlMime) {
        $sql .= ' WHERE ';
        if ($qpath || $qalt) {
            $sql .= '(';
        }
        if ($qpath) {
            $sql .= "MATCH(path) AGAINST('" . $qpath . "')>0";
        }
        if ($qpath && $qalt) {
            $sql .= ' OR ';
        }
        if ($qalt) {
            $sql .= "MATCH(alt) AGAINST('" . $qalt . "')>0";
        }
        if ($qpath) {
            $sql .= " OR `path` LIKE '%" . $qpath . "%' ";
        }
        if ($qalt) {
            $sql .= " OR `alt` LIKE '%" . $qalt . "%'";
        }
        if ($qpath || $qalt) {
            $sql .= ')';
        }
        if (($qpath || $qalt) && !empty($sqlMime)) {
            $sql .= ' AND ';
        }
        if (!empty($sqlMime)) {
            $sql .= $sqlMime;
        }
    }

    $sqlSelect = '';
    if ($qpath || $qalt) {
        $sqlSelect .= ', ';
        if ($qpath && $qalt) {
            $sqlSelect .= '(';
        }
        if ($qpath) {
            $sqlSelect .= 'MATCH(path) AGAINST(\'' . $qpath . '\')';
        }
        if ($qpath && $qalt) {
            $sqlSelect .= ' + ';
        }
        if ($qalt) {
            $sqlSelect .= 'MATCH(alt) AGAINST(\'' . $qalt . '\')';
        }
        if ($qpath && $qalt) {
            $sqlSelect .= ')';
        }
        $sqlSelect .= ' AS score';
        $sql = $sqlSelect . $sql;
        $sql .= ' ORDER BY `score` DESC';
    }

    $html = '';
    $javascript = '';
    foreach (ORM::getByQuery(File::class, 'SELECT *' . $sql) as $file) {
        assert($file instanceof File);
        if ('unused' !== $qmime || !isinuse($file->getPath())) {
            $html .= filehtml($file);
            $javascript .= filejavascript($file);
        }
    }

    return ['id' => 'files', 'html' => $html, 'javascript' => $javascript];
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
 * Use HTMLPurifier to clean HTML-code, preserves youtube videos.
 *
 * @param string $html Sting to clean
 *
 * @return string Cleaned stirng
 **/
function purifyHTML(string $html): string
{
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^(https:|http:)?//www.youtube.com/embed/%u');
    $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
    $config->set('Cache.SerializerPath', _ROOT_ . '/theme/cache/HTMLPurifier');

    $config->set('HTML.DefinitionID', 'html5-definitions'); // unqiue id
    if ($def = $config->maybeGetRawHTMLDefinition()) {
        $def->addAttribute('div', 'data-oembed_provider', 'Text');
        $def->addAttribute('div', 'data-oembed', 'Text');
        $def->addAttribute('div', 'data-widget', 'Text');
        $def->addAttribute('iframe', 'allowfullscreen', 'Bool');

        // http://developers.whatwg.org/the-video-element.html#the-video-element
        $def->addElement('video', 'Block', 'Flow', 'Common', [
            'controls' => 'Bool',
            'height' => 'Length',
            'poster' => 'URI',
            'preload' => 'Enum#auto,metadata,none',
            'src' => 'URI',
            'width' => 'Length',
        ]);
        // http://developers.whatwg.org/the-video-element.html#the-audio-element
        $def->addElement('audio', 'Block', 'Flow', 'Common', [
            'controls' => 'Bool',
            'preload' => 'Enum#auto,metadata,none',
            'src' => 'URI',
        ]);
        $def->addElement('source', 'Block', 'Empty', 'Common', ['src' => 'URI', 'type' => 'Text']);
    }

    $purifier = new HTMLPurifier($config);

    $html = $purifier->purify($html);

    return htmlUrlDecode($html);
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
 * @return string[]
 */
function getRequirementOptions(): array
{
    $options = [0 => 'None'];
    $requirements = db()->fetchArray('SELECT id, navn FROM `krav` ORDER BY navn');
    foreach ($requirements as $requirement) {
        $options[$requirement['id']] = $requirement['navn'];
    }

    return $options;
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

function htmlUrlDecode(string $html): string
{
    $html = trim($html);

    //Double encode importand encodings, to survive next step and remove white space
    $html = preg_replace(
        ['/&lt;/u',  '/&gt;/u',  '/&amp;/u', '/\s+/u'],
        ['&amp;lt;', '&amp;gt;', '&amp;amp;', ' '],
        $html
    );

    //Decode IE style urls
    $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');

    //Decode Firefox style urls
    $html = rawurldecode($html);

    //atempt to make relative paths (generated by Firefox when copy pasting) in to absolute
    $html = preg_replace('/="[.]{2}\//iu', '="/', $html);

    return $html;
}

function updateSide(
    int $id,
    string $navn,
    string $keywords,
    int $pris,
    string $beskrivelse,
    int $for,
    string $html,
    string $varenr,
    int $burde,
    int $fra,
    int $krav = null,
    int $maerke = null,
    string $billed = null
): bool {
    $html = purifyHTML($html);

    $page = ORM::getOne(Page::class, $id);
    assert($page instanceof Page);
    $page->setKeywords($keywords)
        ->setPrice($pris)
        ->setSku($varenr)
        ->setOldPrice($for)
        ->setExcerpt($beskrivelse)
        ->setRequirementId($krav)
        ->setBrandId($maerke)
        ->setIconPath($billed)
        ->setPriceType($fra)
        ->setOldPriceType($burde)
        ->setHtml($html)
        ->setTitle($navn)
        ->save();

    return true;
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
 * @return int[]
 */
function opretSide(
    int $kat,
    string $navn,
    string $keywords,
    int $pris,
    string $beskrivelse,
    int $for,
    string $html,
    string $varenr,
    int $burde,
    int $fra,
    int $krav = null,
    int $maerke = null,
    string $billed = null
): array {
    $html = purifyHTML($html);

    $page = new Page([
        'title'          => $navn,
        'keywords'       => $keywords,
        'excerpt'        => $beskrivelse,
        'html'           => $html,
        'sku'            => $varenr,
        'icon_path'      => $billed,
        'requirement_id' => $krav,
        'brand_id'       => $maerke,
        'price'          => $pris,
        'old_price'      => $for,
        'price_type'     => $fra,
        'old_price_type' => $burde,
    ]);
    $page->save();

    db()->query('INSERT INTO `bind` (`side`, `kat` ) VALUES (' . $page->getId() . ', ' . $kat . ')');

    return ['id' => $page->getId()];
}

/**
 * Delete a page and all it's relations from the database.
 *
 * @return string[]
 */
function sletSide(int $pageId): array
{
    ORM::getOne(Page::class, $pageId)->delete();

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
        $invoice->setAtt($updates['att']);
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
            $invoice->setShippingAtt($updates['shippingAtt']);
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
            $invoice->setTimeStampPay(!empty($updates['paydate']) ? strtotime($updates['paydate']) : time());
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
    $emailTemplate = 'email-invoice';
    if ($invoice->isSent()) {
        $subject = 'Elektronisk faktura vedr. ordre';
        $emailTemplate = 'email-invoice-reminder';
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
            'fax' => Config::get('fax'),
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
    if (!empty($output['type']) && empty($output['overwrite'])) {
        $pathinfo = pathinfo($path);
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

function getBasicAdminTemplateData(): array
{
    return [
        'title'      => 'Administrator menu',
        'theme'      => Config::get('theme', 'default'),
        'hide'       => [
            'activity'    => request()->cookies->get('hideActivity'),
            'binding'     => request()->cookies->get('hidebinding'),
            'categories'  => request()->cookies->get('hidekats'),
            'description' => request()->cookies->get('hidebeskrivelsebox'),
            'indhold'     => request()->cookies->get('hideIndhold'),
            'listbox'     => request()->cookies->get('hidelistbox'),
            'misc'        => request()->cookies->get('hidemiscbox'),
            'prices'      => request()->cookies->get('hidepriser'),
            'suplemanger' => request()->cookies->get('hideSuplemanger'),
            'tilbehor'    => request()->cookies->get('hidetilbehor'),
            'tools'       => request()->cookies->get('hideTools'),
        ],
    ];
}
