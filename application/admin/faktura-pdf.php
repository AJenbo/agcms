<?php

use AGCMS\Config;
use AGCMS\Entity\Invoice;
use AGCMS\ORM;

require_once __DIR__ . '/logon.php';
$countries = [];
include _ROOT_ . '/inc/countries.php';

$id = (int) request()->get('id');
/** @var \AGCMS\Entity\Invoice */
$invoice = ORM::getOne(Invoice::class, $id);
if (!$invoice) {
    die(_('Can\'t print.'));
}
assert($invoice instanceof Invoice);
if ('new' === $invoice->getStatus()) {
    die(_('Can\'t print.'));
}

// create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor(Config::get('site_name'));
$pdf->SetTitle('Online faktura #' . $invoice->getId());

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(8, 9, 8);

//set auto page breaks
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//set some language-dependent strings

// set some language-dependent strings (optional)
if (file_exists(_ROOT_ . '/vendor/tecnickcom/tcpdf/examples/lang/dan.php')) {
    $l = [];
    include _ROOT_ . '/vendor/tecnickcom/tcpdf/examples/lang/dan.php';
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------
$pdf->AddPage();

//Site title
$pdf->SetFont('times', 'B', 37.5);
$pdf->Write(0, Config::get('site_name'));

//Contact info
$pdf->SetY(12);
$pdf->SetFont('times', '', 10);
$addressLine = Config::get('address') . "\n" . Config::get('postcode') . ' ' . Config::get('city') . "\n";
if (Config::get('fax')) {
    $addressLine .= 'Fax: ' . Config::get('fax') . "\n";
}
$pdf->Write(0, $addressLine, '', 0, 'R');
$pdf->SetFont('times', 'B', 11);
$pdf->Write(0, _('Phone:') . ' ' . Config::get('phone') . "\n", '', 0, 'R');
$pdf->SetFont('times', '', 10);

if (!$invoice->getDepartment()) {
    $invoice->setDepartment(first(Config::get('emails'))['address']);
}
$domain = explode('/', Config::get('base_url'));
$domain = $domain[count($domain) - 1];
$pdf->Write(0, $invoice->getDepartment() . "\n" . $domain . "\n\n", '', 0, 'R');
$pdf->SetFont('times', '', 11);
$pdf->Write(0, "Danske Bank (Giro)\nReg.: 9541 Kont.: 169 3336\n", '', 0, 'R');
$pdf->SetFont('times', '', 10);
$pdf->Write(0, "\nIBAN: DK693 000 000-1693336\nSWIFT BIC: DABADKKK\n\n", '', 0, 'R');
$pdf->SetFont('times', 'B', 11);
$pdf->Write(0, 'CVR 1308 1387', '', 0, 'R');

//Seperation lines
$pdf->SetLineWidth(0.5);
//Horizontal
$pdf->Line(8, 27, 150, 27);
//Vertical
$pdf->Line(152.5, 12, 152.5, 74.5);

//Invoice address
$address = '' . $invoice->getName();
if ($invoice->getAttn()) {
    $address .= "\n" . _('Attn.:') . ' ' . $invoice->getAttn();
}
if ($invoice->getAddress()) {
    $address .= "\n" . $invoice->getAddress();
}
if ($invoice->getPostbox()) {
    $address .= "\n" . $invoice->getPostbox();
}
if ($invoice->getPostcode()) {
    $address .= "\n" . $invoice->getPostcode() . ' ' . $invoice->getCity();
} else {
    $address .= "\n" . $invoice->getCity();
}
if ($invoice->getCountry() && 'DK' !== $invoice->getCountry()) {
    $address .= "\n" . $countries[$invoice->getCountry()];
}

$pdf->SetMargins(19, 0, 0);
$pdf->Write(0, "\n");
$pdf->SetY(35);
$pdf->SetFont('times', '', 11);
$pdf->Write(0, trim($address));

//Delivery address
$address = '';
$address .= $invoice->getShippingName();
if ($invoice->getShippingAttn()) {
    $address .= "\n" . _('Attn.:') . ' ' . $invoice->getShippingAttn();
}
if ($invoice->getShippingAddress()) {
    $address .= "\n" . $invoice->getShippingAddress();
}
if ($invoice->getShippingAddress2()) {
    $address .= "\n" . $invoice->getShippingAddress2();
}
if ($invoice->getShippingPostbox()) {
    $address .= "\n" . $invoice->getShippingPostbox();
}
if ($invoice->getShippingPostcode()) {
    $address .= "\n" . $invoice->getShippingPostcode() . ' ' . $invoice->getShippingCity();
} elseif ($invoice->getShippingCity()) {
    $address .= "\n" . $invoice->getShippingCity();
}
if ($invoice->getShippingCountry() && 'DK' !== $invoice->getShippingCountry()) {
    $address .= "\n" . $countries[$invoice->getShippingCountry()];
}

if ($address) {
    $pdf->SetMargins(110, 0, 0);
    $pdf->Write(0, "\n");
    $pdf->SetY(30.6);
    $pdf->SetFont('times', 'BI', 10);
    $pdf->Write(0, _('Delivery address:') . "\n");
    $pdf->SetFont('times', '', 11);
    $pdf->Write(0, trim($address));
}

//Invoice info
$pdf->SetFont('times', '', 10);
$pdf->SetMargins(8, 9, 8);
$pdf->Write(0, "\n");
$pdf->SetY(90.5);
$info = '<strong>' . _('Date') . ':</strong> ' . date(_('m/d/Y'), $invoice->getTimeStamp());
if ($invoice->getIref()) {
    $info .= '       <strong>' . _('Our ref.:') . '</strong> ' . $invoice->getIref();
}
if ($invoice->getEref()) {
    $info .= '       <strong>' . _('Their ref.:') . '</strong> ' . $invoice->getEref();
}
$pdf->writeHTML($info);

//Invoice info
$pdf->SetFont('times', '', 26);
$pdf->Write(0, "\n");
$pdf->SetY(85);
$pdf->writeHTML('<strong>' . _('Online Invoice') . '</strong> ' . $invoice->getId(), false, false, false, false, 'R');

//Invoice table
$pdf->SetFont('times', '', 10);
$pdf->SetLineWidth(0.2);
$pdf->Cell(0, 10.5, '', 0, 1);
//$pdf->SetY(93);

//Header
$pdf->Cell(24, 5, _('Quantity'), 1, 0, 'L');
$pdf->Cell(106, 5, _('Title'), 1, 0, 'L');
$pdf->Cell(29, 5, _('unit price'), 1, 0, 'R');
$pdf->Cell(34, 5, _('Total'), 1, 1, 'R');

//Cells
$extralines = 0;
$lines = 0;
foreach ($invoice->getItems() as $item) {
    if ($lines > 1) {
        --$lines;
        $extralines += $lines;
        $pdf->Cell(24, 6 * $lines, '', 'RL', 0);
        $pdf->Cell(106, 6 * $lines, '', 'RL', 0);
        $pdf->Cell(29, 6 * $lines, '', 'RL', 0);
        $pdf->Cell(34, 6 * $lines, '', 'RL', 1);
    }

    $value = $item['value'] * (1 + $invoice->getVat());
    $lineTotal = $value * $item['quantity'];

    $pdf->Cell(24, 6, $item['quantity'], 'RL', 0, 'R');
    $lines = $pdf->MultiCell(106, 6, $item['title'], 'RL', '0', 0, 0, '', '', true, 0, false, true, 0);
    $pdf->Cell(29, 6, number_format($value, 2, ',', ''), 'RL', 0, 'R');
    $pdf->Cell(34, 6, number_format($lineTotal, 2, ',', ''), 'RL', 1, 'R');
}

//Spacing
$extraSpacing = 6 * (16 - count($invoice->getItems()) - $extralines);
$pdf->Cell(24, $extraSpacing, '', 'RL', 0);
$pdf->Cell(106, $extraSpacing, '', 'RL', 0);
$pdf->Cell(29, $extraSpacing, '', 'RL', 0);
$pdf->Cell(34, $extraSpacing, '', 'RL', 1);

//Footer
$pdf->Cell(24, 6, '', 'RL', 0);
$vatText = ($invoice->getVat() * 100) . _('% VAT is: ')
    . number_format($invoice->getNetAmount() * $invoice->getVat(), 2, ',', '');
$pdf->Cell(106, 6, $vatText, 'RL', 0);
//Forsendelse
$pdf->Cell(29, 6, _('Shipping'), 'RL', 0, 'R');
$pdf->Cell(34, 6, number_format($invoice->getShipping(), 2, ',', ''), 'RL', 1, 'R');

$pdf->SetFont('times', '', 10);
$finePrint = '<strong>' . _('Payment Terms:') . '</strong> ' . _('Net cash at invoice reception.') . '<small><br>'
    . _('In case of payment later than the stated deadline, 2% interest will be added per. started months.')
    . '</small>';
$pdf->MultiCell(130, 9, $finePrint, 1, 'L', false, 0, '', '', false, 8, true, false);
$pdf->SetFont('times', 'B', 11);
$pdf->Cell(29, 9, _('TO PAY'), 1, 0, 'C');
$pdf->SetFont('times', '', 11);
$pdf->Cell(34, 9, number_format($invoice->getAmount(), 2, ',', ''), 1, 1, 'R');

//Note
$note = '';
$date = ' d. ' . date(_('m/d/Y'), $invoice->getTimeStampPay());
if ('accepted' === $invoice->getStatus()) {
    $note .= _('Paid online') . $date . "\n";
} elseif ('giro' === $invoice->getStatus()) {
    $note .= _('Paid via giro') . $date . "\n";
} elseif ('cash' === $invoice->getStatus()) {
    $note .= _('Paid in cash') . $date . "\n";
}

$note .= $invoice->getNote();

if ($note) {
    $pdf->SetFont('times', 'B', 10);
    $pdf->Write(0, "\n" . _('Note:') . "\n");
    $pdf->SetFont('times', '', 10);
    $pdf->Write(0, $note);
}

$pdf->SetFont('times', 'B', 12);
$pdf->SetMargins(137, 0, 0);
$pdf->Write(0, "\n");
$pdf->SetY(-52);
$pdf->Write(0, _('Sincerely,') . "\n\n\n" . $invoice->getClerk() . "\n" . Config::get('site_name'));

//Close and output PDF document
$pdf->Output('Faktura-' . $invoice->getId() . '.pdf', 'I');
