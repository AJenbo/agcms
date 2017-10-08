<?php

use AGCMS\Config;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\EpaymentAdminService;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\User;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';
@include_once _ROOT_ . '/inc/countries.php';

if (request()->get('function') === 'new') {
    redirect('faktura.php?id=' . newfaktura());
}

/** @var Invoice */
$invoice = ORM::getOne(Invoice::class, request()->get('id'));

if ($invoice && $invoice->getStatus() !== 'new') {
    try {
        $epaymentService = new EpaymentAdminService(Config::get('pbsid'), Config::get('pbspwd'));
        $epayment = $epaymentService->getPayment(Config::get('pbsfix') . $invoice->getId());

        if ($epayment->isAnnulled() && !in_array($invoice->getStatus(), ['rejected', 'giro', 'cash', 'canceled'])) {
            // Annulled. The card payment has been deleted by the Merchant, prior to Acquisition.
            $invoice->setStatus('rejected')->save();
        } elseif ($epayment->getAmountCaptured() && !in_array($invoice->getStatus(), ['accepted', 'giro', 'cash'])) {
            // The payment/order placement has been carried out: Paid.
            $invoice->setStatus('accepted')->save();
        } elseif ($epayment->isAuthorized() && !in_array($invoice->getStatus(), ['pbsok', 'giro', 'cash'])) {
            // Authorised. The card payment is authorised and awaiting confirmation and Acquisition.
            $invoice->setStatus('pbsok')->save();
        } elseif (!$epayment->getId() && $invoice->getStatus() === 'pbsok') {
            $invoice->setStatus('locked')->save();
        }
    } catch (SoapFault $e) {
        echo 'Der er opstået en fejl i komunikationen med ePay: ' . $e->getMessage();
        exit;
    }
}

Sajax::export([
    'getAddress'   => ['method' => 'GET', 'uri' => '/ajax.php'],
    'sendReminder' => ['method' => 'GET'],
    'valideMail'   => ['method' => 'GET'],
    'annul'        => ['method' => 'POST'],
    'copytonew'    => ['method' => 'POST'],
    'newfaktura'   => ['method' => 'POST'],
    'pbsconfirm'   => ['method' => 'POST'],
    'save'         => ['method' => 'POST'],
]);
Sajax::handleClientRequest();

if (!$invoice->getClerk()) {
    $invoice->setClerk(curentUser()->getFullName());
}

$data = getBasicAdminTemplateData();
$data = [
    'title' => _('Online Invoice #') . $invoice->getId(),
    'javascript' => $data['javascript'] . ' var status = ' . json_encode($invoice->getStatus()) . ';',
    'currentUser' => curentUser(),
    'users' => ORM::getByQuery(User::class, "SELECT * FROM `users` ORDER BY fullname"),
    'invoice' => $invoice,
    'departments' => array_keys(Config::get('emails', [])),
    'countries' => $countries,
] + $data;

Render::output('admin-faktura', $data);
