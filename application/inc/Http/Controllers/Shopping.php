<?php

namespace App\Http\Controllers;

use App\Countries;
use App\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Email;
use App\Models\VolatilePage;
use App\Services\ConfigService;
use App\Services\EmailService;
use App\Services\InvoiceService;
use App\Services\RenderService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Shopping extends Base
{
    private InvoiceService $invoiceService;

    /**
     * Initialize needed services.
     */
    public function __construct()
    {
        $this->invoiceService = new InvoiceService();
    }

    /**
     * Show the items in the shopping basket.
     */
    public function basket(Request $request): Response
    {
        $rawCart = $request->get('cart');
        if (!is_string($rawCart)) {
            $rawCart = '';
        }
        $cart = json_decode($rawCart, true);
        if (!is_array($cart)) {
            throw new InvalidInput(_('Basket data is invalid'));
        }

        $data = $this->basicPageData();

        $renderable = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $crumbs = $data['crumbs'] ?? null;
        if (!is_array($crumbs)) {
            $crumbs = [];
        }
        $crumbs[] = $renderable;
        $data['crumbs'] = $crumbs ;
        $data['renderable'] = $renderable;
        $data['invoice'] = $this->invoiceService->createFromCart($cart);
        $data['payMethod'] = $cart['payMethod'] ?? '';
        $data['deleveryMethod'] = $cart['deleveryMethod'] ?? '';

        $response = $this->render('order-form', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Show address input page.
     */
    public function address(Request $request): Response
    {
        $rawCart = $request->get('cart');
        if (!is_string($rawCart)) {
            $rawCart = '';
        }
        $cart = json_decode($rawCart, true);
        if (!is_array($cart)) {
            throw new InvalidInput(_('Basket data is invalid'));
        }

        $invoice = $this->invoiceService->createFromCart($cart);

        $data = $this->basicPageData();
        $crumbs = $data['crumbs'] ?? null;
        if (!is_array($crumbs)) {
            $crumbs = [];
        }
        $crumbs[] = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $renderable = new VolatilePage(_('Address'), '/order/address/?cart=' . rawurlencode($rawCart));
        $crumbs[] = $renderable;
        $data['crumbs'] = $crumbs;
        $data['renderable'] = $renderable;
        $data['invoice'] = $invoice;
        $data['invalid'] = $invoice->getInvalid();
        $data['countries'] = Countries::getOrdered();
        $data['newsletter'] = $cart['newsletter'] ?? false;
        $data['onsubmit'] = 'shoppingCart.sendCart(); return false';
        $data['actionLable'] = _('Send order');

        $response = $this->render('order-form1', $data);

        return $this->cachedResponse($response);
    }

    /**
     * Show address input page.
     */
    public function send(Request $request): Response
    {
        $rawCart = $request->get('cart');
        if (!is_string($rawCart)) {
            $rawCart = '';
        }
        $cart = json_decode($rawCart, true);
        if (!is_array($cart)) {
            throw new InvalidInput(_('Basket data is invalid'));
        }

        $invoice = $this->invoiceService->createFromCart($cart);

        if ($invoice->getInvalid()) {
            return redirect('/order/address/?cart=' . rawurlencode($rawCart), Response::HTTP_SEE_OTHER);
        }

        if (!empty($cart['newsletter'])) {
            $this->invoiceService->addToAddressBook($invoice, $request->getClientIp());
        }

        if (!$invoice->getItems()) {
            return redirect('/order/?cart=' . rawurlencode($rawCart), Response::HTTP_SEE_OTHER);
        }

        $note = $this->invoiceService->generateExtraNote($cart);
        $note = trim($note . "\n" . $invoice->getNote());
        $invoice->setNote($note);

        $invoice->save();

        $emailBody = app(RenderService::class)->render(
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
            'recipientName'    => ConfigService::getString('site_name'),
            'recipientAddress' => ConfigService::getDefaultEmail(),
        ]);

        try {
            app(EmailService::class)->send($email);
        } catch (Throwable $exception) {
            app(ExceptionHandler::class)->report($exception);
            $email->save();
        }

        $cart['items'] = [];
        $rawCart = json_encode($cart, JSON_THROW_ON_ERROR) ?: '';

        return redirect('/order/receipt/?cart=' . rawurlencode($rawCart), Response::HTTP_SEE_OTHER);
    }

    /**
     * Show a receipt page.
     */
    public function receipt(Request $request): Response
    {
        $rawCart = $request->get('cart');
        if (!is_string($rawCart)) {
            $rawCart = '';
        }

        $data = $this->basicPageData();
        $crumbs = $data['crumbs'] ?? null;
        if (!is_array($crumbs)) {
            $crumbs = [];
        }

        $crumbs[] = new VolatilePage(_('Shopping list'), '/order/?cart=' . rawurlencode($rawCart));
        $crumbs[] = new VolatilePage(_('Address'), '/order/address/?cart=' . rawurlencode($rawCart));
        $renderable = new VolatilePage(_('Recipient'), '/order/receipt/?cart=' . rawurlencode($rawCart));
        $crumbs[] = $renderable;
        $data['crumbs'] = $crumbs;
        $data['renderable'] = $renderable;

        $response = $this->render('order-form2', $data);

        return $this->cachedResponse($response);
    }
}
