<?php
/**
 * Handle file upload
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
ini_set('display_errors', 1);
error_reporting(-1);
/**/

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/logon.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/get_mime_type.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/image-functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/file-functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

//TODO support bmp
header('HTTP/1.1 500 Internal Server Error');
if (!empty($_FILES['Filedata']['tmp_name'])
    && is_uploaded_file($_FILES['Filedata']['tmp_name'])
) {
    //Mangler file-functions.php
    header('HTTP/1.1 501 Internal Server Error');
    $pathinfo = pathinfo($_FILES['Filedata']['name']);
    //Kunne ikke læse filnavn.
    header('HTTP/1.1 503 Internal Server Error');
    $name = genfilename($pathinfo['filename']) . '.'
    . mb_strtolower($pathinfo['extension'], 'UTF-8');
    //Fejl under flytning af filen.
    header('HTTP/1.1 504 Internal Server Error');
    move_uploaded_file(
        $_FILES['Filedata']['tmp_name'],
        $_SERVER['DOCUMENT_ROOT'] . '/admin/upload/temp/' . $name
    ) or exit;
    //Kunne ikke give tilladelse til filen.
    header('HTTP/1.1 505 Internal Server Error');
    chmod($_SERVER['DOCUMENT_ROOT'] . '/admin/upload/temp/' . $name, 0644);
    //Mangler get_mime_type.php
    header('HTTP/1.1 510 Internal Server Error');
    $mime = get_mime_type('/admin/upload/temp/' . $name);
    //Kunne ikke finde billed størelsen.
    header('HTTP/1.1 512 Internal Server Error');

    $imagesize = array($_POST['x'], $_POST['y']);
    if ($mime == 'image/jpeg'
        || $mime == 'image/gif'
        || $mime == 'image/png'
        || $mime == 'image/vnd.wap.wbmp'
    ) {
        $imagesize = getimagesize($_SERVER['DOCUMENT_ROOT'] . '/admin/upload/temp/' . $name);
    }
    if (!$imagesize) {
        exit;
    }

    if (empty($_POST['aspect'])) {
        $_POST['aspect'] = null;
    }

    if (empty($_POST['type'])) {
        $_POST['type'] = '';
    }

    //TODO test if trim, resize or recompression is needed
    if (($_POST['type'] == 'image' && $mime != 'image/jpeg')
        || (($_POST['type'] == 'image' || $_POST['type'] == 'lineimage')
        && $imagesize[0] > $GLOBALS['_config']['text_width'])
        || (($_POST['type'] == 'image' || $_POST['type'] == 'lineimage')
        && $_FILES['Filedata']['size']/($imagesize[0]*$imagesize[1]) > 0.7)
        || ($_POST['type'] == 'lineimage'
        && $mime != 'image/png' && $mime != 'image/gif')
    ) {

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
                case 'm':
                    $val *= 1024;
                case 'k':
                    $val *= 1024;
            }
            return $val;
        }

        $memory_limit = returnBytes(ini_get('memory_limit'))-270336;

        if ($imagesize[0]*$imagesize[1] > $memory_limit/9.92) {
            //Kunne ikke slette filen.
            header('HTTP/1.1 520 Internal Server Error');

            if (@unlink($_SERVER['DOCUMENT_ROOT'].'/admin/upload/temp/'.$name)) {
                //Billedet er for stor.
                header('HTTP/1.1 521 Internal Server Error');
            }

            die();
        }

        //Mangler image-functions.php
        header('HTTP/1.1 560 Internal Server Error');
        //Fejl under billed behandling.
        header('HTTP/1.1 561 Internal Server Error');

        if ($_POST['type'] == 'lineimage') {
            $output['type'] = 'png';
        } else {
            $output['type'] = 'jpg';
        }

        $output['force'] = true;

        $newfiledata = generateImage(
            '/admin/upload/temp/' . $name,
            0,
            0,
            $imagesize[0],
            $imagesize[1],
            $GLOBALS['_config']['text_width'],
            $imagesize[1],
            0,
            0,
            $output
        );

        $temppath = $newfiledata['path'];
        $width = $newfiledata['width'];
        $height = $newfiledata['height'];
        $destpath = pathinfo($newfiledata['path']);
        $destpath = @$_COOKIE['admin_dir'].'/'.$destpath['basename'];
        $mime = get_mime_type($temppath);
    } else {
        $temppath = '/admin/upload/temp/'.$name;
        $width = $imagesize[0];
        $height = $imagesize[1];
        $destpath = @$_COOKIE['admin_dir'].'/'.$name;
    }

    rename($_SERVER['DOCUMENT_ROOT'].$temppath, $_SERVER['DOCUMENT_ROOT'].$destpath);

    //MySQL DELETE fejl!
    header('HTTP/1.1 542 Internal Server Error');
    db()->query('DELETE FROM files WHERE path = \''.$destpath."'");
    //If the image was edited it inserts it's info
    db()->query('DELETE FROM files WHERE path = \''.$temppath."'");
    //MySQL INSERT fejl!
    header('HTTP/1.1 543 Internal Server Error');

    $alt = empty($_POST['alt']) ? '' : $_POST['alt'];
    $aspect = empty($_POST['aspect']) ? '' : $_POST['aspect'];

    db()->query(
        "
        INSERT INTO files (path, mime, alt, width, height, size, aspect)
        VALUES ('" . $destpath . "', '" . $mime . "', '" . $alt . "', '" . $width
        . "', '" . $height . "', '" . filesize($_SERVER['DOCUMENT_ROOT'] . $destpath)
        . "', " . $aspect . ")
        "
    );

    header('HTTP/1.1 200 OK');
} else {
    //Filen blev ikke sendt
    header('HTTP/1.1 404 Not Found');
}
