<?php
/*
ini_set('display_errors', 1);
error_reporting(-1);
*/

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

chdir('../');

session_start();

/*fake basket content*/
$_SESSION['faktura']['quantities'][0] = 1;
$_SESSION['faktura']['products'][0] = 'Garmin 720 s';
$_SESSION['faktura']['values'][0] = 10499;
$_SESSION['faktura']['quantities'][1] = 1;
$_SESSION['faktura']['products'][1] = 'Amb. Eon Sport 3601 - UDSALG!';
$_SESSION['faktura']['values'][1] = 699;
/**/

//Generate default $GLOBALS['generatedcontent']
$delayprint = true;
require_once 'index.php';
$GLOBALS['generatedcontent']['datetime'] = time();

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
$GLOBALS['generatedcontent']['crumbs'][0] = array('name' => _('Payment'), 'link' => '/', 'icon' => NULL);

$GLOBALS['generatedcontent']['contenttype'] = 'page';
$GLOBALS['generatedcontent']['text'] = '';

if(!empty($_SESSION['faktura']['quantities'])) {
	$rejected = array();
		
	if(empty($_GET['step'])) {
	
		$_SESSION['faktura']['amount'] = 0;
		foreach($_SESSION['faktura']['quantities'] as $i => $quantity) {
			$_SESSION['faktura']['amount'] += $_SESSION['faktura']['values'][$i]*$quantity;
		}
		
		$GLOBALS['generatedcontent']['crumbs'] = array();
		$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => _('Order'), 'link' => '#', 'icon' => NULL);
		$GLOBALS['generatedcontent']['title'] = _('Order');
		$GLOBALS['generatedcontent']['headline'] = _('Order');
	
		
		$GLOBALS['generatedcontent']['text'] = '<form action="" methode="post"><table style="border-bottom:1px solid;margin-bottom:15px;" id="faktura" cellspacing="0">
			<thead>
				<tr>
					<td class="td1">'._('Quantity').'</td>
					<td>'._('Title').'</td>
					<td class="td3 tal">'._('unit price').'</td>
					<td class="td4 tal">'._('Total').'</td>
				</tr>
			</thead>
			<tbody>';
		foreach($_SESSION['faktura']['quantities'] as $i => $quantity) {
			$GLOBALS['generatedcontent']['text'] .= '<tr>
				<td class="tal"><input value="'.$quantity.'" name="quantity" size="3" /></td>
				<td>'.$_SESSION['faktura']['products'][$i].'</td>
				<td class="tal">'.number_format($_SESSION['faktura']['values'][$i], 2, ',', '').'</td>
				<td class="tal">'.number_format($_SESSION['faktura']['values'][$i]*$quantity, 2, ',', '').'</td>
			</tr>';
		}

		print_r($_POST['quantity']);
		
		$GLOBALS['generatedcontent']['text'] .= '</tbody></table><input style="float:right" value="'._('update').'" type="submit" /></form>';
		$GLOBALS['generatedcontent']['text'] .= '<form action="" method="get"><input style="font-weight:bold;" type="submit" value="'._('Continue').'" /></form>';

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
			$updates['altpost'] = @$_POST['altpost'] ? 1 : 0;
			$updates['posttlf'] = $_POST['posttlf'];
			$updates['postname'] = $_POST['postname'];
			$updates['postatt'] = $_POST['postatt'] != $_POST['postname'] ? $_POST['postatt'] : '';
			$updates['postaddress'] = $_POST['postaddress'];
			$updates['postaddress2'] = $_POST['postaddress2'];
			$updates['postpostbox'] = $_POST['postpostbox'];
			$updates['postpostalcode'] = $_POST['postpostalcode'];
			$updates['postcity'] = $_POST['postcity'];
			$updates['postcountry'] = $_POST['postcountry'];
			$updates['enote'] = @$_POST['enote'];
			$updates = array_map('trim', $updates);

			$_SESSION['faktura'] = array_merge($_SESSION['faktura'], $updates);
			$rejected = validate($updates);
		
			if(!count($rejected)) {
				
				header('Location: '.$GLOBALS['_config']['base_url'].'/bestilling/?id='.$_GET['id'].'&checkid='.$_GET['checkid'].'&step=2', TRUE, 303);
				exit;
			}
		} else {
			$rejected = validate($_SESSION['faktura']);
		}

		//TODO set land to DK by default
		
		//TODO add enote
		$GLOBALS['generatedcontent']['crumbs'] = array();
		$GLOBALS['generatedcontent']['crumbs'][1] = array('name' => _('Recipient'), 'link' => '#', 'icon' => NULL);
		$GLOBALS['generatedcontent']['title'] = _('Recipient');
		$GLOBALS['generatedcontent']['headline'] = _('Recipient');
		
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
			<td> '._('Phone:').'</td>
			<td colspan="2"><input name="tlf1" id="tlf1" style="width:157px" value="'.$_SESSION['faktura']['tlf1'].'" /></td>
			<td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'tlf1\').value, get_address_r1);" /></td>
		</tr>
		<tr>
			<td> '._('Mobile:').'</td>
			<td colspan="2"><input name="tlf2" id="tlf2" style="width:157px" value="'.$_SESSION['faktura']['tlf2'].'" /></td>
			<td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'tlf2\').value, get_address_r1);" /></td>
		</tr>
		<tr>
			<td>'._('Name:').'</td>
			<td colspan="2"><input name="navn" id="navn" style="width:157px" value="'.$_SESSION['faktura']['navn'].'" /></td>
			<td>';
		if(!empty($rejected['navn']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td>
		</tr>
		<tr>
			<td> '._('Name:').'</td>
			<td colspan="2"><input name="att" id="att" style="width:157px" value="'.$_SESSION['faktura']['att'].'" /></td>
			<td></td>
		</tr>
		<tr>
			<td> '._('Address:').'</td>
			<td colspan="2"><input name="adresse" id="adresse" style="width:157px" value="'.$_SESSION['faktura']['adresse'].'" /></td>
			<td>';
		if(!empty($rejected['adresse']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td>
		</tr>
		<tr>
			<td> '._('Postbox:').'</td>
			<td colspan="2"><input name="postbox" id="postbox" style="width:157px" value="'.$_SESSION['faktura']['postbox'].'" /></td>
			<td></td>
		</tr>
		<tr>
			<td> '._('Zipcode:').'</td>
			<td><input name="postnr" id="postnr" style="width:35px" value="'.$_SESSION['faktura']['postnr'].'" onblur="chnageZipCode(this.value, \'land\', \'by\')" onkeyup="chnageZipCode(this.value, \'land\', \'by\')" onchange="chnageZipCode(this.value, \'land\', \'by\')" /></td>
			<td align="right">'._('City:').'
				<input name="by" id="by" style="width:90px" value="'.$_SESSION['faktura']['by'].'" /></td>
			<td>';
		if(!empty($rejected['postnr']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		if(!empty($rejected['by']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td>
		</tr>
		<tr>
			<td> '._('Country:').'</td>
			<td colspan="2"><select name="land" id="land" style="width:157px" onblur="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onkeyup="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onchange="chnageZipCode($(\'postnr\').value, \'land\', \'by\')">';
		require_once 'inc/countries.php';
		foreach($countries as $code => $country) {
			$GLOBALS['generatedcontent']['text'] .= '<option value="'.$code.'"';
			if($_SESSION['faktura']['land'] == $code)
				$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
			$GLOBALS['generatedcontent']['text'] .= '>'.htmlspecialchars($country).'</option>';
		}
		$GLOBALS['generatedcontent']['text'] .= '</select></td>
			<td>';
		if(!empty($rejected['land']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td>
		</tr>
		<tr>
			<td> '._('E-mail:').'</td>
			<td colspan="2"><input name="email" id="email" style="width:157px" value="'.$_SESSION['faktura']['email'].'" /></td>
			<td>';
		if(!empty($rejected['email']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td>
		</tr>
		<tr>
			<td colspan="4"><input onclick="showhidealtpost(this.checked);" name="altpost" id="altpost" type="checkbox"';
		 if(!empty($_SESSION['faktura']['altpost'])) $GLOBALS['generatedcontent']['text'] .= ' checked="checked"';
		 $GLOBALS['generatedcontent']['text'] .= ' /><label for="altpost"> '._('Other delivery address').'</label></td>
		</tr>
		<tr class="altpost"';
		if(empty($_SESSION['faktura']['altpost'])) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
		$GLOBALS['generatedcontent']['text'] .= '>
			<td> '._('Phone:').'</td>
			<td colspan="2"><input name="posttlf" id="posttlf" style="width:157px" value="'.$_SESSION['faktura']['posttlf'].'" /></td>
			<td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'posttlf\').value, get_address_r2);" /></td>
		</tr>
		<tr class="altpost"';
		if(empty($_SESSION['faktura']['altpost'])) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
		$GLOBALS['generatedcontent']['text'] .= '>
			<td>'._('Name:').'</td>
			<td colspan="2"><input name="postname" id="postname" style="width:157px" value="'.$_SESSION['faktura']['postname'].'" /></td>
			<td>';
		if(!empty($rejected['postname']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td>
		</tr>
		<tr class="altpost"';
		if(empty($_SESSION['faktura']['altpost'])) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
		$GLOBALS['generatedcontent']['text'] .= '>
			<td> '._('Attn.:').'</td>
			<td colspan="2"><input name="postatt" id="postatt" style="width:157px" value="'.$_SESSION['faktura']['postatt'].'" /></td>
			<td></td>
		</tr>
		<tr class="altpost"';
		if(empty($_SESSION['faktura']['altpost'])) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
		$GLOBALS['generatedcontent']['text'] .= '>
			<td> '._('Address:').'</td>
			<td colspan="2"><input name="postaddress" id="postaddress" style="width:157px" value="'.$_SESSION['faktura']['postaddress'].'" /><br /><input name="postaddress2" id="postaddress2" style="width:157px" value="'.$_SESSION['faktura']['postaddress2'].'" /></td>
			<td>';
		if(!empty($rejected['postaddress']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td>
		</tr>
		<tr class="altpost"';
		if(empty($_SESSION['faktura']['altpost'])) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
		$GLOBALS['generatedcontent']['text'] .= '>
			<td> '._('Postbox:').'</td>
			<td colspan="2"><input name="postpostbox" id="postpostbox" style="width:157px" value="'.$_SESSION['faktura']['postpostbox'].'" /></td>
			<td></td>
		</tr>
		<tr class="altpost"';
		if(empty($_SESSION['faktura']['altpost'])) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
		$GLOBALS['generatedcontent']['text'] .= '>
			<td> '._('Zipcode:').'</td>
			<td><input name="postpostalcode" id="postpostalcode" style="width:35px" value="'.$_SESSION['faktura']['postpostalcode'].'" onblur="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onchange="chnageZipCode(this.value, \'postcountry\', \'postcity\')" /></td>
			<td align="right">'._('City:').'
				<input name="postcity" id="postcity" style="width:90px" value="'.$_SESSION['faktura']['postcity'].'" /></td>
			<td>';
		if(!empty($rejected['postpostalcode']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		if(!empty($rejected['postcity']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td>
		</tr>
		<tr class="altpost"';
		if(empty($_SESSION['faktura']['altpost'])) $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
		$GLOBALS['generatedcontent']['text'] .= '>
			<td> '._('Country:').'</td>
			<td colspan="2"><select name="postcountry" id="postcountry" style="width:157px" onblur="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onchange="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')">';
			
		require_once 'inc/countries.php';
		foreach($countries as $code => $country) {
			$GLOBALS['generatedcontent']['text'] .= '<option value="'.$code.'"';
			if($_SESSION['faktura']['postcountry'] == $code)
				$GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
			$GLOBALS['generatedcontent']['text'] .= '>'.htmlspecialchars($country).'</option>';
		}
		$GLOBALS['generatedcontent']['text'] .= '</select></td><td>';
		if(!empty($rejected['postcountry']))
			$GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
		$GLOBALS['generatedcontent']['text'] .= '</td></tr>';
		$GLOBALS['generatedcontent']['text'] .= '<tr>
			<td colspan="4"><input name="newsletter" id="newsletter" type="checkbox"';
		if(!empty($_POST['newsletter'])) $GLOBALS['generatedcontent']['text'] .= ' checked="checked"';
		$GLOBALS['generatedcontent']['text'] .= ' /><label for="newsletter"> '._('Please send me your newsletter.').'</label></td>
		</tr>';
		$GLOBALS['generatedcontent']['text'] .= '</tbody></table><input style="font-weight:bold;" type="submit" value="'._('Send order').'" /></form>';
	} elseif($_GET['step'] == 2) {
	
		require_once "inc/phpMailer/class.phpmailer.php";
			
		/*

		$_SESSION['faktura']['status'] = 'new';
		$_SESSION['faktura']['premoms'] = 1;

		$sql = "INSERT `fakturas` SET";
		foreach($_SESSION['faktura' as $key => $value)
			$sql .= " `".addcslashes($key, '`\\')."` = '".addcslashes($value, "'\\")."',";
		$sql = substr($sql, 0, -1);

		$mysqli->query($sql);
		*/
		
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
		$mail->Subject    = _('Online order');
		$mail->MsgHTML($emailbody, $_SERVER['DOCUMENT_ROOT']);
		
		//TODO allow other departments to revice orders
		$mail->AddAddress($GLOBALS['_config']['email'][0], $GLOBALS['_config']['site_name']);
	
		if($mail->Send()) {
	
			//Upload email to the sent folder via imap
			if($GLOBALS['_config']['imap']) {
				require_once $_SERVER['DOCUMENT_ROOT'].'/inc/imap.inc.php';
				$imap = new IMAPMAIL;
				$imap->open($GLOBALS['_config']['imap'], $GLOBALS['_config']['imapport']);
				$imap->login($GLOBALS['_config']['email'][0], $GLOBALS['_config']['emailpasswords'][0]);
				$imap->append_mail($GLOBALS['_config']['emailsent'], $mail->CreateHeader().$mail->CreateBody(), '\Seen');
				$imap->close();
			}
		} else {
		//TODO secure this against injects and <; in the email and name
			$mysqli->query("INSERT INTO `emails` (`subject`, `from`, `to`, `body`, `date`) VALUES ('".$mail->Subject."', '".$GLOBALS['_config']['site_name']."<".$GLOBALS['_config']['email'][0].">', '".$GLOBALS['_config']['site_name']."<".$GLOBALS['_config']['email'][0].">', '".$emailbody."', NOW());");
		}
	
	}
} else {
	$GLOBALS['generatedcontent']['title'] = _('Place order');
	$GLOBALS['generatedcontent']['headline'] = _('Place order');
	$GLOBALS['generatedcontent']['text'] = _('Ther is no content in the basket!');
}

//Output page
require_once 'theme/index.php';
?>
