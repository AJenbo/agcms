<?php
//*
ini_set('display_errors', 1);
error_reporting(-1);
/**/

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain('agcms', $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset('agcms', 'UTF-8');
textdomain('agcms');

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/logon.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/file-functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/get_mime_type.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/sajax.php';
//TODO update compleat source with doConditionalGet

/*
$mode
0 = exploere
1 = filemove

$return
rtef = inserthtml
thb = returnid.value, returnid+'thb'.src, thb limit.
icon = returnid.value, returnid+'thb'.src, 16x16 limit.
*/

//load path from cookie, else default to /images
if (empty($_COOKIE['admin_dir']) || !is_dir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'])) {
    @setcookie('admin_dir', '/images');
    @$_COOKIE['admin_dir'] = '/images';
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
    if ($str_file == '.' || $str_file == '..' || $str_file == '.htaccess' || is_dir($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$str_file)) {
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
    global $mysqli;
    $html = '';
    $javascript = '';

    if ($files = scandir($_SERVER['DOCUMENT_ROOT'].$dir)) {
        $files = array_filter($files, 'is_files');
        natcasesort($files);
        $files = array_values($files);
        $nummber_files = count($files);
    }

    for ($i=0; $i<$nummber_files; $i++) {
        $fileinfo = $mysqli->fetchArray('SELECT * FROM files WHERE path = \''.$dir.'/'.$files[$i]."'");

        if (!$fileinfo) {
            //Save file info to db
            $mime = get_mime_type($dir.'/'.$files[$i]);
            $imagesize = @getimagesize($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$files[$i]);
            $size = filesize($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$files[$i]);
            $mysqli->query('INSERT INTO files (path, mime, width, height, size, aspect) VALUES (\''.$dir.'/'.$files[$i]."', '".$mime."', '".$imagesize[0]."', '".$imagesize[1]."', '".$size."', NULL )");
            $fileinfo[0]['path'] = $dir.'/'.$files[$i];
            $fileinfo[0]['mime'] = $mime;
            $fileinfo[0]['width'] = $imagesize[0];
            $fileinfo[0]['height'] = $imagesize[1];
            $fileinfo[0]['size'] = $size;
            global $mysqli;
            $fileinfo[0]['id'] = $mysqli->insert_id;
//          $fileinfo[0]['aspect'] = NULL;
            unset($imagesize);
            unset($mime);
        }

        $html .= filehtml($fileinfo[0]);
        //TODO reduce net to javascript
        $javascript .= filejavascript($fileinfo[0]);
    }
    return array('id' => 'files', 'html' => $html, 'javascript' => $javascript);
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
        case 'image/jpeg':
        case 'image/png':
        case 'image/gif':
            $html .= '<div id="tilebox'.$fileinfo['id'].'" class="imagetile"><div class="image"';
            if ($_GET['return']=='rtef') {
                $html .= ' onclick="addimg('.$fileinfo['id'].')"';
            } elseif ($_GET['return']=='thb') {
                if ($fileinfo['width'] <= $GLOBALS['_config']['thumb_width'] && $fileinfo['height'] <= $GLOBALS['_config']['thumb_height']) {
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
        case 'video/x-shockwave-flash':
        case 'application/x-shockwave-flash':
        case 'application/futuresplash':
            $html .= '<div id="tilebox'.$fileinfo['id'].'" class="swftile"><div class="image"';
            if ($_GET['return']=='rtef') {
                $html .= ' onclick="addswf('.$fileinfo['id'].', '.$fileinfo['width'].', '.$fileinfo['height'].')"';
            } else {
                $html .= ' onclick="files['.$fileinfo['id'].'].openfile();"';
            }
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
            } else /*if ($mode=='file')*/ {
                $html .= ' onclick="files['.$fileinfo['id'].'].openfile();"';
            }
            break;
    }

    $html .='> <img src="';

    switch ($fileinfo['mime']) {
        case 'image/jpeg':
        case 'image/png':
        case 'image/gif':
        case 'image/vnd.wap.wbmp':
            //$url_file_name = rawurlencode($pathinfo['basename']);
            $html .= 'image.php?path='.rawurlencode($pathinfo['dirname'].'/'.$pathinfo['basename']).'&amp;maxW=128&amp;maxH=96';
            break;
        case 'application/pdf':
            $html .= 'images/file-pdf.gif';
            break;
        case 'image/x-psd':
        case 'image/x-photoshop':
        case 'image/tiff':
        case 'image/x-eps':
        case 'image/bmp':
        case 'image/x-ms-bmp':
        case 'application/postscript':
            $html .= 'images/file-image.gif';
            break;
        case 'video/avi':
        case 'video/x-msvideo':
        case 'video/mpeg':
        case 'video/quicktime':
        case 'video/x-shockwave-flash':
        case 'application/x-shockwave-flash':
        case 'application/futuresplash': //missing spl
        case 'video/x-flv':
        case 'video/x-ms-asf': //missing asf
        case 'video/x-ms-wmv':
        case 'application/vnd.ms-powerpoint':
        case 'video/vnd.rn-realvideo': //missing rv
        case 'application/vnd.rn-realmedia':
            $html .= 'images/file-video.gif';
            break;
        case 'audio/x-wav':
        case 'audio/mpeg':
        case 'audio/midi':
        case 'audio/x-ms-wma':
        case 'audio/vnd.rn-realaudio': //missing rma / ra
            $html .= 'images/file-audio.gif';
            break;
        case 'text/plain':
        case 'application/rtf':
        case 'text/rtf':
        case 'application/msword':
        case 'application/vnd.ms-works': //missing wps
        case 'application/vnd.ms-excel':
            $html .= 'images/file-text.gif';
            break;
        case 'text/html':
        case 'text/css':
            $html .= 'images/file-sys.gif';
            break;
        case 'application/x-gzip':
        case 'application/x-gtar':
        case 'application/x-tar':
        case 'application/x-stuffit':
        case 'application/x-stuffitx':
        case 'application/zip':
        case 'application/x-zip':
        case 'application/x-compressed': //missing
        case 'application/x-compress': //missing
        case 'application/mac-binhex40':
        case 'application/x-rar-compressed':
        case 'application/x-rar':
        case 'application/x-bzip2':
        case 'application/x-7z-compressed':
            $html .= 'images/file-zip.gif';
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
    if (is_dir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'].'/'.$name)) {
        return array('error' => _('A file or folder with the same name already exists.'));
    }

    /*Kode til Scannet's server
    if (is_dir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'])) {
        mkdir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'].'/'.$name, 0777); //ends up as 755 and apache as the owner
        system('aduxchown '.mb_substr(@$_COOKIE['admin_dir'], 1-mb_strlen(@$_COOKIE['admin_dir'], 'UTF-8'),, 'UTF-8').'/'); //remove the leading slash from the path and send it to the special scannet uid fixing scrip (dumb safe mode)
        system('aduxchown '.mb_substr(@$_COOKIE['admin_dir'], 1-mb_strlen(@$_COOKIE['admin_dir'], 'UTF-8'),, 'UTF-8').'/'.$name.'/'); //remove the leading slash from the path and send it to the special scannet uid fixing scrip (dumb safe mode)
        chmod($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'].'/'.$name, 0777);
        return true;
    }
    */

    if (!ini_get('safe_mode') || (ini_get('safe_mode') && ini_get('safe_mode_gid')) || !function_exists('ftp_mkdir')) {
        if (!is_dir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir']) ||
        !mkdir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'].'/'.$name, 0771)) {
            return array('error' => _('Could not create folder, you may not have sufficient rights to this folder.'));
        }
    } else {
        //FTP methode for server with secure mode On
        if (!is_dir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir']) ||
        !$FTP_Conn = ftp_connect('localhost')) {
            return array('error' => _('An error occurred with the FTP connection.'));
        }
        if (!@ftp_login($FTP_Conn, $GLOBALS['_config']['ftp_User'], $GLOBALS['_config']['ftp_Pass']) ||
        !@ftp_chdir($FTP_Conn, $GLOBALS['_config']['ftp_Root'].@$_COOKIE['admin_dir']) ||
        !ftp_mkdir($FTP_Conn, $name) ||
        !ftp_site($FTP_Conn, 'CHMOD 0771 '.$name)) {
            return array('error' => _('An error occurred with the FTP connection.'));
        }

        if (!is_dir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'].'/'.$name)) {
            return array('error' => _('Could not create folder, you may not have sufficient rights to this folder.'));
        }
    }
    return array('error' => false);
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
//return array('error' => 'id='.id.' path='.$path.' dir='.$dir.' filename='.$filename.' force='.$force, 'id' => $id);
    global $mysqli;

    $pathinfo = pathinfo($path);
    if ($pathinfo['dirname'] == '/') {
        $pathinfo['dirname'] == '';
    }

    if (!$dir) {
        $dir = $pathinfo['dirname'];
    } elseif ($dir == '/') {
        $dir == '';
    }

    if (!is_dir($_SERVER['DOCUMENT_ROOT'].$path)) {
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
        return array('error' => _('The name is invalid.'), 'id' => $id);
    }

    //Destination folder doesn't exist
    if (!is_dir($_SERVER['DOCUMENT_ROOT'].$dir.'/')) {
        return array('error' => _('The file could not be moved because the destination folder does not exist.'), 'id' => $id);
    }
    if ($pathinfo['extension']) {
        //No changes was requested.
        if ($path == $dir.'/'.$filename.'.'.$pathinfo['extension']) {
            return array('id' => $id, 'filename' => $filename, 'path' => $path);
        }

        //if file path more then 255 erturn error
        if (mb_strlen($dir.'/'.$filename.'.'.$pathinfo['extension'], 'UTF-8') > 255) {
            return array('error' => _('The filename is too long.'), 'id' => $id);
        }

        //File already exists, but are we trying to force a overwrite?
        if (is_file($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$filename.'.'.$pathinfo['extension']) && !$force) {
            return array('yesno' => _('A file with the same name already exists.
Would you like to replace the existing file?'), 'id' => $id);
        }

        //Rename/move or give an error
        if (@rename($_SERVER['DOCUMENT_ROOT'].$path, $_SERVER['DOCUMENT_ROOT'].$dir.'/'.$filename.'.'.$pathinfo['extension'])) {
            if ($force) {
                $mysqli->query("DELETE FROM files WHERE `path` = '".$dir.'/'.$filename.'.'.$pathinfo['extension']."' LIMIT 1");
            }

            $mysqli->query("UPDATE `files` SET `path` = '".$dir.'/'.$filename.'.'.$pathinfo['extension']."' WHERE `path` = '".$path."' LIMIT 1");

            $mysqli->query("UPDATE sider SET navn = REPLACE(navn, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), text = REPLACE(text, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), beskrivelse = REPLACE(beskrivelse, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), billed = REPLACE(billed, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            $mysqli->query("UPDATE template SET navn = REPLACE(navn, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), text = REPLACE(text, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), beskrivelse = REPLACE(beskrivelse, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), billed = REPLACE(billed, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            $mysqli->query("UPDATE special SET text = REPLACE(text, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            $mysqli->query("UPDATE krav SET text = REPLACE(text, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            $mysqli->query("UPDATE maerke SET ico = REPLACE(ico, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            $mysqli->query("UPDATE list_rows SET cells = REPLACE(cells, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");
            $mysqli->query("UPDATE kat SET navn = REPLACE(navn, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."'), icon = REPLACE(icon, '$path', '".$dir.'/'.$filename.'.'.$pathinfo['extension']."')");

            return array('id' => $id, 'filename' => $filename, 'path' => $dir.'/'.$filename.'.'.$pathinfo['extension']);
        } else {
            return array('error' => _('An error occurred with the file operations.'), 'id' => $id);
        }
    } else {
    //Dir or file with no extension
    //TODO ajax rename folder
        //No changes was requested.
        if ($path == $dir.'/'.$filename) {
            return array('id' => $id, 'filename' => $filename, 'path' => $path);
        }


        //folder already exists
        if (is_dir($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$filename)) {
            return array('error' => _('A folder with the same name already exists.'), 'id' => $id);
        }

        //if file path more then 255 erturn error
        if (mb_strlen($dir.'/'.$filename, 'UTF-8') > 255) {
            return array('error' => _('The filename is too long.'), 'id' => $id);
        }

        //File already exists, but are we trying to force a overwrite?
        if (is_file($_SERVER['DOCUMENT_ROOT'].$path) && !$force) {
            return array('yesno' => _('A file with the same name already exists.
Would you like to replace the existing file?'), 'id' => $id);
        }

        //Rename/move or give an error
        //TODO prepared query
        if (@rename($_SERVER['DOCUMENT_ROOT'].$path, $_SERVER['DOCUMENT_ROOT'].$dir.'/'.$filename)) {
            if ($force) {
                $mysqli->query("DELETE FROM files WHERE `path` = '".$dir.'/'.$filename."%'");
                //TODO insert new file data (width, alt, height, aspect)
            }
            $mysqli->query("UPDATE files    SET path = REPLACE(path, '".$path."', '".$dir.'/'.$filename."')");
            $mysqli->query("UPDATE sider    SET navn = REPLACE(navn, '".$path."', '".$dir.'/'.$filename."'), text = REPLACE(text, '$path', '".$dir.'/'.$filename."'), beskrivelse = REPLACE(beskrivelse, '$path', '".$dir.'/'.$filename."'), billed = REPLACE(billed, '$path', '".$dir.'/'.$filename."')");
            $mysqli->query("UPDATE template SET navn = REPLACE(navn, '".$path."', '".$dir.'/'.$filename."'), text = REPLACE(text, '$path', '".$dir.'/'.$filename."'), beskrivelse = REPLACE(beskrivelse, '$path', '".$dir.'/'.$filename."'), billed = REPLACE(billed, '$path', '".$dir.'/'.$filename."')");
            $mysqli->query("UPDATE special  SET text = REPLACE(text, '".$path."', '".$dir.'/'.$filename."')");
            $mysqli->query("UPDATE krav     SET text = REPLACE(text, '".$path."', '".$dir.'/'.$filename."')");
            $mysqli->query("UPDATE maerke   SET ico  = REPLACE( ico, '".$path."', '".$dir.'/'.$filename."')");
            $mysqli->query("UPDATE list_rows  SET cells  = REPLACE(cells, '".$path."', '".$dir.'/'.$filename."')");
            $mysqli->query("UPDATE kat      SET navn = REPLACE(navn, '".$path."', '".$dir.'/'.$filename."'), icon = REPLACE(icon, '$path', '".$dir.'/'.$filename."')");

            if (is_dir($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$filename)) {
                if (@$_COOKIE[@$_COOKIE['admin_dir']]) {
                    @setcookie($dir.'/'.$filename, @$_COOKIE[@$_COOKIE['admin_dir']]);
                }
                @setcookie(@$_COOKIE['admin_dir'], false);
                @setcookie('admin_dir', $dir.'/'.$filename);
            }

            return array('id' => $id, 'filename' => $filename, 'path' => $dir.'/'.$filename);
        } else {
            return array('error' => _('An error occurred with the file operations.'), 'id' => $id);
        }
    }
}

$mysqli = new Simple_Mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

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
        $dirlist = scandir($_SERVER['DOCUMENT_ROOT'].$dir);
        $nr = count($dirlist);
        for ($i=0; $i<$nr; $i++) {
            if ($dirlist[$i] != '.' && $dirlist[$i] != '..') {
                if (is_dir($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$dirlist[$i])) {
                    $deltree = deltree($dir.'/'.$dirlist[$i]);
                    if ($deltree) {
                        return $deltree;
                    }
                    @rmdir($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$dirlist[$i]);
                    @setcookie($dir.'/'.$dirlist[$i], false);
                } else {
                    global $mysqli;
                    if ($mysqli->fetchArray("SELECT id FROM `sider` WHERE `navn` LIKE '%" . $dir . "/" . $dirlist[$i] . "%' OR `text` LIKE '%" . $dir . "/" . $dirlist[$i] . "%' OR `beskrivelse` LIKE '%" . $dir . "/" . $dirlist[$i] . "%' OR `billed` LIKE '%" . $dir . "/" . $dirlist[$i] . "%' LIMIT 1")
                    || $mysqli->fetchArray("SELECT id FROM `template` WHERE `navn` LIKE '%".$dir."/".$dirlist[$i]."%' OR `text` LIKE '%".$dir."/".$dirlist[$i]."%' OR `beskrivelse` LIKE '%".$dir."/".$dirlist[$i]."%' OR `billed` LIKE '%".$dir."/".$dirlist[$i]."%' LIMIT 1")
                    || $mysqli->fetchArray("SELECT id FROM `special` WHERE `text` LIKE '%".$dir."/".$dirlist[$i]."%' LIMIT 1")
                    || $mysqli->fetchArray("SELECT id FROM `krav` WHERE `text` LIKE '%".$dir."/".$dirlist[$i]."%' LIMIT 1")
                    || $mysqli->fetchArray("SELECT id FROM `maerke` WHERE `ico` LIKE '%".$dir."/".$dirlist[$i]."%' LIMIT 1")
                    || $mysqli->fetchArray("SELECT id FROM `list_rows` WHERE `cells` LIKE '%".$dir."/".$dirlist[$i]."%' LIMIT 1")
                    || $mysqli->fetchArray("SELECT id FROM `kat` WHERE `navn` LIKE '%".$dir."/".$dirlist[$i]."%' OR `icon` LIKE '%".$dir."/".$dirlist[$i]."%' LIMIT 1")) {
                        return array('error' => _('A file could not be deleted because it is used on a site.'));
                    }
                    @unlink($_SERVER['DOCUMENT_ROOT'].$dir.'/'.$dirlist[$i]);
                }
            }
        }
    }
    $deltree = deltree(@$_COOKIE['admin_dir']);
    if ($deltree) {
        return $deltree;
    }
    if (@rmdir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'])) {
        @setcookie(@$_COOKIE['admin_dir'], false);
        return true;
    } else {
        return array('error' => _('The folder could not be deleted, you may not have sufficient rights to this folder.'));
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
    global $mysqli;

    $qpath = $mysqli->escapeWildcards($mysqli->real_escape_string($qpath));
    $qalt = $mysqli->escapeWildcards($mysqli->real_escape_string($qalt));

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

    $filecount = $mysqli->fetchArray('SELECT count(id) AS count'.$sql);
    $filecount = $filecount[0]['count'];

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
        $files = $mysqli->fetchArray('SELECT *'.$sql.' LIMIT '.$filenumber.', '.$limit);
        $filenumber += 250;

        foreach ($files as $key => $file) {
            if ($qmime != 'unused' || !isinuse($file['path'])) {
                $html .= filehtml($file);
                $javascript .= filejavascript($file);
            }
            unset($files[$key]);
        }
    }

    return array('id' => 'files', 'html' => $html, 'javascript' => $javascript);
}

/**
 * @param int $qpath
 * @param string $alt
 *
 * @return array
 */
function edit_alt(int $id, string $alt): array
{
    global $mysqli;

    $mysqli->query("UPDATE `files` SET `alt` = '".$mysqli->real_escape_string($alt)."' WHERE `id` = ".$id." LIMIT 1");

    //Update html with new alt...
    $file = $mysqli->fetchArray('SELECT path FROM `files` WHERE `id` = '.$id.' LIMIT 1');
    $sider = $mysqli->fetchArray('SELECT id, text FROM `sider` WHERE `text` LIKE \'%'.$file[0]['path'].'%\'');

    if ($sider) {
        foreach ($sider as $value) {
            //TODO move this to db fixer to test for missing alt="" in img
            /*preg_match_all('/<img[^>]+/?>/ui', $value, $matches);*/
            $value['text'] = preg_replace('/(<img[^>]+src="'.addcslashes(str_replace('.', '[.]', $file[0]['path']), '/').'"[^>]+alt=)"[^"]*"([^>]*>)/iu', '\1"'.xhtmlEsc($alt).'"\2', $value['text']);
            $value['text'] = preg_replace('/(<img[^>]+alt=)"[^"]*"([^>]+src="'.addcslashes(str_replace('.', '[.]', $file[0]['path']), '/').'"[^>]*>)/iu', '\1"'.xhtmlEsc($alt).'"\2', $value['text']);
            $mysqli->query("UPDATE `sider` SET `text` = '".$value['text']."' WHERE `id` = ".$value['id']." LIMIT 1");
        }
    }
    return array('id' => $id, 'alt' => $alt);
}

$sajax_request_type = 'POST';

//$sajax_debug_mode = 1;
sajax_export(
    array('name' => 'renamefile', 'method' => 'POST'),
    array('name' => 'deletefolder', 'method' => 'POST'),
    array('name' => 'deletefile', 'method' => 'POST'),
    array('name' => 'showfiles', 'method' => 'GET'),
    array('name' => 'listdirs', 'method' => 'GET'),
    array('name' => 'makedir', 'method' => 'POST'),
    array('name' => 'searchfiles', 'method' => 'GET'),
    array('name' => 'edit_alt', 'method' => 'POST')
);
//$sajax_remote_uri = '/ajax.php';
sajax_handle_client_request();


if (@$_COOKIE['qpath'] || @$_COOKIE['qalt'] || @$_COOKIE['qtype']) {
    $showfiles = searchfiles(@$_COOKIE['qpath'], @$_COOKIE['qalt'], @$_COOKIE['qtype']);
} else {
    $showfiles = showfiles(@$_COOKIE['admin_dir']);
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('Explorer'); ?></title>
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript" src="javascript/lib/protomenu/proto.menu.js"></script>
<link rel="stylesheet" href="style/proto.menu.css" type="text/css" media="screen" />

<link href="style/explorer.css" rel="stylesheet" type="text/css" />
<!--[if IE]><link href="style/explorer-ie.css" rel="stylesheet" type="text/css" /><![endif]-->
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="javascript/explorer.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript"><!--
var rte = '<?php echo @$_GET['rte']; ?>';
var returnid = '<?php echo @$_GET['returnid']; ?>';
<?php sajax_show_javascript(); ?>

<?php echo $showfiles['javascript']; ?>

//--></script>
<style type="text/css">
#files .filetile div, #files .videotile div, #files .swftile div, #files .flvtile div, #files .imagetile div {
    background-color:#<?php echo $GLOBALS['_config']['bgcolor']; ?>;
}
</style>
</head>
<body scroll="auto">
picture_error
<div id="menu"><img id="loading" src="images/loading.gif" width="16" height="16" alt="<?php echo _('Loading'); ?>" title="<?php echo _('Loading'); ?>" /><a id="dir_bn" class="<?php
if (empty($_COOKIE['qpath']) && empty($_COOKIE['qalt']) && empty($_COOKIE['qtype'])) {
    echo 'down';
} ?>" title="<?php echo _('Folders'); ?>" onclick="return swap_pannel('dir');"><img width="16" height="16" src="images/folder.png" alt="" /> Mapper</a> <a id="search_bn" title="Søg" class="<?php if (@$_COOKIE['qpath'] || @$_COOKIE['qalt'] || @$_COOKIE['qtype']) {
    echo 'down';
} ?>" onclick="return swap_pannel('search');"><img width="16" height="16" src="images/magnifier.png" alt="" /> <?php echo _('Search'); ?></a> <a title="<?php echo _('New folder'); ?>" onclick="makedir();return false"><img width="16" height="16" src="images/folder_add.png" alt="" /> <?php echo _('New folder'); ?></a> <a title="<?php echo _('Delete folder'); ?>" onclick="deletefolder();return false"><img width="16" height="16" src="images/folder_delete.png" alt="" /> <?php echo _('Delete folder'); ?></a> <a title="<?php echo _('Add File'); ?>" onclick="open_file_upload();return false;"><img width="16" height="16" src="images/folder_page_white.png" alt="" /> <?php echo _('Add File'); ?></a></div>
<div id="dir"<?php if (@$_COOKIE['qpath'] || @$_COOKIE['qalt'] || @$_COOKIE['qtype']) {
    echo ' style="display:none"';
} ?>>
  <div id="dir_.images"><img<?php if (@$_COOKIE['/images']) {
        echo ' style="display:none"';
} ?> src="images/+.gif" onclick="dir_expand(this, 0);" height="16" width="16" alt="+" title="" /><img<?php if (empty($_COOKIE['/images'])) {
    echo ' style="display:none"';
} ?> src="images/-.gif" onclick="dir_contract(this);" height="16" width="16" alt="-" title="" /><a<?php
if ('/images' == @$_COOKIE['admin_dir']) {
    echo ' class="active"';
}
    ?> onclick="showfiles('/images', 0);this.className='active'"><img src="images/folder.png" height="16" width="16" alt="" /> <?php echo _('Pictures'); ?> </a>
    <div><?php
    if (@$_COOKIE['/images']) {
        $listdirs = listdirs('/images', 0);
        echo $listdirs['html'];
    }
    ?></div></div>
  <div id="dir_.files"><img<?php if (@$_COOKIE['/files']) {
        echo ' style="display:none"';
} ?> src="images/+.gif" onclick="dir_expand(this, 0);" height="16" width="16" alt="+" title="" /><img<?php if (empty($_COOKIE['/files'])) {
    echo ' style="display:none"';
} ?> src="images/-.gif" onclick="dir_contract(this);" height="16" width="16" alt="-" title="" /><a<?php
if ('/files' == @$_COOKIE['admin_dir']) {
    echo ' class="active"';
}
    ?> onclick="showfiles('/files', 0);this.className='active'"><img src="images/folder.png" height="16" width="16" alt="" /> <?php echo _('Files'); ?> </a><div><?php
if (@$_COOKIE['/files']) {
    $listdirs = listdirs('/files', 0);
    echo $listdirs['html'];
} ?></div></div>
</div>
<form id="search"<?php if (empty($_COOKIE['qpath']) && empty($_COOKIE['qalt']) && empty($_COOKIE['qtype'])) {
    echo ' style="display:none"';
} ?> action="" onsubmit="searchfiles();return false;"><div>
    <?php echo _('Name:'); ?><br />
  <input name="searchpath" id="searchpath" value="<?php echo @$_COOKIE['qpath']; ?>" />
  <br />
  <br />
    <?php echo _('Description:'); ?><br />
  <input name="searchalt" id="searchalt" value="<?php echo @$_COOKIE['qalt']; ?>" />
  <br />
  <br />
    <?php echo _('Type:'); ?><br />
  <select name="searchtype" id="searchtype">
    <option value="" selected="selected">alle</option>
    <option value="image"<?php if (@$_COOKIE['qtype'] == 'image') {
        echo ' selected="selected"';
} ?>><?php echo _('Pictures'); ?></option>
    <option value="imagefile"<?php if (@$_COOKIE['qtype'] == 'imagefile') {
        echo ' selected="selected"';
} ?>><?php echo _('Image files'); ?></option>
    <option value="video"<?php if (@$_COOKIE['qtype'] == 'video') {
        echo ' selected="selected"';
} ?>><?php echo _('Videos'); ?></option>
    <option value="audio"<?php if (@$_COOKIE['qtype'] == 'audio') {
        echo ' selected="selected"';
} ?>><?php echo _('Sounds'); ?></option>
    <option value="text"<?php if (@$_COOKIE['qtype'] == 'text') {
        echo ' selected="selected"';
} ?>><?php echo _('Documents'); ?></option>
    <option value="sysfile"<?php if (@$_COOKIE['qtype'] == 'sysfile') {
        echo ' selected="selected"';
} ?>><?php echo _('System files'); ?></option>
    <option value="compressed"<?php if (@$_COOKIE['qtype'] == 'compressed') {
        echo ' selected="selected"';
} ?>><?php echo _('Compressed files'); ?></option>
    <option value="unused"<?php
    if (@$_COOKIE['qtype'] == 'unused') {
        echo ' selected="selected"';
    }
    ?>><?php echo _('Unused files'); ?></option>
  </select>
  <br />
  <br />
  <input type="submit" value="Søg nu" accesskey="f" />
</div></form>
<div id="files"><?php
echo $showfiles['html'];
//TODO mappper = reload files
?></div>
<script type="text/javascript"><!--
init();
--></script>
</body>
</html>
