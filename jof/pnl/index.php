<?php
mb_language("uni");
mb_internal_encoding('UTF-8');
require_once 'config.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Quick &amp; complient PNL</title>
<script type="text/javascript" src="javascript.js"></script>
<script type="text/javascript"><!--
function init() {
	calcPrice();
	<?php
	foreach($_GET as $key => $value) {
		echo("$('".$key."').value = '".addcslashes($value, "'")."';\r\n");
	}
	?>
}
--></script>
<link href="style.css" rel="stylesheet" type="text/css" />
</head>
<body onload="init();"><div id="menu"><img src="http://www.pannordic.com/pnl_img/logo.gif" alt="PNL" width="145" height="70" title="" /><br />
<a href="/pnl/">Ny pakke</a><br />
<a href="/pnl/liste.php">Forsendelser</a></div>
<div id="content"><form action="lable.php" method="get" onsubmit="return validate();" target="_blank">
<input name="fakturaid" id="fakturaid" value="" style="display:none;" />
    <div style="float:left"><strong>Afsender:</strong>
    <select name="sender" id="sender">
        <option value=""<?php if(@!$_COOKIE['sender']) echo(' selected="selected"'); ?>></option>
        <option value="JF"<?php if('JF' == @$_COOKIE['sender']) echo(' selected="selected"'); ?>>Jagt &amp; Fiskerimagasinet</option>
        <option value="AG"<?php if('AG' == @$_COOKIE['sender']) echo(' selected="selected"'); ?>>Arms Gallery</option>
        <option value="HH"<?php if('HH' == @$_COOKIE['sender']) echo(' selected="selected"'); ?>>Hunters House 7b</option>
        <option value="52"<?php if('52' == @$_COOKIE['sender']) echo(' selected="selected"'); ?>>Hunters House 52a</option>
    </select>
    <br />
    <table>
        <caption>
        Modtager
        </caption>
        <tr>
            <td>Navn:</td>
            <td><input name="name" id="name" /></td>
        </tr>
        <tr>
            <td>Att:</td>
            <td><input name="att" id="att" /></td>
        </tr>
        <tr>
            <td>Adresse:</td>
            <td><input name="address" id="address" /></td>
        </tr>
        <tr>
            <td></td>
            <td><input name="address2" id="address2" /></td>
        </tr>
        <tr>
            <td>Postnr./by:</td>
            <td><input name="postcode" id="postcode" size="5" />
                /
                <input name="city" id="city" /></td>
        </tr>
        <tr>
            <td>Land:</td>
            <td><select name="country" id="country" onkeyup="calcPrice();" onchange="calcPrice();">
			<?php
			require_once 'countries.php';
			foreach($countries as $code => $country) {
				echo('<option value="'.$code.'">'.htmlspecialchars($country).'</option>');
			}
			?>
                </select></td>
        </tr>
        <tr>
            <td>e-Mail:</td>
            <td colspan="2"><input name="email" id="email" /></td>
        </tr>
        <tr id="rem1" style="display:none;"><td colspan="2"><strong>Husk 4 kopier af faktura!</strong></td></tr>
    </table></div>
    <table>
        <caption>
        Pakke
        </caption>
        
    <tr><td>Ekspress:</td>
    <td><input type="checkbox" id="express" onclick="calcPrice();" onkeyup="calcPrice();" /><input name="product" id="product" type="hidden" value="359" /></td></tr>
   <tr><td> Indhold:</td>
    <td><select name="contens" id="contens">
        <option value="001" selected="selected">Handelsvare</option>
        <option value="004">Reparation/Retur</option>
        <option value="006">Midlertidig eksport</option>
        <option value="003">Gave</option>
    </select></td></tr>
        <tr>
            <td>Beskrivelse<br />
(Engelsk)</td>
            <td><input name="text" id="text" /></td>
        </tr>
        <tr>
            <td>Vægt</td>
            <td><input name="kg" id="kg" size="2" onkeyup="calcPrice();" onchange="calcPrice();" />
            Kg</td>
        </tr>
        <tr>
            <td>Længde</td>
            <td><input name="l" id="l" size="3" onkeyup="calcPrice();" onchange="calcPrice();" />
            cm</td>
        </tr>
        <tr>
            <td>Brede</td>
            <td><input name="w" id="w" size="3" onkeyup="calcPrice();" onchange="calcPrice();" />
            cm</td>
        </tr>
        <tr>
            <td>Højde</td>
            <td><input name="h" id="h" size="3" onkeyup="calcPrice();" onchange="calcPrice();" />
            cm</td>
        </tr>
   <tr>
    <td> Hvis ikke leveret:</td>
    <td><select name="return" id="return">
        <option value="1" selected="selected">Retuner mod betaling</option>
        <option value="2">Opgiv pakken</option>
    </select></td></tr>
    <tr><td>Reference:</td>
        <td><input name="ref" id="ref" /></td></tr>
<tr><td>Forsikrings beløb:</td>
    <td><input name="insurance" id="insurance" onkeyup="calcPrice();" onchange="calcPrice();" />
DKK</td></tr>
<tr><td>Beregnet fragt:</td>
    <td id="price"></td></tr>
    
    </table>
    <input type="submit" value="Generere PDF" />
    <input type="reset" value="Nulstil felter" />
</form>
</div></body>
</html>