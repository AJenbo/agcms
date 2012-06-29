<?php
/**
 * Pages for taking the user thew the payment process
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

/**/
ini_set('display_errors', 1);
error_reporting(-1);
/**/

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

chdir('../');

//Generate default $GLOBALS['generatedcontent']
$delayprint = true;
require_once 'index.php';
$GLOBALS['generatedcontent']['datetime'] = time();

/**
 * Generate a 5 didget code from the order id
 *
 * @param int $id Order id to generate code from
 *
 * @return string
 */
function getCheckid($id)
{
    return substr(md5($id . $GLOBALS['_config']['pbspassword']), 3, 5);
}

/**
 * Checks that all nessesery contact information has been filled out correctly
 *
 * @param array $values Keys are: email, navn, land, postbox, adresse, postnr, by,
 *                      altpost (bool), postname, postpostbox, postaddress,
 *                      postcountry, postpostalcode, postcity
 *
 * @return array Key with bool true for each faild feald
 */
function validate($values)
{
    $rejected = array();

    if (!validemail(@$values['email'])) {
        $rejected['email'] = true;
    }

    if (empty($values['navn'])) {
        $rejected['navn'] = true;
    }
    if (empty($values['land'])) {
        $rejected['land'] = true;
    }
    if (empty($values['postbox'])
        && (empty($values['adresse']) || ($values['land'] == 'DK' && !preg_match('/\s/ui', @$values['adresse'])))
    ) {
        $rejected['adresse'] = true;
    }
    if (empty($values['postnr'])) {
        $rejected['postnr'] = true;
    }
    //TODO if land = DK and postnr != by
    if (empty($values['by'])) {
        $rejected['by'] = true;
    }
    if (!$values['land']) {
        $rejected['land'] = true;
    }
    if (!empty($values['altpost'])) {
        if (empty($values['postname'])) {
            $rejected['postname'] = true;
        }
        if (empty($values['land'])) {
            $rejected['land'] = true;
        }
        if (empty($values['postpostbox'])
            && (empty($values['postaddress']) || ($values['postcountry'] == 'DK' && !preg_match('/\s/ui', $values['postaddress'])))
        ) {
            $rejected['postaddress'] = true;
        }
        if (empty($values['postpostalcode'])) {
            $rejected['postpostalcode'] = true;
        }
        //TODO if postcountry = DK and postpostalcode != postcity
        if (empty($values['postcity'])) {
            $rejected['postcity'] = true;
        }
        if (empty($values['postcountry'])) {
            $rejected['postcountry'] = true;
        }
    }
    return $rejected;
}

//Generate return page
$GLOBALS['generatedcontent']['crumbs'] = array();
if (!empty($_GET['id'])) {
    $GLOBALS['generatedcontent']['crumbs'][0] = array(
        'name' => _('Payment'),
        'link' => '/?id=' . $_GET['id'] . '&checkid=' . $_GET['checkid'],
        'icon' => null
    );
} else {
    $GLOBALS['generatedcontent']['crumbs'][0] = array(
        'name' => _('Payment'),
        'link' => '/',
        'icon' => null
    );
}
$GLOBALS['generatedcontent']['contenttype'] = 'page';
$GLOBALS['generatedcontent']['text'] = '';
$productslines = 0;

if (!empty($_GET['id']) && @$_GET['checkid'] == getCheckid($_GET['id'])) {
    $rejected = array();
    $faktura = $mysqli->fetchOne(
        "
        SELECT *
        FROM `fakturas`
        WHERE `id` = ".$_GET['id']
    );

    if ($faktura['status'] == 'new' || $faktura['status'] == 'locked') {
        $faktura['quantities'] = explode('<', $faktura['quantities']);
        $faktura['products'] = explode('<', $faktura['products']);
        $faktura['values'] = explode('<', $faktura['values']);

        if ($faktura['premoms']) {
            foreach ($faktura['values'] as $key => $value) {
                $faktura['values'][$key] = $value/1.25;
            }
        }

        $productslines = max(
            count($faktura['quantities']),
            count($faktura['products']),
            count($faktura['values'])
        );

        $netto = 0;
        for ($i = 0; $i < $productslines; $i++) {
            $netto += $faktura['values'][$i]*$faktura['quantities'][$i];
        }

        if (empty($_GET['step'])) {
            $mysqli->query(
                "
                UPDATE `fakturas`
                SET `status` = 'locked'
                WHERE `status` IN('new', 'pbserror')
                  AND `id` = " . (int) $_GET['id']
            );

            $GLOBALS['generatedcontent']['crumbs'] = array();
            $GLOBALS['generatedcontent']['crumbs'][1] = array(
                'name' => _('Order #') . $_GET['id'],
                'link' => '#',
                'icon' => null
            );
            $GLOBALS['generatedcontent']['title'] = _('Order #').$_GET['id'];
            $GLOBALS['generatedcontent']['headline'] = _('Order #').$_GET['id'];

            $GLOBALS['generatedcontent']['text'] = '<table id="faktura" cellspacing="0">
                <thead>
                    <tr>
                        <td class="td1">'._('Quantity').'</td>
                        <td>'._('Title').'</td>
                        <td class="td3 tal">'._('unit price').'</td>
                        <td class="td4 tal">'._('Total').'</td>
                    </tr>
                </thead>
                <tfoot>
                    <tr style="height:auto;min-height:auto;max-height:auto;">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="tal">'._('Net Amount').'</td>';

            $GLOBALS['generatedcontent']['text'] .= '<td class="tal">'.number_format($netto, 2, ',', '').'</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="tal">'._('Freight').'</td>
                    <td class="tal">'.number_format($faktura['fragt'], 2, ',', '').'</td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td style="text-align:right" class="tal">'.($faktura['momssats']*100).'%</td>
                    <td class="tal">'._('VAT Amount').'</td>
                    <td class="tal">'.number_format($netto*$faktura['momssats'], 2, ',', '').'</td>
                </tr>
                <tr class="border">
                    <td colspan="2">'._('All figures are in DKK').'</td>
                    <td style="text-align:center; font-weight:bold;">'._('TO PAY').'</td>
                    <td class="tal"><big>'.number_format($faktura['amount'], 2, ',', '').'</big></td>
                </tr>
            </tfoot>
            <tbody>';
            for ($i=0; $i<$productslines; $i++) {
                $GLOBALS['generatedcontent']['text'] .= '<tr>
                    <td class="tal">'.$faktura['quantities'][$i].'</td>
                    <td>'.htmlspecialchars_decode($faktura['products'][$i]).'</td>
                    <td class="tal">'.number_format($faktura['values'][$i]*(1+$faktura['momssats']), 2, ',', '').'</td>
                    <td class="tal">'.number_format($faktura['values'][$i]*(1+$faktura['momssats'])*$faktura['quantities'][$i], 2, ',', '').'</td>
                </tr>';
            }

            $GLOBALS['generatedcontent']['text'] .= '</tbody></table>';

            if ($faktura['note']) {
                $GLOBALS['generatedcontent']['text'] .= '<br /><strong>'._('Note:').'</strong><br /><p class="note">';
                $GLOBALS['generatedcontent']['text'] .= nl2br(htmlspecialchars($faktura['note'])).'</p>';
            }
            $GLOBALS['generatedcontent']['text'] .= '<form action="" method="get"><input type="hidden" name="id" value="'.$_GET['id'].'" /><input type="hidden" name="checkid" value="'.$_GET['checkid'].'" /><input type="hidden" name="step" value="1" /><input type="hidden" name="checkid" value="'.$_GET['checkid'].'" /><input style="font-weight:bold;" type="submit" value="'._('Continue').'" /></form>';

        } elseif ($_GET['step'] == 1) {
            if ($_POST) {
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

                $rejected = validate($updates);

                $sql = "UPDATE `fakturas` SET";
                foreach ($updates as $key => $value) {
                    $sql .= " `".addcslashes($key, '`\\')."` = '".addcslashes($value, "'\\")."',";
                }
                $sql = substr($sql, 0, -1);

                $sql .= 'WHERE `id` = '.$_GET['id'];

                $mysqli->query($sql);

                $faktura = array_merge($faktura, $updates);

                //TODO move down to skip address page if valid
                if (!count($rejected)) {
                    if (@$_POST['newsletter'] ? 1 : 0) {
                        include_once 'inc/countries.php';
                        $mysqli->query(
                            "
                            INSERT INTO `email` (
                                `navn`,
                                `email`,
                                `adresse`,
                                `land`,
                                `post`,
                                `by`,
                                `tlf1`,
                                `tlf2`,
                                `kartotek`,
                                `dato`,
                                `ip`
                            )
                            VALUES (
                                '" .addcslashes($updates['navn'], '`\\')."',
                                '". addcslashes($updates['email'], '`\\')."',
                                '". addcslashes($updates['adresse'], '`\\')."',
                                '". addcslashes($countries[$updates['land']], '`\\')."',
                                '". addcslashes($updates['postnr'], '`\\')."',
                                '". addcslashes($updates['by'], '`\\')."',
                                '". addcslashes($updates['tlf1'], '`\\')."',
                                '". addcslashes($updates['tlf2'], '`\\')."',
                                '1',
                                now(),
                                '". addcslashes($_SERVER['REMOTE_ADDR'], '`\\')."'
                            )
                            "
                        );
                    }

                    apache_setenv('no-gzip', 1);
                    ini_set('zlib.output_compression', '0');
                    header('Location: '.$GLOBALS['_config']['base_url'].'/betaling/?id='.$_GET['id'].'&checkid='.$_GET['checkid'].'&step=2', true, 303);
                    exit;
                }
            } else {
                $rejected = validate($faktura);
            }

            //TODO add enote
            $GLOBALS['generatedcontent']['crumbs'] = array();
            $GLOBALS['generatedcontent']['crumbs'][1] = array('name' => _('Recipient'), 'link' => '#', 'icon' => null);
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
                <td colspan="2"><input name="tlf1" id="tlf1" style="width:157px" value="'.$faktura['tlf1'].'" /></td>
                <td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'tlf1\').value, get_address_r1);" /></td>
            </tr>
            <tr>
                <td> '._('Mobile:').'</td>
                <td colspan="2"><input name="tlf2" id="tlf2" style="width:157px" value="'.$faktura['tlf2'].'" /></td>
                <td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'tlf2\').value, get_address_r1);" /></td>
            </tr>
            <tr>
                <td>'._('Name:').'</td>
                <td colspan="2"><input name="navn" id="navn" style="width:157px" value="'.$faktura['navn'].'" /></td>
                <td>';
            if (!empty($rejected['navn'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td>
            </tr>
            <tr>
                <td> '._('Name:').'</td>
                <td colspan="2"><input name="att" id="att" style="width:157px" value="'.$faktura['att'].'" /></td>
                <td></td>
            </tr>
            <tr>
                <td> '._('Address:').'</td>
                <td colspan="2"><input name="adresse" id="adresse" style="width:157px" value="'.$faktura['adresse'].'" /></td>
                <td>';
            if (!empty($rejected['adresse'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td>
            </tr>
            <tr>
                <td> '._('Postbox:').'</td>
                <td colspan="2"><input name="postbox" id="postbox" style="width:157px" value="'.$faktura['postbox'].'" /></td>
                <td></td>
            </tr>
            <tr>
                <td> '._('Zipcode:').'</td>
                <td><input name="postnr" id="postnr" style="width:35px" value="'.$faktura['postnr'].'" onblur="chnageZipCode(this.value, \'land\', \'by\')" onkeyup="chnageZipCode(this.value, \'land\', \'by\')" onchange="chnageZipCode(this.value, \'land\', \'by\')" /></td>
                <td align="right">'._('City:').'
                    <input name="by" id="by" style="width:90px" value="'.$faktura['by'].'" /></td>
                <td>';
            if (!empty($rejected['postnr'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            if (!empty($rejected['by'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td>
            </tr>
            <tr>
                <td> '._('Country:').'</td>
                <td colspan="2"><select name="land" id="land" style="width:157px" onblur="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onkeyup="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onchange="chnageZipCode($(\'postnr\').value, \'land\', \'by\')">';
            include_once 'inc/countries.php';
            foreach ($countries as $code => $country) {
                $GLOBALS['generatedcontent']['text'] .= '<option value="'.$code.'"';
                if ($faktura['land'] == $code) {
                    $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
                }
                $GLOBALS['generatedcontent']['text'] .= '>'.htmlspecialchars($country).'</option>';
            }
            $GLOBALS['generatedcontent']['text'] .= '</select></td>
                <td>';
            if (!empty($rejected['land'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td>
            </tr>
            <tr>
                <td> '._('E-mail:').'</td>
                <td colspan="2"><input name="email" id="email" style="width:157px" value="'.$faktura['email'].'" /></td>
                <td>';
            if (!empty($rejected['email'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td>
            </tr>
            <tr>
                <td colspan="4"><input onclick="showhidealtpost(this.checked);" name="altpost" id="altpost" type="checkbox"';
            if (!empty($faktura['altpost'])) {
                $GLOBALS['generatedcontent']['text'] .= ' checked="checked"';
            }
            $GLOBALS['generatedcontent']['text'] .= ' /><label for="altpost"> '._('Other delivery address').'</label></td>
            </tr>
            <tr class="altpost"';
            if (empty($faktura['altpost'])) {
                $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>
                <td> '._('Phone:').'</td>
                <td colspan="2"><input name="posttlf" id="posttlf" style="width:157px" value="'.$faktura['posttlf'].'" /></td>
                <td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'posttlf\').value, get_address_r2);" /></td>
            </tr>
            <tr class="altpost"';
            if (empty($faktura['altpost'])) {
                $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>
                <td>'._('Name:').'</td>
                <td colspan="2"><input name="postname" id="postname" style="width:157px" value="'.$faktura['postname'].'" /></td>
                <td>';
            if (!empty($rejected['postname'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td>
            </tr>
            <tr class="altpost"';
            if (empty($faktura['altpost'])) {
                $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>
                <td> '._('Attn.:').'</td>
                <td colspan="2"><input name="postatt" id="postatt" style="width:157px" value="'.$faktura['postatt'].'" /></td>
                <td></td>
            </tr>
            <tr class="altpost"';
            if (empty($faktura['altpost'])) {
                $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>
            <td> '._('Address:').'</td>
            <td colspan="2"><input name="postaddress" id="postaddress" style="width:157px" value="'.$faktura['postaddress'].'" /><br /><input name="postaddress2" id="postaddress2" style="width:157px" value="'.$faktura['postaddress2'].'" /></td>
            <td>';
            if (!empty($rejected['postaddress'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td>
            </tr>
            <tr class="altpost"';
            if (empty($faktura['altpost'])) {
                $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>
                <td> '._('Postbox:').'</td>
                <td colspan="2"><input name="postpostbox" id="postpostbox" style="width:157px" value="'.$faktura['postpostbox'].'" /></td>
                <td></td>
            </tr>
            <tr class="altpost"';
            if (empty($faktura['altpost'])) {
                $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>
                <td> '._('Zipcode:').'</td>
                <td><input name="postpostalcode" id="postpostalcode" style="width:35px" value="'.$faktura['postpostalcode'].'" onblur="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onchange="chnageZipCode(this.value, \'postcountry\', \'postcity\')" /></td>
                <td align="right">'._('City:').'
                    <input name="postcity" id="postcity" style="width:90px" value="'.$faktura['postcity'].'" /></td>
                <td>';
            if (!empty($rejected['postpostalcode'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            if (!empty($rejected['postcity'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td>
            </tr>
            <tr class="altpost"';
            if (empty($faktura['altpost'])) {
                $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>
                <td> '._('Country:').'</td>
                <td colspan="2"><select name="postcountry" id="postcountry" style="width:157px" onblur="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onchange="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')">';

            include_once 'inc/countries.php';
            foreach ($countries as $code => $country) {
                $GLOBALS['generatedcontent']['text'] .= '<option value="'.$code.'"';
                if ($faktura['postcountry'] == $code) {
                    $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
                }
                $GLOBALS['generatedcontent']['text'] .= '>'.htmlspecialchars($country).'</option>';
            }
            $GLOBALS['generatedcontent']['text'] .= '</select></td><td>';
            if (!empty($rejected['postcountry'])) {
                $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td></tr>';
            $GLOBALS['generatedcontent']['text'] .= '<tr>
                <td colspan="4"><input name="newsletter" id="newsletter" type="checkbox"';
            if (!empty($_POST['newsletter'])) {
                $GLOBALS['generatedcontent']['text'] .= ' checked="checked"';
            }
            $GLOBALS['generatedcontent']['text'] .= ' /><label for="newsletter"> '._('Please send me your newsletter.').'</label></td>
            </tr>';
            $GLOBALS['generatedcontent']['text'] .= '</tbody></table><input style="font-weight:bold;" type="submit" value="'._('Proceed to the terms of trade').'" /></form>';
        } elseif ($_GET['step'] == 2) {

            if (count(validate($faktura))) {
                apache_setenv('no-gzip', 1);
                ini_set('zlib.output_compression', '0');
                header(
                    'Location: '.$GLOBALS['_config']['base_url'].'/betaling/?id='.$_GET['id'].'&checkid='.$_GET['checkid'].'&step=1',
                    true,
                    303
                );
                exit;
            }

            $mysqli->query(
                "
                UPDATE `fakturas`
                SET `status` = 'locked'
                WHERE `status` IN('new', 'pbserror')
                AND `id` = ".$_GET['id']
            );

            $GLOBALS['generatedcontent']['crumbs'] = array();
            $GLOBALS['generatedcontent']['crumbs'][1] = array(
                'name' => _('Trade Conditions'),
                'link' => '#',
                'icon' => null
            );
            $GLOBALS['generatedcontent']['title'] = _('Trade Conditions');
            $GLOBALS['generatedcontent']['headline'] = _('Trade Conditions');

            $special = $mysqli->fetchArray(
                "
                SELECT `text`
                FROM `special`
                WHERE `id` = 3
                LIMIT 1
                "
            );
            $GLOBALS['generatedcontent']['text'] .= '<br />'.$special[0]['text'];

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
            foreach ($submit as $key => $value) {
                $GLOBALS['generatedcontent']['text'] .= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'" />';
            }
            $GLOBALS['generatedcontent']['text'] .= '<input type="hidden" name="MAC" value="'.md5(implode('', $submit).$GLOBALS['_config']['pbspassword']).'" />';
            $GLOBALS['generatedcontent']['text'] .= '<input class="web" type="submit" value="'._('I hereby agree to the terms of trade').'" /></form>';
        }
    } else {
        $GLOBALS['generatedcontent']['crumbs'] = array();
        $GLOBALS['generatedcontent']['crumbs'][1] = array(
            'name' => _('Error'),
            'link' => '#',
            'icon' => null
        );
        $GLOBALS['generatedcontent']['title'] = _('Error');
        $GLOBALS['generatedcontent']['headline'] = _('Error');
        if ($faktura['status'] == 'pbserror') {
            $GLOBALS['generatedcontent']['crumbs'][1] = array(
                'name' => _('Status'),
                'link' => '#',
                'icon' => null
            );
            $GLOBALS['generatedcontent']['title'] = _('Status');
            $GLOBALS['generatedcontent']['headline'] = _('Status');
            $GLOBALS['generatedcontent']['text'] = _('The payment was rejected at first attempt. Due to security measures at PBS, you must contact the store before you can try to pay again.');
        } elseif ($faktura['status'] == 'pbsok') {
            $GLOBALS['generatedcontent']['crumbs'][1] = array(
                'name' => _('Status'),
                'link' => '#',
                'icon' => null
            );
            $GLOBALS['generatedcontent']['title'] = _('Status');
            $GLOBALS['generatedcontent']['headline'] = _('Status');
            $GLOBALS['generatedcontent']['text'] = _('Payment received.');
        } elseif ($faktura['status'] == 'accepted') {
            $GLOBALS['generatedcontent']['crumbs'][1] = array(
                'name' => _('Status'),
                'link' => '#',
                'icon' => null
            );
            $GLOBALS['generatedcontent']['title'] = _('Status');
            $GLOBALS['generatedcontent']['headline'] = _('Status');
            $GLOBALS['generatedcontent']['text'] = _('The payment was received and the package is sent.');
            $pakker = $mysqli->fetchArray(
                "
                SELECT `STREGKODE`
                FROM `post`
                WHERE `deleted` = 0
                  AND `fakturaid` = " . (int) $faktura['id']
            );

            include_once 'inc/snoopy.class.php';
            include_once 'inc/htmlsql.class.php';

            $wsql = new htmlsql();

            foreach ($pakker as $pakke) {
                // connect to a URL
                $GLOBALS['generatedcontent']['text'] .= '<br /><br />'._('Shipment Number:').' <strong>'.$pakke['STREGKODE'].'</strong><br /><br />';
                if ($wsql->connect('url', 'http://www.postdanmark.dk/tracktrace/TrackTrace.do?i_lang=IND&i_stregkode='.$pakke['STREGKODE'])) {

                    if ($wsql->query('SELECT text FROM div WHERE $id == "pdkTable"')) {
                        // show results:
                        foreach ($wsql->fetchArray() as $row) {
                            $GLOBALS['generatedcontent']['text'] .= utf8_encode(
                                preg_replace(
                                    array(
                                        '/\\sborder=0\\scellpadding=0/',
                                        '/\\snowrap/',
                                        '/&nbsp;/'
                                    ),
                                    '',
                                    $row['text']
                                )
                            );
                        }
                    }
                }
            }
            $pakker = $mysqli->fetchArray(
                "
                SELECT `packageId`
                FROM `PNL`
                WHERE `fakturaid` = " . $faktura['id']
            );
            foreach ($pakker as $pakke) {
                $GLOBALS['generatedcontent']['text'] .= '<br /><a href="http://online.pannordic.com/pn_logistics/index_tracking_email.jsp?id='.$pakke['packageId'].'&Search=search" target="_blank">'.$pakke['packageId'].'</a>';
            }
        } elseif ($faktura['status'] == 'giro') {
            $GLOBALS['generatedcontent']['text'] = _('The payment is already received in cash.');
        } elseif ($faktura['status'] == 'cash') {
            $GLOBALS['generatedcontent']['text'] = _('The payment is already received in cash.');
        } elseif ($faktura['status'] == 'canceled') {
            $GLOBALS['generatedcontent']['text'] = _('The transaction is canceled.');
        } elseif ($faktura['status'] == 'rejected') {
            $GLOBALS['generatedcontent']['text'] = _('Payment rejected.');
        } else {
            $GLOBALS['generatedcontent']['text'] = _('An errror occured.');
        }
    }
} elseif (!empty($_GET['Customer_refno'])) {
    $id = mb_substr(
        $_GET['Customer_refno'],
        mb_strlen($GLOBALS['_config']['pbsfix'])
    );

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
    $GLOBALS['generatedcontent']['crumbs'][1] = array(
        'name' => _('Error'),
        'link' => '#',
        'icon' => null
    );
    $GLOBALS['generatedcontent']['title'] = _('Error');
    $GLOBALS['generatedcontent']['headline'] = _('Error');
    $GLOBALS['generatedcontent']['text'] = _('An unknown error occured.');

    if (!empty($_GET['Status']) && !empty($_GET['Status_code'])) {
        $shopSubject = $_GET['Status'].$_GET['Status_code'];
    } else {
        $shopSubject = _('No Status');
    }
    $shopBody = '<br />'.sprintf(_('There was an error on the payment page of online invoice #%d!'), $id).'<br />';

    $faktura = $mysqli->fetchOne("SELECT * FROM `fakturas` WHERE `id` = ".$id);

    if ($_GET['MAC'] != md5(implode('', $validate).$GLOBALS['_config']['pbspassword'])) {
        $GLOBALS['generatedcontent']['text'] = _('The communication could not be validated!');
    } elseif (!$faktura) {
        $GLOBALS['generatedcontent']['text'] = '<p>' . _('The payment does not exist in our system.') . '</p>';
        $shopBody = '<br />' . sprintf(
            _('A user tried to pay online invoice #%d, which is not in the system!'),
            $id
        ) . '<br />';
    } elseif ($faktura['status'] == 'pbserror' || $faktura['status'] == 'canceled' || $faktura['status'] == 'rejected') {
        $GLOBALS['generatedcontent']['crumbs'][1] = array('name' => _('Reciept'), 'link' => '#', 'icon' => null);
        $GLOBALS['generatedcontent']['title'] = _('Reciept');
        $GLOBALS['generatedcontent']['headline'] = _('Reciept');
        $GLOBALS['generatedcontent']['text'] = '<p>'._('This trade has been canceled or refused.').'</p>';
        $shopBody = '<br />'.sprintf(_('A customer tried to see the status page for online invoice #%d which is canceled or rejected.'), $id).'<br />';
    } elseif ($faktura['status'] != 'locked' && $faktura['status'] != 'new') {
        $GLOBALS['generatedcontent']['crumbs'][1] = array(
            'name' => _('Reciept'),
            'link' => '#',
            'icon' => null
        );
        $GLOBALS['generatedcontent']['title'] = _('Reciept');
        $GLOBALS['generatedcontent']['headline'] = _('Reciept');
        $GLOBALS['generatedcontent']['text'] = '<p>'._('Payment is registered and you ought to have received a receipt by email.').'</p>';
        $shopBody = '<br />'.sprintf(_('A customer tried to see the status page for online invoice #%d, which is already paid.'). $id).'<br />';
    } elseif (empty($_GET['Status'])) {
        //User pressed "back"
        apache_setenv('no-gzip', 1);
        ini_set('zlib.output_compression', '0');
        header('Location: '.$GLOBALS['_config']['base_url'].'/betaling/?id='.$id.'&checkid='.$_GET['checkid'].'&step=2', true, 303);
        exit;
    } elseif ($_GET['Status'] == 'E') {
        $GLOBALS['generatedcontent']['title'] = _('Error #').$_GET['Status_code'];
        $GLOBALS['generatedcontent']['headline'] = _('Error #').$_GET['Status_code'];
        $GLOBALS['generatedcontent']['text'] = _(
            'An error occurred during the payment.<br />
Error number:'
        );
        $GLOBALS['generatedcontent']['text'] .= ' ' .$_GET['Status_code'];
        $mysqli->query(
            "
            UPDATE `fakturas`
            SET `status` = 'pbserror', `paydate` = NOW()
            WHERE `status` IN('new', 'locked')
              AND `id` = " . $id
        );
        switch ($_GET['Status_code']) {
            //Theas has been seen IRL
        case 12:
            $GLOBALS['generatedcontent']['text'] = _('The communication could not be validated!');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Data was manipulated.');
            $shopBody = '<br />'.sprintf(_('Communication could not be validated as %s was about to pay!'), $faktura['navn']).'<br />';
            break;
        case 18:
            $GLOBALS['generatedcontent']['text'] = _('Payment page is not responding.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment page is not responding.');
            $shopBody = '<br />'.sprintf(_('Payment page did not answer when %s was about to pay!'), $faktura['navn']).'<br />';
            break;
        case 19:
            $GLOBALS['generatedcontent']['text'] = _('Payment was refused by the bank, the card has expired.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment was refused by the bank, the card has expired.');
            $shopBody = '<br />'.sprintf(_('Payment was refused by the bank, %s\'s card has expired!'), $faktura['navn']).'<br />';
            break;
        case 20:
            $GLOBALS['generatedcontent']['text'] = _('Payment was refused by the bank, contact your bank.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment was refused by the bank, contact your bank.');
            $shopBody = '<br />'.sprintf(_('Payment was refused by the bank, %s must contact his or her bank.'), $faktura['navn']).'<br />';
            break;
        case 26:
            $GLOBALS['generatedcontent']['text'] = _('Suspected fraud.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Suspected fraud.');
            $shopBody = '<br />'._('Suspected fraud.').'<br />';
            break;
        case 28:
            $GLOBALS['generatedcontent']['text'] = _('Denied due to too many payment attempts.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment was refused.');
            $shopBody = $_GET['Status'].$_GET['Status_code'].' '._('Denied due to too many payment attempts.');
            break;
        case 56:
            $GLOBALS['generatedcontent']['text'] = _('The payment was refused because the customer has already attempted to pay.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment was refused.');
            $shopBody = '<br />'._('The payment was refused because the customer has already attempted to pay.').'<br />';
            break;
        //Theas havent
        case 3:
            $GLOBALS['generatedcontent']['text'] = _('An obligatory field is lacking or is incorrectly formatted.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment was refused.');
            $shopBody = '<br />'._('An obligatory field is lacking or is incorrectly formatted.').'<br />';
            break;
        case 4:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect Merchant ID.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect Merchant ID.');
            $shopBody = '<br />'._('Incorrect Merchant ID.').'<br />';
            break;
        case 5:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect amount.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect amount.');
            $shopBody = '<br />'._('Incorrect amount.').'<br />';
            break;
        case 6:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect amount.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect amount.');
            $shopBody = '<br />'._('Incorrect amount.').'<br />';
            break;
        case 11:
            $GLOBALS['generatedcontent']['text'] = _('Was not possible to contact bank.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Was not possible to contact bank.');
            $shopBody = '<br />'._('Was not possible to contact bank.').'<br />';
            break;
        case 13:
            $GLOBALS['generatedcontent']['text'] = _('Amount too large.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Amount too large.');
            $shopBody = '<br />'._('Amount too large.').'<br />';
            break;
        case 14:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect date/time format.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect date/time format.');
            $shopBody = '<br />'._('Incorrect date/time format.').'<br />';
            break;
        case 15:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect purchase date.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect purchase date.');
            $shopBody = '<br />'._('Incorrect purchase date.').'<br />';
            break;
        case 17:
            $GLOBALS['generatedcontent']['text'] = _('Payment Type not configured for the Merchant.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment Type not configured for the Merchant.');
            $shopBody = '<br />'._('Payment Type not configured for the Merchant.').'<br />';
            break;
        case 18:
            $GLOBALS['generatedcontent']['text'] = _('Card Payment: Error upon Authorisation or no contact with the bank.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Card Payment: Error upon Authorisation or no contact with the bank.');
            $shopBody = '<br />'._('Card Payment: Error upon Authorisation or no contact with the bank.').'<br />';
            break;
        case 19:
            $GLOBALS['generatedcontent']['text'] = _(
                'Card Payment: Purchase denied at the bank (the card\'s validity
period has expired), contact the bank.
'
            );
            $shopSubject = $_GET['Status'] .$_GET['Status_code'] .' ' ._(
                'Card Payment: Purchase denied at the bank (the card\'s validity
period has expired), contact the bank.
'
            );
            $shopBody = '<br />' ._(
                'Card Payment: Purchase denied at the bank (the card\'s validity
period has expired), contact the bank.
'
            ) .'<br />';
            break;
        case 21:
            $GLOBALS['generatedcontent']['text'] = _('Country for card-issuing bank is not permitted.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Country for card-issuing bank is not permitted.');
            $shopBody = '<br />'._('Country for card-issuing bank is not permitted.').'<br />';
            break;
        case 22:
            $GLOBALS['generatedcontent']['text'] = _('The risk assessment value for the transaction exceeds the permissible value.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('The risk assessment value for the transaction exceeds the permissible value.');
            $shopBody = '<br />'._('The risk assessment value for the transaction exceeds the permissible value.').'<br />';
            break;
        case 23:
            $GLOBALS['generatedcontent']['text'] = _(
                'Card Payment: Card saved/inactivated at the card-issuing bank. E.g.
lost, stolen, too many incorrect PIN entry attempts, etc.'
            );
            $shopSubject = $_GET['Status'] .$_GET['Status_code'] .' ' ._(
                'Card Payment: Card saved/inactivated at the card-issuing bank. E.g.
lost, stolen, too many incorrect PIN entry attempts, etc.'
            );
            $shopBody = '<br />' ._(
                'Card Payment: Card saved/inactivated at the card-issuing bank. E.g.
lost, stolen, too many incorrect PIN entry attempts, etc.'
            ) .'<br />';
            break;
        case 24:
            $GLOBALS['generatedcontent']['text'] = _('Error Request_type in Order Administration call.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Error Request_type in Order Administration call.');
            $shopBody = '<br />'._('Error Request_type in Order Administration call.').'<br />';
            break;
        case 25:
            $GLOBALS['generatedcontent']['text'] = _('Amount too high: insufficient balance, card-issuing bank will not allow this amount on this card.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Amount too high: insufficient balance, card-issuing bank will not allow this amount on this card.');
            $shopBody = '<br />'._('Amount too high: insufficient balance, card-issuing bank will not allow this amount on this card.').'<br />';
            break;
        case 27:
            $GLOBALS['generatedcontent']['text'] = _('Purchase amount must be greater than zero.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Purchase amount must be greater than zero.');
            $shopBody = '<br />'._('Purchase amount must be greater than zero.').'<br />';
            break;
        case 30:
            $GLOBALS['generatedcontent']['text'] = _('Denied due to time-out, no response from bank');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Denied due to time-out, no response from bank');
            $shopBody = '<br />'._('Denied due to time-out, no response from bank').'<br />';
            break;
        case 31:
            $GLOBALS['generatedcontent']['text'] = _('Purchase terminated (by the purchaser).');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Purchase terminated (by the purchaser).');
            $shopBody = '<br />'._('Purchase terminated (by the purchaser).').'<br />';
            break;
        case 32:
            $GLOBALS['generatedcontent']['text'] = _(
                'Error in order, transaction already registered and paid. This
Customer_refno is already registered on another transaction.'
            );
            $shopSubject = $_GET['Status'] .$_GET['Status_code'] .' ' ._(
                'Error in order, transaction already registered and paid. This
Customer_refno is already registered on another transaction.'
            );
            $shopBody = '<br />' ._(
                'Error in order, transaction already registered and paid. This
Customer_refno is already registered on another transaction.'
            ) .'<br />';
            break;
        case 33:
            $GLOBALS['generatedcontent']['text'] = _(
                'eKöp: Technical error in communication with Postgirot/Nordea.
Please try again later.'
            );
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._(
                'eKöp: Technical error in communication with Postgirot/Nordea.
Please try again later.'
            );
            $shopBody = '<br />' ._(
                'eKöp: Technical error in communication with Postgirot/Nordea.
Please try again later.'
            ) .'<br />';
            break;
        case 34:
            $GLOBALS['generatedcontent']['text'] = _('Provided recipient account incorrect.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Provided recipient account incorrect.');
            $shopBody = '<br />'._('Provided recipient account incorrect.').'<br />';
            break;
        case 35:
            $GLOBALS['generatedcontent']['text'] = _('Provided sending account incorrect.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Provided sending account incorrect.');
            $shopBody = '<br />'._('Provided sending account incorrect.').'<br />';
            break;
        case 36:
            $GLOBALS['generatedcontent']['text'] = _('eKöp: Provided sending account temporarily frozen.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('eKöp: Provided sending account temporarily frozen.');
            $shopBody = '<br />'._('eKöp: Provided sending account temporarily frozen.').'<br />';
            break;
        case 39:
            $GLOBALS['generatedcontent']['text'] = _('eKöp: Provided certificate incorrect.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('eKöp: Provided certificate incorrect.');
            $shopBody = '<br />'._('eKöp: Provided certificate incorrect.').'<br />';
            break;
        case 40:
            $GLOBALS['generatedcontent']['text'] = _('eKöp: Account not connected to the service.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('eKöp: Account not connected to the service.');
            $shopBody = '<br />'._('eKöp: Account not connected to the service.').'<br />';
            break;
        case 41:
            $GLOBALS['generatedcontent']['text'] = _('Payment Selection Page/Card Payment Page: Merchant not connected to the service.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment Selection Page/Card Payment Page: Merchant not connected to the service.');
            $shopBody = '<br />'._('Payment Selection Page/Card Payment Page: Merchant not connected to the service.').'<br />';
            break;
        case 42:
            $GLOBALS['generatedcontent']['text'] = _('Bad Auth_null. Should be YES or NO.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Bad Auth_null. Should be YES or NO.');
            $shopBody = '<br />'._('Bad Auth_null. Should be YES or NO.').'<br />';
            break;
        case 43:
            $GLOBALS['generatedcontent']['text'] = _('Bad Capture_now. Should be YES or NO.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Bad Capture_now. Should be YES or NO.');
            $shopBody = '<br />'._('Bad Capture_now. Should be YES or NO.').'<br />';
            break;
        case 45:
            $GLOBALS['generatedcontent']['text'] = _('For Order Administration: The status of the transaction does not permit the operation.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('For Order Administration: The status of the transaction does not permit the operation.');
            $shopBody = '<br />'._('For Order Administration: The status of the transaction does not permit the operation.').'<br />';
            break;
        case 48:
            $GLOBALS['generatedcontent']['text'] = _('For Order Administration: The transaction does not exist.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('For Order Administration: The transaction does not exist.');
            $shopBody = '<br />'._('For Order Administration: The transaction does not exist.').'<br />';
            break;
        case 50:
            $GLOBALS['generatedcontent']['text'] = _(
                'Card Payment: Merchant not configured for currency/card type combination... Card type not uploaded, incorrect card type
or card-issuing bank declines the card.'
            );
            $shopSubject = $_GET['Status'] .$_GET['Status_code'] .' ' ._(
                'Card Payment: Merchant not configured for currency/card type combination... Card type not uploaded, incorrect card type
or card-issuing bank declines the card.'
            );
            $shopBody = '<br />' ._(
                'Card Payment: Merchant not configured for currency/card type combination... Card type not uploaded, incorrect card type
or card-issuing bank declines the card.'
            ) .'<br />';
            break;
        case 54:
            $GLOBALS['generatedcontent']['text'] = _('Bad Track2');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Bad Track2');
            $shopBody = '<br />'._('Bad Track2').'<br />';
            break;
        case 55:
            $GLOBALS['generatedcontent']['text'] = _('Bad MOTO method. Should be PHONE or MAIL');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Bad MOTO method. Should be PHONE or MAIL');
            $shopBody = '<br />'._('Bad MOTO method. Should be PHONE or MAIL').'<br />';
            break;
        case 57:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect Customer_refno');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect Customer_refno');
            $shopBody = '<br />'._('Incorrect Customer_refno').'<br />';
            break;
        case 58:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect Version, incorrect format on the version parameters (should be Version=2).');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect Version, incorrect format on the version parameters (should be Version=2).');
            $shopBody = '<br />' ._(
                'Incorrect Version, incorrect format on the version parameters
(should be Version=2).'
            ) .'<br />';
            break;
        case 65:
            $GLOBALS['generatedcontent']['text'] = _('Transaction already registered and awaiting a response from the bank.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Transaction already registered and awaiting a response from the bank.');
            $shopBody = '<br />'._('Transaction already registered and awaiting a response from the bank.').'<br />';
            break;
        case 67:
            $GLOBALS['generatedcontent']['text'] = _('Crediting could not be carried out.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Crediting could not be carried out.');
            $shopBody = '<br />'._('Crediting could not be carried out.').'<br />';
            break;
        case 69:
            $GLOBALS['generatedcontent']['text'] = _('Technical error, Auriga ePayment');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Technical error, Auriga ePayment');
            $shopBody = '<br />'._('Technical error, Auriga ePayment').'<br />';
            break;
        case 70:
            $GLOBALS['generatedcontent']['text'] = _('For Order Administration: The function is not supported.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('For Order Administration: The function is not supported.');
            $shopBody = '<br />'._('For Order Administration: The function is not supported.').'<br />';
            break;
        case 71:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect format or size on the Response URL.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect format or size on the Response URL.');
            $shopBody = '<br />'._('Incorrect format or size on the Response URL.').'<br />';
            break;
        case 72:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect Currency code.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect Currency code.');
            $shopBody = '<br />'._('Incorrect Currency code.').'<br />';
            break;
        case 73:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect Language Code.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect Language Code.');
            $shopBody = '<br />'._('Incorrect Language Code.').'<br />';
            break;
        case 75:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect Comments.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect Comments.');
            $shopBody = '<br />'._('Incorrect Comments.').'<br />';
            break;
        case 76:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect goods description.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Incorrect goods description.');
            $shopBody = '<br />'._('Incorrect goods description.').'<br />';
            break;
        case 77:
            $GLOBALS['generatedcontent']['text'] = _('Customer_refno does not match the Transaction_id.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Customer_refno does not match the Transaction_id.');
            $shopBody = '<br />'._('Customer_refno does not match the Transaction_id.').'<br />';
            break;
        case 78:
            $GLOBALS['generatedcontent']['text'] = _('Card Payment: Authorisation Reversal could not be carried out.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Card Payment: Authorisation Reversal could not be carried out.');
            $shopBody = '<br />'._('Card Payment: Authorisation Reversal could not be carried out.').'<br />';
            break;
        case 79:
            $GLOBALS['generatedcontent']['text'] = _('Card Payment: Acquiring could not be carried out (capture).');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Card Payment: Acquiring could not be carried out (capture).');
            $shopBody = '<br />'._('Card Payment: Acquiring could not be carried out (capture).').'<br />';
            break;
        case 81:
            $GLOBALS['generatedcontent']['text'] = _('Card Payment: Unsuccessful 3-D Secure identification (for Verified by Visa or SecureCode). Card Authorisation is not carried out.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Card Payment: Unsuccessful 3-D Secure identification (for Verified by Visa or SecureCode). Card Authorisation is not carried out.');
            $shopBody = '<br />'._('Card Payment: Unsuccessful 3-D Secure identification (for Verified by Visa or SecureCode). Card Authorisation is not carried out.').'<br />';
            break;
        case 82:
            $GLOBALS['generatedcontent']['text'] = _('Card Payment: 3-D Secure identification denied due to timeout (for Verified by Visa or SecureCode). Occurs when the cardholder cannot identify themselves within approximately 5 minutes of the start of the transaction. Card Authorisation is not carried out.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Card Payment: 3-D Secure identification denied due to timeout (for Verified by Visa or SecureCode). Occurs when the cardholder cannot identify themselves within approximately 5 minutes of the start of the transaction. Card Authorisation is not carried out.');
            $shopBody = '<br />'._('Card Payment: 3-D Secure identification denied due to timeout (for Verified by Visa or SecureCode). Occurs when the cardholder cannot identify themselves within approximately 5 minutes of the start of the transaction. Card Authorisation is not carried out.').'<br />';
            break;
        case 90:
            $GLOBALS['generatedcontent']['text'] = _('Subscription has ended.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Subscription has ended.');
            $shopBody = '<br />'._('Subscription has ended.').'<br />';
            break;
        case 91:
            $GLOBALS['generatedcontent']['text'] = _('Subscription not found.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Subscription not found.');
            $shopBody = '<br />'._('Subscription not found.').'<br />';
            break;
        case 92:
            $GLOBALS['generatedcontent']['text'] = _('Subscription not captured, does not have Paid status.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Subscription not captured, does not have Paid status.');
            $shopBody = '<br />' ._(
                'Subscription not captured, does not have Paid
status.'
            ) .'<br />';
            break;
        case 93:
            $GLOBALS['generatedcontent']['text'] = _('Payment Selection Page: Incorrect format or size for Cancel URL.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Payment Selection Page: Incorrect format or size for Cancel URL.');
            $shopBody = '<br />'._('Payment Selection Page: Incorrect format or size for Cancel URL.').'<br />';
            break;
        case 95:
            $GLOBALS['generatedcontent']['text'] = _('Invoice purchase: Incorrect OCR number, e.g. incorrect control digit.');
            $shopSubject = $_GET['Status'].$_GET['Status_code'].' '._('Invoice purchase: Incorrect OCR number, e.g. incorrect control digit.');
            $shopBody = '<br />'._('Invoice purchase: Incorrect OCR number, e.g. incorrect control digit.').'<br />';
            break;
        case 96:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect length for invoice parameters.');
            $shopSubject = $_GET['Status'] . $_GET['Status_code'] . ' ' . _('Incorrect length for invoice parameters.');
            $shopBody = '<br />' . _('Incorrect length for invoice parameters.')
            . '<br />';
            break;
        case 97:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect guarantee parameter.');
            $shopSubject = $_GET['Status'] . $_GET['Status_code'] . ' '
            . _('Incorrect guarantee parameter.');
            $shopBody = '<br />' . _('Incorrect guarantee parameter.') . '<br />';
            break;
        case 98:
            $GLOBALS['generatedcontent']['text'] = _('Incorrect postal code.');
            $shopSubject = $_GET['Status'] . $_GET['Status_code'] . ' '
            . _('Incorrect postal code.');
            $shopBody = '<br />' . _('Incorrect postal code.') . '<br />';
            break;
        case 110:
            $GLOBALS['generatedcontent']['text'] = _('Invoice purchase: Invalid personal identity number or organisation number.');
            $shopSubject = $_GET['Status'] . $_GET['Status_code'] . ' '
            . _('Invoice purchase: Invalid personal identity number or organisation number.');
            $shopBody = '<br />' . _('Invoice purchase: Invalid personal identity number or organisation number.') . '<br />';
            break;
        }

        //TODO email error to us
        //TODO add better description for errors
    } elseif ($_GET['Status'] == 'A') {
        $GLOBALS['generatedcontent']['crumbs'][1] = array(
            'name' => _('Reciept'),
            'link' => '#',
            'icon' => null
        );
        $GLOBALS['generatedcontent']['title'] = _('Reciept');
        $GLOBALS['generatedcontent']['headline'] = _('Reciept');
        switch($_GET['Status_code']) {
        case 0:
            $mysqli->query(
                "
                UPDATE `fakturas`
                SET `cardtype` = '" . $_GET['Card_type'] . "',
                    `status` = 'pbsok',
                    `paydate` = NOW()
                WHERE `status` IN('new', 'locked', 'pbserror')
                  AND `id` = " . $id
            );

            $faktura = $mysqli->fetchOne(
                "
                SELECT *
                FROM `fakturas`
                WHERE `id` = " . $id
            );

            $GLOBALS['generatedcontent']['text'] = _(
                '<p style="text-align:center;"><img src="images/ok.png" alt="" /></p>

<p>Payment is now accepted. We will send your goods by mail as soon as possible.</p>

<p>A copy of your order is sent to your email.</p>'
            );

            $faktura['quantities'] = explode('<', $faktura['quantities']);
            $faktura['products'] = explode('<', $faktura['products']);
            $faktura['values'] = explode('<', $faktura['values']);

            if ($faktura['premoms']) {
                foreach ($faktura['values'] as $key => $value) {
                    $faktura['values'][$key] = $value/1.25;
                }
            }

            $shopSubject = _('Payment complete');
            $shopBody = _(
                'The customer has approved the payment and the following order must be shipped to the customer.<br />
<br />
Remember to \'expedite\' the payment when the product is sent (The payment is first transferred from the customer\'s account once we hit \'Expedite\').'
            ) .'<br />';

            include_once 'inc/countries.php';
            $withTax = $faktura['amount'] - $faktura['fragt'];
            $tax = $withTax * (1 - (1 / (1 + $faktura['momssats'])));

            $GLOBALS['generatedcontent']['track'] = ' pageTracker._addTrans("'
            . $faktura['id'] . '", "", "' . $faktura['amount'] . '", "'
            . $tax . '", "' . $faktura['fragt'] . '", "' . $faktura['by']
            . '", "", "' . $countries[$faktura['land']] . '");';
            foreach ($faktura['products'] as $key => $product) {
                $GLOBALS['generatedcontent']['track'] .= ' pageTracker._addItem("'
                . $faktura['id'] . '", "' . $faktura['id'] . $key . '", "' . $product
                . '", "", "'
                . ($faktura['values'][$key] * (1 + $faktura['momssats'])) . '", "'
                . $faktura['quantities'][$key] . '");';
            }
            $GLOBALS['generatedcontent']['track'] .= ' pageTracker._trackTrans(); ';

            //Mail to customer start
            $emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>';
            $emailbody .= sprintf(_('Ordre %d - Payment complete'), $faktura['id']);
            $emailbody .= '</title><style type="text/css">
#faktura td { border:1px #000 solid; border-collapse:collapse; padding:2px; }
</style></head><body>';

            //Generate the reseaving address
            $emailbody_address = '';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>' . _('Delivery address:') . '</td>';
            }
            $emailbody_address .= '</tr><tr><td>' . _('Name:') . '</td><td>'
            . $faktura['navn'] . '</td>';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>' . $faktura['postname'] . '</td>';
            }
            $emailbody_address .= '</tr>';
            if ($faktura['tlf1'] || ($faktura['altpost'] && $faktura['posttlf'])) {
                $emailbody_address .= '<tr><td>' . _('Phone:') . '</td><td>'
                . $faktura['tlf1'] . '</td>';
                if ($faktura['altpost']) {
                    $emailbody_address .= '<td>' . $faktura['posttlf'] . '</td>';
                }
                $emailbody_address .= '</tr>';
            }
            if ($faktura['att'] || ($faktura['altpost'] && $faktura['postatt'])) {
                $emailbody_address .= '<tr><td>' . _('Attn.:') . '</td><td>'
                . $faktura['att'] . '</td>';
                if ($faktura['altpost']) {
                    $emailbody_address .= '<td>'.$faktura['postatt'].'</td>';
                }
                $emailbody_address .= '</tr>';
            }
            if ($faktura['adresse']
                || ($faktura['adresse'] && ($faktura['postaddress'] || $faktura['postaddress2']))
            ) {
                $emailbody_address .= '<tr><td>' . _('Address:') . '</td><td>'
                . $faktura['adresse'] . '</td>';
                if ($faktura['altpost']) {
                    $emailbody_address .= '<td>' . $faktura['postaddress'] . '<br />'
                    . $faktura['postaddress2'] . '</td>';
                }
                $emailbody_address .= '</tr>';
            }
            if ($faktura['postbox']
                || ($faktura['altpost'] && $faktura['postpostbox'])
            ) {
                $emailbody_address .= '<tr><td>' . _('Postbox:') . '</td><td>'
                . $faktura['postbox'] . '</td>';
                if ($faktura['altpost']) {
                    $emailbody_address .= '<td>' . $faktura['postpostbox'] . '</td>';
                }
                $emailbody_address .= '</tr>';
            }

            $emailbody_address .= '<tr><td>' . _('Zipcode:') . '</td><td>'
            . $faktura['postnr'] . '</td>';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>' . $faktura['postpostalcode'] . '</td>';
            }
            $emailbody_address .= '</tr><tr><td>' . _('City:') . '</td><td>'
            . $faktura['by'] . '</td>';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>' . $faktura['postcity'] . '</td>';
            }
            $emailbody_address .= '</tr><tr><td>' . _('Country:') . '</td><td>'
            . $countries[$faktura['land']] . '</td>';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>' . $countries[$faktura['postcountry']]
                . '</td>';
            }
            if ($faktura['tlf2']) {
                $emailbody_address .= '</tr><tr><td>' . _('Mobile:') . '</td><td>'
                . $faktura['tlf2'].'</td>';
            }
            $netto = 0;
            for ($i = 0; $i < $productslines; $i++) {
                $netto += $faktura['values'][$i] * $faktura['quantities'][$i];
            }

            $productslines = max(
                count($faktura['quantities']),
                count($faktura['products']),
                count($faktura['values'])
            );

            $emailbody_tablerows = '';
            for ($i=0; $i<$productslines; $i++) {
                $plusTax = $faktura['values'][$i] * (1 + $faktura['momssats']);
                $emailbody_tablerows .= '<tr><td class="tal">'
                . $faktura['quantities'][$i] . '</td><td>'
                . htmlspecialchars_decode($faktura['products'][$i])
                . '</td><td class="tal">'
                . number_format($plusTax, 2, ',', '') . '</td><td class="tal">'
                . number_format($plusTax * $faktura['quantities'][$i], 2, ',', '')
                . '</td></tr>';
            }

            $emailbody_nore = '';
            if ($faktura['note']) {
                $emailbody_nore = '<br /><strong>' . _('Note:')
                . '</strong><br /><p class="note">';
                $note = htmlspecialchars(
                    $faktura['note'],
                    ENT_COMPAT | ENT_XHTML,
                    'UTF-8'
                );
                $emailbody_nore .= nl2br($note) . '</p>';
            }

            if (!validemail($faktura['department'])) {
                $faktura['department'] = $GLOBALS['_config']['email'][0];
            }

            //generate the actual email content
            $emailbody .= sprintf(
                _(
                    '<p>Date: %s<br />
</p>
<table><tr><td></td><td>customer:</td>%s</tr>
<tr><td>Email:</td><td><a href="mailto:%s">%s</a></td></tr></table>
<p>Payment for your order no. %s is now approved. Your product will be shipped as soon as possible. There will automatically be sent an email with a Track &amp; Trace link where they can follow the package.<br />
</p>
<table id="faktura" cellspacing="0"><thead><tr><td class="td1">Number</td><td>Quantity</td><td>Title</td><td class="td3 tal">unit price</td><td class="td4 tal">Total</td></tr></thead><tfoot>
<tr style="height:auto;min-height:auto;max-height:auto;"><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Net Amount</td><td class="tal">%s</td></tr>
<tr><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Freight</td><td class="tal">%s</td></tr>
<tr><td>&nbsp;</td><td style="text-align:right" class="tal">%d%%</td><td class="tal">Vat Amount</td><td class="tal">%s</td></tr>
<tr class="border"><td colspan="2">All figures are in DKK</td><td style="text-align:center; font-weight:bold;">TO PAY </td><td class="tal"><big>%s</big></td></tr></tfoot>
<tbody>%s</tbody></table>%s
<p>Sincerely the computer<br />
</p>

<p>%s<br />
%s<br />
%s<br />
%s %s.<br />
Tel. %s<br />
<a href="mailto:%s">%s</a></p>'
                ),
                $faktura['paydate'],
                $emailbody_address,
                $faktura['email'],
                $faktura['email'],
                $faktura['id'],
                number_format($netto, 2, ',', ''),
                number_format($faktura['fragt'], 2, ',', ''),
                $faktura['momssats']*100,
                number_format($netto*$faktura['momssats'], 2, ',', ''),
                number_format($faktura['amount'], 2, ',', ''),
                $emailbody_tablerows,
                $emailbody_nore,
                $faktura['clerk'],
                $GLOBALS['_config']['site_name'],
                $GLOBALS['_config']['address'],
                $GLOBALS['_config']['postcode'],
                $GLOBALS['_config']['city'],
                $GLOBALS['_config']['phone'],
                $faktura['department'],
                $faktura['department']
            );

            $emailbody .= '</body></html>';

            include_once "inc/phpMailer/class.phpmailer.php";

            $mail             = new PHPMailer();
            $mail->SetLanguage('dk');
            $mail->IsSMTP();
            if ($GLOBALS['_config']['emailpassword'] !== false) {
                $mail->SMTPAuth   = true; // enable SMTP authentication
                $mail->Username   = $GLOBALS['_config']['email'][0];
                $mail->Password   = $GLOBALS['_config']['emailpassword'];
            } else {
                $mail->SMTPAuth   = false;
            }
            $mail->Host       = $GLOBALS['_config']['smtp'];
            $mail->Port       = $GLOBALS['_config']['smtpport'];
            $mail->CharSet    = 'utf-8';
            $mail->AddReplyTo(
                $faktura['department'],
                $GLOBALS['_config']['site_name']
            );
            $mail->From       = $faktura['department'];
            $mail->FromName   = $GLOBALS['_config']['site_name'];
            $subject = _('Order #%d - payment completed');
            $mail->Subject    = sprintf($subject, $faktura['id']);
            $mail->MsgHTML($emailbody, $_SERVER['DOCUMENT_ROOT']);
            $mail->AddAddress($faktura['email'], $GLOBALS['_config']['site_name']);
            if ($mail->Send()) {
                //Upload email to the sent folder via imap
                if ($GLOBALS['_config']['imap']) {
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/inc/imap.php';
                    $emailnr = array_search(
                        $faktura['department'],
                        $GLOBALS['_config']['email']
                    );
                    $imap = new IMAP(
                        $faktura['department'],
                        $GLOBALS['_config']['emailpasswords'][$emailnr ? $emailnr : 0],
                        $GLOBALS['_config']['imap'],
                        $GLOBALS['_config']['imapport']
                    );
                    $imap->append(
                        $GLOBALS['_config']['emailsent'],
                        $mail->CreateHeader() . $mail->CreateBody(),
                        '\Seen'
                    );
                    unset($imap);
                }
            } else {
                //TODO secure this against injects and <; in the email and name
                $mysqli->query(
                    "
                    INSERT INTO `emails` (
                        `subject`,
                        `from`,
                        `to`,
                        `body`,
                        `date`
                    )
                    VALUES (
                        'Ordre " . $faktura['id'] . " - " . _('Payment complete') . "',
                        '" . $GLOBALS['_config']['site_name'] . "<" . $faktura['department'] . ">',
                        '" . $GLOBALS['_config']['site_name'] . "<" . $faktura['email'] . ">',
                        '" . $emailbody . "',
                        NOW()
                    )
                    "
                );
            }
            //Mail to customer end
            break;
        case 1:
            $GLOBALS['generatedcontent']['text'] = _('Denied/interrupted. The payment was denied or interrupted.');
            $mysqli->query(
                "
                UPDATE `fakturas`
                SET `status` = 'pbserror',
                    `paydate` = NOW()
                WHERE `status` IN('new', 'locked')
                  AND `id` = " . $id
            );
            break;
        case 2:
            $GLOBALS['generatedcontent']['text'] = _('Ongoing. Payment awaiting an response from the bank.');
            break;
        case 3:
            $GLOBALS['generatedcontent']['text'] = _('Canceled. The card payment is shredded by the store before cash in.');
            break;
        case 4:
            $GLOBALS['generatedcontent']['text'] = _('Commenced. Payment is initiated by the customer.');
            break;
        case 6:
            $GLOBALS['generatedcontent']['text'] = _('Authorized. Card payment is authorized; awaiting confirmation and cash in.');
            break;
        case 7:
            $GLOBALS['generatedcontent']['text'] = _('Cash in failed. Card payments can not be cashed in.');
            break;
        case 8:
            $GLOBALS['generatedcontent']['text'] = _('Cash-in in progress. Redeeming card payment is currently underway.');
            break;
        case 9:
            $GLOBALS['generatedcontent']['text'] = _('Confirmed. The card payments is confirmed and will be cashed in.');
            break;
        case 11:
            $GLOBALS['generatedcontent']['text'] = _('Sent to bank or Svea Ekonomi. Applies only to the Payment Method INVOICE');
            break;
        }
    }

    include_once "inc/phpMailer/class.phpmailer.php";

    //To shop
    $faktura = $mysqli->fetchOne("SELECT * FROM `fakturas` WHERE `id` = ".$id);
    if (!validemail($faktura['department'])) {
        $faktura['department'] = $GLOBALS['_config']['email'][0];
    }
    if ($faktura) {

        $faktura['quantities'] = explode('<', $faktura['quantities']);
        $faktura['products'] = explode('<', $faktura['products']);
        $faktura['values'] = explode('<', $faktura['values']);

        if ($faktura['premoms']) {
            foreach ($faktura['values'] as $key => $value) {
                $faktura['values'][$key] = $value / 1.25;
            }
        }

        $productslines = max(
            count($faktura['quantities']),
            count($faktura['products']),
            count($faktura['values'])
        );

        $netto = 0;
        for ($i=0;$i<$productslines;$i++) {
            $netto += $faktura['values'][$i]*$faktura['quantities'][$i];
        }

        $emailbody_tablerows = '';
        for ($i = 0; $i < $productslines; $i++) {
            $emailbody_tablerows .= '<tr><td class="tal">'
            . $faktura['quantities'][$i] . '</td><td>'
            . htmlspecialchars_decode($faktura['products'][$i])
            . '</td><td class="tal">';
            $plusTax = $faktura['values'][$i] * (1 + $faktura['momssats']);
            $emailbody_tablerows .= number_format(
                $plusTax,
                2,
                ',',
                ''
            );
            $emailbody_tablerows .= '</td><td class="tal">';
            $emailbody_tablerows .= number_format(
                $plusTax * $faktura['quantities'][$i],
                2,
                ',',
                ''
            );
            $emailbody_tablerows .= '</td></tr>';
        }

        //TODO make this a gettext
        $emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>';
        $emailbody .= sprintf(
            _('Attn.: %s - Online invoice #%d : %s'),
            $faktura['clerk'],
            $id,
            $shopSubject
        );
        $emailbody .= '</title>
<style type="text/css">
td {
    border:1px solid #000;
    border-collapse:collapse;
}
</style>
</head>
<body>';

        $msg = _(
            '<p>%s<br />
Click <a href="%s/admin/faktura.php?id=%d">here</a> to open the invoice page.</p>
<p><a href="mailto:%s">%s</a><br />
Mobile: %s<br />
Phone: %s<br />
Delivery phone: %s</p>
<table id="faktura" cellspacing="0"><thead><tr><td class="td1">Quantity</td><td>Title</td><td class="td3 tal">unit price</td><td class="td4 tal">Total</td></tr></thead>
<tfoot><tr style="height:auto;min-height:auto;max-height:auto;"><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Net Amount</td><td class="tal">%s</td></tr>
<tr><td>&nbsp;</td><td>&nbsp;</td><td class="tal">Freight</td><td class="tal">%s</td></tr>
<tr><td>&nbsp;</td><td style="text-align:right" class="tal">%d%%</td><td class="tal">VAT Amount</td><td class="tal">%s</td></tr>
<tr class="border"><td colspan="2">All figures are in DKK</td><td style="text-align:center; font-weight:bold;">TO PAY</td><td class="tal"><big>%s</big></td></tr></tfoot>
<tbody>%s</tbody></table>
<p>Sincerely, the computer</p>'
        );

        $emailbody .= sprintf(
            $msg,
            $shopBody,
            $GLOBALS['_config']['base_url'],
            $id,
            $faktura['email'],
            $faktura['email'],
            $faktura['tlf2'],
            $faktura['tlf1'],
            $faktura['posttlf'],
            number_format($netto, 2, ',', ''),
            number_format($faktura['fragt'], 2, ',', ''),
            $faktura['momssats']*100,
            number_format($netto*$faktura['momssats'], 2, ',', ''),
            number_format($faktura['amount'], 2, ',', ''),
            $emailbody_tablerows
        );

        $emailbody .= '</body></html>';
    } else {
        $emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.sprintf(_('Online Invoice #%d: does\'t exist'), $id).'</title></head><body>
' . $shopBody . '<br />' . _('Status:') . ' ' . $_GET['Status']
        . $_GET['Status_code'] . '<p>' . _('Sincerely the computer') . '</p></body>
</html>
</body></html>';
    }

    if (!empty($faktura)) {
        $mail = new PHPMailer();
        $mail->SetLanguage('dk');
        $mail->IsSMTP();
        if ($GLOBALS['_config']['emailpassword'] !== false) {
            $mail->SMTPAuth   = true;
            $mail->Username   = $GLOBALS['_config']['email'][0];
            $mail->Password   = $GLOBALS['_config']['emailpassword'];
        } else {
            $mail->SMTPAuth   = false;
        }

        $subject = _('Attn.: %s - Online invoice #%d : %s');
        $subject = sprintf($subject, $faktura['clerk'], $id, $shopSubject);

        $mail->Host       = $GLOBALS['_config']['smtp'];
        $mail->Port       = $GLOBALS['_config']['smtpport'];
        $mail->CharSet    = 'utf-8';
        $mail->From       = $GLOBALS['_config']['email'][0];
        $mail->FromName   = $GLOBALS['_config']['site_name'];
        $mail->Subject    = $subject;
        $mail->MsgHTML($emailbody, $_SERVER['DOCUMENT_ROOT']);

        $mail->AddAddress($faktura['department'], $GLOBALS['_config']['site_name']);

        if ($mail->Send()) {
            //Upload email to the sent folder via imap
            if ($GLOBALS['_config']['imap']) {
                include_once $_SERVER['DOCUMENT_ROOT'].'/inc/imap.php';
                $imap = new IMAP(
                    $GLOBALS['_config']['email'][0],
                    $GLOBALS['_config']['emailpasswords'][0],
                    $GLOBALS['_config']['imap'],
                    $GLOBALS['_config']['imapport']
                );
                $imap->append(
                    $GLOBALS['_config']['emailsent'],
                    $mail->CreateHeader() . $mail->CreateBody(),
                    '\Seen'
                );
                unset($imap);
            }
        } else {
            //TODO secure this against injects and <; in the email and name
            $mysqli->query(
                "
                INSERT INTO `emails` (
                    `subject`,
                    `from`,
                    `to`,
                    `body`,
                    `date`
                )
                VALUES (
                    '" . $subject . "',
                    '" . $GLOBALS['_config']['site_name'] . "<" . $GLOBALS['_config']['email'][0] . ">',
                    '" . $GLOBALS['_config']['site_name'] . "<" . $faktura['department'] . ">',
                    '" . $emailbody . "',
                    NOW()
                )
                "
            );
        }
    }
} else {
    $GLOBALS['generatedcontent']['title'] = _('Payment');
    $GLOBALS['generatedcontent']['headline'] = _('Payment');

    $GLOBALS['generatedcontent']['text'] = '<form action="" method="get">
      <table>
        <tbody>
          <tr>
            <td>'._('Order No:').'</td>
            <td><input name="id" value="'.@$_GET['id'].'" /></td>
          </tr>
          <tr>
            <td>'._('Code:').'</td>
            <td><input name="checkid" value="'.@$_GET['checkid'].'" /></td>
          </tr>
        </tbody>
      </table><input type="submit" value="'._('Continue').'" />
    </form>';
    if (!empty($_GET['checkid'])) {
        $GLOBALS['generatedcontent']['text'] = _('The code is not correct!');
    }
}

//Output page
require_once 'theme/index.php';

