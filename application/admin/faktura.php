<?php

use AGCMS\Config;
use AGCMS\Entity\Invoice;
use AGCMS\Entity\User;
use AGCMS\EpaymentAdminService;
use AGCMS\ORM;
use AGCMS\Render;
use Exception;

require_once __DIR__ . '/logon.php';
$countries = [];
include _ROOT_ . '/inc/countries.php';

if ('new' === request()->get('function')) {
    redirect('faktura.php?id=' . newfaktura());
}

/** @var Invoice */
$invoice = ORM::getOne(Invoice::class, request()->get('id'));
assert($invoice instanceof Invoice);

if ($invoice && 'new' !== $invoice->getStatus()) {
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
        } elseif (!$epayment->getId() && 'pbsok' === $invoice->getStatus()) {
            $invoice->setStatus('locked')->save();
        }
    } catch (SoapFault $e) {
        throw new Exception('Der er opstået en fejl i komunikationen med ePay: ' . $e->getMessage(), 0, $e);
    }
}

if (!$invoice->getClerk()) {
    $invoice->setClerk(curentUser()->getFullName());
}

$data = getBasicAdminTemplateData();
$data = [
    'title'       => _('Online Invoice #') . $invoice->getId(),
    'status'      => $invoice->getStatus(),
    'currentUser' => curentUser(),
    'users'       => ORM::getByQuery(User::class, 'SELECT * FROM `users` ORDER BY fullname'),
    'invoice'     => $invoice,
    'departments' => array_keys(Config::get('emails', [])),
    'countries'   => $countries,
] + $data;

Render::output('admin-faktura', $data);
