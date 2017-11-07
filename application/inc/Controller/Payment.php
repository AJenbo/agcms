<?php namespace AGCMS\Controller;

use AGCMS\Config;
use AGCMS\Entity\CustomPage;
use AGCMS\Entity\Invoice;
use AGCMS\EpaymentAdminService;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Service\InvoiceService;
use AGCMS\VolatilePage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Payment extends Base
{
    /** @var InvoiceService */
    private $invoiceService;

    /**
     * Initialize needed services.
     */
    public function __construct()
    {
        $this->invoiceService = new InvoiceService();
    }

    /**
     * Page for manually entering the id and checkid code.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $data = $this->basicPageData();

        $renderable = new VolatilePage(_('Payment'), $request->getRequestUri());
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['id'] = $request->get('id');
        $data['checkid'] = $request->get('checkid');

        $content = Render::render('payment-manual', $data);

        return new Response($content);
    }

    /**
     * Show the items in the shopping basket.
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function basket(Request $request, int $id, string $checkId): Response
    {
        /** @var Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if ($redirect = $this->checkStatus($request, $id, $checkId, $invoice)) {
            return $redirect;
        }
        assert($invoice instanceof Invoice);

        $invoice->setStatus('locked')->save();

        $data = $this->basicPageData();

        $renderable = new VolatilePage(_('Order #') . $id, $invoice->getLink());
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['invoice'] = $invoice;

        $content = Render::render('payment-form0', $data);

        return new Response($content);
    }

    /**
     * Page for user to correct there contact info
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function address(Request $request, int $id, string $checkId): Response
    {
        /** @var Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if ($redirect = $this->checkStatus($request, $id, $checkId, $invoice)) {
            return $redirect;
        }
        assert($invoice instanceof Invoice);

        $data = $this->basicPageData();

        $countries = [];
        include _ROOT_ . '/inc/countries.php';

        $data['countries'] = $countries;
        $data['crumbs'][] = new VolatilePage(_('Order #') . $id, $invoice->getLink());
        $renderable = new VolatilePage(_('Address'), $invoice->getLink() . 'address/');
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['newsletter'] = $request->query->getBoolean('newsletter');
        $data['invoice'] = $invoice;
        $data['invalid'] = $invoice->getInvalid();
        $data['action'] = $invoice->getLink() . 'address/';

        $content = Render::render('order-form1', $data);

        return new Response($content);
    }

    /**
     * Update the contact infor and forwared the user in the payment process
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function addressSave(Request $request, int $id, string $checkId): Response
    {
        /** @var Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if ($redirect = $this->checkStatus($request, $id, $checkId, $invoice)) {
            return $redirect;
        }
        assert($invoice instanceof Invoice);

        $invoice->setStatus('locked')
            ->setName($request->get('name'))
            ->setAttn($request->get('attn') !== $request->get('name') ? $request->get('attn') : '')
            ->setAddress($request->get('address'))
            ->setPostbox($request->get('postbox'))
            ->setPostcode($request->get('postcode'))
            ->setCity($request->get('city'))
            ->setCountry($request->get('country'))
            ->setEmail($request->get('email'))
            ->setPhone1($request->get('phone1') !== $request->get('phone2') ? $request->get('phone1') : '')
            ->setPhone2($request->get('phone2'))
            ->setHasShippingAddress($request->request->getBoolean('altpost'))
            ->setShippingPhone($request->get('shippingPhone'))
            ->setShippingName($request->get('shippingName'))
            ->setShippingAttn($request->get('shippingAttn') !== $request->get('shippingName') ? $request->get('shippingAttn') : '')
            ->setShippingAddress($request->get('shippingAddress'))
            ->setShippingAddress2($request->get('shippingAddress2'))
            ->setShippingPostbox($request->get('shippingPostbox'))
            ->setShippingPostcode($request->get('shippingPostcode'))
            ->setShippingCity($request->get('shippingCity'))
            ->setShippingCountry($request->get('shippingCountry'))
            ->save();

        if ($invoice->getInvalid()) {
            if ($request->request->getBoolean('newsletter')) {
                return $this->redirect($request, $invoice->getLink() . 'address/?newsletter=1');
            }
            return $this->redirect($request, $invoice->getLink() . 'address/');
        }

        if ($request->request->getBoolean('newsletter')) {
            $this->invoiceService->addToAddressBook($invoice, $request->getClientIp());
        }

        return $this->redirect($request, $invoice->getLink() . 'terms/');
    }

    /**
     * Show the terms of condition
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function terms(Request $request, int $id, string $checkId): Response
    {
        /** @var Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if ($redirect = $this->checkStatus($request, $id, $checkId, $invoice)) {
            return $redirect;
        }
        assert($invoice instanceof Invoice);

        $invoice->setStatus('locked')->save();

        $data = $this->basicPageData();

        $data['crumbs'][] = new VolatilePage(_('Order #') . $id, $invoice->getLink());
        $data['crumbs'][] = new VolatilePage(_('Address'), $invoice->getLink() . 'address/');
        $renderable = new VolatilePage(_('Trade Conditions'), $invoice->getLink() . 'terms/');
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;

        $inputs = [
            'group'          => Config::get('pbsfix'),
            'merchantnumber' => Config::get('pbsid'),
            'orderid'        => Config::get('pbsfix') . $invoice->getId(),
            'currency'       => 208,
            'amount'         => number_format($invoice->getAmount(), 2, '', ''),
            'ownreceipt'     => 1,
            'accepturl'      => $invoice->getLink() . 'status/',
            'cancelurl'      => $invoice->getLink() . 'terms/',
            'callbackurl'    => $invoice->getLink() . 'callback/',
            'windowstate'    => 3,
            'windowid'       => Config::get('pbswindow'),
        ];
        $inputs['hash'] = md5(implode('', $inputs) . Config::get('pbspassword'));
        $data['inputs'] = $inputs;
        $data['html'] = ORM::getOne(CustomPage::class, 3)->getHtml();

        $content = Render::render('payment-form2', $data);

        return new Response($content);
    }

    /**
     * Show the order status page
     *
     * Also set the order payment status if txnid is provided
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function status(Request $request, int $id, string $checkId): Response
    {
        /** @var Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice || $checkId !== $invoice->getCheckid()) {
            return $this->redirect($request, '/betaling/?id=' . $id . '&checkid=' . rawurlencode($checkId));
        }
        assert($invoice instanceof Invoice);

        if (!$invoice->isFinalized() && 'pbsok' !== $invoice->getStatus() && !$request->query->has('txnid')) {
            return $this->redirect($request, $invoice->getLink());
        }

        if ($request->query->has('txnid')) {
            if (!$this->isHashValid($request)) {
                return $this->redirect($request, $invoice->getLink());
            }

            $this->setPaymentStatus($request, $invoice);
        }

        $data = $this->basicPageData();

        $data['crumbs'][] = new VolatilePage(_('Order #') . $id, $invoice->getLink());
        $data['crumbs'][] = new VolatilePage(_('Address'), $invoice->getLink() . 'address/');
        $data['crumbs'][] = new VolatilePage(_('Trade Conditions'), $invoice->getLink() . 'terms/');
        $renderable = new VolatilePage(_('Reciept'), $invoice->getLink() . 'status/');
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['newsletter'] = $request->query->getBoolean('newsletter');
        $data['invoice'] = $invoice;
        $data['invalid'] = $invoice->getInvalid();
        $data['action'] = $invoice->getLink() . 'address/';
        $data['statusMessage'] = $this->getStatusMessage($invoice);

        $content = Render::render('payment-status', $data);

        return new Response($content);
    }

    /**
     * Get the status message
     *
     * @param Invoice $invoice
     *
     * @return string
     */
    private function getStatusMessage(Invoice $invoice): string
    {
        switch ($invoice->getStatus()) {
            case 'pbsok':
                return _('Payment is now accepted. We will send your goods by mail as soon as possible.')
                    . "\n" . _('A copy of your order is sent to your email.');
            case 'canceled':
                return _('The transaction is canceled.');
            case 'giro':
                return _('The payment is already received via giro.');
            case 'cash':
                return _('The payment is already received in cash.');
            case 'accepted':
                return _('The payment was received and the package is sent.');
        }

        return _('An errror occured.');
    }

    /**
     * Set the order payment status
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function callback(Request $request, int $id, string $checkId): Response
    {
        /** @var Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice
            || $checkId !== $invoice->getCheckid()
            || !$this->isHashValid($request)
        ) {
            return new Response('', 400);
        }
        assert($invoice instanceof Invoice);

        if (!$invoice->isFinalized() && $invoice->getStatus() !== 'pbsok') {
            $this->setPaymentStatus($request, $invoice);
        }

        return new Response();
    }

    /**
     * Validate payment hash
     *
     * @param mixed $request
     *
     * @return bool
     */
    private function isHashValid($request): bool
    {
        $params = $request->query->all();
        unset($params['hash']);

        $eKey = md5(implode('', $params) . Config::get('pbspassword'));

        if ($eKey !== $request->get('hash')) {
            return true;
        }

        return false;
    }

    /**
     * Set the order payment status
     *
     * @param Request $request
     * @param Invoice $invoice
     *
     * @return void
     */
    private function setPaymentStatus(Request $request, Invoice $invoice): void
    {
        $cardType = EpaymentAdminService::getPaymentName($request->get('paymenttype'));
        $internalNote = $this->generateInternalPaymentNote($request);

        if (!valideMail($invoice->getDepartment())) {
            $invoice->setDepartment(first(Config::get('emails'))['address']);
        }

        $invoice->setCardtype($cardType)
            ->setInternalNote(trim($invoice->getInternalNote() . "\n" . $internalNote))
            ->setStatus('pbsok')
            ->setTimeStampPay(time())
            ->save();

        $this->sendCustomerEmail($invoice);
        $this->sendAdminEmail($invoice);
    }

    /**
     * Send recipt to the customer
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    private function sendCustomerEmail(Invoice $invoice): void
    {
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
            Render::render('email/payment-confirmation', $data),
            $invoice->getDepartment(),
            Config::get('site_name'),
            $invoice->getEmail(),
            $invoice->getName()
        );
    }

    /**
     * Send status email to the admin
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    private function sendAdminEmail(Invoice $invoice): void
    {
        $subject = sprintf(
            _('Attn.: %s - Online invoice #%d : %s'),
            $invoice->getClerk(),
            $invoice->getId(),
            _('Payment complete')
        );
        sendEmails(
            $subject,
            Render::render('admin/email/payment-confirmation', ['invoice' => $invoice]),
            $invoice->getDepartment(),
            Config::get('site_name'),
            $invoice->getDepartment(),
            Config::get('site_name')
        );
    }

    /**
     * Generate message for the internal note about fraud status
     *
     * @param Request $request
     *
     * @return string
     */
    private function generateInternalPaymentNote(Request $request): string
    {
        $internalNote = '';
        if ($request->get('fraud')) {
            $internalNote .= _('Possible payment fraud.') . "\n";
        }
        if ($request->get('cardno')) {
            $internalNote .= _('Credit card no.: ') . $request->get('cardno') . "\n";
        }

        $countries = [];
        include _ROOT_ . '/inc/countries.php';
        if ($request->get('issuercountry')) {
            $internalNote .= _('Card is from: ') . $countries[$request->get('issuercountry')] . "\n";
        }
        if ($request->get('payercountry')) {
            $internalNote .= _('Payment was made from: ') . $countries[$request->get('payercountry')] . "\n";
        }

        return trim($internalNote);
    }

    /**
     * Check if request should be redirected to a different page in the process.
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     * @param ?Invoice $invoice
     *
     * @return ?RedirectResponse
     */
    private function checkStatus(Request $request, int $id, string $checkId, ?Invoice $invoice): ?RedirectResponse
    {
        if (!$invoice || $checkId !== $invoice->getCheckid()) {
            return $this->redirect($request, '/betaling/?id=' . $id . '&checkid=' . rawurlencode($checkId));
        }
        assert($invoice instanceof Invoice);
        if ($invoice->isFinalized() || $invoice->getStatus() === 'pbsok') {
            return $this->redirect($request, $invoice->getLink() . 'status/');
        }

        return null;
    }
}
