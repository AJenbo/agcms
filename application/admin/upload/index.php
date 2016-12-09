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

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/logon.php';

//TODO support bmp
header('HTTP/1.1 500 Internal Server Error');
if (!empty($_FILES['Filedata']['tmp_name'])
    && is_uploaded_file($_FILES['Filedata']['tmp_name'])
) {
    $uploadPath = $_FILES['Filedata']['tmp_name'];
    $tempPath = tempnam(sys_get_temp_dir(), 'upload');

    //Mangler file-functions.php
    header('HTTP/1.1 501 Internal Server Error');
    $pathinfo = pathinfo($_FILES['Filedata']['name']);

    //Kunne ikke læse filnavn.
    header('HTTP/1.1 503 Internal Server Error');
    $name = genfilename($pathinfo['filename']);
    $ext = mb_strtolower($pathinfo['extension'], 'UTF-8');

    //Fejl under flytning af filen.
    header('HTTP/1.1 504 Internal Server Error');
    move_uploaded_file($uploadPath, $tempPath) || die();
    $targetDir = (string) realpath(_ROOT_ . ($_COOKIE['admin_dir'] ?? '/files')); // Get actual path
    $targetDir = mb_substr($targetDir, mb_strlen(_ROOT_)); // Remove _ROOT_
    if (mb_stripos($targetDir, '/files') !== 0 && mb_stripos($targetDir, '/images') !== 0) {
        $targetDir = '/files';
    }

    //Kunne ikke give tilladelse til filen.
    header('HTTP/1.1 505 Internal Server Error');
    chmod($tempPath, 0644);

    //Kunne ikke finde billed størelsen.
    header('HTTP/1.1 512 Internal Server Error');

    $mime = get_mime_type($tempPath);

    $imagesize = [$_POST['x'], $_POST['y']];
    if ($mime === 'image/jpeg'
        || $mime === 'image/gif'
        || $mime === 'image/png'
        || $mime === 'image/vnd.wap.wbmp'
    ) {
        $imagesize = getimagesize($tempPath);
    }
    if (!$imagesize) {
        die();
    }

    $type = !empty($_POST['type']) ? $_POST['type'] : '';

    //TODO test if trim, resize or recompression is needed
    if (($type === 'image' && $mime !== 'image/jpeg')
        || (($type === 'image' || $type === 'lineimage')
            && $imagesize[0] > Config::get('text_width')
        )
        || (($type === 'image' || $type === 'lineimage')
            && $_FILES['Filedata']['size'] / ($imagesize[0] * $imagesize[1]) > 0.7 // max byte per pixels
        )
        || ($type === 'lineimage'
            && $mime !== 'image/png' && $mime !== 'image/gif'
        )
    ) {
        $memory_limit = returnBytes(ini_get('memory_limit')) - 270336;

        if ($imagesize[0] * $imagesize[1] > $memory_limit/10) {
            //Kunne ikke slette filen.
            header('HTTP/1.1 520 Internal Server Error');

            if (@unlink($tempPath)) {
                //Billedet er for stor.
                header('HTTP/1.1 521 Internal Server Error');
            }
            die();
        }

        //Fejl under billed behandling.
        header('HTTP/1.1 561 Internal Server Error');

        $ext = 'jpg';
        if ($_POST['type'] === 'lineimage') {
            $ext = 'png';
        }
        $output = ['type' => $ext];

        $output['force'] = true;

        $newfiledata = generateImage(
            $tempPath,
            0,
            0,
            $imagesize[0],
            $imagesize[1],
            Config::get('text_width'),
            $imagesize[1],
            0,
            0,
            $output
        );

        $tempPath = $newfiledata['path'];
        $aspect = null;
        $width = $newfiledata['width'];
        $height = $newfiledata['height'];
    } else {
        $aspect = empty($_POST['aspect']) ? '' : $_POST['aspect'];
        $width = $imagesize[0];
        $height = $imagesize[1];
    }
    $destpath = $targetDir . '/' . $name . '.' . $ext;

    //MySQL DELETE fejl!
    header('HTTP/1.1 542 Internal Server Error');
    $file = File::getByPath($destpath);
    if ($file) {
        $file->delete();
    }

    //Fejl under flytning af filen.
    header('HTTP/1.1 504 Internal Server Error');
    rename($tempPath, _ROOT_ . $destpath);

    //MySQL INSERT fejl!
    header('HTTP/1.1 543 Internal Server Error');

    $alt = empty($_POST['alt']) ? '' : $_POST['alt'];
    File::fromPath($destpath)
        ->setDescription($alt)
        ->setAspect($aspect)
        ->setWidth($width)
        ->setHeight($height)
        ->save();

    header('HTTP/1.1 200 OK');
} else {
    //Filen blev ikke sendt
    header('HTTP/1.1 404 Not Found');
}
