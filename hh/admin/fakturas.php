<?php
	require_once '../inc/sajax.php';
	require_once '../inc/config.php';
	require_once '../inc/mysqli.php';
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	$sajax_request_type = 'POST';
	sajax_init();
	
	
	function getCheckid($id) {
		return mb_substr(md5($id.'salt24raej098'), 3, 5);
	}

	function save($type, $id, $quantities, $products, $values, $fragt, $momssats, $premoms, $date, $iref, $eref, $navn, $att, $adresse, $postbox, $postnr, $by, $land, $email, $tlf1, $tlf2, $note, $department) {
		global $mysqli;
		
		$faktura = $mysqli->fetch_array("SELECT `id`, `clerk` FROM `fakturas` WHERE (`status` = 'new' OR `status` = 'locked') AND `id` = ".$id." LIMIT 1");
		
		if($GLOBALS['_user']['fullname'] != $faktura[0]['clerk'] && $GLOBALS['_user']['access'] > 2) {
			return array('error' => 'Du har ikke retigheder til at ændre i denne faktura!');
		}
		
		if(!$faktura && $type != 'cancel') {
			return array('error' => 'Fakturaen er låst og kune ikke ændres!');
		}
		
		$mysqli->query('UPDATE `fakturas` SET `quantities` = \''.$quantities.'\', `products` = \''.$products.'\', `values` = \''.$values.'\', `fragt` = \''.$fragt.'\', `department` = \''.$department.'\', `momssats` = \''.$momssats.'\', `premoms` = \''.$premoms.'\', `date` = STR_TO_DATE(\''.$date.'\', \'%d/%m/%Y\'), `iref` = \''.$iref.'\', `eref` = \''.$eref.'\', `navn` = \''.$navn.'\', `att` = \''.$att.'\', `adresse` = \''.$adresse.'\', `postbox` = \''.$postbox.'\', `postnr` = \''.$postnr.'\', `by` = \''.$by.'\', `land` = \''.$land.'\', `email` = \''.$email.'\', `tlf1` = \''.$tlf1.'\', `tlf2` = \''.$tlf2.'\', `clerk` = \''.$GLOBALS['_user']['fullname'].'\', `note` = \''.$note.'\'  WHERE `id` = '.$id.' AND `status` = \'new\' LIMIT 1;');
		if($type == 'lock')
			$mysqli->query('UPDATE `fakturas` SET `status` = \'locked\'  WHERE `id` = '.$id.' AND `status` = \'new\' LIMIT 1;');
		if($type == 'cancel')
			$mysqli->query('UPDATE `fakturas` SET `status` = \'canceled\'  WHERE `id` = '.$id.' AND `status` IN(\'new\', \'locked\', \'pbserror\') LIMIT 1;');
	
		return array('type' => $type);
	}
	
	function ny() {
		global $mysqli;
		
		$mysqli->query("INSERT INTO `fakturas` (`date`, `clerk`) VALUES (now(), '".$GLOBALS['_user']['fullname']."');");
		return $mysqli->insert_id;
	}
	
	require_once '../inc/getaddress.php';

//	$sajax_debug_mode = 1;
	sajax_export('save', 'ny', 'getAddress');
//	$sajax_remote_uri = '/ajax.php';
	sajax_handle_client_request();

	if(!$_GET['id']) {
		$next = $mysqli->fetch_array('SELECT id FROM fakturas WHERE `status` != \'canceled\' ORDER BY id DESC LIMIT 1');
		$_GET['id'] = $next[0]['id'];
	}
	if(!$_GET['id']) {
		$_GET['id'] = ny();
	}

	$prev = $mysqli->fetch_array('SELECT id FROM fakturas WHERE id < '.$_GET['id'].' AND `status` != \'canceled\' ORDER BY id DESC LIMIT 1');
	$next = $mysqli->fetch_array('SELECT id FROM fakturas WHERE id > '.$_GET['id'].' AND `status` != \'canceled\' ORDER BY id ASC LIMIT 1');
	$faktura = $mysqli->fetch_array('SELECT *, UNIX_TIMESTAMP( date ) AS udate FROM fakturas WHERE id = '.$_GET['id'].' LIMIT 1');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Faktura <?php echo($_GET['id']); ?></title>
<link href="style/fakturas.css" rel="stylesheet" type="text/css" />
<link  href="style/calendar.css"rel="stylesheet" type="text/css" />
<link href="style/fakturas_print.css" rel="stylesheet" type="text/css" media="print" />
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript" src="/javascript/zipcodedk.js"></script>
<script type="text/javascript" src="javascript/fakturas.js"></script>
<script type="text/javascript" src="javascript/calendar.js"></script>
<script type="text/javascript"><?

//	sajax_show_javascript();
	
	?>
	var id = <?php echo($faktura[0]['id']); ?>;
	var checkid = '<?php echo(getCheckid($faktura[0]['id'])); ?>';
	var quantities = '<?php echo(preg_replace('/\'/u', '\\\'', $faktura[0]['quantities'])); ?>';
	var products = '<?php echo(preg_replace('/\'/u', '\\\'', $faktura[0]['products'])); ?>';
	var values = '<?php echo(preg_replace('/\'/u', '\\\'', $faktura[0]['values'])); ?>';
	<?php
	
	$faktura[0]['quantities'] = explode('<', $faktura[0]['quantities']);
	$faktura[0]['products'] = explode('<', $faktura[0]['products']);
	$faktura[0]['values'] = explode('<', $faktura[0]['values']);
	
	if($faktura[0]['status'] != 'new' && $faktura[0]['premoms']) {
		//if it's new the price will be updated via javascript
		function removeMoms($value) {
			global $faktura;
			return $value/(1+$faktura[0]['momssats']);
		}
		
		$faktura[0]['values'] = array_map('removeMoms', $faktura[0]['values']);
	}
	
	$productslines = max(count($faktura[0]['quantities']), count($faktura[0]['products']), count($faktura[0]['values']));
?>
</script>
</head>
<body onload="setEmailLink(); <?php if($faktura[0]['status'] == 'new') { ?>prisUpdate(); <?php } ?>$('loading').style.display = 'none';">
<div id="main"<?php if($faktura[0]['status'] == 'new') { ?> class="web"<?php } ?>>
<div id="menu" class="web">
    <p><form action="" onsubmit="x_ny(ny_r); return false;" method="post" style="display:none;"><input type="submit" accesskey="n" /></form><a onclick="x_ny(ny_r); return false;" href="#"><img src="images/table_add.png" alt="Opret ny" title="Opret ny" width="16" height="16" /></a> <?php
	if($faktura[0]['status'] == 'new') {
	    ?><a id="savebn" onclick="save(); return false;" href="#"><img src="images/table_save.png" alt="Gem" title="Gem" width="16" height="16" /></a> <?php
	    ?><form action="" onsubmit="save('lock'); return false;" method="post" style="display:none;"><input type="submit" accesskey="l" /></form><a id="lockbn" onclick="save('lock'); return false;" href="#"><img src="images/lock.png" alt="Lås" title="Lås" width="16" height="16" /></a> <?php
	}
    ?><a id="printbn"<?php if($faktura[0]['status'] == 'new') { ?> style="display:none;"<?php } ?> onclick="window.print(); return false;" href="#"><img src="images/printer.png" alt="Udskriv" title="Udskriv" width="16" height="16" /></a> <?php
	if($faktura[0]['status'] == 'new' || $faktura[0]['status'] == 'locked' || $faktura[0]['status'] == 'pbserror') {
	    ?><form action="" onsubmit="save('cancel'); return false;" method="post" style="display:none;"><input type="submit" accesskey="c" /></form><a onclick="save('cancel'); return false;" href="#"><img src="images/bin.png" alt="Annuller" title="Annuller" width="16" height="16" /></a> <?php
	}
	?><img src="images/loading.gif" alt="Indlæser" id="loading" /></p><p><?php
    if($prev[0]['id']) {
		?><form action="?id=<?php echo($prev[0]['id']); ?>" method="post" style="display:none;"><input type="submit" accesskey="p" /></form><a href="?id=<?php echo($prev[0]['id']); ?>"><img src="images/table_goback.png" alt="Forige" title="Forige" width="16" height="16" /></a> <?php
	}
    if($next[0]['id']) {
		?><form action="?id=<?php echo($next[0]['id']); ?>" method="post" style="display:none;"><input type="submit" accesskey="x" /></form><a href="?id=<?php echo($next[0]['id']); ?>"><img src="images/table_go.png" alt="Næste" title="Næste" width="16" height="16" /></a> <?php
    }
    ?></p>
    <form action="" method="get">
      <input maxlength="6" name="id" value="<?php echo($faktura[0]['id']); ?>" style="width:100px;" />
    </form>
  </div>
  <form id="wrapper" action="" method="post" onsubmit="save(); return false;"><input type="submit" accesskey="s" style="display:none;" />
    <?php
  if($faktura[0]['status'] != 'new') { echo('<p class="web"><strong>Status: '.$faktura[0]['status'].'</strong></p>'); }
  ?>
    <address class="printblock"><?php
	/*
    ?>H.C. Ørsteds Vej 7 B<br />
	1879 Frederiksberg C<br />
    Fax.: 33 22 82 00<br />
    <big>Tel.: 33 222 333<br /><?php
	/**/
	//*
	echo($GLOBALS['_config']['address']); ?><br />
	<?php echo($GLOBALS['_config']['postcode'].' '.$GLOBALS['_config']['city']); ?><br />
	Fax: +45 <?php echo($GLOBALS['_config']['fax']); ?><br />
    <big>Tel.: <?php echo($GLOBALS['_config']['phone']); ?><br /><?php
	/**/
	?>
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
    <h1 class="printblock"><?php echo($GLOBALS['_config']['site_name']); ?></h1>
    <table id="postadresse"<?php if($faktura[0]['status'] == 'new') { ?> class="printblock"<?php } ?>>
      <tr>
        <td id="postadressetd"><?php echo($faktura[0]['navn']);
		if($faktura[0]['att']) echo('<br />Att.: '.$faktura[0]['att']);
		if($faktura[0]['adresse']) echo('<br />'.$faktura[0]['adresse']);
		if($faktura[0]['postbox']) echo('<br />'.$faktura[0]['postbox']);
		if($faktura[0]['postnr']) echo('<br />'.$faktura[0]['postnr'].' '.$faktura[0]['by']);
		else echo('<br />'.$faktura[0]['by']); 
		if($faktura[0]['land']) echo('<br />'.$faktura[0]['land']); ?></td>
      </tr>
    </table>
    <table style="width:300px;<?php if($faktura[0]['status'] != 'new') { ?>display:none;<?php } ?>" class="web">
      <tr>
        <td><label for="tlf1">Tlf1.: </label></td>
        <td colspan="3"><input maxlength="16" id="tlf1" name="tlf1" style="width:200px;" value="<?php echo($faktura[0]['tlf1']); ?>" />
          <input style="width:90px;" value="Hent" type="button" onclick="getAddress($('tlf1').value); return false;" /></td>
      </tr>
        <td><label for="tlf2">Tlf2.: </label></td>
        <td colspan="3"><input maxlength="16" id="tlf2" name="tlf2" style="width:200px;" value="<?php echo($faktura[0]['tlf2']); ?>" />
          <input style="width:90px;" value="Hent" type="button" onclick="getAddress($('tlf2').value); return false;" /></td>
      </tr>
      <tr>
        <td><label for="navn">Navn: </label></td>
        <td colspan="3"><input maxlength="64" id="navn" name="navn" style="width:295px;" value="<?php echo($faktura[0]['navn']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
      </tr>
      <tr>
        <td><label for="att">Att.: </label></td>
        <td colspan="3"><input maxlength="64" id="att" name="att" style="width:295px;" value="<?php echo($faktura[0]['att']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
      </tr>
      <tr>
        <td><label for="adresse">Adresse: </label></td>
        <td colspan="3"><input maxlength="64" id="adresse" name="adresse" style="width:295px;" value="<?php echo($faktura[0]['adresse']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
      </tr>
      <tr>
        <td><label for="postbox">Posboks: </label></td>
        <td colspan="3"><input maxlength="20" id="postbox" name="postbox" style="width:295px;" value="<?php echo($faktura[0]['postbox']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
      </tr>
      <tr>
        <td><label for="postnr">Postnr.: </label></td>
        <td><input maxlength="7" id="postnr" name="postnr" size="4" value="<?php if($faktura[0]['postnr']) echo($faktura[0]['postnr']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
        <td style="text-align:right;"><label for="by">By: </label></td>
        <td style="text-align:right"><input maxlength="128" id="by" name="by" style="width:200px;" value="<?php echo($faktura[0]['by']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
      </tr>
      <tr>
        <td><label for="land">Land: </label></td>
        <td colspan="3"><input maxlength="64" id="land" name="land" style="width:295px;" value="<?php echo($faktura[0]['land']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
      </tr>
      <tr>
        <td><label for="email">E-mail: </label></td>
        <td colspan="3"><input maxlength="64" id="email" name="email" style="width:295px;" value="<?php echo($faktura[0]['email']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
      </tr>
    </table>
    <div id="spec">
    <div style="margin-bottom:5pt" id="ref">
      <label for="date"><strong>Dato: </strong></label>
      <input maxlength="10" name="date" id="date" size="10" class="web"<?php if($faktura[0]['status'] != 'new') { ?> style="display:none;"<?php } ?> value="<?php echo(date('d/m/Y',$faktura[0]['udate'])); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /><?php
	  if($faktura[0]['status'] == 'new') {
      ?><script type="text/javascript"><!--
new tcal ({ 'controlid': 'date' });
--></script><?php } ?></form>
      <span class="date<?php if($faktura[0]['status'] == 'new') { ?> printinline<?php } ?>"><?php echo($faktura[0]['date']); ?></span>
      <label for="iref"><strong>Vor ref.: </strong></label>
      <input maxlength="32" name="iref" id="iref" style="width:44px;<?php if($faktura[0]['status'] != 'new') { ?>display:none;<?php } ?>" class="web" value="<?php echo($faktura[0]['iref']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />
      <span class="iref<?php if($faktura[0]['status'] == 'new') { ?> printinline<?php } ?>"><?php echo($faktura[0]['iref']); ?></span>
      <label for="eref"> <strong>Deres ref.: </strong></label>
      <input maxlength="32" name="eref" id="eref" style="width:93px;<?php if($faktura[0]['status'] != 'new') { ?>display:none;<?php } ?>" class="web" value="<?php echo($faktura[0]['eref']); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />
      <span class="eref<?php if($faktura[0]['status'] == 'new') { ?> printinline<?php } ?>"><?php echo($faktura[0]['eref']); ?></span></div>
    <div id="fakturadiv"><strong>Online faktura</strong> <?php echo($faktura[0]['id']); ?> </div>
	<div class="web"<?php if($faktura[0]['status'] != 'new') { ?> style="display:none;"<?php } ?>><input type="checkbox"<?php if($faktura[0]['premoms']) echo(' checked="checked"'); ?> id="premoms" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" onclick="prisUpdate()" /> <label for="premoms">Indtasted beløb er med moms</label></div>
    <table id="data" cellspacing="0">
      <thead>
        <tr>
          <td class="td1">Antal</td>
          <td class="td2">Benævnelse</td>
          <td class="td3 tal">á pris</td>
          <td class="td4 tal">Total</td>
        </tr>
      </thead>
      <tfoot>
        <tr style="height:auto;min-height:auto;max-height:auto;">
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td class="tal">Nettobeløb</td>
          <td class="tal" id="netto"><?php
		
		$netto = 0;
		for($i=0;$i<$productslines;$i++) {
			$netto += $faktura[0]['values'][$i]*$faktura[0]['quantities'][$i];
		}
		
        echo(number_format($netto, 2, ',', ''));
		
		?></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td class="tal">Fragt</td>
          <td><input maxlength="7" name="fragt" id="fragt" style="width:80px;<?php if($faktura[0]['status'] != 'new') { ?> display:none;<?php } ?>" class="tal web" value="<?php echo(number_format($faktura[0]['fragt'], 2, ',', '')); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />
            <p class="fragt tal<?php if($faktura[0]['status'] == 'new') { ?> printblock<?php } ?>"><?php echo(number_format($faktura[0]['fragt'], 2, ',', '')); ?></p></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td style="text-align:right"><p class="momssats tal<?php if($faktura[0]['status'] == 'new') { ?> printblock<?php } ?>"><?php echo(($faktura[0]['momssats']*100).'%');?></p>
            <select class="web"<?php if($faktura[0]['status'] != 'new') { ?> style="display:none;"<?php } ?> name="momssats" id="momssats" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()">
              <option value="0.25"<?php if($faktura[0]['momssats'] == 0.25) echo(' selected="selected"');?>>25%</option>
              <option value="0"<?php if(!$faktura[0]['momssats']) echo(' selected="selected"');?>>0%</option>
            </select></td>
          <td class="tal">Momsbeløb</td>
          <td class="tal" id="moms"><?php echo(number_format($netto*$faktura[0]['momssats'], 2, ',', '')); ?></td>
        </tr>
        <tr id="border">
          <td colspan="2" id="warning"><strong>Betalingsbetingelser:</strong> Netto kontant ved faktura modtagelse.<br />
            <span style="font-size:8pt;">Ved senere indbetaling end anførte frist, vil der blive debiteret 2% rente pr. påbegyndt måned.</span></td>
          <td style="text-align:center; font-weight:bold;">AT BETALE</td>
          <td class="tal" id="payamount"><?php echo(number_format($netto*$faktura[0]['momssats']+$netto+$faktura[0]['fragt'], 2, ',', '')); ?></td>
        </tr>
      </tfoot>
      <tbody id="vareTable">
        <?php
	for($i=0;$i<$productslines;$i++) {
		?>
        <tr>
          <td><input name="quantitie" style="width:58px;<?php if($faktura[0]['status'] != 'new') { ?>display:none;<?php } ?>" class="tal web" value="<?php echo($faktura[0]['quantities'][$i]); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />
            <p class="quantitie tal<?php if($faktura[0]['status'] == 'new') { ?> printblock<?php } ?>"><?php echo($faktura[0]['quantities'][$i]); ?></p></td>
          <td><input name="product" style="width:303px;<?php if($faktura[0]['status'] != 'new') { ?>display:none;<?php } ?>" class="web" value="<?php echo($faktura[0]['products'][$i]); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />
            <p class="product<?php if($faktura[0]['status'] == 'new') { ?> printblock<?php } ?>"><?php echo($faktura[0]['products'][$i]); ?></p></td>
          <td><input name="value" style="width:69px;<?php if($faktura[0]['status'] != 'new') { ?>display:none;<?php } ?>" class="tal web" value="<?php if($faktura[0]['values'][$i]) echo(number_format($faktura[0]['values'][$i], 2, ',', '')); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />
            <p class="value tal<?php if($faktura[0]['status'] == 'new') { ?> printblock<?php } ?>">
              <?php if($faktura[0]['values'][$i]) echo(number_format($faktura[0]['values'][$i], 2, ',', '')); ?>
            </p></td>
          <td class="tal"><span class="total web"<?php if($faktura[0]['status'] != 'new') { ?> style="display:none;"<?php } ?>><?php echo(number_format($faktura[0]['values'][$i]*$faktura[0]['quantities'][$i], 2, ',', '')); ?></span><span class="total<?php if($faktura[0]['status'] == 'new') { ?> printinline<?php } ?>"><?php echo(number_format($faktura[0]['values'][$i]*$faktura[0]['quantities'][$i], 2, ',', '')); ?></span></td>
          <td style="border:0; font-weight:bold;<?php if($faktura[0]['status'] != 'new') { ?>display:none;<?php } ?>" class="web"><a href="#" onclick="removeRow(this); return false"><img alt="X" src="images/cross.png" height="16" width="16" title="Fjern linje" /></a></td>
        </tr>
        <?php
	}
	?>
      </tbody>
    </table>
    <br />
    <strong>Notat:</strong><br />
    <p class="note<?php if($faktura[0]['status'] == 'new') { ?> printblock<?php } ?>"><?php echo(nl2br($faktura[0]['note'])); ?></p>
    <textarea cols="" class="web"<?php if($faktura[0]['status'] != 'new') { ?> style="display:none;"<?php } ?> name="note" id="note" style="width:512px;" rows="3" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()"><?php echo($faktura[0]['note']); ?></textarea>
    <br />
    <br />
    <div class="web" id="webfunction"<?php if($faktura[0]['status'] == 'cancled' || $faktura[0]['status'] == 'pbsok' || $faktura[0]['status'] == 'rejected' || $faktura[0]['status'] == 'accepted') { ?> style="display:none;"<?php } ?>>
	<?php
	
    if($faktura[0]['status'] == 'new') { echo('<p class="web">Ekspedient: '.$faktura[0]['clerk'].'</p>'); }
	//TODO move to menu
	if(count($GLOBALS['_config']['email']) == 1) {
      ?><input name="department" id="department" value="<?php echo($GLOBALS['_user']['email'][0]); ?>" type="hidden" /><?php
	} else {
		?>Afsender: <select id="department" name="department"><?php
		foreach($GLOBALS['_config']['email'] as $value) {
			?><option<?php if($faktura[0]['department'] == $value) { ?> selected="selected"<?php } ?> value="<?php echo($value); ?>"><?php echo($value); ?></option><?php
		}
		?></select><br /><?php
	}
	//TODO remove this input
	?><input maxlength="32" value="<?php echo($GLOBALS['_user']['fullname']); ?>" name="clerk" id="clerk" type="hidden" /><?
	?>Online betaling: <?php echo($GLOBALS['_config']['base_url']); ?>/faktura/?id=<?php echo($faktura[0]['id']); ?>&checkid=<?php echo(getCheckid($faktura[0]['id'])); ?><br /><a href="" id="emaillink"<?php if(!$faktura[0]['status'] == 'new') { ?> onclick="save();"<?php } if(!$faktura[0]['email']) { ?> style="display:none;"<?php } ?>>Send link til kunden</a>
    </div>
    <p<?php if($faktura[0]['status'] == 'new') { ?> class="printblock"<?php } ?> style="margin-left:12cm; font-size:12pt;"><strong>Med venlig hilsen<br />
      <br />
      <br />
      <span class="clerk<?php if($faktura[0]['status'] == 'new') { ?> printinline<?php } ?>"><?php echo($faktura[0]['clerk']); ?></span> <br />
      </strong><strong><?php echo($GLOBALS['_config']['site_name']); ?></strong></p></div>
  </form>
</div>
</body>
</html>
