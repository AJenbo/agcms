<?php namespace AGCMS\Controller;

use AGCMS\Application;
use AGCMS\Entity\Email;
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
        $rawCart = $request->get('cart', '{}');
        $cart = json_decode($rawCart, true);

        $data = $this->basicPageData();

        $renderable = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['invoice'] = $this->invoiceService->createFromCart($cart);
        $data['payMethod'] = $cart['payMethod'] ?? '';
        $data['deleveryMethode'] = $cart['deleveryMethode'] ?? '';

        $content = Render::render('order-form', $data);

        return new Response($content);
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
        $rawCart = $request->get('cart', '{}');
        $cart = json_decode($rawCart, true);

        $invoice = $this->invoiceService->createFromCart($cart);

        $data = $this->basicPageData();
        $data['crumbs'][] = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $renderable = new VolatilePage(_('Recipient'), '/order/address/?cart=' . rawurlencode($rawCart));
        $data['crumbs'][] = $renderable;
        $data['renderable'] = $renderable;
        $data['invoice'] = $invoice;
        $data['invalid'] = $invoice->getInvalid();
        $data['countries'] = $countries;
        $data['newsletter'] = $cart['newsletter'] ?? false;
        $data['onsubmit'] = 'shoppingCart.sendCart(); return false';

        $content = Render::render('order-form1', $data);

        return new Response($content);
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
        $rawCart = $request->get('cart', '{}');
        $cart = json_decode($rawCart, true);
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

        $email = new Email([
            'subject'          => _('Online order #') . $invoice->getId(),
            'body'             => Render::render('email/order-notification', ['invoice' => $invoice]),
            'senderName'       => $invoice->getName(),
            'senderAddress'    => $invoice->getEmail(),
            'recipientName'    => Config::get('site_name'),
            'recipientAddress' => first(Config::get('emails'))['address'],
        ]);
        $emailService = new EmailService();
        try {
            $emailService->send($email);
        } catch (Throwable $exception) {
            Application::getInstance()->logException($exception);
            $email->save();
        }

        $data = $this->basicPageData();

        $data['crumbs'][] = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $data['crumbs'][] = new VolatilePage(_('Recipient'), '/order/address/?cart=' . rawurlencode($rawCart));
        $data['renderable'] = new VolatilePage(_('Order placed'), '/order/send/');

        $content = Render::render('order-form2', $data);

        return new Response($content);
    }
}
