<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Invoice;
use AGCMS\Entity\User;
use AGCMS\Exception\InvalidInput;
use AGCMS\ORM;
use AGCMS\Request;
use AGCMS\Service\InvoicePdfService;
use AGCMS\Service\InvoiceService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends AbstractAdminController
{
    /**
     * List of invoices.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $selected = [
            'id'         => (int) $request->get('id') ?: null,
            'year'       => $request->query->getInt('y'),
            'month'      => $request->query->getInt('m'),
            'department' => $request->get('department'),
            'status'     => $request->get('status', 'activ'),
            'name'       => $request->get('name'),
            'tlf'        => $request->get('tlf'),
            'email'      => $request->get('email'),
            'momssats'   => $request->get('momssats'),
            'clerk'      => $request->get('clerk'),
        ];
        if ('' === $selected['momssats']) {
            $selected['momssats'] = null;
        }

        /** @var User */
        $user = $request->user();

        $where = $this->generateFilterInvoiceBySelection($selected, $user);

        db()->addLoadedTable('fakturas');
        $oldest = db()->fetchOne('SELECT `date` FROM `fakturas` ORDER BY `date`')['date'] ?? 'now';
        $oldest = strtotime($oldest);
        $oldest = date('Y', $oldest);

        /** @var Invoice[] */
        $invoices = ORM::getByQuery(Invoice::class, 'SELECT * FROM `fakturas`' . $where . ' ORDER BY `id` DESC');

        $data = [
            'title'         => _('Invoice list'),
            'currentUser'   => $user,
            'selected'      => $selected,
            'countries'     => include app()->basePath('/inc/countries.php'),
            'departments'   => array_keys(config('emails', [])),
            'users'         => ORM::getByQuery(User::class, 'SELECT * FROM `users` ORDER BY `fullname`'),
            'invoices'      => $invoices,
            'years'         => range($oldest, date('Y')),
            'statusOptions' => [
                ''         => _('All'),
                'activ'    => _('Current'),
                'inactiv'  => _('Finalized'),
                'new'      => _('New'),
                'locked'   => _('Locked'),
                'pbsok'    => _('Ready'),
                'accepted' => _('Processed'),
                'giro'     => _('Giro'),
                'cash'     => _('Cash'),
                'pbserror' => _('Error'),
                'canceled' => _('Canceled'),
                'rejected' => _('Rejected'),
            ],
        ] + $this->basicPageData($request);

        return $this->render('admin/fakturas', $data);
    }

    /**
     * Generate an SQL where clause from a select array.
     *
     * @param array $selected
     * @param User  $user
     *
     * @return string
     */
    private function generateFilterInvoiceBySelection(array $selected, User $user): string
    {
        if ($selected['id']) {
            return 'WHERE `id` = ' . $selected['id'];
        }

        if (null === $selected['clerk'] && !$user->hasAccess(User::ADMINISTRATOR)) {
            $selected['clerk'] = $user->getFullName();
        }

        $where = [];

        if ($selected['month'] && $selected['year']) {
            $where[] = "`date` >= '" . $selected['year'] . '-' . $selected['month'] . "-01'";
            $where[] = "`date` <= '" . $selected['year'] . '-' . $selected['month'] . "-31'";
        } elseif ($selected['year']) {
            $where[] = "`date` >= '" . $selected['year'] . "-01-01'";
            $where[] = "`date` <= '" . $selected['year'] . "-12-31'";
        }

        if ($selected['department']) {
            $where[] = '`department` = ' . db()->quote($selected['department']);
        }
        if ($selected['clerk']
            && (!$user->hasAccess(User::ADMINISTRATOR) || $user->getFullName() === $selected['clerk'])
        ) {
            //Viewing your self
            $where[] = '(`clerk` = ' . db()->quote($selected['clerk']) . " OR `clerk` = '')";
        } elseif ($selected['clerk']) {
            //Viewing some one else
            $where[] = '`clerk` = ' . db()->quote($selected['clerk']);
        }

        if ('activ' === $selected['status']) {
            $where[] = "`status` IN('new', 'locked', 'pbsok', 'pbserror')";
        } elseif ('inactiv' === $selected['status']) {
            $where[] = "`status` NOT IN('new', 'locked', 'pbsok', 'pbserror')";
        } elseif ($selected['status']) {
            $where[] = '`status` = ' . db()->quote($selected['status']);
        }

        if ($selected['name']) {
            $where[] = '`navn` LIKE ' . db()->quote('%' . $selected['name'] . '%');
        }

        if ($selected['tlf']) {
            $where[] = '(`tlf1` LIKE ' . db()->quote('%' . $selected['tlf'] . '%')
                . ' OR `tlf2` LIKE ' . db()->quote('%' . $selected['tlf'] . '%') . ')';
        }

        if ($selected['email']) {
            $where[] = '`email` LIKE ' . db()->endq('%' . $selected['email'] . '%');
        }

        if (null !== $selected['momssats']) {
            $where[] = '`momssats` = ' . db()->quote($selected['momssats']);
        }

        if (!$where) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $where);
    }

    /**
     * List of invoices.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function validationList(Request $request): Response
    {
        /** @var Invoice[] */
        $invoices = ORM::getByQuery(
            Invoice::class,
            "
            SELECT * FROM `fakturas`
            WHERE `transferred` = 0 AND `status` = 'accepted'
            ORDER BY `paydate` DESC, `id` DESC
            "
        );

        $data = [
            'title'    => _('Invoice validation'),
            'invoices' => $invoices,
        ] + $this->basicPageData($request);

        return $this->render('admin/fakturasvalidate', $data);
    }

    /**
     * Set payment transferred status.
     *
     * @param Request $request
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function validate(Request $request, int $id): JsonResponse
    {
        /** @var User */
        $user = $request->user();
        if (!$user->hasAccess(User::ADMINISTRATOR)) {
            throw new InvalidInput(_('You do not have permission to validate payments.'), 403);
        }

        /** @var ?Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), 404);
        }

        $invoice->setTransferred($request->request->getBoolean('transferred'))->save();

        return new JsonResponse([]);
    }

    /**
     * Create a new invoice.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        /** @var User */
        $user = $request->user();
        $action = $request->get('action', 'save');
        $data = $request->request->all();
        unset($data['action']);

        $invoice = new Invoice(['clerk' => $user->getFullName()]);

        $invoiceService = new InvoiceService();
        $invoiceService->invoiceBasicUpdate($invoice, $user, $action, $data);

        if ('email' === $action) {
            $invoiceService->sendInvoice($invoice);
        }

        return new JsonResponse(['id' => $invoice->getId()]);
    }

    /**
     * Update invoice.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $action = $request->get('action', 'save');
        $data = $request->request->all();
        unset($data['action']);

        /** @var ?Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), 404);
        }

        $invoiceService = new InvoiceService();

        /** @var User */
        $user = $request->user();
        $invoiceService->invoiceBasicUpdate($invoice, $user, $action, $data);

        if ('email' === $action) {
            $invoiceService->sendInvoice($invoice);
        }

        return new JsonResponse(['type' => $action, 'status' => $invoice->getStatus()]);
    }

    /**
     * Clone invoice.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function clone(Request $request, int $id): JsonResponse
    {
        /** @var ?Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), 404);
        }

        $invoice = clone $invoice;
        /** @var User */
        $user = $request->user();
        $invoice->setClerk($user->getFullName())->save();

        return new JsonResponse(['id' => $invoice->getId()]);
    }

    /**
     * Send payment reminder.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function sendReminder(Request $request, int $id): JsonResponse
    {
        /** @var ?Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), 404);
        }

        $invoiceService = new InvoiceService();
        $invoiceService->sendInvoice($invoice);

        return new JsonResponse([]);
    }

    /**
     * Accept payment.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function capturePayment(Request $request, int $id): JsonResponse
    {
        /** @var ?Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), 404);
        }

        $invoiceService = new InvoiceService();
        $invoiceService->capturePayment($invoice);

        return new JsonResponse([]);
    }

    /**
     * Cancle payment.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return JsonResponse
     */
    public function annulPayment(Request $request, int $id): JsonResponse
    {
        /** @var ?Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), 404);
        }

        $invoiceService = new InvoiceService();
        $invoiceService->annulPayment($invoice);

        return new JsonResponse([]);
    }

    /**
     * Display invoice.
     *
     * @param Request $request
     * @param int     $id
     *
     * @throws InvalidInput
     *
     * @return Response
     */
    public function invoice(Request $request, int $id = null): Response
    {
        $invoice = null;
        if (null !== $id) {
            /** @var ?Invoice */
            $invoice = ORM::getOne(Invoice::class, $id);
            if (!$invoice) {
                throw new InvalidInput(_('Invoice not found.'), 404);
            }
        }

        /** @var User */
        $user = $request->user();

        if ($invoice && !$invoice->getClerk()) {
            $invoice->setClerk($user->getFullName());
        }

        $data = [
            'title'       => $invoice ? _('Online Invoice #') . $invoice->getId() : _('Create invoice'),
            'invoice'     => $invoice,
            'currentUser' => $user,
            'users'       => ORM::getByQuery(User::class, 'SELECT * FROM `users` ORDER BY fullname'),
            'departments' => array_keys(config('emails', [])),
            'countries'   => include app()->basePath('/inc/countries.php'),
        ] + $this->basicPageData($request);

        return $this->render('admin/faktura', $data);
    }

    /**
     * Show a pdf version of the invoice.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function pdf(Request $request, int $id): Response
    {
        /** @var ?Invoice */
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            return new Response(_('Invoice not found.'), 404);
        }

        $invoicePdfService = new InvoicePdfService($invoice);
        $pdfData = $invoicePdfService->getStream();

        $header = [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Faktura-' . $invoice->getId() . '.pdf"',
        ];

        return new Response($pdfData, 200, $header);
    }
}
