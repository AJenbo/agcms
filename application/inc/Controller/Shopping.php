<?php namespace AGCMS\Controller;

use AGCMS\Entity\Email;
use AGCMS\Exception\InvalidInput;
use AGCMS\Render;
use AGCMS\Service\EmailService;
use AGCMS\Service\InvoiceService;
use AGCMS\VolatilePage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Shopping extends Base
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
     * Show the items in the shopping basket.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function basket(Request $request): Response
    {
        $rawCart = $request->get('cart', '');
        $cart = json_decode($rawCart, true);
        if (!$cart) {
            throw new InvalidInput(_('Basket data is invalid'));
        }

        $data = $this->basicPageData();

        $renderable = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['invoice'] = $this->invoiceService->createFromCart($cart);
        $data['payMethod'] = $cart['payMethod'] ?? '';
        $data['deleveryMethod'] = $cart['deleveryMethod'] ?? '';

        $response = $this->render('order-form', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Show address input page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function address(Request $request): Response
    {
        $rawCart = $request->get('cart', '');
        $cart = json_decode($rawCart, true);
        if (!$cart) {
            throw new InvalidInput(_('Basket data is invalid'));
        }

        $invoice = $this->invoiceService->createFromCart($cart);

        $data = $this->basicPageData();
        $data['crumbs'][] = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $renderable = new VolatilePage(_('Address'), '/order/address/?cart=' . rawurlencode($rawCart));
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['invoice'] = $invoice;
        $data['invalid'] = $invoice->getInvalid();
        $data['countries'] = include app()->basePath('/inc/countries.php');
        $data['newsletter'] = $cart['newsletter'] ?? false;
        $data['onsubmit'] = 'shoppingCart.sendCart(); return false';
        $data['actionLable'] = _('Send order');

        $response = $this->render('order-form1', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Show address input page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function send(Request $request): Response
    {
        $rawCart = $request->get('cart', '');
        $cart = json_decode($rawCart, true);
        if (!$cart) {
            throw new InvalidInput(_('Basket data is invalid'));
        }

        $invoice = $this->invoiceService->createFromCart($cart);

        if ($invoice->getInvalid()) {
            return $this->redirect($request, '/order/address/?cart=' . rawurlencode($rawCart));
        }

        if (!empty($cart['newsletter'])) {
            $this->invoiceService->addToAddressBook($invoice, $request->getClientIp());
        }

        if (!$invoice->getItems()) {
            return $this->redirect($request, '/order/?cart=' . rawurlencode($rawCart));
        }

        $note = $this->invoiceService->generateExtraNote($cart);
        $note = trim($note . "\n" . $invoice->getNote());
        $invoice->setNote($note);

        $invoice->save();

        $emailBody = app('render')->render(
            'admin/email/order-notification',
            [
                'invoice'    => $invoice,
                'localeconv' => localeconv(),
            ]
        );

        $email = new Email([
            'subject'          => _('Online order #') . $invoice->getId(),
            'body'             => $emailBody,
            'senderName'       => $invoice->getName(),
            'senderAddress'    => $invoice->getEmail(),
            'recipientName'    => config('site_name'),
            'recipientAddress' => first(config('emails'))['address'],
        ]);
        $emailService = app(EmailService::class);

        try {
            $emailService->send($email);
        } catch (Throwable $exception) {
            app()->logException($exception);
            $email->save();
        }

        $cart['items'] = [];
        $rawCart = json_encode($cart);

        return $this->redirect($request, '/order/receipt/?cart=' . rawurlencode($rawCart));
    }

    /**
     * Show a receipt page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function receipt(Request $request): Response
    {
        $rawCart = $request->get('cart', '');

        $data = $this->basicPageData();

        $data['crumbs'][] = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $data['crumbs'][] = new VolatilePage(_('Address'), '/order/address/?cart=' . rawurlencode($rawCart));
        $renderable = new VolatilePage(_('Recipient'), '/order/receipt/?cart=' . rawurlencode($rawCart));
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;

        $response = $this->render('order-form2', $data);

        return $this->cachedResponse($response);
    }
}
