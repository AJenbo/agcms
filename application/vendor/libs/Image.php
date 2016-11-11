<?php

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
