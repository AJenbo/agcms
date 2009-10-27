<?php
/*
ini_set('display_errors', 1);
error_reporting(-1);
*/

chdir('../');

//Generate default $GLOBALS['generatedcontent']
$delayprint = true;
require_once 'index.php';
$GLOBALS['generatedcontent']['datetime'] = time();

function getCheckid($id) {
	return substr(md5($id.$GLOBALS['_config']['pbspassword']), 3, 5);
}

function validemail($email) {
	if($email &&
	preg_match('/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/ui', $email) &&
	getmxrr(preg_replace('/.+?@(.?)/u', '$1', $email), $dummy)) {
		return true;
	} else {
		return false;
	}
}

function validate($values) {
	
	$rejected = array();
	
	if(!validemail($values['email']))
		$rejected['email'] = true;
	
	if(!$values['navn']) {
		$rejected['navn'] = true;
	}
	if(!$values['land']) {
		$rejected['land'] = true;
	}
	if((!$values['adresse'] || ($values['land'] == 'DK' && !preg_match('/\s/ui', $values['adresse']))) && !$values['postbox']) {
		$rejected['adresse'] = true;
	}
	if(!$values['postnr']) {
		$rejected['postnr'] = true;
	}
	//TODO if land = DK and postnr != by
	if(!$values['by']) {
		$rejected['by'] = true;
	}
	if(!$values['land']) {
		$rejected['land'] = true;
	}
	if($values['altpost']) {
		if(!$values['postname']) {
			$rejected['postname'] = true;
		}
		if(!$values['land']) {
			$rejected['land'] = true;
		}
		if((!$values['postaddress'] || ($values['postcountry'] == 'DK' && !preg_match('/\s/ui', $values['postaddress']))) && !$values['postpostbox']) {
			$rejected['postaddress'] = true;
		}
		if(!$values['postpostalcode']) {
			$rejected['postpostalcode'] = true;
		}
		//TODO if postcountry = DK and postpostalcode != postcity
		if(!$values['postcity']) {
			$rejected['postcity'] = true;
		}
		if(!$values['postcountry']) {
			$rejected['postcountry'] = true;
		}
	}
	return $rejected;
}

//Generate return page
$GLOBALS['generatedcontent']['crumbs'] = array();
if(!empty($_GET['id'])) {
	$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => 'Betaling', 'link' => '/?id='.$_GET['id'].'&checkid='.$_GET['checkid'], 'icon' => NULL);
} else {
	$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => 'Betaling', 'link' => '/', 'icon' => NULL);
}
$GLOBALS['generatedcontent']['contenttype'] = 'page';
$GLOBALS['generatedcontent']['text'] = '';

if(!empty($_GET['id']) && @$_GET['checkid'] == getCheckid($_GET['id'])) {
	$faktura = $mysqli->fetch_array("SELECT * FROM `fakturas` WHERE `id` = ".$_GET['id']);
	$faktura = @$faktura[0];
	
	if($faktura['status'] == 'new' || $faktura['status'] == 'locked' || $faktura['status'] == 'pbserror') {
		$faktura['quantities'] = explode('<', $faktura['quantities']);
		$faktura['products'] = explode('<', $faktura['products']);
		$faktura['values'] = explode('<', $faktura['values']);
		
		if($faktura['premoms']) {
			foreach($faktura['values'] as $key => $value) {
				$faktura['values'][$key] = $value/1.25;
			}
		}
		
		$productslines = max(count($faktura['quantities']), count($faktura['products']), count($faktura['values']));
		
		$netto = 0;
		for($i=0;$i<$productslines;$i++) {
			$netto += $faktura['values'][$i]*$faktura['quantities'][$i];
		}
		
		if(!$_GET['step']) {
			$mysqli->query("UPDATE `fakturas` SET `status` = 'locked' WHERE `status` IN('new', 'pbserror') AND `id` = ".$_GET['id']);
			
			$GLOBALS['generatedcontent']['crumbs'] = array();
			$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Ordre #'.$_GET['id'], 'link' => '#', 'icon' => NULL);
			$GLOBALS['generatedcontent']['title'] = 'Ordre #'.$_GET['id'];
			$GLOBALS['generatedcontent']['headline'] = 'Ordre #'.$_GET['id'];
		
			
			$GLOBALS['generatedcontent']['text'] = '<table id="faktura" cellspacing="0">
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
						<td class="tal">Nettobeløb</td>';
			
			$GLOBALS['generatedcontent']['text'] .= '<td class="tal">'.number_format($netto, 2, ',', '').'</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td class="tal">Fragt</td>
					<td class="tal">'.number_format($faktura['fragt'], 2, ',', '').'</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td style="text-align:right" class="tal">'.($faktura['momssats']*100).'%</td>
					<td class="tal">Momsbeløb</td>
					<td class="tal">'.number_format($netto*$faktura['momssats'], 2, ',', '').'</td>
				</tr>
				<tr class="border">
					<td colspan="2">Alle beløb er i DKK</td>
					<td style="text-align:center; font-weight:bold;">AT BETALE</td>
					<td class="tal"><big>'.number_format($faktura['amount'], 2, ',', '').'</big></td>
				</tr>
			</tfoot>
			<tbody>';
			for($i=0; $i<$productslines; $i++) {
				$GLOBALS['generatedcontent']['text'] .= '<tr>
					<td class="tal">'.$faktura['quantities'][$i].'</td>
					<td>'.$faktura['products'][$i].'</td>
					<td class="tal">'.number_format($faktura['values'][$i], 2, ',', '').'</td>
					<td class="tal">'.number_format($faktura['values'][$i]*$faktura['quantities'][$i], 2, ',', '').'</td>
				</tr>';
			}
			
			$GLOBALS['generatedcontent']['text'] .= '</tbody></table>';
			
			if($faktura['note']) {
				$GLOBALS['generatedcontent']['text'] .= '<br /><strong>Notat:</strong><br /><p class="note">';	
				$GLOBALS['generatedcontent']['text'] .= nl2br(htmlspecialchars($faktura['note'])).'</p>';
			}
			$GLOBALS['generatedcontent']['text'] .= '<form action="" method="get"><input type="hidden" name="id" value="'.$_GET['id'].'" /><input type="hidden" name="checkid" value="'.$_GET['checkid'].'" /><input type="hidden" name="step" value="1" /><input type="hidden" name="checkid" value="'.$_GET['checkid'].'" /><input style="font-weight:bold;" type="submit" value="Fortsæt" /></form>';
			
		} elseif($_GET['step'] == 1) {
			if($_POST) {
				$updates = array();
				$updates['navn'] = $_POST['navn'];
				$updates['att'] = $_POST['att'] != $_POST['navn'] ? $_POST['att'] : '';
				$updates['adresse'] = $_POST['adresse'];
				$updates['postbox'] = $_POST['postbox'];
				$updates['postnr'] = $_POST['postnr'];
				$updates['by'] = $_POST['by'];
				$updates['land'] = $_POST['land'];
				$updates['email'] = $_POST['email'];
				$updates['tlf1'] = $_POST['tlf1'] != $_POST['tlf2'] ? $_POST['tlf1'] : '';
				$updates['tlf2'] = $_POST['tlf2'];
				$updates['altpost'] = $_POST['altpost'] ? 1 : 0;
				$updates['posttlf'] = $_POST['posttlf'];
				$updates['postname'] = $_POST['postname'];
				$updates['postatt'] = $_POST['postatt'] != $_POST['postname'] ? $_POST['postatt'] : '';
				$updates['postaddress'] = $_POST['postaddress'];
				$updates['postaddress2'] = $_POST['postaddress2'];
				$updates['postpostbox'] = $_POST['postpostbox'];
				$updates['postpostalcode'] = $_POST['postpostalcode'];
				$updates['postcity'] = $_POST['postcity'];
				$updates['postcountry'] = $_POST['postcountry'];
				$updates['enote'] = $_POST['enote'];
				$updates = array_map('trim', $updates);
				
				$rejected = validate($updates);
				
				$sql = "UPDATE `fakturas` SET";
				foreach($updates as $key => $value)
					$sql .= " `".addcslashes($key, '`')."` = '".addcslashes($value, "'")."',";
				$sql = substr($sql, 0, -1);
				
				$sql .= 'WHERE `id` = '.$_GET['id'];
				
				$mysqli->query($sql);
				
				$faktura = array_merge($faktura, $updates);
			
				//TODO move down to skip address page if valid
				if(!count($rejected)) {
					header('Location: '.$GLOBALS['_config']['base_url'].'/betaling/?id='.$_GET['id'].'&checkid='.$_GET['checkid'].'&step=2', TRUE, 303);
					exit;
				}
			} else {
				$rejected = validate($faktura);
			}
			
			//TODO add enote
			$GLOBALS['generatedcontent']['crumbs'] = array();
			$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Modtager', 'link' => '#', 'icon' => NULL);
			$GLOBALS['generatedcontent']['title'] = 'Modtager';
			$GLOBALS['generatedcontent']['headline'] = 'Modtager';
			
			$GLOBALS['generatedcontent']['text'] = '
			<script type="text/javascript"><!--
			window.history.forward(1);
			--></script>
			<script type="text/javascript" src="javascript.js"></script>
			<script type="text/javascript" src="/javascript/zipcodedk.js"></script>
			<form action="" method="post" onsubmit="return validateaddres()">
	<table>
		<tbody>
			<tr>
				<td> Telfon:</td>
				<td colspan="2"><input name="tlf1" id="tlf1" style="width:157px" value="'.$faktura['tlf1'].'" /></td>
				<td><input type="button" value="Hent adresse" /></td>
			</tr>
			<tr>
				<td> Mobil:</td>
				<td colspan="2"><input name="tlf2" id="tlf2" style="width:157px" value="'.$faktura['tlf2'].'" /></td>
				<td><input type="button" value="Hent adresse" /></td>
			</tr>
			<tr>
				<td>Navn:</td>
				<td colspan="2"><input name="navn" id="navn" style="width:157px" value="'.$faktura['navn'].'" /></td>
				<td>';
			if($rejected['navn'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td>
			</tr>
			<tr>
				<td> Att:</td>
				<td colspan="2"><input name="att" id="att" style="width:157px" value="'.$faktura['att'].'" /></td>
				<td></td>
			</tr>
			<tr>
				<td> Adresse:</td>
				<td colspan="2"><input name="adresse" id="adresse" style="width:157px" value="'.$faktura['adresse'].'" /></td>
				<td>';
			if($rejected['adresse'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td>
			</tr>
			<tr>
				<td> Postboks:</td>
				<td colspan="2"><input name="postbox" id="postbox" style="width:157px" value="'.$faktura['postbox'].'" /></td>
				<td></td>
			</tr>
			<tr>
				<td> Postnr:</td>
				<td><input name="postnr" id="postnr" style="width:35px" value="'.$faktura['postnr'].'" onblur="chnageZipCode(this.value, \'land\', \'by\')" onkeyup="chnageZipCode(this.value, \'land\', \'by\')" onchange="chnageZipCode(this.value, \'land\', \'by\')" /></td>
				<td align="right">By:
					<input name="by" id="by" style="width:90px" value="'.$faktura['by'].'" /></td>
				<td>';
			if($rejected['postnr'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			if($rejected['by'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td>
			</tr>
			<tr>
				<td> Land:</td>
				<td colspan="2"><select name="land" id="land" style="width:157px" onblur="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onkeyup="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onchange="chnageZipCode($(\'postnr\').value, \'land\', \'by\')">';
			require_once 'inc/countries.php';
			foreach($countries as $code => $country) {
				$GLOBALS['generatedcontent']['text'] .= '<option value="'.$code.'"';
				if($faktura['land'] == $code)
					$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
				$GLOBALS['generatedcontent']['text'] .= '>'.htmlspecialchars($country).'</option>';
			}
			$GLOBALS['generatedcontent']['text'] .= '</select></td>
				<td>';
			if($rejected['land'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td>
			</tr>
			<tr>
				<td> Email:</td>
				<td colspan="2"><input name="email" id="email" style="width:157px" value="'.$faktura['email'].'" /></td>
				<td>';
			if($rejected['email'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td>
			</tr>
			<tr>
				<td colspan="4"><input onclick="showhidealtpost(this.checked);" name="altpost" id="altpost" type="checkbox"';
			 if($faktura['altpost']) $GLOBALS['generatedcontent']['text'] .= ' checked="checked"';
			 $GLOBALS['generatedcontent']['text'] .= ' /><label for="altpost"> Anden leveringsadresse</label></td>
			</tr>
			<tr class="altpost"';
			if(!$faktura['altpost']) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
			$GLOBALS['generatedcontent']['text'] .= '>
				<td> Telfon:</td>
				<td colspan="2"><input name="posttlf" id="posttlf" style="width:157px" value="'.$faktura['posttlf'].'" /></td>
				<td><input type="button" value="Hent" /></td>
			</tr>
			<tr class="altpost"';
			if(!$faktura['altpost']) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
			$GLOBALS['generatedcontent']['text'] .= '>
				<td>Navn:</td>
				<td colspan="2"><input name="postname" id="postname" style="width:157px" value="'.$faktura['postname'].'" /></td>
				<td>';
			if($rejected['postname'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td>
			</tr>
			<tr class="altpost"';
			if(!$faktura['altpost']) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
			$GLOBALS['generatedcontent']['text'] .= '>
				<td> Att:</td>
				<td colspan="2"><input name="postatt" id="postatt" style="width:157px" value="'.$faktura['postatt'].'" /></td>
				<td></td>
			</tr>
			<tr class="altpost"';
			if(!$faktura['altpost']) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
			$GLOBALS['generatedcontent']['text'] .= '>
				<td> Adresse:</td>
				<td colspan="2"><input name="postaddress" id="postaddress" style="width:157px" value="'.$faktura['postaddress'].'" /><br /><input name="postaddress2" id="postaddress2" style="width:157px" value="'.$faktura['postaddress2'].'" /></td>
				<td>';
			if($rejected['postaddress'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td>
			</tr>
			<tr class="altpost"';
			if(!$faktura['altpost']) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
			$GLOBALS['generatedcontent']['text'] .= '>
				<td> Postboks:</td>
				<td colspan="2"><input name="postpostbox" id="postpostbox" style="width:157px" value="'.$faktura['postpostbox'].'" /></td>
				<td></td>
			</tr>
			<tr class="altpost"';
			if(!$faktura['altpost']) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
			$GLOBALS['generatedcontent']['text'] .= '>
				<td> Postnr:</td>
				<td><input name="postpostalcode" id="postpostalcode" style="width:35px" value="'.$faktura['postpostalcode'].'" onblur="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onchange="chnageZipCode(this.value, \'postcountry\', \'postcity\')" /></td>
				<td align="right">By:
					<input name="postcity" id="postcity" style="width:90px" value="'.$faktura['postcity'].'" /></td>
				<td>';
			if($rejected['postpostalcode'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			if($rejected['postcity'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td>
			</tr>
			<tr class="altpost"';
			if(!$faktura['altpost']) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
			$GLOBALS['generatedcontent']['text'] .= '>
				<td> Land:</td>
				<td colspan="2"><select name="postcountry" id="postcountry" style="width:157px" onblur="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onchange="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')">';
				
			require_once 'inc/countries.php';
			foreach($countries as $code => $country) {
				$GLOBALS['generatedcontent']['text'] .= '<option value="'.$code.'"';
				if($faktura['postcountry'] == $code)
					$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
				$GLOBALS['generatedcontent']['text'] .= '>'.htmlspecialchars($country).'</option>';
			}
			$GLOBALS['generatedcontent']['text'] .= '</select></td><td>';
			if($rejected['postcountry'])
				$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
			$GLOBALS['generatedcontent']['text'] .= '</td></tr></tbody></table><input style="font-weight:bold;" type="submit" value="Fortsæt til handelsbetingelserne" /></form>';
		} elseif($_GET['step'] == 2) {

			if(count(validate($faktura))) {
				header('Location: '.$GLOBALS['_config']['base_url'].'/betaling/?id='.$_GET['id'].'&checkid='.$_GET['checkid'].'&step=1', TRUE, 303);
				exit;
			}
			
			$mysqli->query("UPDATE `fakturas` SET `status` = 'locked' WHERE `status` IN('new', 'pbserror') AND `id` = ".$_GET['id']);
			
			$GLOBALS['generatedcontent']['crumbs'] = array();
			$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Handelsbetingelser', 'link' => '#', 'icon' => NULL);
			$GLOBALS['generatedcontent']['title'] = 'Handelsbetingelser';
			$GLOBALS['generatedcontent']['headline'] = 'Handelsbetingelser';
			
			$special = $mysqli->fetch_array("SELECT `text` FROM `special` WHERE `id` = 3 LIMIT 1");
			$GLOBALS['generatedcontent']['text'] .= '<br />'.$special[0][text];
			
			$submit['Merchant_id'] = $GLOBALS['_config']['pbsid'];
			$submit['Version'] = '2';
			$submit['Customer_refno'] = $GLOBALS['_config']['pbsfix'].$faktura['id'];
			$submit['Currency'] = 'DKK';
			$submit['Amount'] = number_format($faktura['amount'], 2, '', '');
			$submit['VAT'] = number_format($netto*$faktura['momssats'], 2, '', '');
			$submit['Payment_method'] = 'KORTINDK';
			$submit['Response_URL'] = $GLOBALS['_config']['base_url'].'/betaling/?checkid='.$_GET['checkid'];
			$submit['Goods_description'] = '';
			$submit['Language'] = 'DAN';
			$submit['Comment'] = '';
			$submit['Country'] = 'DK';
			$submit['Cancel_URL'] = $submit['Response_URL'];
			
			$GLOBALS['generatedcontent']['text'] .= '<form style="text-align:center;" action="https://epayment.auriganet.eu/paypagegw" method="post">';
			foreach($submit as $key => $value)
				$GLOBALS['generatedcontent']['text'] .= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'" />';
			$GLOBALS['generatedcontent']['text'] .= '<input type="hidden" name="MAC" value="'.md5(implode('', $submit).$GLOBALS['_config']['pbspassword']).'" />';
			$GLOBALS['generatedcontent']['text'] .= '<input class="web" type="submit" value="Jeg accepterer hermed handelsbetingelserne" /></form>';
		}
	} else {
		$GLOBALS['generatedcontent']['crumbs'] = array();
		$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Fejl', 'link' => '#', 'icon' => NULL);
		$GLOBALS['generatedcontent']['title'] = 'Fejl';
		$GLOBALS['generatedcontent']['headline'] = 'Fejl';
		if($faktura['status'] == 'pbsok') {
			$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Status', 'link' => '#', 'icon' => NULL);
			$GLOBALS['generatedcontent']['title'] = 'Status';
			$GLOBALS['generatedcontent']['headline'] = 'Status';
			$GLOBALS['generatedcontent']['text'] = 'Betalingen er modtaget.';
		} elseif($faktura['status'] == 'accepted') {
			$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Status', 'link' => '#', 'icon' => NULL);
			$GLOBALS['generatedcontent']['title'] = 'Status';
			$GLOBALS['generatedcontent']['headline'] = 'Status';
			$GLOBALS['generatedcontent']['text'] = 'Betalingen er modtaget og pakken er sendt.';
			$pakker = $mysqli->fetch_array("SELECT `STREGKODE` FROM `post` WHERE `deleted` = 0 AND `fakturaid` = ".$faktura['id']);
			
			require_once 'inc/snoopy.class.php';
			require_once 'inc/htmlsql.class.php';
			
			$wsql = new htmlsql();
			
			foreach($pakker as $pakke) {
				// connect to a URL
				$GLOBALS['generatedcontent']['text'] .= '<br /><br />Forsendelsens nummer: <strong>'.$pakke['STREGKODE'].'</strong><br /><br />';
				if (!$wsql->connect('url', 'http://www.postdanmark.dk/tracktrace/TrackTrace.do?i_lang=IND&i_stregkode='.$pakke['STREGKODE'])){
					print 'Error while connecting: ' . $wsql->error;
					exit;
				}
				
				if (!$wsql->query('SELECT text FROM div WHERE $id == "pdkTable"')){
					print "Query error: " . $wsql->error; 
					exit;
				}
			
				// show results:
				foreach($wsql->fetch_array() as $row){
				
					$GLOBALS['generatedcontent']['text'] .= utf8_encode(preg_replace(
						array('/\\sborder=0\\scellpadding=0/',
							  '/\\snowrap/',
							  '/&nbsp;/'), '', $row['text']));
					
			}
			}
			$pakker = $mysqli->fetch_array("SELECT `packageId` FROM `PNL` WHERE `fakturaid` = ".$faktura['id']);
			foreach($pakker as $pakke) {
				$GLOBALS['generatedcontent']['text'] .= '<br /><a href="http://online.pannordic.com/pn_logistics/index_tracking_email.jsp?id='.$pakke['packageId'].'&Search=search" target="_blank">'.$pakke['packageId'].'</a>';
			}
		} elseif($faktura['status'] == 'giro') {
			$GLOBALS['generatedcontent']['text'] = 'Betalingen er allerede modtaget via giro.';
		} elseif($faktura['status'] == 'cash') {
			$GLOBALS['generatedcontent']['text'] = 'Betalingen er allerede modtaget kontant.';
		} elseif($faktura['status'] == 'canceled') {
			$GLOBALS['generatedcontent']['text'] = 'Handlen er annulleret.';
		} elseif($faktura['status'] == 'rejected') {
			$GLOBALS['generatedcontent']['text'] = 'Betalingen er afvist.';
		} else {
			$GLOBALS['generatedcontent']['text'] = 'Der opstod en fejl.';
		}
	}

} elseif(!empty($_GET['Customer_refno'])) {
	$id = mb_substr($_GET['Customer_refno'], mb_strlen($GLOBALS['_config']['pbsfix']));
	
	//Set the proper order for the values
	$validate['Merchant_id'] = '';
	$validate['Version'] = '';
	$validate['Customer_refno'] = '';
	$validate['Transaction_id'] = '';
	$validate['Status'] = '';
	$validate['Status_code'] = '';
	$validate['AuthCode'] = '';
	$validate['3DSec'] = '';
	$validate['Batch_id'] = '';
	$validate['Payment_method'] = '';
	$validate['Card_type'] = '';
	$validate['Risk_score'] = '';
	$validate['Authorized_amount'] = '';
	$validate['Fee_amount'] = '';
	$validate = array_merge($validate, $_GET);
	unset($validate['checkid']);
	unset($validate['MAC']);
	
	$GLOBALS['generatedcontent']['crumbs'] = array();
	$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Fejl', 'link' => '#', 'icon' => NULL);
	$GLOBALS['generatedcontent']['title'] = 'Fejl';
	$GLOBALS['generatedcontent']['headline'] = 'Fejl';
	$GLOBALS['generatedcontent']['text'] = 'Der opstod en ukendt fejl.';
	
	$shopSubject = $_GET['Status'].$_GET['Status_code'];
	$shopBody = '<br />Der opstået en fejl på betalings siden ved online faktura #'.$id.'!<br />';
	
	if($faktura = $mysqli->fetch_array("SELECT * FROM `fakturas` WHERE `id` = ".$id)) {
		$faktura = @$faktura[0];
	}
	
	if($_GET['MAC'] != md5(implode('', $validate).$GLOBALS['_config']['pbspassword'])) {
		$GLOBALS['generatedcontent']['text'] = 'Kommunikationen kunne ikke valideres!';
	} elseif(!$faktura) {
		$GLOBALS['generatedcontent']['text'] = '<p>Betalingen findes ikke i vores system.</p>';
		$shopBody = '<br />En brugere forsøgte at betale online faktura #'.$id.' som ikke fines i systemet!<br />';
	} elseif($faktura['status'] != 'locked' && $faktura['status'] != 'new') {
		$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Kvittering', 'link' => '#', 'icon' => NULL);
		$GLOBALS['generatedcontent']['title'] = 'Kvittering';
		$GLOBALS['generatedcontent']['headline'] = 'Kvittering';
		$GLOBALS['generatedcontent']['text'] = '<p>Betalingen er registret og du skulde have modtaget en kvitering via E-mail.</p>';
		$shopBody = '<br />En brugere forsøgte at se status side for online faktura #'.$id.' som allerede er betalt.<br />';
	} elseif(!$_GET['Status']) {
		//User pressed "back"
		header('Location: '.$GLOBALS['_config']['base_url'].'/betaling/?id='.$id.'&checkid='.$_GET['checkid'].'&step=2', TRUE, 303);
		exit;
	} elseif($_GET['Status'] == 'E') {
		$GLOBALS['generatedcontent']['title'] = 'Fejl #'.$_GET['Status_code'];
		$GLOBALS['generatedcontent']['headline'] = 'Fejl #'.$_GET['Status_code'];
		$GLOBALS['generatedcontent']['text'] = 'Der opstod en fejl under betalingen.<br />Fejl nummer: '.$_GET['Status_code'];
		$mysqli->query("UPDATE `fakturas` SET `status` = 'pbserror', `paydate` = NOW() WHERE `status` IN('new', 'locked') AND `id` = ".$id);
		switch($_GET['Status_code']) {
			case 12:
				$GLOBALS['generatedcontent']['text'] = 'Kommunikationen kunne ikke valideres!';
				$shopSubject = $_GET['Status'].$_GET['Status_code'].' Fusk med data.';
				$shopBody = '<br />Kommunikationen kunne ikke valideres da '.$faktura['navn'].' skulde betale!<br />';
			break;
			case 18:
				$GLOBALS['generatedcontent']['text'] = 'Betalingssiden svarer ikke.';
				$shopSubject = $_GET['Status'].$_GET['Status_code'].' Betalingssiden svarer ikke.';
				$shopBody = '<br />Betalingssiden svarede ikke da '.$faktura['navn'].' skulde betale!<br />';
			break;
			case 19:
				$GLOBALS['generatedcontent']['text'] = 'Betalingen blev afvist af banken, korted er udløbet.';
				$shopSubject = $_GET['Status'].$_GET['Status_code'].' Betalingen blev afvist af banken, korted er udløbet.';
				$shopBody = '<br />Betalingen blev afvist af banken, '.$faktura['navn'].'\'s kort er udløbet!<br />';
			break;
			case 20:
				$GLOBALS['generatedcontent']['text'] = 'Betalingen blev afvist af banken, contact deres bank.';
				$shopSubject = $_GET['Status'].$_GET['Status_code'].' Betalingen blev afvist af banken, contact deres bank.';
				$shopBody = '<br />Betalingen blev afvist af banken, '.$faktura['navn'].' skal kontakte sin bank.<br />';
			break;
			case 56:
				$GLOBALS['generatedcontent']['text'] = 'Betalingen blev afvist da den allerede er forsøgt betalt.';
				$shopSubject = $_GET['Status'].$_GET['Status_code'].' Betalingen blev afvist.';
				$shopBody = '<br />Betalingen blev afvist da den allerede er forsøgt betalt.<br />';
			break;
		}
		
		//TODO email error to us
		//TODO add better description for errors
	} elseif($_GET['Status'] == 'A') {
		$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => 'Kvittering', 'link' => '#', 'icon' => NULL);
		$GLOBALS['generatedcontent']['title'] = 'Kvittering';
		$GLOBALS['generatedcontent']['headline'] = 'Kvittering';
		switch($_GET['Status_code']) {
			case 0:
				$mysqli->query("UPDATE `fakturas` SET `status` = 'pbsok', `paydate` = NOW() WHERE `status` IN('new', 'locked', 'pbserror') AND `id` = ".$id);
				
				$faktura = $mysqli->fetch_array("SELECT * FROM `fakturas` WHERE `id` = ".$id);
				$faktura = @$faktura[0];
				
				$GLOBALS['generatedcontent']['text'] = '<p style="text-align:center;"><img src="images/ok.png" alt="" /></p><p>Betalingen er nu godkendt. Vi sender Deres vare med posten hurtigst muligt.</p><p>En kopi af Deres ordre er sendt til Deres email.</p>';
				
				$faktura['quantities'] = explode('<', $faktura['quantities']);
				$faktura['products'] = explode('<', $faktura['products']);
				$faktura['values'] = explode('<', $faktura['values']);
					
				if($faktura['premoms']) {
					foreach($faktura['values'] as $key => $value) {
						$faktura['values'][$key] = $value/1.25;
					}
				}
				
				$shopSubject = 'Betaling gennemført';
				$shopBody = 'Kunden har godkendt betalingen og nedenstående ordre skal sendes til kunden.<br /><br />Husk at "ekspedere" betalingen når varen sendes (Betaling overføres først fra kundens konto, når vi trykker "Ekspedér").<br />';
				
				require_once 'inc/countries.php';
				$GLOBALS['generatedcontent']['track'] = ' pageTracker._addTrans("'.$faktura['id'].'", "", "'.$faktura['amount'].'", "'.(($faktura['amount']-$faktura['fragt'])*(1-(1/(1+$faktura['momssats'])))).'", "'.$faktura['fragt'].'", "'.$faktura['by'].'", "", "'.$countries[$faktura['land']].'");';
				foreach($faktura['products'] as $key => $product)
					$GLOBALS['generatedcontent']['track'] .= ' pageTracker._addItem("'.$faktura['id'].'", "'.$faktura['id'].$key.'", "'.$product.'", "", "'.($faktura['values'][$key]*(1+$faktura['momssats'])).'", "'.$faktura['quantities'][$key].'");';
				$GLOBALS['generatedcontent']['track'] .= ' pageTracker._trackTrans(); ';

				
				//Mail to customer start
				$emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>';
				$emailbody .= 'Ordre '.$faktura['id'].' - Betaling gennemført';
				$emailbody .= '</title><style type="text/css">#faktura td { border:1px #000 solid; border-collapse:collapse; padding:2px; }</style></head><body><p>Dato: '.$faktura['paydate'].'<br /></p><table><tr><td></td><td>Kunde:</td>';
				if($faktura['altpost']) $emailbody .= '<td>Lev. adresse:</td>';
				$emailbody .= '</tr><tr><td>Navn:</td><td>'.$faktura['navn'].'</td>';
				if($faktura['altpost']) $emailbody .= '<td>'.$faktura['postname'].'</td>';
				$emailbody .= '</tr>';
				if($faktura['tlf1'] || ($faktura['altpost'] && $faktura['posttlf'])) {
					$emailbody .= '<tr><td>Tlf.:</td><td>'.$faktura['tlf1'].'</td>';
					if($faktura['altpost']) $emailbody .= '<td>'.$faktura['posttlf'].'</td>';
					$emailbody .= '</tr>';
				}
				if($faktura['att'] || ($faktura['altpost'] && $faktura['postatt'])) {
					$emailbody .= '<tr><td>Att.:</td><td>'.$faktura['att'].'</td>';
					if($faktura['altpost']) $emailbody .= '<td>'.$faktura['postatt'].'</td>';
					$emailbody .= '</tr>';
				}
				if($faktura['adresse'] || ($faktura['adresse'] && ($faktura['postaddress'] || $faktura['postaddress2']))) {
					$emailbody .= '<tr><td>Adresse:</td><td>'.$faktura['adresse'].'</td>';
					if($faktura['altpost']) $emailbody .= '<td>'.$faktura['postaddress'].'<br />'.$faktura['postaddress2'].'</td>';
					$emailbody .= '</tr>';
				}
				if($faktura['postbox'] || ($faktura['altpost'] && $faktura['postpostbox'])) {
					$emailbody .= '<tr><td>Postboks:</td><td>'.$faktura['postbox'].'</td>';
					if($faktura['altpost']) $emailbody .= '<td>'.$faktura['postpostbox'].'</td>';
					$emailbody .= '</tr>';
				}
				$emailbody .= '<tr><td>Postnr.:</td><td>'.$faktura['postnr'].'</td>';
				if($faktura['altpost']) $emailbody .= '<td>'.$faktura['postpostalcode'].'</td>';
				$emailbody .= '</tr><tr><td>By:</td><td>'.$faktura['by'].'</td>';
				if($faktura['altpost']) $emailbody .= '<td>'.$faktura['postcity'].'</td>';
				$emailbody .= '</tr><tr><td>Land:</td><td>'.$countries[$faktura['land']].'</td>';
				if($faktura['altpost']) $emailbody .= '<td>'.$countries[$faktura['postcountry']].'</td>';
				$emailbody .= '</tr>';
				if($faktura['tlf2']) $emailbody .= '<tr><td>Mobil:</td><td>'.$faktura['tlf2'].'</td></tr>';
				$emailbody .= '<tr><td>Email:</td><td><a href="mailto:'.$faktura['email'].'">'.$faktura['email'].'</a></td></tr></table><p>Betaling for Deres ordre nr. '.$faktura['id'].' er nu godkendt. Deres vare vil blive afsendt hurtigst muligt. Der vil automatisk blive sendt en email med et Track &amp; Trace link hvor de kan følge pakken.<br /></p>';
				$emailbody .= '<table id="faktura" cellspacing="0"><thead><tr><td class="td1">Antal</td><td>Benævnelse</td><td class="td3 tal">á pris</td><td class="td4 tal">Total</td></tr></thead><tfoot><tr style="height:auto;min-height:auto;max-height:auto;"><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Nettobeløb</td>';

				$productslines = max(count($faktura['quantities']), count($faktura['products']), count($faktura['values']));
				
				$netto = 0;
				for($i=0;$i<$productslines;$i++) {
					$netto += $faktura['values'][$i]*$faktura['quantities'][$i];
				}

				$emailbody .= '<td class="tal">'.number_format($netto, 2, ',', '').'</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Fragt</td><td class="tal">'.number_format($faktura['fragt'], 2, ',', '').'</td></tr><tr><td>&nbsp;</td><td style="text-align:right" class="tal">'.($faktura['momssats']*100).'%</td><td class="tal">Momsbeløb</td><td class="tal">'.number_format($netto*$faktura['momssats'], 2, ',', '').'</td></tr><tr class="border"><td colspan="2">Alle beløb er i DKK</td><td style="text-align:center; font-weight:bold;">AT BETALE</td><td class="tal"><big>'.number_format($faktura['amount'], 2, ',', '').'</big></td></tr></tfoot><tbody>';
				for($i=0; $i<$productslines; $i++) {
					$emailbody .= '<tr><td class="tal">'.$faktura['quantities'][$i].'</td><td>'.$faktura['products'][$i].'</td><td class="tal">'.number_format($faktura['values'][$i], 2, ',', '').'</td><td class="tal">'.number_format($faktura['values'][$i]*$faktura['quantities'][$i], 2, ',', '').'</td></tr>';
				}
				$emailbody .= '</tbody></table>';
				if($faktura['note']) {
					$emailbody .= '<br /><strong>Notat:</strong><br /><p class="note">';	
					$emailbody .= nl2br(htmlspecialchars($faktura['note'])).'</p>';
				}
				
				if(!validemail($faktura['department'])) {
					$faktura['department'] = $GLOBALS['_config']['email'][0];
				}
				$emailbody .= '<p>Med venlig hilsen,<br /></p><p> </p><p>'.$faktura['clerk'].'<br />'.$GLOBALS['_config']['site_name'].'<br />'.$GLOBALS['_config']['address'].'<br />'.$GLOBALS['_config']['postcode'].' '.$GLOBALS['_config']['city'].'.<br />Tlf. '.$GLOBALS['_config']['phone'].'<br /><a href="mailto:'.$faktura['department'].'">'.$faktura['department'].'</a></p></body></html>';

				require_once "inc/phpMailer/class.phpmailer.php";
	
				$mail             = new PHPMailer();
				$mail->SetLanguage('dk');
				$mail->IsSMTP();
				if($GLOBALS['_config']['emailpassword'] !== false) {
					$mail->SMTPAuth   = true; // enable SMTP authentication
					$mail->Username   = $GLOBALS['_config']['email'][0];
					$mail->Password   = $GLOBALS['_config']['emailpassword'];
				} else {
					$mail->SMTPAuth   = false;
				}
				$mail->Host       = $GLOBALS['_config']['smtp'];      // sets the SMTP server
				$mail->Port       = $GLOBALS['_config']['smtpport'];                   // set the SMTP port for the server
				$mail->CharSet    = 'utf-8';
				$mail->AddReplyTo($faktura['department'], $GLOBALS['_config']['site_name']);
				$mail->From       = $faktura['department'];
				$mail->FromName   = $GLOBALS['_config']['site_name'];
				$mail->Subject    = 'Ordre '.$faktura['id'].' - Betaling gennemført';
				$mail->MsgHTML($emailbody, $_SERVER['DOCUMENT_ROOT']);
				$mail->AddAddress($faktura['email'], $GLOBALS['_config']['site_name']);
				$mail->Send();
				//Mail to customer end
				
				//Mail to Ole start
				$emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>';
				$emailbody .= 'Att: Ole - Online faktura #'.$GLOBALS['_config']['pbsfix'].$faktura['id'].' : Betaling gennemført</title><style type="text/css">#faktura td { border:1px #000 solid; border-collapse:collapse; padding:2px; }</style></head><body><p>Den '.$faktura['paydate'].' godkendte '.$faktura['navn'].' online faktura #'.$GLOBALS['_config']['pbsfix'].$faktura['id'].', Som blev opretted af '.$faktura['clerk'].'.<br />Ordren lød på følgende:';
				$emailbody .= '</p><table id="faktura" cellspacing="0"><thead><tr><td class="td1">Antal</td><td>Benævnelse</td><td class="td3 tal">á pris</td><td class="td4 tal">Total</td></tr></thead><tfoot><tr style="height:auto;min-height:auto;max-height:auto;"><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Nettobeløb</td>';
				$productslines = max(count($faktura['quantities']), count($faktura['products']), count($faktura['values']));
				
				$netto = 0;
				for($i=0;$i<$productslines;$i++) {
					$netto += $faktura['values'][$i]*$faktura['quantities'][$i];
				}

				$emailbody .= '<td class="tal">'.number_format($netto, 2, ',', '').'</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Fragt</td><td class="tal">'.number_format($faktura['fragt'], 2, ',', '').'</td></tr><tr><td>&nbsp;</td><td style="text-align:right" class="tal">'.($faktura['momssats']*100).'%</td><td class="tal">Momsbeløb</td><td class="tal">'.number_format($netto*$faktura['momssats'], 2, ',', '').'</td></tr><tr class="border"><td colspan="2">Alle beløb er i DKK</td><td style="text-align:center; font-weight:bold;">AT BETALE</td><td class="tal"><big>'.number_format($faktura['amount'], 2, ',', '').'</big></td></tr></tfoot><tbody>';

				for($i=0; $i<$productslines; $i++) {
					$emailbody .= '<tr><td class="tal">'.$faktura['quantities'][$i].'</td><td>'.$faktura['products'][$i].'</td><td class="tal">'.number_format($faktura['values'][$i], 2, ',', '').'</td><td class="tal">'.number_format($faktura['values'][$i]*$faktura['quantities'][$i], 2, ',', '').'</td></tr>';
				}
				
				$emailbody .= '</tbody></table>';
				
				if($faktura['note']) {
					$emailbody .= '<br /><strong>Notat:</strong><br /><p class="note">';	
					$emailbody .= nl2br(htmlspecialchars($faktura['note'])).'</p>';
				}
				$emailbody .= '<p>Med venlig hilsen</p><p>Computeren</p></body></html>';
				
				require_once "inc/phpMailer/class.phpmailer.php";
				
				$mail             = new PHPMailer();
				$mail->SetLanguage('dk');
				$mail->IsSMTP();
				if($GLOBALS['_config']['emailpassword'] !== false) {
					$mail->SMTPAuth   = true; // enable SMTP authentication
					$mail->Username   = $GLOBALS['_config']['email'][0];
					$mail->Password   = $GLOBALS['_config']['emailpassword'];
				} else {
					$mail->SMTPAuth   = false;
				}
				$mail->Host       = $GLOBALS['_config']['smtp'];      // sets the SMTP server
				$mail->Port       = $GLOBALS['_config']['smtpport'];              //  password
				$mail->CharSet    = 'utf-8';
				if(!validemail($faktura['department'])) {
					$faktura['department'] = $GLOBALS['_config']['email'][0];
				}
				$mail->AddReplyTo($faktura['department'], $GLOBALS['_config']['site_name']);
				$mail->From       = $faktura['department'];
				$mail->FromName   = $GLOBALS['_config']['site_name'];
				$mail->Subject    = 'Att: Ole - Online faktura #'.$GLOBALS['_config']['pbsfix'].$faktura['id'].' : Betaling gennemført';
				$mail->MsgHTML($emailbody, $_SERVER['DOCUMENT_ROOT']);
				$mail->AddAddress('mail@huntershouse.dk', 'Hunters House A/S');
				$mailtoole = $mail->Send();
				//Mail to Ole end
				
			break;
			case 1:
				$GLOBALS['generatedcontent']['text'] = 'Nægtet/Afbrudt. Betalingen er nægtet eller afbrudt.';
				$mysqli->query("UPDATE `fakturas` SET `status` = 'pbserror', `paydate` = NOW() WHERE `status` IN('new', 'locked') AND `id` = ".$id);
			break;
			case 2:
				$GLOBALS['generatedcontent']['text'] = 'I gang. Betaling venter på svar fra banken.';
			break;
			case 3:
				$GLOBALS['generatedcontent']['text'] = 'Annulleret. Kortbetalingen er makuleret af Butikken før indløsningen.';
			break;
			case 4:
				$GLOBALS['generatedcontent']['text'] = 'Påbegyndt. Betalingen er initieret af køberen.';
			break;
			case 6:
				$GLOBALS['generatedcontent']['text'] = 'Autoriseret. Kortbetaling er autoriseret; venter på bekræftelse og indløsning.';
			break;
			case 7:
				$GLOBALS['generatedcontent']['text'] = 'Indløsning mislykkedes. Kortbetalingen kunne ikke indløses.';
			break;
			case 8:
				$GLOBALS['generatedcontent']['text'] = 'Indløsning i gang. Indløsning af kortbetalingen er i øjeblikket i gang.';
			break;
			case 9:
				$GLOBALS['generatedcontent']['text'] = 'Bekræftet. Kortbetalingen er bekræftet og bliver indløst.';
			break;
			case 11:
				$GLOBALS['generatedcontent']['text'] = 'Sent to bank or Svea Ekonomi. Applies only to the Payment Method INVOICE';
			break;
		}
	}
	
	require_once "inc/phpMailer/class.phpmailer.php";
	
	//To shop
	$faktura = $mysqli->fetch_array("SELECT * FROM `fakturas` WHERE `id` = ".$id);
	$faktura = @$faktura[0];
	if(!validemail($faktura['department']))
		$faktura['department'] = $GLOBALS['_config']['email'][0];
	
	if($faktura) {
		
		$faktura['quantities'] = explode('<', $faktura['quantities']);
		$faktura['products'] = explode('<', $faktura['products']);
		$faktura['values'] = explode('<', $faktura['values']);
			
		if($faktura['premoms']) {
			foreach($faktura['values'] as $key => $value) {
				$faktura['values'][$key] = $value/1.25;
			}
		}
		
		$emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Att: '.$faktura['clerk'].' - Online faktura #'.$id.' : '.$shopSubject.'</title>
<style type="text/css">
td {
	border:1px solid #000;
	border-collapse:collapse;
}
</style>
</head>
<body>
<p>'.$faktura['navn'].'<br />
'.$faktura['adresse'].'<br />
'.$faktura['postnr'].' '.$faktura['by'].'<br />
'.$faktura['land'].'</p>
<p>'.$shopBody.'<br />
Klik <a href="'.$GLOBALS['_config']['base_url'].'/admin/faktura.php?id='.$id.'">her</a> for at åbne faktura siden.</p>
<p><a href="mailto:'.$faktura['email'].'">'.$faktura['email'].'</a><br />
	Mobil: '.$faktura['tlf2'].'<br />
	Tlf.: '.$faktura['tlf1'].'<br />
	Leverings tlf.: '.$faktura['posttlf'].'</p>';
		$emailbody .= '<table id="faktura" cellspacing="0"><thead><tr><td class="td1">Antal</td><td>Benævnelse</td><td class="td3 tal">á pris</td><td class="td4 tal">Total</td></tr></thead><tfoot><tr style="height:auto;min-height:auto;max-height:auto;"><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Nettobeløb</td>';
		
		$productslines = max(count($faktura['quantities']), count($faktura['products']), count($faktura['values']));
		
		$netto = 0;
		for($i=0;$i<$productslines;$i++) {
			$netto += $faktura['values'][$i]*$faktura['quantities'][$i];
		}
		
		$emailbody .= '<td class="tal">'.number_format($netto, 2, ',', '').'</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Fragt</td><td class="tal">'.number_format($faktura['fragt'], 2, ',', '').'</td></tr><tr><td>&nbsp;</td><td style="text-align:right" class="tal">'.($faktura['momssats']*100).'%</td><td class="tal">Momsbeløb</td><td class="tal">'.number_format($netto*$faktura['momssats'], 2, ',', '').'</td></tr><tr class="border"><td colspan="2">Alle beløb er i DKK</td><td style="text-align:center; font-weight:bold;">AT BETALE</td><td class="tal"><big>'.number_format($faktura['amount'], 2, ',', '').'</big></td></tr></tfoot><tbody>';
		for($i=0; $i<$productslines; $i++) {
			$emailbody .= '<tr><td class="tal">'.$faktura['quantities'][$i].'</td><td>'.$faktura['products'][$i].'</td><td class="tal">'.number_format($faktura['values'][$i], 2, ',', '').'</td><td class="tal">'.number_format($faktura['values'][$i]*$faktura['quantities'][$i], 2, ',', '').'</td></tr>';
		}
		$emailbody .= '</tbody></table>';
		$emailbody .= '<p>Mvh Computeren</p></body></html>';
	} else {
		$emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Online faktura #'.$id.' : eksistere ikke</title>
</head>

<body>
'.$shopBody.'<br />Status: '.$_GET['Status'].$_GET['Status_code'].
'<p>Mvh Computeren</p>
</body>
</html>
</body></html>';
	}
	
	
	if((!empty($faktura) && (!$mailtoole || $faktura['department'] != 'mail@huntershouse.dk')) || empty($faktura)) {
		$mail = new PHPMailer();
		$mail->SetLanguage('dk');
		$mail->IsSMTP();
		if($GLOBALS['_config']['emailpassword'] !== false) {
			$mail->SMTPAuth   = true; // enable SMTP authentication
			$mail->Username   = $GLOBALS['_config']['email'][0];
			$mail->Password   = $GLOBALS['_config']['emailpassword'];
		} else {
			$mail->SMTPAuth   = false;
		}
		$mail->Host       = $GLOBALS['_config']['smtp'];      // sets the SMTP server
		$mail->Port       = $GLOBALS['_config']['smtpport'];              //  password
		$mail->CharSet    = 'utf-8';
		$mail->From       = $GLOBALS['_config']['email'][0];
		$mail->FromName   = $GLOBALS['_config']['site_name'];
		$mail->Subject    = 'Att: '.$faktura['clerk'].' - Online faktura #'.$id.' : '.$shopSubject;
		$mail->MsgHTML($emailbody, $_SERVER['DOCUMENT_ROOT']);
		
		$mail->AddAddress($faktura['department'], $GLOBALS['_config']['site_name']);
		
		$mail->Send();
	}
	
} else {
	$GLOBALS['generatedcontent']['title'] = 'Betaling';
	$GLOBALS['generatedcontent']['headline'] = 'Betaling';
	
	$GLOBALS['generatedcontent']['text'] = '<form action="" method="get">
	  <table>
		<tbody>
		  <tr>
			<td>Ordre nr:</td>
			<td><input name="id" value="'.@$_GET['id'].'" /></td>
		  </tr>
		  <tr>
			<td>Kode:</td>
			<td><input name="checkid" value="'.@$_GET['checkid'].'" /></td>
		  </tr>
		</tbody>
	  </table><input type="submit" value="Fortsæt" />
	</form>';
	if(!empty($_GET['checkid']))
		$GLOBALS['generatedcontent']['text'] = 'Koden er ikke korrekt!';
}

//Output page
require_once 'theme/index.php';
?>
