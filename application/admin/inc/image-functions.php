<?php

require_once __DIR__ . '/logon.php';
//TODO if (no changes and !$output) do redirect

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
    array $output = null
): array {
    $imagesize = @getimagesize(_ROOT_ . $path);
    $pathinfo = pathinfo($path);

    if (empty($output['filename'])) {
        $output['filename'] = $pathinfo['filename'];
    }

    if (@$output['type'] == 'jpg') {
        $output['path'] = $pathinfo['dirname'].'/'.$output['filename'].'.jpg';
    } elseif (@$output['type'] == 'png') {
        $output['path'] = $pathinfo['dirname'].'/'.$output['filename'].'.png';
    }

    if (!$cropW) {
        $cropW = $imagesize[0];
    }
    if (!$cropH) {
        $cropH = $imagesize[1];
    }

    $cropW = min($imagesize[0], $cropW);
    $cropH = min($imagesize[1], $cropH);

    if ($cropW == $imagesize[0]) {
        $cropX = 0;
    }
    if ($cropH == $imagesize[1]) {
        $cropY = 0;
    }

    $maxW = min($cropW, $maxW);
    $maxH = min($cropH, $maxH);

    //used by scale and rotate
    $ratio = $cropW/$cropH;

    //witch side exceads the bounds the most
    if ($cropW/$maxW > $cropH/$maxH) {
        $width = $maxW;
        $height = round($maxW / $ratio);
    } else {
        $width = round($maxH * $ratio);
        $height = $maxH;
    }

    $mimeType = get_mime_type($path);

    if (@$output['type'] && !$output['force'] && is_file(_ROOT_ . $output['path'])) {
        return ['yesno' => _('A file with the same name already exists.'."\n".'Would you like to replace the existing file?'), 'filename' => $output['filename']];
    }

    switch ($mimeType) {
        case 'image/jpeg':
            //TODO error if jpg > 1610361 pixel
            $image = imagecreatefromjpeg(_ROOT_ . $path);
            $fill = false;
            break;
        case 'image/png':
            //TODO error if png > 804609 Pixels
            $temp = imagecreatefrompng(_ROOT_ . $path);

            //Fill back ground
            $image = imagecreatetruecolor(imagesx($temp), imagesy($temp)); // Create a blank image
            imagealphablending($image, true);
            $fill = true;
            imagefilledrectangle($image, 0, 0, imagesx($temp), imagesy($temp), imagecolorallocate($image, $GLOBALS['_config']['bgcolorR'], $GLOBALS['_config']['bgcolorG'], $GLOBALS['_config']['bgcolorB']));
            imagecopy($image, $temp, 0, 0, 0, 0, imagesx($temp), imagesy($temp));
            imagedestroy($temp);
            break;
        case 'image/gif':
            //TODO error if gif > 1149184 pixel
            $temp = imagecreatefromgif(_ROOT_ . $path);

            //Fill back ground
            $image = imagecreatetruecolor(imagesx($temp), imagesy($temp)); // Create a blank image
            imagealphablending($image, true);
            $fill = true;
            imagefilledrectangle($image, 0, 0, imagesx($temp), imagesy($temp), imagecolorallocate($image, $GLOBALS['_config']['bgcolorR'], $GLOBALS['_config']['bgcolorG'], $GLOBALS['_config']['bgcolorB']));
            imagecopy($image, $temp, 0, 0, 0, 0, imagesx($temp), imagesy($temp));
            imagedestroy($temp);
            break;
        case 'image/vnd.wap.wbmp':
            //TODO error if gif > 1149184 pixel
            $image = imagecreatefromwbmp(_ROOT_ . $path);
            $fill = false;
            break;
    }

    //Crop image
    $image = crop($image, $cropX, $cropY, $cropW, $cropH, $fill);

    $fill = false;

    //trim image whitespace
    $image = imagetrim($image, imagecolorallocate($image, $GLOBALS['_config']['bgcolorR'], $GLOBALS['_config']['bgcolorG'], $GLOBALS['_config']['bgcolorB']), $fill);

    //TODO grab 0x0's color and trim by it
    //Most images has a white background so trim that even if it isn't the normal site color
    if ($GLOBALS['_config']['bgcolor'] != 'FFFFFF') {
        $image = imagetrim($image, imagecolorallocate($image, 255, 255, 255), $fill);
    }

    $image = resize($image, $maxW, $maxH);

    //flip/mirror
    if ($flip == 1 || $flip == 2) {
        $image = flip($image, $flip);
    }

    switch ($rotate) {
        case 180:
            $image = imagerotate($image, $rotate, 0, 1);
            break;

        case 90:
        case 270:
            $image = rotateImage($image, $rotate);
            break;
    }

    $width = imagesx($image);
    $height = imagesy($image);

    if (@$output['type'] == 'png') {
        $mimeType = 'image/png';
        imagepng($image, _ROOT_ . $output['path'], 9);
    } elseif (@$output['type'] == 'jpg') {
        $mimeType = 'image/jpeg';
        imagejpeg($image, _ROOT_ . $output['path'], 80);
    } elseif ($mimeType == 'image/jpeg') {
        header('Content-Type: image/jpeg');
        imagejpeg($image, null, 80);
        die();
    } else {
        header('Content-Type: image/png');
        imagepng($image, null, 9);
        die();
    }

    imagedestroy($image);

    $filesize = filesize(_ROOT_ . $output['path']);


    //save or output image
    if ($output['filename'] == $pathinfo['filename'] && $output['path'] != $path) {
        $id = db()->fetchOne("SELECT id FROM files WHERE path = '" . $path . "'");
        @unlink(_ROOT_ . $path);
        db()->query("DELETE FROM files WHERE path = '" .$output['path'] . "'");
    } else {
        $id = db()->fetchOne("SELECT id FROM files WHERE path = '" . $output['path'] . "'");
    }
    $id = $id['id'] ?? null;

    if ($id) {
        db()->query("UPDATE files SET path = '".$output['path']."', size = ".$filesize.", mime = '".$mimeType."', width = '".$width."', height = '".$height."' WHERE id = " . $id);
    } else {
        db()->query("INSERT INTO files (path, mime, width, height, size, aspect) VALUES ('".$output['path']."', '" . $mimeType . "', '".$width."', '".$height."', '".$filesize."', NULL )");
        $id = db()->insert_id;
    }

    return ['id' => $id, 'path' => $output['path'], 'width' => $width, 'height' => $height];
}

/**
 * @param resource $image
 * @param int $flip
 */
function flip($image, int $flip)
{
    $width = imagesx($image);
    $height = imagesy($image);

    $temp = imagecreatetruecolor($width, $height);
    //imagealphablending($temp, false);

    if ($flip == 1) {
        for ($x=0; $x<$width; $x++) {
            imagecopy($temp, $image, $width-$x-1, 0, $x, 0, 1, $height);
        }
    } elseif ($flip == 2) {
        for ($y=0; $y<$height; $y++) {
            imagecopy($temp, $image, 0, $height-$y-1, 0, $y, $width, 1);
        }
    }
    imagedestroy($image);
    return $temp;
}

/**
 * @param resource $image
 * @param int $degrees
 */
function rotateImage($image, int $degrees)
{
    $width = imagesx($image);
    $height = imagesy($image);
    $side = $width > $height ? $width : $height;
    $imageSquare = imagecreatetruecolor($side, $side);
    imagecopy($imageSquare, $image, 0, 0, 0, 0, $width, $height);
    imagedestroy($image);
    $imageSquare = imagerotate($imageSquare, $degrees, 0, -1);
    $image = imagecreatetruecolor($height, $width);
    $x = $degrees == 90 ? 0 : ($height > $width ? 0 : ($side - $height));
    $y = $degrees == 270 ? 0 : ($height < $width ? 0 : ($side - $width));
    imagecopy($image, $imageSquare, 0, 0, $x, $y, $height, $width);
    imagedestroy($imageSquare);
    return $image;
}

/**
 * @param resource $image
 * @param int $maxW
 * @param int $maxH
 */
function resize($image, int $maxW, int $maxH)
{
    $imageW = imagesx($image);
    $imageH = imagesy($image);

    if (!$maxW || !$maxH || ($maxW >= $imageW && $maxH >= $imageH)) {
        return $image;
    } else {
        //used by scale and rotate
        $ratio = $imageW/$imageH;

        //witch side exceads the bounds the most
        if ($imageW/$maxW > $imageH/$maxH) {
            $width = $maxW;
            $height = round($maxW / $ratio);
        } else {
            $width = round($maxH * $ratio);
            $height = $maxH;
        }

        $temp = imagecreatetruecolor($width, $height);
        imagecopyresampled($temp, $image, 0, 0, 0, 0, $width, $height, $imageW, $imageH);
        imagedestroy($image);
        return $temp;
    }
}

/**
 * @param resource $image
 * @param int $cropX
 * @param int $cropY
 * @param int $cropW
 * @param int $cropH
 * @param bool $fill
 */
function crop($image, int $cropX, int $cropY, int $cropW, int $cropH, bool $fill = true)
{
    //crop image and set background color
    if (!$cropW) {
        $cropW == imagesx($image);
    }
    if (!$cropH) {
        $cropH == imagesy($image);
    }

    if ($fill == false && ($cropW == imagesx($image) && $cropH == imagesy($image))) {
        return $image;
    } else {
        $temp = imagecreatetruecolor($cropW, $cropH); // Create a blank image
        if ($fill) {
            imagefilledrectangle($temp, 0, 0, $cropW, $cropH, imagecolorallocate($temp, $GLOBALS['_config']['bgcolorR'], $GLOBALS['_config']['bgcolorG'], $GLOBALS['_config']['bgcolorB']));
            imagealphablending($temp, true);
            imagealphablending($image, true);
        }
        imagecopy($temp, $image, 0, 0, $cropX, $cropY, $cropW, $cropH);
        imagedestroy($image);
        return $temp;
    }
}

/**
 * @param resource $image
 * @param int $bg
 * @param bool $fill
 */
function imagetrim($image, int $bg, bool $fill)
{
    // Get the image width and height.
    $imageW = imagesx($image);
    $imageH = imagesy($image);

    //Scann for left
    for ($ix=0; $ix<$imageW; $ix++) {
        for ($iy=0; $iy<$imageH; $iy++) {
            if ($bg != imagecolorat($image, $ix, $iy)) {
                $cropX = $ix;
                //Not set in stone but may provide speed bump
                $cropY = $iy;
                break 2;
            }
        }
    }

    //Scann for top
    for ($iy=0; $iy<$cropY-1; $iy++) {
        for ($ix=0; $ix<$imageW; $ix++) {
            if ($bg != imagecolorat($image, $ix, $iy)) {
                $cropY = $iy;
                break 2;
            }
        }
    }

    //Scann for right
    for ($ix=$imageW-1; $ix>=0; $ix--) {
        for ($iy=$imageH-1; $iy>=0; $iy--) {
            if ($bg != imagecolorat($image, $ix, $iy)) {
                $cropW = $ix - $cropX + 1;
                break 2;
            }
        }
    }

    //Scann for bottom
    for ($iy=$imageH-1; $iy>=0; $iy--) {
        for ($ix=$imageW-1; $ix>=0; $ix--) {
            if ($bg != imagecolorat($image, $ix, $iy)) {
                $cropH = $iy - $cropY + 1;
                break 2;
            }
        }
    }

    //if nothing need changeing then break here.
    return crop($image, $cropX, $cropY, $cropW, $cropH, $fill);
}
