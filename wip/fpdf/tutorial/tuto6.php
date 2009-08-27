<?php
require('../fpdf.php');

class PDF extends FPDF
{
var $B;
var $I;
var $U;
var $HREF;

function PDF($orientation='P',$unit='mm',$format='A4')
{
	//Call parent constructor
	$this->FPDF($orientation,$unit,$format);
	//Initialization
	$this->B=0;
	$this->I=0;
	$this->U=0;
	$this->HREF='';
}

function WriteHTML($html)
{
	//HTML parser
	$html=str_replace("\n",' ',$html);
	$a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
	foreach($a as $i=>$e)
	{
		if($i%2==0)
		{
			//Text
			if($this->HREF)
				$this->PutLink($this->HREF,$e);
			else
				$this->Write(5,$e);
		}
		else
		{
			//Tag
			if($e[0]=='/')
				$this->CloseTag(strtoupper(substr($e,1)));
			else
			{
				//Extract attributes
				$a2=explode(' ',$e);
				$tag=strtoupper(array_shift($a2));
				$attr=array();
				foreach($a2 as $v)
				{
					if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
						$attr[strtoupper($a3[1])]=$a3[2];
				}
				$this->OpenTag($tag,$attr);
			}
		}
	}
}

function OpenTag($tag,$attr)
{
	//Opening tag
	if($tag=='B' || $tag=='I' || $tag=='U')
		$this->SetStyle($tag,true);
	if($tag=='A')
		$this->HREF=$attr['HREF'];
	if($tag=='BR')
		$this->Ln(5);
}

function CloseTag($tag)
{
	//Closing tag
	if($tag=='B' || $tag=='I' || $tag=='U')
		$this->SetStyle($tag,false);
	if($tag=='A')
		$this->HREF='';
}

function SetStyle($tag,$enable)
{
	//Modify style and select corresponding font
	$this->$tag+=($enable ? 1 : -1);
	$style='';
	foreach(array('B','I','U') as $s)
	{
		if($this->$s>0)
			$style.=$s;
	}
	$this->SetFont('',$style);
}

function PutLink($URL,$txt)
{
	//Put a hyperlink
	$this->SetTextColor(0,0,255);
	$this->SetStyle('U',true);
	$this->Write(5,$txt,$URL);
	$this->SetStyle('U',false);
	$this->SetTextColor(0);
}
}

$html='<div id="main">
        <address>Nørre Voldgade 8-10
        <br />
        1358 København K<br />
        Fax: +45 33 14 04 07<br />
        <big>Tel.: 33 33 77 77<br />
        <br />
        </big> <big>Danske Bank <small>(Giro)</small><br />
        9541 - 169 3336</big><br />
        <br />
        IBAN:<br />
        DK693 000 000-1693336<br />
        SWIFT BIC:<br />
        DABADKKK<br />
        <small><br />
        </small> <big><strong> SE 1308 1387</strong></big>
        </address>
        <h1>Jagt og Fiskerimagasinet</h1>
        <table id="postadresse">
            <tr>
                <td>Flemming Kiel Sørensen<br />Tyrings Ager 47<br />6580 Vamdrup<br />Danmark</td>
            </tr>
        </table>
    </div>
    <div id="fakturadiv"><strong>Online faktura</strong> 898</div>
    <div id="ref"> <strong>Dato: </strong> <span>26/08/2009</span> <strong>Vor ref.: </strong> <span></span> <strong>Deres ref.: </strong> <span></span></div><table id="printdata" cellspacing="0">
        <thead>
            <tr>
                <td class="td1">Antal</td>
                <td>Benævnelse</td>
                <td class="td3 tal">á pris</td>
                <td class="td4 tal">Total</td>
            </tr>
        </thead>
        <tfoot>
            <tr style="height:auto;min-height:auto;max-height:auto;">
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td class="tal">Nettobeløb</td><td class="tal">2800,00</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td class="tal">Fragt</td>
                <td class="tal">65,00</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td style="text-align:right" class="tal">25%</td>
                <td class="tal">Momsbeløb</td>
				<td class="tal">700,00</td>
            </tr>
            <tr class="border">
                <td colspan="2" id="warning"><strong>Betalingsbetingelser:</strong> Netto kontant ved faktura modtagelse.<br />
                    <span style="font-size:8pt;">Ved senere indbetaling end anførte frist, vil der blive debiteret 2% rente pr. påbegyndt måned.</span></td>
                <td style="text-align:center; font-weight:bold;">AT BETALE</td>
                <td class="tal" id="printpayamount">3565,00</td>
            </tr>
        </tfoot>
        <tbody><tr>
					<td class="tal">1</td>
					<td>Dif. for bytte af Hardy Angel2 12´#7 med Angel2 10´#7</td>
					<td class="tal">2800,00</td>
					<td class="tal">2800,00</td>
				</tr></tbody>
    </table>
    <br />
    <strong>Notat:</strong><br />
    <p class="note">Afventer indbetaling via Netbank.</p>
    <br />
    <br />
    <p style="font-size:12pt; float:right; min-width:6cm;"><strong>Med venlig hilsen<br />
        <br />
        <br />
        <span class="clerk">Oliver Bernát</span> <br />
        </strong><strong>Jagt og Fiskerimagasinet</strong></p>';

$pdf=new PDF();
//First page
$pdf->AddPage();
$pdf->SetFont('Arial','',20);
$pdf->Write(5,'To find out what\'s new in this tutorial, click ');
$pdf->SetFont('','U');
$link=$pdf->AddLink();
$pdf->Write(5,'here',$link);
$pdf->SetFont('');
//Second page
$pdf->AddPage();
$pdf->SetLink($link);
$pdf->Image('logo.png',10,12,30,0,'','http://www.fpdf.org');
$pdf->SetLeftMargin(45);
$pdf->SetFontSize(14);
$pdf->WriteHTML($html);
$pdf->Output();
?>
