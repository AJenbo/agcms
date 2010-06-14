<?php

require_once('../inc/tcpdf/config/lang/dan.php');
require_once('../inc/tcpdf/tcpdf.php');

// create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Jagt og Fiskerimagasinet');
$pdf->SetTitle('Online faktura #1547');

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(8, 9, 8);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

//set some language-dependent strings
$pdf->setLanguageArray($l);

// ---------------------------------------------------------
$pdf->AddPage();

//Site title
$pdf->SetFont('times', 'B', 37.5);
$pdf->Write(0, 'Jagt og Fiskerimagasinet');

//Seperation lines
$pdf->SetLineWidth(0.5);
//Horizontal
$pdf->Line(8, 27, 150, 27);
//Vertical
$pdf->Line(154, 12, 154, 80);

//Contact info
$pdf->SetY(12);
$pdf->SetFont('times', '', 10);
$pdf->Write(0, "Nørre Voldgade 8-10\n1358 København K\nFax: +45 33 14 04 07\n", '', 0, 'R');
$pdf->SetFont('times', '', 11);
$pdf->Write(0, "Telefon: 33 33 77 77\n", '', 0, 'R');
$pdf->SetFont('times', '', 10);
$pdf->Write(0, "mail@jagtogfiskerimagsinet.dk\n\n", '', 0, 'R');
$pdf->SetFont('times', '', 11);
$pdf->Write(0, "Danske Bank (Giro)\nReg.: 9541 Kont.: 169 3336\n", '', 0, 'R');
$pdf->SetFont('times', '', 10);
$pdf->Write(0, "\nIBAN:\nDK693 000 000- 1693336\nSWIFT BIC:\nDABADKKK\n\n", '', 0, 'R');
$pdf->SetFont('times', 'B', 11);
$pdf->Write(0, "CVR 1308 1387", '', 0, 'R');

//Invoice address
$pdf->SetMargins(19, 0, 0);
$pdf->Write(0, "\n");
$pdf->SetY(35);
$pdf->SetFont('times', '', 11);
$pdf->Write(0, "Sygehus Nord\nAtt: Indkøbskontoret\nKøgevej 7-13\n4000 Roskilde\nDanmark");

//Delivery address
$pdf->SetMargins(110, 0, 0);
$pdf->Write(0, "\n");
$pdf->SetY(30.6);
$pdf->SetFont('times', 'BI', 10);
$pdf->Write(0, "Leveringsadresse:\n");
$pdf->SetFont('times', '', 11);
$pdf->Write(0, "Lene Andersen\nLeveringsvej 8\n4000 Roskilde\nDanmark");

//Invoice info
$pdf->SetFont('times', '', 10);
$pdf->SetMargins(8, 9, 8);
$pdf->Write(0, "\n");
$pdf->SetY(90.5);
$pdf->writeHTML("<strong>Dato:</strong> 26/05/2010       <strong>Vor ref.:</strong> CPG       <strong>Deres ref.:</strong> Hansen");

//Invoice info
$pdf->SetFont('times', '', 26);
$pdf->Write(0, "\n");
$pdf->SetY(85);
$pdf->writeHTML("<strong>Online faktura</strong> 1547", false, false, false, false, 'R');

//Invoice table
$pdf->SetFont('times', '', 10);
$pdf->SetLineWidth(0.2);
$pdf->Cell(0, 10.5, '', 0, 1);
//$pdf->SetY(93);

//Header
$pdf->Cell(24, 5, 'Antal', 1, 0, 'L');
$pdf->Cell(106, 5, 'Benævnelse', 1, 0, 'L');
$pdf->Cell(29, 5, 'á pris', 1, 0, 'R');
$pdf->Cell(34, 5, 'Total', 1, 1, 'R');

//Cells
$pdf->Cell(24, 6, '1', 'RL', 0, 'R');
$pdf->Cell(106, 6, 'Stetson hat', 'RL', 0, 'L');
$pdf->Cell(29, 6, '319,20', 'RL', 0, 'R');
$pdf->Cell(34, 6, '319,20', 'RL', 1, 'R');

$pdf->Cell(24, 6, '1', 'RL', 0, 'R');
$pdf->Cell(106, 6, 'Stetson cap', 'RL', 0, 'L');
$pdf->Cell(29, 6, '319,20', 'RL', 0, 'R');
$pdf->Cell(34, 6, '319,20', 'RL', 1, 'R');

$pdf->Cell(24, 6, '1', 'RL', 0, 'R');
$pdf->Cell(106, 6, 'Stetson cap', 'RL', 0, 'L');
$pdf->Cell(29, 6, '159,20', 'RL', 0, 'R');
$pdf->Cell(34, 6, '159,20', 'RL', 1, 'R');

$pdf->Cell(24, 6, '1', 'RL', 0, 'R');
$pdf->Cell(106, 6, 'Buff hoved og hals beklædning', 'RL', 0, 'L');
$pdf->Cell(29, 6, '135,20', 'RL', 0, 'R');
$pdf->Cell(34, 6, '135,20', 'RL', 1, 'R');

//Spacing
$pdf->Cell(24, 6*(16-4), '', 'RL', 0);
$pdf->Cell(106, 6*(16-4), '', 'RL', 0);
$pdf->Cell(29, 6*(16-4), '', 'RL', 0);
$pdf->Cell(34, 6*(16-4), '', 'RL', 1);

//Footer
$pdf->Cell(24, 6, '', 'RL', 0);
$pdf->Cell(106, 6, '', 'RL', 0);
$pdf->Cell(29, 6, 'Nettobeløb', 'RL', 0, 'R');
$pdf->Cell(34, 6, '932,80', 'RL', 1, 'R');

$pdf->Cell(24, 6, '', 'RL', 0);
$pdf->Cell(106, 6, '', 'RL', 0);
$pdf->Cell(29, 6, 'Forsendelse', 'RL', 0, 'R');
$pdf->Cell(34, 6, '0,00', 'RL', 1, 'R');

$pdf->Cell(24, 6, '', 'RL', 0);
$pdf->Cell(106, 6, '25%', 'RL', 0, 'R');
$pdf->Cell(29, 6, 'Momsbeløb', 'RL', 0, 'R');
$pdf->Cell(34, 6, '233,20', 'RL', 1, 'R');

$pdf->SetFont('times', '', 10);
$pdf->MultiCell(130, 8, "<strong>Betalingsbetingelser:</strong> Netto kontant ved faktura modtagelse.<small><br>Ved senere indbetaling end anførte frist vil der blive debiteret 2% rente pr. påbegyndt måned.</small>", 1, 'L', 0, 0, '', '', false, 1, true, false);
$pdf->SetFont('times', 'B', 11);
$pdf->Cell(29, 8, 'AT BETALE', 1, 0, 'C');
$pdf->SetFont('times', '', 11);
$pdf->Cell(34, 8, '1166,00', 1, 1, 'R');

//Note
$pdf->SetFont('times', 'B', 10);
$pdf->Write(0, "\nNotat:\n");
$pdf->SetFont('times', '', 10);
$pdf->Write(0, "Ordrenr.: 192362\nRekvirent 081 - Onkologisk afdeling 081, Roskilde\nVaren er leveret til C. Lang");

//Sign off
$pdf->SetFont('times', 'B', 12);
$pdf->SetMargins(137, 0, 0);
$pdf->Write(0, "\n");
$pdf->SetY(-52);
$pdf->Write(0, "Med venlig hilsen\n\n\nCarsten P. Grølsted\nJagt og Fiskerimgasinet");

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('Faktura-1547.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
