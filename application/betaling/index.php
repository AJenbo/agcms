<?php

use AGCMS\Config;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Invoice;
use AGCMS\ORM;
use AGCMS\Render;

/**
 * Pages for taking the user thew the payment process.
 */
require_once __DIR__ . '/../inc/Bootstrap.php';
@include_once _ROOT_ . '/inc/countries.php';

$id = intval($_GET['id'] ?? null);
$checkid = $_GET['checkid'] ?? '';

Render::$pageType = 'custome';
Render::$crumbs = [
    [
        'name' => _('Payment'),
        'link' => '/betaling/' . ($id ? '?id=' . $id . '&checkid=' . rawurlencode($checkid) : ''),
        'icon' => null,
    ],
];

if (!empty($id) && $checkid === getCheckid($id) && !isset($_GET['txnid'])) {
    $invalid = [];

    /** @var Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);

    if (in_array($invoice->getStatus(), ['new', 'locked', 'pbserror'])) {
        if (empty($_GET['step'])) { //Show order
            $invoice->setStatus('locked')->save();

            Render::$crumbs = [[
                'name' => _('Order #') . $id,
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ]];
            Render::$title = _('Order #') . $id;
            Render::$headline = _('Order #') . $id;
            Render::$bodyHtml = Render::render('partial-order-form', compact('invoice'));
        } elseif ($_GET['step'] == 1) { //Fill out customer info
            if ($_POST) {
                $invoice->setName($_POST['navn'])
                    ->setAtt($_POST['att'] != $_POST['navn'] ? $_POST['att'] : '')
                    ->setAddress($_POST['adresse'])
                    ->setPostbox($_POST['postbox'])
                    ->setPostcode($_POST['postnr'])
                    ->setCity($_POST['by'])
                    ->setCountry($_POST['land'])
                    ->setEmail($_POST['email'])
                    ->setPhone1($_POST['tlf1'] != $_POST['tlf2'] ? $_POST['tlf1'] : '')
                    ->setPhone2($_POST['tlf2'])
                    ->setHasShippingAddress(!empty($_POST['altpost']))
                    ->setShippingPhone($_POST['posttlf'])
                    ->setShippingName($_POST['postname'])
                    ->setShippingAtt($_POST['postatt'] != $_POST['postname'] ? $_POST['postatt'] : '')
                    ->setShippingAddress($_POST['postaddress'])
                    ->setShippingAddress2($_POST['postaddress2'])
                    ->setShippingPostbox($_POST['postpostbox'])
                    ->setShippingPostcode($_POST['postpostalcode'])
                    ->setShippingCity($_POST['postcity'])
                    ->setShippingCountry($_POST['postcountry'])
                    ->setNote($_POST['note'] ?? '')
                    ->save();
            }

            $invalid = $invoice->getInvalid();

            //TODO move down to skip address page if valid
            if ($_POST && !$invalid) {
                if (!empty($_POST['newsletter'])) {
                    // TODO check if email already exists and overwrite.
                    $conteact = new Contact([
                        'title'      => $updates['navn'],
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

                redirect('/betaling/?id=' . $id . '&checkid=' . $checkid . '&step=2');
            }

            //TODO add enote
            Render::$crumbs[] = [
                'name' => _('Recipient'),
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ];
            Render::$title = _('Recipient');
            Render::$headline = _('Recipient');

            $data = [
                'countries' => $countries,
                'newsletter' => !empty($_POST['newsletter']),
                'invoice' => $invoice,
                'invalid' => $invalid,
                'submitLabel' => 'Proceed to the terms of trade',
            ];
            Render::$bodyHtml = Render::render('partial-payment-form1', $data);
        } elseif ($_GET['step'] == 2) { //Accept terms and continue to payment
            if ($invoice->getInvalid()) {
                redirect('/betaling/?id=' . $id . '&checkid=' . $checkid . '&step=1');
            }

            $invoice->setStatus('locked')->save();

            Render::$crumbs[] = [
                'name' => _('Trade Conditions'),
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ];
            Render::$title = _('Trade Conditions');
            Render::$headline = _('Trade Conditions');

            $inputs = [
                'group'             => Config::get('pbsfix'),
                'merchantnumber'    => Config::get('pbsid'),
                'orderid'           => Config::get('pbsfix') . $invoice->getId(),
                'currency'          => 208,
                'amount'            => number_format($invoice->getAmount(), 2, '', ''),
                'ownreceipt'        => 1,
                'accepturl'         => Config::get('base_url') . '/betaling/?id=' . $id . '&checkid=' . $checkid,
                'cancelurl'         => Config::get('base_url') . $_SERVER['REQUEST_URI'],
                'windowstate'       => 3,
                'windowid'          => Config::get('pbswindow'),
            ];
            $inputs['hash'] = md5(implode('', $submit) . Config::get('pbspassword'));

            $data = [
                'html' => ORM::getOne(CustomPage::class, 3)->getHtml(),
                'inputs' => $inputs,
            ];
            Render::$bodyHtml = Render::render('partial-payment-form2.html', $data);
        }
    } else { //Show order status
        Render::$crumbs[] = [
            'name' => in_array($invoice->getStatus(), ['pbsok', 'accepted'], true) ? _('Status') : _('Error'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ];
        Render::$title = _('Error');
        Render::$headline = _('Error');
        Render::$bodyHtml = _('An errror occured.');
        if ($invoice->getStatus() == 'pbsok') {
            Render::$title = _('Status');
            Render::$headline = _('Status');
            Render::$bodyHtml = _('Payment received.');
        } elseif ($invoice->getStatus() == 'accepted') {
            Render::$title = _('Status');
            Render::$headline = _('Status');
            Render::$bodyHtml = _('The payment was received and the package is sent.');
        } elseif ($invoice->getStatus() == 'giro') {
            Render::$bodyHtml = _('The payment is already received in cash.');
        } elseif ($invoice->getStatus() == 'cash') {
            Render::$bodyHtml = _('The payment is already received in cash.');
        } elseif ($invoice->getStatus() == 'canceled') {
            Render::$bodyHtml = _('The transaction is canceled.');
        } elseif ($invoice->getStatus() == 'rejected') {
            Render::$bodyHtml = _('Payment rejected.');
        }
    }
} elseif (isset($_GET['txnid'])) {
    Render::$crumbs = [
        [
            'name' => _('Error'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ],
    ];
    Render::$title = _('Error');
    Render::$headline = _('Error');
    Render::$bodyHtml = _('An unknown error occured.');

    $amount = intval($_GET['amount'] ?? 0);

    $params = $_GET;
    unset($params['hash']);
    $eKey = md5(implode('', $params) . Config::get('pbspassword'));
    unset($params);

    /** @var Invoice */
    $invoice = ORM::getOne(Invoice::class, $id);

    $adminEmailSubject = _('Payment code was tampered with!');
    $adminEmailTemplate = 'email-admin-payment-error';

    if (!$invoice) {
        Render::$bodyHtml = _('The payment does not exist in our system.');
        $adminEmailSubject = _('Payment not found!');
        $adminEmailTemplate = 'email-admin-payment-404';
    } elseif (in_array($invoice->getStatus(), ['canceled', 'rejected'])) {
        Render::$crumbs[] = [
            'name' => _('Reciept'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ];
        Render::$title = _('Reciept');
        Render::$headline = _('Reciept');
        Render::$bodyHtml = _('This trade has been canceled or refused.');
        $adminEmailSubject = _('Payment cancled');
        $adminEmailTemplate = 'email-admin-payment-cancle';
    } elseif (!in_array($invoice->getStatus(), ['locked', 'new', 'pbserror'])) {
        Render::$crumbs[] = [
            'name' => _('Reciept'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ];
        Render::$title = _('Reciept');
        Render::$headline = _('Reciept');
        Render::$bodyHtml = _('Payment is registered and you ought to have received a receipt by email.');
        $adminEmailSubject = _('Viewed payment');
        $adminEmailTemplate = 'email-admin-payment-blocked';
    } elseif ($eKey == $_GET['hash']) {
        Render::$crumbs[] = [
            'name' => _('Reciept'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ];
        Render::$title = _('Reciept');
        Render::$headline = _('Reciept');

        $cardtype = [
            1  => 'Dankort/Visa-Dankort',
            2  => 'eDankort',
            3  => 'Visa / Visa Electron',
            4  => 'MastercCard',
            6  => 'JCB',
            7  => 'Maestro',
            8  => 'Diners Club',
            9  => 'American Express',
            11 => 'Forbrugsforeningen',
            12 => 'Nordea e-betaling',
            13 => 'Danske Netbetalinger',
            14 => 'PayPal',
            17 => 'Klarna',
            18 => 'SveaWebPay',
            23 => 'ViaBill',
            24 => 'NemPay',
        ];

        $invoice->setCardtype($cardtype[$_GET['paymenttype']])->save();

        Render::$bodyHtml = Render::render('partial-payment-confirmation');

        $adminEmailSubject = _('Payment complete');
        $adminEmailTemplate = 'email-admin-payment-confirmation';

        $withTax = $invoice->getAmount() - $invoice->getShipping();
        $tax = $withTax * (1 - (1 / (1 + $invoice->getVat())));

        Render::$track = "ga('ecommerce:addTransaction',{'id':'" . $invoice->getId()
        . "','revenue':'" . $invoice->getAmount()
        . "','shipping':'" . $invoice->getShipping()
        . "','tax':'" . $tax . "'});";
        foreach ($invoice->getItems() as $item) {
            Render::$track .= "ga('ecommerce:addItem',{'id':'" . $invoice->getId()
            . "','name':" . json_encode($item['title'])
            . ",'price': '" . ($item['value'] * (1 + $invoice->getVat()))
            . "','quantity': '" . $item['quantity'] . "'});";
        }
        Render::$track .= "ga('ecommerce:send');";

        if (!valideMail($faktura['department'])) {
            $faktura['department'] = first(Config::get('emails'))['address'];
        }

        $data = [
            'invoice'  => $invoice,
            'siteName' => Config::get('site_name'),
            'address'  => Config::get('address'),
            'postcode' => Config::get('postcode'),
            'city'     => Config::get('city'),
            'phone'    => Config::get('phone'),
        ];
        sendEmails(
            sprintf(_('Order #%d - payment completed'), $invoice->getId()),
            Render::render('email-payment-confirmation', $data),
            $invoice->getDepartment(),
            '',
            $invoice->getEmail(),
            $invoice->getName()
        );
    }

    //To shop
    $invoice = ORM::getOne(Invoice::class, $id);
    if ($invoice) {
        if (!valideMail($invoice->getDepartment())) {
            $invoice->setDepartment(first(Config::get('emails'))['address']);
        }

        sendEmails(
            sprintf(_('Attn.: %s - Online invoice #%d : %s'), $invoice->getClerk(), $id, $adminEmailSubject),
            Render::render($adminEmailTemplate, ['invoice' => $invoice]),
            $invoice->getDepartment(),
            '',
            $invoice->getDepartment()
        );
    }
} else {
    Render::$title = _('Payment');
    Render::$headline = _('Payment');

    $data = [
        'id'      => $id,
        'checkid' => $checkid,
    ];
    Render::$bodyHtml = Render::render('partial-payment-form0', $data);
    if ($checkid) {
        Render::$bodyHtml = _('The code is not correct!');
    }
}

Render::outputPage();
