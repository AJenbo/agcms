<?php

use AGCMS\Entity\Contact;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\Page;
use AGCMS\Entity\Table;
use AGCMS\ORM;
use AGCMS\Render;

/**
 * Page for sending an order request.
 */
require_once __DIR__ . '/../inc/Bootstrap.php';
@include_once _ROOT_ . '/inc/countries.php';

// Add item to basket
$rowId = 0;
if (($pageId = (int) ($_GET['add'] ?? 0)) || ($rowId = (int) ($_GET['add_list_item'] ?? 0))) {
    $redirectUrl = '/';
    if ($_SERVER['HTTP_REFERER']) {
        $redirectUrl = $_SERVER['HTTP_REFERER'];
    }

    $productTitle = '';
    $productPrice = null;
    if ($rowId) { // Find item based on price table row
        $listRow = db()->fetchOne(
            "
            SELECT *
            FROM `list_rows`
            WHERE id = " . $rowId
        );
        Render::addLoadedTable('list_rows');
        if (!$listRow) {
            redirect($redirectUrl); // Row does not exist
        }

        $cells = explode('<', $listRow['cells']);
        $cells = array_map('html_entity_decode', $cells);

        $table = ORM::getOne(Table::class, $listRow['list_id']);
        if (!$table) {
            redirect($redirectUrl);
        }
        foreach ($table->getColumns() as $i => $column) {
            if (in_array($column['type'], [Table::COLUMN_TYPE_STRING, Table::COLUMN_TYPE_INT], true)) {
                $productTitle .= ' ' . ($cells[$i] ?? '');
            } elseif (in_array($column['type'], [Table::COLUMN_TYPE_PRICE, Table::COLUMN_TYPE_PRICE_NEW], true)) {
                $productPrice = intval($cells[$i] ?? 0) ?: $productPrice;
            }
        }
        $productTitle = trim($productTitle);
        $pageId = $pageId ?: $table->getPage()->getId();
    }

    if ($pageId) { // Finde item based on page
        $page = ORM::getOne(Page::class, $pageId);
        if (!$page || $page->isInactive()) {
            redirect($redirectUrl); // Product does not exist or is disabled
        }

        if (!$productTitle) {
            $productTitle = $page->getTitle();
            if ($page->getSku()) {
                if ($productTitle) {
                    $productTitle .= ' - ';
                }

                $productTitle .= $page->getSku();
            }
        }
        $productPrice = $productPrice ?: $page->getPrice();
        $redirectUrl = $page->getCanonicalLink();
    }

    session_start();
    $_SESSION['faktura']['products'] = $_SESSION['faktura']['products'] ?? [];
    if (($productId = array_search($productTitle, $_SESSION['faktura']['products'])) !== false) {
        ++$_SESSION['faktura']['quantities'][$productId]; // Increment item quantity
    } else {
        // Add new item to basket
        $_SESSION['faktura']['quantities'][] = 1;
        $_SESSION['faktura']['products'][] = $productTitle;
        $_SESSION['faktura']['values'][] = $productPrice;
    }
    redirect($redirectUrl);
}

// Shopping process
session_start();
Render::$pageType = 'custome';
if (empty($_SESSION['faktura']['note'])) {
    $_SESSION['faktura']['note'] = '';
}

unset($_POST['values'], $_POST['products']);

if (count($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION['faktura'][(int) $key] = $value;
    }
    $_SESSION['faktura']['note'] = $_POST['note'] ?? $_SESSION['faktura']['note'];
}
if (!empty($_POST['paymethod'])) {
    $_SESSION['faktura']['paymethod'] = $_POST['paymethod'];
}
if (!empty($_POST['delevery'])) {
    $_SESSION['faktura']['delevery'] = $_POST['delevery'];
}

//Generate return page
Render::$crumbs = [
    [
        'name' => _('Payment'),
        'link' => '/',
        'icon' => null,
    ],
];

Render::$pageType = 'custome';

if (!empty($_SESSION['faktura']['quantities'])) {
    if (empty($_GET['step'])) {
        if (!empty($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $i => $quantiy) {
                if ($quantiy < 1) {
                    unset($_SESSION['faktura']['quantities'][$i], $_SESSION['faktura']['products'][$i], $_SESSION['faktura']['values'][$i]);
                } else {
                    $_SESSION['faktura']['quantities'][$i] = (int) $quantiy;
                }
            }
            $_SESSION['faktura']['quantities'] = array_values($_SESSION['faktura']['quantities']);
            $_SESSION['faktura']['products'] = array_values($_SESSION['faktura']['products']);
            $_SESSION['faktura']['values'] = array_values($_SESSION['faktura']['values']);

            redirect('/bestilling/?step=1');
        }

        $_SESSION['faktura']['amount'] = 0;
        foreach ($_SESSION['faktura']['quantities'] as $i => $quantity) {
            $_SESSION['faktura']['amount'] += $_SESSION['faktura']['values'][$i] * $quantity;
        }

        Render::$crumbs = [
            [
                'name' => _('Place order'),
                'link' => '#',
                'icon' => null,
            ],
        ];
        Render::$title = _('Place order');
        Render::$headline = _('Place order');

        $data = [
            'invoice' => invoiceFromSession(),
            'payMethod' => $_SESSION['faktura']['paymethod'] ?? '',
            'deleveryMethode' => $_SESSION['faktura']['delevery'] ?? '',
            'note' => $_SESSION['faktura']['note'] ?? '',
        ];
        Render::$bodyHtml = Render::render('partial-order-form', $data);
    } elseif ($_GET['step'] == 1) {
        if (empty($_SESSION['faktura']['postcountry'])) {
            $_SESSION['faktura']['postcountry'] = 'DK';
        }
        if (empty($_SESSION['faktura']['land'])) {
            $_SESSION['faktura']['land'] = 'DK';
        }

        if ($_POST) {
            $updates = [
                'navn'           => $_POST['navn'],
                'att'            => $_POST['att'] != $_POST['navn'] ? $_POST['att'] : '',
                'adresse'        => $_POST['adresse'],
                'postbox'        => $_POST['postbox'],
                'postnr'         => $_POST['postnr'],
                'by'             => $_POST['by'],
                'land'           => $_POST['land'],
                'email'          => $_POST['email'],
                'tlf1'           => $_POST['tlf1'] != $_POST['tlf2'] ? $_POST['tlf1'] : '',
                'tlf2'           => $_POST['tlf2'],
                'altpost'        => (int) !empty($_POST['altpost']),
                'posttlf'        => $_POST['posttlf'],
                'postname'       => $_POST['postname'],
                'postatt'        => $_POST['postatt'] != $_POST['postname'] ? $_POST['postatt'] : '',
                'postaddress'    => $_POST['postaddress'],
                'postaddress2'   => $_POST['postaddress2'],
                'postpostbox'    => $_POST['postpostbox'],
                'postpostalcode' => $_POST['postpostalcode'],
                'postcity'       => $_POST['postcity'],
                'postcountry'    => $_POST['postcountry'],
                'note'           => $_POST['note'] ?? '',
            ];
            $updates = array_map('trim', $updates);

            $_SESSION['faktura'] = $updates + $_SESSION['faktura'];
        }

        $invoice = invoiceFromSession();
        $invalid = $invoice->getInvalid();

        if ($_POST && !$invalid) {
            if (!empty($_POST['newsletter'])) {
                $conteact = new Contact([
                    'name'       => $updates['navn'],
                    'email'      => $updates['email'],
                    'address'    => $updates['adresse'],
                    'country'    => $countries[$updates['land']],
                    'postcode'   => $updates['postnr'],
                    'city'       => $updates['by'],
                    'phone1'     => $updates['tlf1'],
                    'phone2'     => $updates['tlf2'],
                    'newsletter' => 1,
                    'ip'         => $_SERVER['REMOTE_ADDR'],
                ]);
                $conteact->save();
            }
            redirect('/bestilling/?step=2');
        }

        Render::$crumbs = [
            [
                'name' => _('Recipient'),
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ],
        ];
        Render::$title = _('Recipient');
        Render::$headline = _('Recipient');

        $data = [
            'countries' => $countries,
            'newsletter' => !empty($_POST['newsletter']),
            'invoice' => $invoice,
            'invalid' => $invalid,
            'submitLabel' => 'Send order',
        ];
        Render::$bodyHtml = Render::render('partial-order-form1', $data);
    } elseif ($_GET['step'] == 2) {
        if (!$_SESSION['faktura'] || !$_SESSION['faktura']['email']) {
            redirect('/bestilling/');
        }

        $invoice = invoiceFromSession();
        $invoice->save();

        sendEmails(
            _('Online order #') . $invoice->getId(),
            Render::render('email-order-notification', compact('invoice')),
            $invoice->getEmail(),
            $invoice->getName()
        );

        Render::$title = _('Order placed');
        Render::$headline = _('Order placed');
        Render::$bodyHtml = _(
            'Thank you for your order, you will recive an email with instructions on how to perform the payment as soon as we have validated that all goods are in stock.'
        );

        session_destroy();
    }
} else {
    Render::$title = _('Place order');
    Render::$headline = _('Place order');
    Render::$bodyHtml = _('Ther is no content in the basket!');
}

Render::outputPage();
