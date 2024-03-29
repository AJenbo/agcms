<?php

namespace App\Http\Controllers\Admin;

use App\Countries;
use App\DTO\InvoiceFilter;
use App\Enums\InvoiceAction;
use App\Exceptions\Exception;
use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ConfigService;
use App\Services\DbService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends AbstractAdminController
{
    /**
     * List of invoices.
     *
     * @throws Exception
     */
    public function index(Request $request): Response
    {
        $momssats = strval($request->get('momssats'));
        if (!$momssats) {
            $momssats = null;
        }
        $selected = new InvoiceFilter(
            $request->query->getInt('id') ?: null,
            $request->query->getInt('y'),
            $request->query->getInt('m'),
            strval($request->get('department') ?? ''),
            strval($request->get('status', 'activ')),
            strval($request->get('name') ?? ''),
            strval($request->get('tlf') ?? ''),
            strval($request->get('email') ?? ''),
            $momssats,
            strval($request->get('clerk') ?? ''),
        );

        $user = $request->user();
        if (!$user) {
            throw new Exception('You need to be logged in to access invoices.');
        }

        $where = $this->generateFilterInvoiceBySelection($selected, $user);

        $db = app(DbService::class);

        $db->addLoadedTable('fakturas');
        $oldest = $db->fetchOne('SELECT `date` FROM `fakturas` ORDER BY `date`')['date'] ?? 'now';
        $oldest = strtotime($oldest);
        if ($oldest === false) {
            throw new Exception('Unable to get time from database server');
        }
        $oldest = date('Y', $oldest);

        $orm = app(OrmService::class);

        $invoices = $orm->getByQuery(Invoice::class, 'SELECT * FROM `fakturas`' . $where . ' ORDER BY `id` DESC');

        $data = [
            'title'         => _('Invoice list'),
            'currentUser'   => $user,
            'selected'      => $selected,
            'countries'     => Countries::getOrdered(),
            'departments'   => array_keys(ConfigService::getEmailConfigs()),
            'users'         => $orm->getByQuery(User::class, 'SELECT * FROM `users` ORDER BY `fullname`'),
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
     */
    private function generateFilterInvoiceBySelection(InvoiceFilter $selected, User $user): string
    {
        if ($selected->id) {
            return 'WHERE `id` = ' . $selected->id;
        }

        $clerk = $selected->clerk;
        if (!$clerk && !$user->hasAccess(User::ADMINISTRATOR)) {
            $clerk = $user->getFullName();
        }

        $where = [];

        if ($selected->month && $selected->year) {
            $where[] = "`date` >= '" . $selected->year . '-' . $selected->month . "-01'";
            $where[] = "`date` <= '" . $selected->year . '-' . $selected->month . "-31'";
        } elseif ($selected->year) {
            $where[] = "`date` >= '" . $selected->year . "-01-01'";
            $where[] = "`date` <= '" . $selected->year . "-12-31'";
        }

        $db = app(DbService::class);

        if ($selected->department) {
            $where[] = '`department` = ' . $db->quote($selected->department);
        }
        if ($clerk
            && (!$user->hasAccess(User::ADMINISTRATOR) || $user->getFullName() === $clerk)
        ) {
            //Viewing your self
            $where[] = '(`clerk` = ' . $db->quote($clerk) . " OR `clerk` = '')";
        } elseif ($clerk) {
            //Viewing some one else
            $where[] = '`clerk` = ' . $db->quote($clerk);
        }

        if ('activ' === $selected->status) {
            $where[] = "`status` IN('new', 'locked', 'pbsok', 'pbserror')";
        } elseif ('inactiv' === $selected->status) {
            $where[] = "`status` NOT IN('new', 'locked', 'pbsok', 'pbserror')";
        } elseif ($selected->status) {
            $where[] = '`status` = ' . $db->quote($selected->status);
        }

        if ($selected->name) {
            $where[] = '`navn` LIKE ' . $db->quote('%' . $selected->name . '%');
        }

        if ($selected->tlf) {
            $where[] = '(`tlf1` LIKE ' . $db->quote('%' . $selected->tlf . '%')
                . ' OR `tlf2` LIKE ' . $db->quote('%' . $selected->tlf . '%') . ')';
        }

        if ($selected->email) {
            $where[] = '`email` LIKE ' . $db->quote('%' . $selected->email . '%');
        }

        if (null !== $selected->momssats) {
            $where[] = '`momssats` = ' . $db->quote($selected->momssats);
        }

        if (!$where) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $where);
    }

    /**
     * List of invoices.
     */
    public function validationList(Request $request): Response
    {
        $invoices = app(OrmService::class)->getByQuery(
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
     */
    public function validate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasAccess(User::ADMINISTRATOR)) {
            throw new InvalidInput(_('You do not have permission to validate payments.'), Response::HTTP_FORBIDDEN);
        }

        $invoice = app(OrmService::class)->getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), Response::HTTP_NOT_FOUND);
        }

        $invoice->setTransferred($request->request->getBoolean('transferred'))->save();

        return new JsonResponse([]);
    }

    /**
     * Create a new invoice.
     *
     * @throws Exception
     */
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            throw new Exception('You need to be logged in to access invoices.');
        }

        $action = $request->getRequestString('action') ?? 'save';
        $action = InvoiceAction::from($action);
        $data = $request->request->all();
        unset($data['action']);

        $invoice = new Invoice(['clerk' => $user->getFullName()]);

        $invoiceService = new InvoiceService();
        $invoiceService->invoiceBasicUpdate($invoice, $user, $action, $data);

        if (InvoiceAction::Email === $action) {
            $invoiceService->sendInvoice($invoice);
        }

        return new JsonResponse(['id' => $invoice->getId()]);
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $action = $request->getRequestString('action') ?? 'save';
        $action = InvoiceAction::from($action);

        $data = $request->request->all();
        unset($data['action']);

        $invoice = app(OrmService::class)->getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();
        if (!$user) {
            throw new Exception('You need to be logged in to access invoices.');
        }

        $invoiceService = new InvoiceService();
        $invoiceService->invoiceBasicUpdate($invoice, $user, $action, $data);

        if (InvoiceAction::Email === $action) {
            $invoiceService->sendInvoice($invoice);
        }

        return new JsonResponse(['type' => $action, 'status' => $invoice->getStatus()]);
    }

    /**
     * @throws Exception
     */
    public function clone(Request $request, int $id): JsonResponse
    {
        $invoice = app(OrmService::class)->getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), Response::HTTP_NOT_FOUND);
        }

        $invoice = clone $invoice;
        $user = $request->user();
        if (!$user) {
            throw new Exception('You need to be logged in to access invoices.');
        }

        $invoice->setClerk($user->getFullName())->save();

        return new JsonResponse(['id' => $invoice->getId()]);
    }

    /**
     * Send payment reminder.
     */
    public function sendReminder(Request $request, int $id): JsonResponse
    {
        $invoice = app(OrmService::class)->getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), Response::HTTP_NOT_FOUND);
        }

        $invoiceService = new InvoiceService();
        $invoiceService->sendInvoice($invoice);

        return new JsonResponse([]);
    }

    /**
     * Accept payment.
     */
    public function capturePayment(Request $request, int $id): JsonResponse
    {
        $invoice = app(OrmService::class)->getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), Response::HTTP_NOT_FOUND);
        }

        $invoiceService = new InvoiceService();
        $invoiceService->capturePayment($invoice);

        return new JsonResponse([]);
    }

    /**
     * Cancle payment.
     */
    public function annulPayment(Request $request, int $id): JsonResponse
    {
        $invoice = app(OrmService::class)->getOne(Invoice::class, $id);
        if (!$invoice) {
            throw new InvalidInput(_('Invoice not found.'), Response::HTTP_NOT_FOUND);
        }

        $invoiceService = new InvoiceService();
        $invoiceService->annulPayment($invoice);

        return new JsonResponse([]);
    }

    /**
     * Display invoice.
     *
     * @throws Exception
     */
    public function invoice(Request $request, ?int $id = null): Response
    {
        $orm = app(OrmService::class);

        $invoice = null;
        if (null !== $id) {
            $invoice = $orm->getOne(Invoice::class, $id);
            if (!$invoice) {
                throw new InvalidInput(_('Invoice not found.'), Response::HTTP_NOT_FOUND);
            }
        }

        $user = $request->user();
        if (!$user) {
            throw new Exception('You need to be logged in to access invoices.');
        }

        if ($invoice && !$invoice->getClerk()) {
            $invoice->setClerk($user->getFullName());
        }

        $data = [
            'title'       => $invoice ? _('Online Invoice #') . $invoice->getId() : _('Create invoice'),
            'invoice'     => $invoice,
            'currentUser' => $user,
            'users'       => $orm->getByQuery(User::class, 'SELECT * FROM `users` ORDER BY fullname'),
            'departments' => array_keys(ConfigService::getEmailConfigs()),
            'countries'   => Countries::getOrdered(),
        ] + $this->basicPageData($request);

        return $this->render('admin/faktura', $data);
    }

    /**
     * Show a pdf version of the invoice.
     */
    public function pdf(Request $request, int $id): Response
    {
        $invoice = app(OrmService::class)->getOne(Invoice::class, $id);
        if (!$invoice) {
            return new Response(_('Invoice not found.'), Response::HTTP_NOT_FOUND);
        }

        $invoicePdfService = new InvoicePdfService($invoice);
        $pdfData = $invoicePdfService->getStream();

        $header = [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Faktura-' . $invoice->getId() . '.pdf"',
        ];

        return new Response($pdfData, Response::HTTP_OK, $header);
    }
}
