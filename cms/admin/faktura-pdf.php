<?php
date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';

if ($_GET['id'] > 0) {
	$id = $_GET['id'];
} else {
	die(_('Wrong id.'));
}

require_once '../inc/config.php';
require_once '../inc/mysqli.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

$faktura = $mysqli->fetch_one("SELECT *, UNIX_TIMESTAMP(`date`) AS `date`, UNIX_TIMESTAMP(`paydate`) AS `paydate` FROM `fakturas` WHERE `id` = ".$id." AND `status` != 'new'");
if (!$faktura) {
	die(_('Can\'t print.'));
}

$faktura['quantities'] = explode('<', $faktura['quantities']);
$faktura['products'] = explode('<', $faktura['products']);
$faktura['values'] = explode('<', $faktura['values']);

if (!$faktura['premoms'] && $faktura['momssats']) {
	//if numbers where aded with out vat but vat should be payed, then add it
	foreach ($faktura['values'] as $key => $value) {
		$faktura['values'][$key] = $value*(1.25);
	}
} elseif (!$faktura['momssats']) {
	//if values where entered including vat, but no vat should be payed, then remove the vat
	foreach ($faktura['values'] as $key => $value) {
		$faktura['values'][$key] = $value/1.25;
	}
}

require_once '../inc/tcpdf/config/lang/dan.php';
require_once '../inc/tcpdf/tcpdf.php';

// create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($GLOBALS['_config']['site_name']);
$pdf->SetTitle('Online faktura #'.$faktura['id']);

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
$pdf->setLanguageArray($l);

// ---------------------------------------------------------
$pdf->AddPage();

//Site title
$pdf->SetFont('times', 'B', 37.5);
$pdf->Write(0, $GLOBALS['_config']['site_name']);

//Contact info
$pdf->SetY(12);
$pdf->SetFont('times', '', 10);
$pdf->Write(
	0, 
	$GLOBALS['_config']['address']."\n".
	$GLOBALS['_config']['postcode']." ".$GLOBALS['_config']['city']."\n".
	"Fax: ".$GLOBALS['_config']['fax']."\n",
	'',
	0,
	'R'
);
$pdf->SetFont('times', 'B', 11);
$pdf->Write(0, _('Phone:')." ".$GLOBALS['_config']['phone']."\n", '', 0, 'R');
$pdf->SetFont('times', '', 10);

if (empty($faktura['department'])) {
	$faktura['department'] = $GLOBALS['_config']['email'][0];
}
$domain = explode('/', $GLOBALS['_config']['base_url']);
$domain = $domain[count($domain)-1];
$pdf->Write(0, $faktura['department']."\n".$domain."\n\n", '', 0, 'R');
$pdf->SetFont('times', '', 11);
$pdf->Write(0, "Danske Bank (Giro)\nReg.: 9541 Kont.: 169 3336\n", '', 0, 'R');
$pdf->SetFont('times', '', 10);
$pdf->Write(0, "\nIBAN: DK693 000 000-1693336\nSWIFT BIC: DABADKKK\n\n", '', 0, 'R');
$pdf->SetFont('times', 'B', 11);
$pdf->Write(0, "CVR 1308 1387", '', 0, 'R');

//Seperation lines
$pdf->SetLineWidth(0.5);
//Horizontal
$pdf->Line(8, 27, 150, 27);
//Vertical
$pdf->Line(152.5, 12, 152.5, 74.5);

//Invoice address
$address = '';
$address .= $faktura['navn'];
if ($faktura['att']) {
	$address .= "\n"._('Attn.:').' '.$faktura['att'];
}
if ($faktura['adresse']) {
	$address .= "\n".$faktura['adresse'];
}
if ($faktura['postbox']) {
	$address .= "\n".$faktura['postbox'];
}
if ($faktura['postnr']) {
	$address .= "\n".$faktura['postnr'].' '.$faktura['by'];
} else {
	$address .= "\n".$faktura['by'];
}
if ($faktura['land'] && $faktura['land'] != 'DK') {
	include_once '../inc/countries.php';
	$address .= "\n"._($countries[$faktura['land']]);
}

$pdf->SetMargins(19, 0, 0);
$pdf->Write(0, "\n");
$pdf->SetY(35);
$pdf->SetFont('times', '', 11);
$pdf->Write(0, trim($address));

//Delivery address
$address = '';
$address .= $faktura['postname'];
if ($faktura['postatt']) {
	$address .= "\n"._('Attn.:').' '.$faktura['postatt'];
}
if ($faktura['postaddress']) {
	$address .= "\n".$faktura['postaddress'];
}
if ($faktura['postaddress2']) {
	$address .= "\n".$faktura['postaddress2'];
}
if ($faktura['postpostbox']) {
	$address .= "\n".$faktura['postpostbox'];
}
if ($faktura['postpostalcode']) {
	$address .= "\n".$faktura['postpostalcode'].' '.$faktura['postcity'];
}
} elseif ($faktura['postcity']) {
	$address .= "\n".$faktura['postcity'];
}
if ($faktura['land'] && $faktura['land'] != 'DK') {
	include_once '../inc/countries.php';
	$address .= "\n"._($countries[$faktura['land']]);
}

if ($address) {
	$pdf->SetMargins(110, 0, 0);
	$pdf->Write(0, "\n");
	$pdf->SetY(30.6);
	$pdf->SetFont('times', 'BI', 10);
	$pdf->Write(0, _('Delivery address:')."\n");
	$pdf->SetFont('times', '', 11);
	$pdf->Write(0, trim($address));
}

//Invoice info
$pdf->SetFont('times', '', 10);
$pdf->SetMargins(8, 9, 8);
$pdf->Write(0, "\n");
$pdf->SetY(90.5);
$info = '<strong>'._('Date').':</strong> '.date(_('m/d/Y'), $faktura['date']);
if ($faktura['iref']) {
	$info .= '       <strong>'._('Our ref.:').'</strong> '.$faktura['iref'];
}
if ($faktura['eref']) {
	$info .= '       <strong>'._('Their ref.:').'</strong> '.$faktura['eref'];
}
$pdf->writeHTML($info);

//Invoice info
$pdf->SetFont('times', '', 26);
$pdf->Write(0, "\n");
$pdf->SetY(85);
$pdf->writeHTML("<strong>"._('Online Invoice')."</strong> ".$faktura['id'], false, false, false, false, 'R');

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
$netto = 0;
$extralines = 0;
foreach ($faktura['values'] as $i => $value) {
	
	if ($lines > 1) {
		$lines -= 1;
		$extralines += $lines;
		$pdf->Cell(24, 6*$lines, '', 'RL', 0);
		$pdf->Cell(106, 6*$lines, '', 'RL', 0);
		$pdf->Cell(29, 6*$lines, '', 'RL', 0);
		$pdf->Cell(34, 6*$lines, '', 'RL', 1);
	}

	$netto += $value/(1+$faktura['momssats'])*$faktura['quantities'][$i];

	$pdf->Cell(24, 6, $faktura['quantities'][$i], 'RL', 0, 'R');
	$lines = $pdf->MultiCell(106, 6, html_entity_decode(htmlspecialchars_decode($faktura['products'][$i], ENT_QUOTES)), 'RL', '0', 0, 0, '', '', true, 0, false, true, 0);
	//$pdf->Cell(106, 6, $faktura['products'][$i], 'RL', 0, 'L');
	$pdf->Cell(29, 6, number_format($value, 2, ',', ''), 'RL', 0, 'R');
	$pdf->Cell(34, 6, number_format($value*$faktura['quantities'][$i], 2, ',', ''), 'RL', 1, 'R');
}

//Spacing
$pdf->Cell(24, 6*(17-$i-$extralines), '', 'RL', 0);
$pdf->Cell(106, 6*(17-$i-$extralines), '', 'RL', 0);
$pdf->Cell(29, 6*(17-$i-$extralines), '', 'RL', 0);
$pdf->Cell(34, 6*(17-$i-$extralines), '', 'RL', 1);

//Footer
/*
$pdf->Cell( 24, 6, '', 'RL', 0);
$pdf->Cell(106, 6, '', 'RL', 0);
$pdf->Cell( 29, 6, _('Net Amount'), 'RL', 0, 'R');
$pdf->Cell( 34, 6, number_format($netto, 2, ',', ''), 'RL', 1, 'R');
*/
/*
//Her af moms
$pdf->Cell( 24, 6, '', 'RL', 0);
$pdf->Cell(106, 6, ($faktura['momssats']*100).'%', 'RL', 0, 'R');
$pdf->Cell( 29, 6, _('VAT Amount'), 'RL', 0, 'R');
$pdf->Cell( 34, 6, number_format($netto*$faktura['momssats'], 2, ',', ''), 'RL', 1, 'R');
*/
$pdf->Cell(24, 6, '', 'RL', 0);
$pdf->Cell(106, 6, ($faktura['momssats']*100)._('% VAT is: ').number_format($netto*$faktura['momssats'], 2, ',', ''), 'RL', 0);
//Forsendelse
$pdf->Cell(29, 6, _('Shipping'), 'RL', 0, 'R');
$pdf->Cell(34, 6, number_format($faktura['fragt'], 2, ',', ''), 'RL', 1, 'R');

$pdf->SetFont('times', '', 10);
$pdf->MultiCell(130, 8, '<strong>'._('Payment Terms:').'</strong> '._('Net cash at invoice reception.').'<small><br>'._('In case of payment later than the stated deadline, 2% interest will be added per. started months.').'</small>', 1, 'L', 0, 0, '', '', false, 1, true, false);
$pdf->SetFont('times', 'B', 11);
$pdf->Cell(29, 8, _('TO PAY'), 1, 0, 'C');
$pdf->SetFont('times', '', 11);
$pdf->Cell(34, 8, number_format($faktura['amount'], 2, ',', ''), 1, 1, 'R');

//Note
$note = '';
if ($faktura['status'] == 'accepted') {
	$note .= _('Paid online');
	if ($faktura['paydate']) {
		$note .= ' d. '.date(_('m/d/Y'), $faktura['paydate']);
	}
	$note .= "\n";
} elseif ($faktura['status'] == 'giro') {
	$note .= _('Paid via giro');
	if ($faktura['paydate']) {
		$note .= ' d. '.date(_('m/d/Y'), $faktura['paydate']);
	}
	$note .= "\n";
} elseif ($faktura['status'] == 'cash') {
	$note .= _('Paid in cash');
	if ($faktura['paydate']) {
		$note .= ' d. '.date(_('m/d/Y'), $faktura['paydate']);
	}
	$note .= "\n";
}

$note .= $faktura['note'];

if ($note) {
	$pdf->SetFont('times', 'B', 10);
	$pdf->Write(0, "\n"._('Note:')."\n");
	$pdf->SetFont('times', '', 10);
	$pdf->Write(0, $note);
}

//Sign off'.$GLOBALS['_config']['address'].'
$pdf->SetFont('times', 'B', 12);
$pdf->SetMargins(137, 0, 0);
$pdf->Write(0, "\n");
$pdf->SetY(-52);
$pdf->Write(0, _('Sincerely,')."\n\n\n".$faktura['clerk']."\n".$GLOBALS['_config']['site_name']);

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('Faktura-'.$faktura['id'].'.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
