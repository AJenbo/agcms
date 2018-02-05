<?php namespace App\Http\Controllers;

use App\Exceptions\Handler as ExceptionHandler;
use App\Exceptions\InvalidInput;
use App\Models\Email;
use App\Models\VolatilePage;
use App\Render;
use App\Services\EmailService;
use App\Services\InvoiceService;
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
            app(ExceptionHandler::class)->report($exception);
            $email->save();
        }

        $cart['items'] = [];
        $rawCart = json_encode($cart);

        return redirect('/order/receipt/?cart=' . rawurlencode($rawCart), Response::HTTP_SEE_OTHER);
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
