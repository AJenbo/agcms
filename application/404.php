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

$category = null;
$page = null;

if (!isset($_GET['q'])) {
    $redirectUrl = '/?sog=1&q=&sogikke=&minpris=&maxpris=&maerke=';
    $q = preg_replace(
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
    );
    $q = trim($q);
    if ($q) {
        $redirectUrl = '/?q=' . rawurlencode($q) . '&sogikke=&minpris=&maxpris=&maerke=0';
    }

    // Detect and redirect old urls
    $categoryId = (int) preg_replace('/.*kat=([0-9]+).*\s*|.*/u', '\1', $url);
    $pageId = (int) preg_replace('/.*side=([0-9]+).*\s*|.*/u', '\1', $url);
    if (!$pageId && isset($_GET['id'])) {
        $pageId = (int) $_GET['id'];
    }
    $maerkeId = (int) preg_replace('/.*\/maerke([0-9]*)-.*|.*/u', '\1', $url);
    if ($maerkeId || $categoryId || $pageId) {
        if ($categoryId) {
            $category = ORM::getOne(Category::class, $categoryId);
        }
        if ($pageId) {
            $page = ORM::getOne(Page::class, $pageId);
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
            $GLOBALS['side']['404'] = true;
        }
    }

    $categoryId = (int) preg_replace('/.*\/kat([0-9]*)-.*|.*/u', '\1', $url);
    $pageId = (int) preg_replace('/.*\/side([0-9]*)-.*|.*/u', '\1', $url);
    $redirect = !$categoryId && !$pageId;
    if ($categoryId) {
        $category = ORM::getOne(Category::class, $categoryId);
        if (!$category || $category->isInactive()) {
            $category = null;
        }
    }
    if ($pageId) {
        $page = ORM::getOne(Page::class, $pageId);
        if (!$page || $page->isInactive()) {
            $page = null;
        }
    }
    if (($categoryId && !$category)
        || ($pageId && !$page)
        || (!$category && !$page)
    ) {
        if ($page) {
            $redirectUrl = $page->getCanonicalLink(true, $category);
        } elseif ($category) {
            $redirectUrl = '/' . $category->getSlug(true);
        }

        redirect($redirectUrl);
    }
}

$GLOBALS['generatedcontent']['activmenu'] = $category ? $category->getId() : 0;
$GLOBALS['side']['id'] = $page ? $page->getId() : 0;

//TODO stop space efter æøå
header('Status: 200', true, 200);
header('HTTP/1.1 200 OK', true, 200);
require 'index.php';
