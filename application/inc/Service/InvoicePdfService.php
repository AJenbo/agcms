<?php namespace AGCMS\Service;

use AGCMS\Entity\Invoice;
use AGCMS\Exception\InvalidInput;
use TCPDF;

class InvoicePdfService
{
    const CELL_WIDTH_QUANTITY = 24;
    const CELL_WIDTH_TITLE = 106;
    const CELL_WIDTH_PRICE = 29;
    const CELL_WIDTH_TOTAL = 34;
    const MAX_PRODCUTS = 20;

    /** @var TCPDF */
    private $pdf;
    /** @var Invoice */
    private $invoice;

    /**
     * Create the service.
     *
     * @param Invoice $invoice
     *
     * @throws InvalidInput
     */
    public function __construct(Invoice $invoice)
    {
        if ('new' === $invoice->getStatus()) {
            throw new InvalidInput(_('Can\'t print invoice before it\'s locked.'));
        }

        $this->invoice = $invoice;

        $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->setupDocument();
        $this->generateHeader();
        $this->addProductTable();
        $this->generateFooter();
    }

    /**
     * Get the PDF as a blob.
     *
     * @return string
     */
    public function getStream(): string
    {
        return $this->pdf->Output('', 'S');
    }

    /**
     * Set up document defaults, title, size and margins.
     *
     * @return void
     */
    private function setupDocument(): void
    {
        // set document information
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor(config('site_name'));
        $this->pdf->SetTitle('Online faktura #' . $this->invoice->getId());

        // remove default header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        // set default monospaced font
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        //set margins
        $this->pdf->SetMargins(8, 9, 8);

        //set auto page breaks
        $this->pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        //set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $this->pdf->setLanguageArray([
            'a_meta_language' => 'da',
            'w_page'          => 'side',
        ]);

        $this->pdf->AddPage();
    }

    /**
     * Generate the header part of the document.
     *
     * @return void
     */
    private function generateHeader(): void
    {
        $this->insertPageTitle();
        $this->insertCompanyContacts();
        $this->addSeporationLines();
        $this->insertCustomerAddresses();
        $this->insertInvoiceInformation();
    }

    /**
     * Insert date, id and references.
     *
     * @return void
     */
    private function insertInvoiceInformation(): void
    {
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->SetMargins(8, 9, 8);
        $this->pdf->Write(0, "\n");
        $this->pdf->SetY(90.5);
        $info = '<strong>' . _('Date') . ':</strong> ' . date(_('m/d/Y'), $this->invoice->getTimeStamp());
        if ($this->invoice->getIref()) {
            $info .= '       <strong>' . _('Our ref.:') . '</strong> ' . $this->invoice->getIref();
        }
        if ($this->invoice->getEref()) {
            $info .= '       <strong>' . _('Their ref.:') . '</strong> ' . $this->invoice->getEref();
        }
        $this->pdf->writeHTML($info);

        $idText = '<strong>' . _('Online Invoice') . '</strong> ' . $this->invoice->getId();
        $this->pdf->SetFont('times', '', 26);
        $this->pdf->SetY(85);
        $this->pdf->writeHTML($idText, false, false, false, false, 'R');
    }

    /**
     * Add lines to seporate the document, client and company addresses.
     *
     * @return void
     */
    private function addSeporationLines(): void
    {
        $this->pdf->SetLineWidth(0.5);
        //Horizontal
        $this->pdf->Line(8, 27, 150, 27);
        //Vertical
        $this->pdf->Line(152.5, 12, 152.5, 74.5);
    }

    /**
     * Insert the company name in big bold letters.
     *
     * @return void
     */
    private function insertPageTitle(): void
    {
        $this->pdf->SetFont('times', 'B', 37.5);
        $this->pdf->Write(0, config('site_name'));
    }

    /**
     * Insert company address, phone, email and bank account.
     *
     * @return void
     */
    private function insertCompanyContacts(): void
    {
        $this->pdf->SetY(12);
        $this->pdf->SetFont('times', '', 10);
        $addressLine = config('address') . "\n" . config('postcode') . ' ' . config('city') . "\n";
        $this->pdf->Write(0, $addressLine, '', 0, 'R');
        $this->pdf->SetFont('times', 'B', 11);
        $this->pdf->Write(0, _('Phone:') . ' ' . config('phone') . "\n", '', 0, 'R');
        $this->pdf->SetFont('times', '', 10);

        if (!$this->invoice->getDepartment()) {
            $this->invoice->setDepartment(first(config('emails'))['address']);
        }
        $domain = explode('/', config('base_url'));
        $domain = $domain[count($domain) - 1];
        $this->pdf->Write(0, $this->invoice->getDepartment() . "\n" . $domain . "\n\n", '', 0, 'R');
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->Write(0, "Danske Bank (Giro)\nReg.: 9541 Kont.: 169 3336\n", '', 0, 'R');
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->Write(0, "\nIBAN: DK693 000 000-1693336\nSWIFT BIC: DABADKKK\n\n", '', 0, 'R');
        $this->pdf->SetFont('times', 'B', 11);
        $this->pdf->Write(0, 'CVR 1308 1387', '', 0, 'R');
    }

    /**
     * Insert billing and shipping addresses.
     *
     * @return void
     */
    private function insertCustomerAddresses(): void
    {
        $countries = include app()->basePath('/inc/countries.php');

        //Invoice address
        $address = $this->getBillingAddress($countries);
        $this->pdf->SetMargins(19, 0, 0);
        $this->pdf->Write(0, "\n");
        $this->pdf->SetY(35);
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->Write(0, $address);

        //Delivery address
        $address = $this->getShippingAddress($countries);
        if ($address) {
            $this->pdf->SetMargins(110, 0, 0);
            $this->pdf->Write(0, "\n");
            $this->pdf->SetY(30.6);
            $this->pdf->SetFont('times', 'BI', 10);
            $this->pdf->Write(0, _('Delivery address:') . "\n");
            $this->pdf->SetFont('times', '', 11);
            $this->pdf->Write(0, $address);
        }
    }

    /**
     * Get the billing addres.
     *
     * @param string[] $countries
     *
     * @return string
     */
    private function getBillingAddress(array $countries): string
    {
        $address = $this->invoice->getName();
        if ($this->invoice->getAttn()) {
            $address .= "\n" . _('Attn.:') . ' ' . $this->invoice->getAttn();
        }
        if ($this->invoice->getAddress()) {
            $address .= "\n" . $this->invoice->getAddress();
        }
        if ($this->invoice->getPostbox()) {
            $address .= "\n" . $this->invoice->getPostbox();
        }
        $cityLine = $this->invoice->getCity();
        if ($this->invoice->getPostcode()) {
            $cityLine = $this->invoice->getPostcode() . ' ' . $this->invoice->getCity();
        }
        $address .= "\n" . $cityLine;
        if ($this->invoice->getCountry() && 'DK' !== $this->invoice->getCountry()) {
            $address .= "\n" . $countries[$this->invoice->getCountry()];
        }

        return trim($address);
    }

    /**
     * Get the shippig addres.
     *
     * @param string[] $countries
     *
     * @return string
     */
    private function getShippingAddress(array $countries): string
    {
        if (!$this->invoice->hasShippingAddress()) {
            return '';
        }

        $address = $this->invoice->getShippingName();
        if ($this->invoice->getShippingAttn()) {
            $address .= "\n" . _('Attn.:') . ' ' . $this->invoice->getShippingAttn();
        }
        if ($this->invoice->getShippingAddress()) {
            $address .= "\n" . $this->invoice->getShippingAddress();
        }
        if ($this->invoice->getShippingAddress2()) {
            $address .= "\n" . $this->invoice->getShippingAddress2();
        }
        if ($this->invoice->getShippingPostbox()) {
            $address .= "\n" . $this->invoice->getShippingPostbox();
        }
        if ($this->invoice->getShippingPostcode()) {
            $address .= "\n" . $this->invoice->getShippingPostcode() . ' ' . $this->invoice->getShippingCity();
        } elseif ($this->invoice->getShippingCity()) {
            $address .= "\n" . $this->invoice->getShippingCity();
        }
        if ($this->invoice->getShippingCountry() && 'DK' !== $this->invoice->getShippingCountry()) {
            $address .= "\n" . $countries[$this->invoice->getShippingCountry()];
        }

        return trim($address);
    }

    /**
     * Add product table.
     *
     * @return void
     */
    private function addProductTable(): void
    {
        $this->pdf->SetY(85);
        $this->pdf->SetFont('times', '', 10);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Cell(0, 10.5, '', 0, 1);

        //Header
        $this->pdf->Cell(self::CELL_WIDTH_QUANTITY, 5, _('Quantity'), 1, 0, 'L');
        $this->pdf->Cell(self::CELL_WIDTH_TITLE, 5, _('Title'), 1, 0, 'L');
        $this->pdf->Cell(self::CELL_WIDTH_PRICE, 5, _('unit price'), 1, 0, 'R');
        $this->pdf->Cell(self::CELL_WIDTH_TOTAL, 5, _('Total'), 1, 1, 'R');

        //Cells
        $productLines = 0;
        foreach ($this->invoice->getItems() as $item) {
            $productLines += $this->insertProductLine($item);
        }

        $this->insertTableSpacing(self::MAX_PRODCUTS - $productLines);
        $this->insertTableFooter();
    }

    /**
     * Insert a single product line in the product table.
     *
     * @param (int|string)[] $item
     *
     * @return int
     */
    private function insertProductLine(array $item): int
    {
        $value = $item['value'] * (1 + $this->invoice->getVat());
        $lineTotal = $value * $item['quantity'];

        $this->pdf->Cell(self::CELL_WIDTH_QUANTITY, 6, $item['quantity'], 'RL', 0, 'R');
        $lines = $this->pdf->MultiCell(self::CELL_WIDTH_TITLE, 6, $item['title'], 'RL', 'L', false, 0);
        $this->pdf->Cell(self::CELL_WIDTH_PRICE, 6, number_format($value, 2, ',', ''), 'RL', 0, 'R');
        $this->pdf->Cell(self::CELL_WIDTH_TOTAL, 6, number_format($lineTotal, 2, ',', ''), 'RL', 1, 'R');

        if ($lines > 1) {
            $this->insertTableSpacing($lines - 1);
        }

        return $lines;
    }

    /**
     * Insert empty lines at the of the table to keep it at a consistent height.
     *
     * @param int $lines
     *
     * @return void
     */
    private function insertTableSpacing(int $lines): void
    {
        $this->pdf->Cell(self::CELL_WIDTH_QUANTITY, 6 * $lines, '', 'RL', 0);
        $this->pdf->Cell(self::CELL_WIDTH_TITLE, 6 * $lines, '', 'RL', 0);
        $this->pdf->Cell(self::CELL_WIDTH_PRICE, 6 * $lines, '', 'RL', 0);
        $this->pdf->Cell(self::CELL_WIDTH_TOTAL, 6 * $lines, '', 'RL', 1);
    }

    /**
     * Set the table footer, contaning total amount, shipping, conditions.
     *
     * @return void
     */
    private function insertTableFooter(): void
    {
        $vatText = ($this->invoice->getVat() * 100) . _('% VAT is: ')
            . number_format($this->invoice->getNetAmount() * $this->invoice->getVat(), 2, ',', '');
        $shippingPrice = number_format($this->invoice->getShipping(), 2, ',', '');
        $finePrint = '<strong>' . _('Payment Terms:') . '</strong> ' . _('Initial net amount.')
            . '<small><br>'
            . _('In case of payment later than the stated deadline, 2% interest will be added per month.')
            . '</small>';

        $this->pdf->Cell(self::CELL_WIDTH_QUANTITY, 6, '', 'RL', 0);
        $this->pdf->Cell(self::CELL_WIDTH_TITLE, 6, $vatText, 'RL', 0);
        $this->pdf->Cell(self::CELL_WIDTH_PRICE, 6, _('Shipping'), 'RL', 0, 'R');
        $this->pdf->Cell(self::CELL_WIDTH_TOTAL, 6, $shippingPrice, 'RL', 1, 'R');

        $this->pdf->SetFont('times', '', 10);
        $cellWidth = self::CELL_WIDTH_QUANTITY + self::CELL_WIDTH_TITLE;
        $this->pdf->MultiCell($cellWidth, 9, $finePrint, 1, 'L', false, 0, '', '', false, 8, true, false);
        $this->pdf->SetFont('times', 'B', 11);
        $this->pdf->Cell(self::CELL_WIDTH_PRICE, 9, _('Total (USD)'), 1, 0, 'C');
        $this->pdf->SetFont('times', '', 11);
        $this->pdf->Cell(self::CELL_WIDTH_TOTAL, 9, number_format($this->invoice->getAmount(), 2, ',', ''), 1, 1, 'R');
    }

    /**
     * Generate the footer part of the invoice.
     *
     * @return void
     */
    private function generateFooter(): void
    {
        //Note
        $note = $this->getPaymentNote();
        $note .= $this->invoice->getNote();
        $note = trim($note);

        if ($note) {
            $this->pdf->SetFont('times', 'B', 10);
            $this->pdf->Write(0, "\n" . _('Note:') . "\n");
            $this->pdf->SetFont('times', '', 10);
            $this->pdf->Write(0, $note);
        }

        $this->pdf->SetFont('times', 'B', 12);
        $this->pdf->SetMargins(137, 0, 0);
        $this->pdf->Write(0, "\n");
        $this->pdf->SetY(-52);
        $this->pdf->Write(0, _('Sincerely,') . "\n\n\n" . $this->invoice->getClerk() . "\n" . config('site_name'));
    }

    /**
     * Generate the payment note containing date and type of payment.
     *
     * @return string
     */
    private function getPaymentNote(): string
    {
        switch ($this->invoice->getStatus()) {
            case 'accepted':
                $note = _('Paid online');
                break;
            case 'giro':
                $note = _('Paid via giro');
                break;
            case 'cash':
                $note = _('Paid in cash');
                break;
            default:
                return '';
        }

        if (null === $this->invoice->getTimeStampPay()) {
            return $note . "\n";
        }

        return $note . ' d. ' . date(_('m/d/Y'), $this->invoice->getTimeStampPay()) . "\n";
    }
}
