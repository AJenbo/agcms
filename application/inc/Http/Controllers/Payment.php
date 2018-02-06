<?php namespace App\Http\Controllers;

use App\Exceptions\Exception;
use App\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\InvalidInput;
use App\Models\CustomPage;
use App\Models\Email;
use App\Models\Invoice;
use App\Models\VolatilePage;
use App\Render;
use App\Services\EmailService;
use App\Services\EpaymentService;
use App\Services\InvoiceService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

        $response = $this->render('payment-manual', $data);

        return $this->cachedResponse($response);
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
        /** @var ?Invoice */
        $invoice = app('orm')->getOne(Invoice::class, $id);
        if ($redirect = $this->checkStatus($id, $checkId, $invoice)) {
            return $redirect;
        }

        $invoice->setStatus('locked')->save();

        $data = $this->basicPageData();

        $renderable = new VolatilePage(_('Order #') . $id, $invoice->getLink());
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['invoice'] = $invoice;

        $response = $this->render('payment-form0', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Page for user to correct there contact info.
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function address(Request $request, int $id, string $checkId): Response
    {
        /** @var ?Invoice */
        $invoice = app('orm')->getOne(Invoice::class, $id);
        if ($redirect = $this->checkStatus($id, $checkId, $invoice)) {
            return $redirect;
        }

        $data = $this->basicPageData();

        $data['countries'] = include app()->basePath('/inc/countries.php');
        $data['crumbs'][] = new VolatilePage(_('Order #') . $id, $invoice->getLink());
        $renderable = new VolatilePage(_('Address'), $invoice->getLink() . 'address/');
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['newsletter'] = $request->query->getBoolean('newsletter');
        $data['invoice'] = $invoice;
        $data['invalid'] = $invoice->getInvalid();
        $data['action'] = $invoice->getLink() . 'address/';
        $data['actionLable'] = _('Continue');

        $response = $this->render('order-form1', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Update the contact infor and forwared the user in the payment process.
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function addressSave(Request $request, int $id, string $checkId): Response
    {
        /** @var ?Invoice */
        $invoice = app('orm')->getOne(Invoice::class, $id);
        if ($redirect = $this->checkStatus($id, $checkId, $invoice)) {
            return $redirect;
        }

        $shippingAttn = '';
        if ($request->get('shippingAttn') !== $request->get('shippingName')) {
            $shippingAttn = $request->get('shippingAttn');
        }

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
            ->setShippingAttn($shippingAttn)
            ->setShippingAddress($request->get('shippingAddress'))
            ->setShippingAddress2($request->get('shippingAddress2'))
            ->setShippingPostbox($request->get('shippingPostbox'))
            ->setShippingPostcode($request->get('shippingPostcode'))
            ->setShippingCity($request->get('shippingCity'))
            ->setShippingCountry($request->get('shippingCountry'))
            ->save();

        if ($invoice->getInvalid()) {
            if ($request->request->getBoolean('newsletter')) {
                return redirect($invoice->getLink() . 'address/?newsletter=1', Response::HTTP_SEE_OTHER);
            }

            return redirect($invoice->getLink() . 'address/', Response::HTTP_SEE_OTHER);
        }

        if ($request->request->getBoolean('newsletter')) {
            $this->invoiceService->addToAddressBook($invoice, $request->getClientIp());
        }

        return redirect($invoice->getLink() . 'terms/', Response::HTTP_SEE_OTHER);
    }

    /**
     * Show the terms of condition.
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function terms(Request $request, int $id, string $checkId): Response
    {
        /** @var ?Invoice */
        $invoice = app('orm')->getOne(Invoice::class, $id);
        if ($redirect = $this->checkStatus($id, $checkId, $invoice)) {
            return $redirect;
        }

        $invoice->setStatus('locked')->save();

        $data = $this->basicPageData();

        $data['crumbs'][] = new VolatilePage(_('Order #') . $id, $invoice->getLink());
        $data['crumbs'][] = new VolatilePage(_('Address'), $invoice->getLink() . 'address/');
        $renderable = new VolatilePage(_('Trade Conditions'), $invoice->getLink() . 'terms/');
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;

        $inputs = [
            'group'          => config('pbsfix'),
            'merchantnumber' => config('pbsid'),
            'orderid'        => config('pbsfix') . $invoice->getId(),
            'currency'       => 208,
            'amount'         => number_format($invoice->getAmount(), 2, '', ''),
            'ownreceipt'     => 1,
            'accepturl'      => $invoice->getLink() . 'status/',
            'cancelurl'      => $invoice->getLink() . 'terms/',
            'callbackurl'    => $invoice->getLink() . 'callback/',
            'windowstate'    => 3,
            'windowid'       => config('pbswindow'),
        ];
        $inputs['hash'] = md5(implode('', $inputs) . config('pbspassword'));
        $data['inputs'] = $inputs;
        $data['html'] = $this->getTermsHtml();

        $response = $this->render('payment-form2', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Get the terms and conditions text.
     *
     * @return string
     */
    public function getTermsHtml(): string
    {
        /** @var ?CustomPage */
        $shoppingTerms = app('orm')->getOne(CustomPage::class, 3);
        if (!$shoppingTerms) {
            app(ExceptionHandler::class)->report(new Exception(_('Missing terms and conditions')));

            return '';
        }

        return $shoppingTerms->getHtml();
    }

    /**
     * Show the order status page.
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
        /** @var ?Invoice */
        $invoice = app('orm')->getOne(Invoice::class, $id);
        if (!$invoice || $checkId !== $invoice->getCheckId()) {
            return redirect('/betaling/?id=' . $id . '&checkid=' . rawurlencode($checkId), Response::HTTP_SEE_OTHER);
        }

        if (!$invoice->isFinalized() && 'pbsok' !== $invoice->getStatus() && !$request->query->has('txnid')) {
            return redirect($invoice->getLink(), Response::HTTP_SEE_OTHER);
        }

        if ($request->query->has('txnid')) {
            if (!$this->isHashValid($request)) {
                return redirect($invoice->getLink(), Response::HTTP_SEE_OTHER);
            }

            $this->setPaymentStatus($request, $invoice);
        }

        $data = $this->basicPageData();

        $data['crumbs'][] = new VolatilePage(_('Order #') . $id, $invoice->getLink());
        $data['crumbs'][] = new VolatilePage(_('Address'), $invoice->getLink() . 'address/');
        $data['crumbs'][] = new VolatilePage(_('Trade Conditions'), $invoice->getLink() . 'terms/');
        $renderable = new VolatilePage(_('Receipt'), $invoice->getLink() . 'status/');
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['newsletter'] = $request->query->getBoolean('newsletter');
        $data['invoice'] = $invoice;
        $data['invalid'] = $invoice->getInvalid();
        $data['action'] = $invoice->getLink() . 'address/';
        $data['statusMessage'] = $this->getStatusMessage($invoice);

        $response = $this->render('payment-status', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Get the status message.
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
                    . "\n" . _('A copy of your order has been sent to your email.');
            case 'canceled':
                return _('The transaction is canceled.');
            case 'giro':
                return _('The payment has already been received via giro.');
            case 'cash':
                return _('The payment has already been received in cash.');
            case 'accepted':
                return _('The payment was received and the package is sent.');
        }

        throw new Exception(_('Unknown status.'));
    }

    /**
     * Set the order payment status.
     *
     * @param Request $request
     * @param int     $id
     * @param string  $checkId
     *
     * @return Response
     */
    public function callback(Request $request, int $id, string $checkId): Response
    {
        /** @var ?Invoice */
        $invoice = app('orm')->getOne(Invoice::class, $id);
        if (!$invoice
            || $checkId !== $invoice->getCheckId()
            || !$this->isHashValid($request)
        ) {
            throw new InvalidInput(Response::HTTP_BAD_REQUEST);
        }

        $this->setPaymentStatus($request, $invoice);

        return new Response();
    }

    /**
     * Validate payment hash.
     *
     * @param mixed $request
     *
     * @return bool
     */
    private function isHashValid($request): bool
    {
        $params = $request->query->all();
        unset($params['hash']);

        $eKey = md5(implode('', $params) . config('pbspassword'));

        return $eKey === $request->get('hash');
    }

    /**
     * Set the order payment status.
     *
     * @param Request $request
     * @param Invoice $invoice
     *
     * @return void
     */
    private function setPaymentStatus(Request $request, Invoice $invoice): void
    {
        if ($invoice->isFinalized() || 'pbsok' === $invoice->getStatus()) {
            return;
        }

        $cardType = EpaymentService::getPaymentName($request->get('paymenttype'));
        $internalNote = $this->generateInternalPaymentNote($request);

        $emailService = app(EmailService::class);
        if (!$emailService->valideMail($invoice->getDepartment())) {
            $invoice->setDepartment(first(config('emails'))['address']);
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
     * Send recipt to the customer.
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    private function sendCustomerEmail(Invoice $invoice): void
    {
        $data = [
            'invoice'  => $invoice,
            'siteName' => config('site_name'),
            'address'  => config('address'),
            'postcode' => config('postcode'),
            'city'     => config('city'),
            'phone'    => config('phone'),
        ];
        $email = new Email([
            'subject'          => sprintf(_('Order #%d - payment completed'), $invoice->getId()),
            'body'             => app('render')->render('email/payment-confirmation', $data),
            'senderName'       => config('site_name'),
            'senderAddress'    => $invoice->getDepartment(),
            'recipientName'    => $invoice->getName(),
            'recipientAddress' => $invoice->getEmail(),
        ]);
        $emailService = app(EmailService::class);

        try {
            $emailService->send($email);
        } catch (Throwable $exception) {
            app(ExceptionHandler::class)->report($exception);
            $email->save();
        }
    }

    /**
     * Send status email to the admin.
     *
     * @param Invoice $invoice
     *
     * @return void
     */
    private function sendAdminEmail(Invoice $invoice): void
    {
        $subject = sprintf(
            _('Attn.: %s - Payment received for invoice #%d'),
            $invoice->getClerk(),
            $invoice->getId()
        );
        $email = new Email([
            'subject'          => $subject,
            'body'             => app('render')->render('admin/email/payment-confirmation', ['invoice' => $invoice]),
            'senderName'       => config('site_name'),
            'senderAddress'    => $invoice->getDepartment(),
            'recipientName'    => config('site_name'),
            'recipientAddress' => $invoice->getDepartment(),
        ]);
        $emailService = app(EmailService::class);

        try {
            $emailService->send($email);
        } catch (Throwable $exception) {
            app(ExceptionHandler::class)->report($exception);
            $email->save();
        }
    }

    /**
     * Generate message for the internal note about fraud status.
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

        $countries = include app()->basePath('/inc/countries.php');
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
     * @param int      $id
     * @param string   $checkId
     * @param ?Invoice $invoice
     *
     * @return ?RedirectResponse
     */
    private function checkStatus(int $id, string $checkId, ?Invoice $invoice): ?RedirectResponse
    {
        if (!$invoice || $checkId !== $invoice->getCheckId()) {
            return redirect('/betaling/?id=' . $id . '&checkid=' . rawurlencode($checkId), Response::HTTP_SEE_OTHER);
        }
        if ($invoice->isFinalized() || 'pbsok' === $invoice->getStatus()) {
            return redirect($invoice->getLink() . 'status/', Response::HTTP_SEE_OTHER);
        }

        return null;
    }
}
