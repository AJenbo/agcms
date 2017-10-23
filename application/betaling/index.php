<?php

use AGCMS\Config;
use AGCMS\Entity\Contact;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Invoice;
use AGCMS\ORM;
use AGCMS\Render;

/**
 * Pages for taking the user thew the payment process.
 */
require_once __DIR__ . '/../inc/Bootstrap.php';
$countries = [];
include _ROOT_ . '/inc/countries.php';

$id = (int) request()->get('id', 0) ?: null;
$checkid = request()->get('checkid', '');

Render::$pageType = 'custome';
Render::$crumbs = [[
    'title' => _('Payment'),
    'canonicalLink' => '/betaling/' . ($id ? '?id=' . $id . '&checkid=' . rawurlencode($checkid) : ''),
]];

/** @var Invoice */
$invoice = $id ? ORM::getOne(Invoice::class, $id) : null;

if (request()->query->has('txnid')) {
    Render::$title = _('Error');
    Render::$headline = _('Error');

    $amount = (float) request()->get('amount', 0);

    $params = request()->query->all();
    unset($params['hash']);
    $eKey = md5(implode('', $params) . Config::get('pbspassword'));
    unset($params);

    if (!$invoice) {
        Render::$bodyHtml = _('The payment does not exist in our system.');
        Render::outputPage();
        return;
    }

    $adminEmailSubject = _('Payment code was tampered with!');
    $adminEmailTemplate = 'email-admin-payment-error';

    if (!valideMail($invoice->getDepartment())) {
        $invoice->setDepartment(first(Config::get('emails'))['address']);
    }

    Render::$bodyHtml = _('An unknown error occured.');
    assert($invoice instanceof Invoice);
    if (in_array($invoice->getStatus(), ['canceled', 'rejected'])) {
        Render::$crumbs[] = [
            'title' => _('Reciept'),
            'canonicalLink' => urldecode(request()->getRequestUri()),
        ];
        Render::$title = _('Reciept');
        Render::$headline = _('Reciept');
        Render::$bodyHtml = _('This trade has been canceled or refused.');
        $adminEmailSubject = _('Payment cancled');
        $adminEmailTemplate = 'email-admin-payment-cancle';
    } elseif (!in_array($invoice->getStatus(), ['locked', 'new', 'pbserror'])) {
        Render::$crumbs[] = [
            'title' => _('Reciept'),
            'canonicalLink' => urldecode(request()->getRequestUri()),
        ];
        Render::$title = _('Reciept');
        Render::$headline = _('Reciept');
        Render::$bodyHtml = _('Payment is registered and you ought to have received a receipt by email.');
        $adminEmailSubject = _('Viewed payment');
        $adminEmailTemplate = 'email-admin-payment-blocked';
    } elseif ($eKey === request()->get('hash')) {
        Render::$crumbs[] = [
            'title' => _('Reciept'),
            'canonicalLink' => urldecode(request()->getRequestUri()),
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

        $invoice->setCardtype($cardtype[request()->get('paymenttype')])
            ->setStatus('pbsok')
            ->setTimeStampPay(time());

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

        if (!valideMail($invoice->getDepartment())) {
            $invoice->setDepartment(first(Config::get('emails'))['address']);
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

    $invoice->save();

    sendEmails(
        sprintf(_('Attn.: %s - Online invoice #%d : %s'), $invoice->getClerk(), $id, $adminEmailSubject),
        Render::render($adminEmailTemplate, ['invoice' => $invoice]),
        $invoice->getDepartment(),
        '',
        $invoice->getDepartment()
    );

    Render::outputPage();
    return;
}

if (!$invoice || $checkid !== $invoice->getCheckid()) {
    Render::$title = _('Payment');
    Render::$headline = _('Payment');

    $data = [
        'id'      => $id,
        'checkid' => $checkid,
    ];
    Render::$bodyHtml = Render::render('partial-payment-manual', $data);
    if ($invoice && $checkid) {
        Render::$bodyHtml = _('The code is not correct!');
    }
    Render::outputPage();
    return;
}
assert($invoice instanceof Invoice);

if (in_array($invoice->getStatus(), ['new', 'locked', 'pbserror'])) {
    if (!request()->get('step')) { //Show order
        $invoice->setStatus('locked');

        Render::$crumbs = [[
            'title' => _('Payment'),
            'canonicalLink' => $invoice->getLink(),
        ]];
        Render::$title = _('Order #') . $id;
        Render::$headline = _('Order #') . $id;
        Render::$bodyHtml = Render::render('partial-payment-form0', ['invoice' => $invoice]);
    } elseif (1 === request()->query->getInt('step')) { //Fill out customer info
        if (request()->request->count()) {
            $invoice->setName(request()->get('navn'))
                ->setAtt(request()->get('att') !== request()->get('navn') ? request()->get('att') : '')
                ->setAddress(request()->get('adresse'))
                ->setPostbox(request()->get('postbox'))
                ->setPostcode(request()->get('postnr'))
                ->setCity(request()->get('by'))
                ->setCountry(request()->get('land'))
                ->setEmail(request()->get('email'))
                ->setPhone1(request()->get('tlf1') !== request()->get('tlf2') ? request()->get('tlf1') : '')
                ->setPhone2(request()->get('tlf2'))
                ->setHasShippingAddress(request()->request->getBoolean('altpost'))
                ->setShippingPhone(request()->get('posttlf'))
                ->setShippingName(request()->get('postname'))
                ->setShippingAtt(request()->get('postatt') !== request()->get('postname') ? request()->get('postatt') : '')
                ->setShippingAddress(request()->get('postaddress'))
                ->setShippingAddress2(request()->get('postaddress2'))
                ->setShippingPostbox(request()->get('postpostbox'))
                ->setShippingPostcode(request()->get('postpostalcode'))
                ->setShippingCity(request()->get('postcity'))
                ->setShippingCountry(request()->get('postcountry'))
                ->setNote(request()->get('note', ''))
                ->save();
        }

        $invalid = $invoice->getInvalid();

        if (request()->request->count() && !$invalid) {
            if (request()->request->getBoolean('newsletter')) {
                // TODO check if email already exists and overwrite.
                $conteact = new Contact([
                    'name'       => $invoice->getName(),
                    'email'      => $invoice->getEmail(),
                    'address'    => $invoice->getAddress(),
                    'country'    => $countries[$invoice->getCountry()],
                    'postcode'   => $invoice->getPostcode(),
                    'city'       => $invoice->getCity(),
                    'phone1'     => $invoice->getPhone1(),
                    'phone2'     => $invoice->getPhone2(),
                    'newsletter' => 1,
                    'ip'         => request()->getClientIp(),
                ]);
                $conteact->save();
            }

            redirect($invoice->getLink() . '&step=2');
        }

        Render::$crumbs[] = [
            'title'         => _('Recipient'),
            'canonicalLink' => $invoice->getLink() . '&step=1',
        ];
        Render::$title = _('Recipient');
        Render::$headline = _('Recipient');

        $data = [
            'countries'   => $countries,
            'newsletter'  => request()->request->getBoolean('newsletter'),
            'invoice'     => $invoice,
            'invalid'     => $invalid,
            'submitLabel' => 'Proceed to the terms of trade',
        ];
        Render::$bodyHtml = Render::render('partial-order-form1', $data);
    } elseif (2 === request()->query->getInt('step')) { //Accept terms and continue to payment
        if ($invoice->getInvalid()) {
            redirect($invoice->getLink() . '&step=1');
        }

        $invoice->setStatus('locked');

        Render::$crumbs[] = [
            'title'         => _('Recipient'),
            'canonicalLink' => $invoice->getLink() . '&step=1',
        ];
        Render::$crumbs[] = [
            'title'         => _('Trade Conditions'),
            'canonicalLink' => $invoice->getLink() . '&step=2',
        ];
        Render::$title = _('Trade Conditions');
        Render::$headline = _('Trade Conditions');

        $inputs = [
            'group'          => Config::get('pbsfix'),
            'merchantnumber' => Config::get('pbsid'),
            'orderid'        => Config::get('pbsfix') . $invoice->getId(),
            'currency'       => 208,
            'amount'         => number_format($invoice->getAmount(), 2, '', ''),
            'ownreceipt'     => 1,
            'accepturl'      => $invoice->getLink(),
            'cancelurl'      => Config::get('base_url') . request()->getRequestUri(),
            'windowstate'    => 3,
            'windowid'       => Config::get('pbswindow'),
        ];
        $inputs['hash'] = md5(implode('', $inputs) . Config::get('pbspassword'));

        $page = ORM::getOne(CustomPage::class, 3);
        assert($page instanceof CustomPage);
        $data = [
            'html'   => $page->getHtml(),
            'inputs' => $inputs,
        ];
        Render::$bodyHtml = Render::render('partial-payment-form2', $data);
    }

    $invoice->save();
} else { //Show order status
    Render::$crumbs = [[
        'title' => in_array($invoice->getStatus(), ['pbsok', 'accepted', 'giro', 'cash', 'canceled'], true) ? _('Receipt') : _('Error'),
        'canonicalLink' => $invoice->getLink(),
    ]];
    Render::$title = _('Error');
    Render::$headline = _('Error');
    Render::$bodyHtml = _('An errror occured.');
    if ('pbsok' == $invoice->getStatus()) {
        Render::$title = _('Receipt');
        Render::$headline = _('Receipt');
        Render::$bodyHtml = _('Payment received.');
    } elseif ('accepted' == $invoice->getStatus()) {
        Render::$title = _('Receipt');
        Render::$headline = _('Receipt');
        Render::$bodyHtml = _('The payment was received and the package is sent.');
    } elseif ('giro' == $invoice->getStatus()) {
        Render::$title = _('Receipt');
        Render::$headline = _('Receipt');
        Render::$bodyHtml = _('The payment is already received via giro.');
    } elseif ('cash' == $invoice->getStatus()) {
        Render::$title = _('Receipt');
        Render::$headline = _('Receipt');
        Render::$bodyHtml = _('The payment is already received in cash.');
    } elseif ('canceled' == $invoice->getStatus()) {
        Render::$title = _('Receipt');
        Render::$headline = _('Receipt');
        Render::$bodyHtml = _('The transaction is canceled.');
    } elseif ('rejected' == $invoice->getStatus()) {
        Render::$bodyHtml = _('Payment rejected.');
    }
}

Render::outputPage();
