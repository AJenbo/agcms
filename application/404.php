<?php
/**
 * Handle SEO requests
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';

//If url !utf-8 make it fucking utf-8 and try again
$url = urldecode($_SERVER['REQUEST_URI']);
//can't detect windows-1252
$encoding = mb_detect_encoding($url, 'UTF-8, ISO-8859-1');
if ($encoding != 'UTF-8') {
    //Firefox uses windows-1252 if it can get away with it
    /**
     * We can't detect windows-1252 from iso-8859-1, but it's a superset, so bouth
     * should handle fine as windows-1252
     */
    if (!$encoding || $encoding == 'ISO-8859-1') {
        $encoding = 'windows-1252';
    }
    $url = mb_convert_encoding($url, 'UTF-8', $encoding);
    //TODO rawurlencode $url (PIE doesn't do it buy it self :(
    $url = implode('/', array_map('rawurlencode', explode('/', $url)));
    redirect($url);
}

if (!isset($_GET['q'])) {
    $redirectUrl = '/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=';
    $q = trim(
        preg_replace(
            [
                '/\/|-|_|\.html|\.htm|\.php|\.gif|\.jpeg|\.jpg|\.png|kat-|side-|\.php/u',
                '/[^\w0-9]/u',
                '/([0-9]+)/u',
                '/([[:upper:]]?[[:lower:]]+)/u',
                '/\s+/u'
            ],
            [
                ' ',
                ' ',
                ' \1 ',
                ' \1',
                ' '
            ],
            $url
        )
    );
    $q = trim($q);
    if ($q) {
        $redirectUrl = '/?q=' . rawurlencode($q) . '&sogikke=&minpris=&maxpris=&maerke=0';
    }

    $category = null;
    $page = null;

    // Detect and redirect old urls
    $categoryId = (int) preg_replace('/.*kat=([0-9]+).*\s*|.*/u', '\1', $url);
    $sideId = (int) preg_replace('/.*side=([0-9]+).*\s*|.*/u', '\1', $url);
    if (!$sideId && isset($_GET['id'])) {
        $sideId = (int) $_GET['id'];
    }
    $maerkeId = (int) preg_replace('/.*\/maerke([0-9]*)-.*|.*/u', '\1', $url);
    if ($maerkeId || $categoryId || $sideId) {
        if ($categoryId) {
            $category = ORM::getOne(Category::class, $categoryId);
        }
        if ($sideId) {
            $page = ORM::getOne(Page::class, $sideId);
            if ($page && !$category) {
                $category = $page->getPrimaryCategory();
            }
        }
        if ($maerkeId) {
            $maerkeet = db()->fetchOne(
                "
                SELECT `id`, `navn`
                FROM `maerke`
                WHERE id = " . $maerkeId
            );
            if ($maerkeet) {
                $redirectUrl = '/m%C3%A6rke' . $maerkeet['id'] . '-' . rawurlencode(clearFileName($maerkeet['navn'])) . '/';
            }
        } elseif ($category || $page) {
            $redirectUrl = '/' . ($category ? $category->getSlug(true) : '') . ($page ? $page->getSlug(true) : '');
        }

        redirect($redirectUrl);
    }

    //Get maerke
    $maerke = (int) preg_replace('/.*\/mærke([0-9]*)-.*|.*/u', '\1', $url);
    if ($maerke) {
        $maerkeet = db()->fetchOne(
            "
            SELECT `id`, `navn`
            FROM `maerke`
            WHERE id = " . $maerke
        );
        if (!$maerkeet) {
            $_GET['sog'] = 1;
            $GLOBALS['side']['404'] = true:
        }
    }

    $GLOBALS['generatedcontent']['activmenu'] = 0;
    $GLOBALS['side']['id'] = 0;
    $sideId = (int) preg_replace('/.*\/side([0-9]*)-.*|.*/u', '\1', $url);
    $categoryId = (int) preg_replace('/.*\/kat([0-9]*)-.*|.*/u', '\1', $url);
    if ($categoryId) {
        $category = ORM::getOne(Category::class, $categoryId);
        if ($category && !$category->isInactive()) {
            $GLOBALS['generatedcontent']['activmenu'] = $category->getId();
        } else {
            $category = null;
        }
    }
    if ($sideId) {
        $page = ORM::getOne(Page::class, $sideId);
        if (!$page || $page->isInactive()) {
            $redirect = true;
        }
        $GLOBALS['side']['id'] = $page->getId();
    }
    if ($redirect) {
        if ($category || $page) {
            if (!$category) {
                $category = $page->getPrimaryCategory();
            }
            $redirectUrl = '/' . ($category ? $category->getSlug(true) : '') . ($page ? $page->getSlug(true) : '');
        }
        redirect($redirectUrl);
    }
}

//TODO stop space efter æøå
header('Status: 200', true, 200);
header('HTTP/1.1 200 OK', true, 200);
require 'index.php';
