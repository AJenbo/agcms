<?php namespace AGCMS\Controller\Admin;

use AGCMS\Entity\Invoice;
use AGCMS\ORM;
use AGCMS\Service\InvoicePdfService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends AbstractAdminController
{
    /**
     * Show a pdf version of the invoice
     *
     * @param Request $request
     *
     * @return Response
     */
    public function pdf(Request $request, int $id): Response
    {
        $invoice = ORM::getOne(Invoice::class, $id);
        if (!$invoice) {
            return new Response(_('Invoice not found.'), 404);
        }

        $invoicePdfService = new InvoicePdfService();
        $pdfData = $invoicePdfService->createPdf($invoice);

        $header = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Faktura-' . $invoice->getId() . '.pdf"',
        ];

        return new Response($pdfData, 200, $header);
    }
}
