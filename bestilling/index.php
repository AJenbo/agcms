<?php
/**
 * Page for sending an order request
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

if (is_numeric(@$_GET['add']) || is_numeric(@$_GET['add_list_item'])) {
    include_once 'inc/config.php';
    include_once 'inc/mysqli.php';
    //Open database
    $mysqli = new Simple_Mysqli(
        $GLOBALS['_config']['mysql_server'],
        $GLOBALS['_config']['mysql_user'],
        $GLOBALS['_config']['mysql_password'],
        $GLOBALS['_config']['mysql_database']
    );

    if ($_SERVER['HTTP_REFERER']) {
        $goto_uri = $_SERVER['HTTP_REFERER'];
    } else {
        $goto_uri = '';
    }

    if (is_numeric(@$_GET['add_list_item'])) {
        $list_row = $mysqli->fetchOne(
            "
            SELECT *
            FROM `list_rows`
            WHERE id = " . (int) $_GET['add_list_item']
        );
        if ($list_row['link']) {
            $product = $mysqli->fetchOne(
                "
                SELECT `navn`, `pris`, `fra`
                FROM `sider`
                WHERE id = " . (int) $list_row['link']
            );

            if (!$goto_uri) {
                $goto_uri = '/?side='.$product['link'];
            }
        } else {
            $list = $mysqli->fetchOne(
                "
                SELECT `page_id`, `cells`
                FROM `lists` WHERE id = " . (int) $list_row['list_id']
            );
            $list['cells'] = explode('<', $list['cells']);
            $list_row['cells'] = explode('<', $list_row['cells']);
            $product['navn'] = '';
            $product['pris'] = null;
            $product['fra'] = 0;
            foreach ($list['cells'] as $i => $celltype) {
                if ($celltype == 0 || $celltype == 1) {
                    $product['navn'] .= ' '.@$list_row['cells'][$i];
                } elseif ($celltype == 2 || $celltype == 3) {
                    $product['pris'] = @$list_row['cells'][$i];
                }
            }

            if (!$goto_uri) {
                $goto_uri = '/?side='.$list['page_id'];
            }
        }
    } elseif (is_numeric(@$_GET['add'])) {
        $product = $mysqli->fetchOne(
            "SELECT `navn`, `pris`, `fra`
            FROM `sider`
            WHERE id = " . (int) $_GET['add']
        );

        if (!$goto_uri) {
            $goto_uri = '/?side=' . (int) $_GET['add'];
        }
    }

    session_start();

    $product_exists = false;
    if (!empty($_SESSION['faktura']['quantities'])) {
        foreach ($_SESSION['faktura']['products'] as $i => $product_name) {
            if ($product_name == $product['navn']) {
                $_SESSION['faktura']['quantities'][$i]++;
                $product_exists = true;
                break;
            }
        }
    }
    if (!$product_exists) {
        $_SESSION['faktura']['quantities'][] = 1;
        $_SESSION['faktura']['products'][] = $product['navn'];
        if ($product['fra'] == 1) {
            $product['pris'] = null;
        }
        $_SESSION['faktura']['values'][] = $product['pris'];
    }

    ini_set('zlib.output_compression', '0');

    if ($_SERVER['HTTP_REFERER']) {
        header('Location: '.$_SERVER['HTTP_REFERER'], true, 303);
    } else {
        header('Location: /?side='.$_GET['add'], true, 303);
    }
    exit;
}

/*fake basket content*/
/*
if (!$_SESSION['faktura'] && empty($_GET['step'])) {
    $_SESSION['faktura']['quantities'][0] = 1;
    $_SESSION['faktura']['products'][0] = 'Garmin 720 s';
    $_SESSION['faktura']['values'][0] = 10499;
    $_SESSION['faktura']['quantities'][1] = 1;
    $_SESSION['faktura']['products'][1] = 'Amb. Eon Sport 3601 - UDSALG!';
    $_SESSION['faktura']['values'][1] = 699;
}
/**/

//Generate default $GLOBALS['generatedcontent']
$delayprint = true;
require_once 'index.php';
$GLOBALS['generatedcontent']['datetime'] = time();

unset($_POST['values']);
unset($_POST['products']);
if (count($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION['faktura'][$key] = $value;
    }
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
$GLOBALS['generatedcontent']['crumbs'][0] = array(
    'name' => _('Payment'),
    'link' => '/',
    'icon' => null
);

$GLOBALS['generatedcontent']['contenttype'] = 'page';
$GLOBALS['generatedcontent']['text'] = '';

if (!empty($_SESSION['faktura']['quantities'])) {
    $rejected = array();

    if (empty($_GET['step'])) {
        if ($_POST) {
            foreach ($_POST['quantity'] as $i => $quantiy) {
                if ($quantiy < 1) {
                    unset($_SESSION['faktura']['quantities'][$i]);
                    unset($_SESSION['faktura']['products'][$i]);
                    unset($_SESSION['faktura']['values'][$i]);
                } else {
                    $_SESSION['faktura']['quantities'][$i] = $quantiy;
                }
            }
            $_SESSION['faktura']['quantities'] = array_values($_SESSION['faktura']['quantities']);
            $_SESSION['faktura']['products'] = array_values($_SESSION['faktura']['products']);
            $_SESSION['faktura']['values'] = array_values($_SESSION['faktura']['values']);

            ini_set('zlib.output_compression', '0');
            header(
                'Location: '.$GLOBALS['_config']['base_url'].'/bestilling/?step=1',
                true,
                303
            );
            exit;
        }

        $_SESSION['faktura']['amount'] = 0;
        foreach ($_SESSION['faktura']['quantities'] as $i => $quantity) {
            $_SESSION['faktura']['amount'] += $_SESSION['faktura']['values'][$i] * $quantity;
        }

        $GLOBALS['generatedcontent']['crumbs'] = array();
        $GLOBALS['generatedcontent']['crumbs'][1] = array(
            'name' => _('Place order'),
            'link' => '#',
            'icon' => null
        );
        $GLOBALS['generatedcontent']['title'] = _('Place order');
        $GLOBALS['generatedcontent']['headline'] = _('Place order');


        $GLOBALS['generatedcontent']['text'] = '<script type="text/javascript" src="javascript.js"></script>
        <form action="" method="post"><p><table style="border-bottom:1px solid;" id="faktura" cellspacing="0">
            <thead>
                <tr>
                    <td class="td1">'._('Quantity').'</td>
                    <td>'._('Title').'</td>
                    <td class="td3 tal" style="width:64px">'._('unit price').'</td>
                    <td class="td4 tal" style="width:72px">'._('Total').'</td>
                </tr>
            </thead>
            <tfoot>
                <tr style="border:1px solid #000">
                    <td class="td1"></td>
                    <td></td>
                    <td class="td3 tal">'._('Total').'</td>
                    <td class="td4 tal" id="total">'.number_format($_SESSION['faktura']['amount'], 2, ',', '').'</td>
                </tr>
            </tfoot>
            <tbody>';

        $unknownvalue = false;
        $javascript = 'var values = [];';
        foreach ($_SESSION['faktura']['quantities'] as $i => $quantity) {
            $GLOBALS['generatedcontent']['text'] .= '<tr>
                <td class="tal"><input onkeyup="updateprice();" onchange="updateprice();" class="tal" value="'.$quantity.'" name="quantity[ ]" size="3" /></td>
                <td>'.$_SESSION['faktura']['products'][$i].'</td>
                <td class="tal">';
            if (is_numeric($_SESSION['faktura']['values'][$i])) {
                $GLOBALS['generatedcontent']['text'] .= number_format($_SESSION['faktura']['values'][$i], 2, ',', '');
                $javascript .= "\n".'values['.$i.'] = '.$_SESSION['faktura']['values'][$i].';';
            } else {
                $GLOBALS['generatedcontent']['text'] .= '*';
                $javascript .= "\n".'values['.$i.'] = 0;';
                $unknownvalue = true;
            }
            $GLOBALS['generatedcontent']['text'] .= '</td><td class="tal total">';
            if (is_numeric($_SESSION['faktura']['values'][$i])) {
                $GLOBALS['generatedcontent']['text'] .= number_format($_SESSION['faktura']['values'][$i] * $quantity, 2, ',', '');
            } else {
                $GLOBALS['generatedcontent']['text'] .= '*';
            }
            $GLOBALS['generatedcontent']['text'] .= '</td></tr>';
        }
        $GLOBALS['generatedcontent']['text'] .= '</tbody></table>';
        $GLOBALS['generatedcontent']['text'] .= '<script type="text/javascript"><!--
        ' . $javascript . '
        --></script>';
        if ($unknownvalue) {
            $GLOBALS['generatedcontent']['text'] .= '<small>'
            . _('* The price cannot be determined automatically, please make sure to describe the exact type in the note field.')
            . '</small></p>';
        }
        if (empty($_SESSION['faktura']['paymethod'])) {
            $_SESSION['faktura']['paymethod'] = '';
        }
        $GLOBALS['generatedcontent']['text'] .= '<p>' . _('Prefered payment method:')
        . ' <select name="paymethod" style="float:right;">
            <option value="creditcard"';
        if ($_SESSION['faktura']['paymethod'] == 'creditcard') {
            $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>'._('Credit Card').'</option>
            <option value="bank"';
        if ($_SESSION['faktura']['paymethod'] == 'bank') {
            $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>'._('Bank transaction').'</option>
            <option value="cash"';
        if ($_SESSION['faktura']['paymethod'] == 'cash') {
            $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>'._('Cash').'</option>
        </select></p>';

        if (empty($_SESSION['faktura']['delevery'])) {
            $_SESSION['faktura']['delevery'] = '';
        }
        $GLOBALS['generatedcontent']['text'] .= '<p>' . _('Delevery:')
        . ' <select style="float:right;" name="delevery">
            <option value="postal"';
        if ($_SESSION['faktura']['delevery'] == 'postal') {
            $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>'._('Mail').'</option>
            <option value="express"';
        if ($_SESSION['faktura']['delevery'] == 'express') {
            $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>'._('Mail express').'</option>
            <option value="pickup"';
        if ($_SESSION['faktura']['delevery'] == 'pickup') {
            $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>'._('Pickup in store').'</option>
        </select><small id="shipping"><br />'
        . _('The excact shipping cost will be calculcated as the goods are packed.')
        . '</small></p>';


        if (empty($_SESSION['faktura']['note'])) {
            $_SESSION['faktura']['note'] = '';
        }
        $GLOBALS['generatedcontent']['text'] .= '<p>' . _('Note:')
        . '<br /><textarea style="width:100%;" name="note">'
        . htmlspecialchars($_SESSION['faktura']['note'], ENT_COMPAT | ENT_XHTML, 'UTF-8')
        . '</textarea><p>';

        $GLOBALS['generatedcontent']['text'] .= '<input value="' . _('Continue')
        . '" type="submit" /></form>';

    } elseif ($_GET['step'] == 1) {

        if (empty($_SESSION['faktura']['postcountry'])) {
            $_SESSION['faktura']['postcountry'] = 'DK';
        }
        if (empty($_SESSION['faktura']['land'])) {
            $_SESSION['faktura']['land'] = 'DK';
        }

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

            $_SESSION['faktura'] = array_merge($_SESSION['faktura'], $updates);
            $rejected = validate($updates);

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
                            `dato` ,
                            `ip`
                        )
                        VALUES (
                            '" .addcslashes($updates['navn'], '`\\')."',
                            '" .addcslashes($updates['email'], '`\\')."',
                            '" .addcslashes($updates['adresse'], '`\\')."',
                            '" .addcslashes(_($countries[$updates['land']]), '`\\')."',
                            '" .addcslashes($updates['postnr'], '`\\')."',
                            '" .addcslashes($updates['by'], '`\\')."',
                            '" .addcslashes($updates['tlf1'], '`\\')."',
                            '" .addcslashes($updates['tlf2'], '`\\')."',
                            '1',
                            now(),
                            '" .addcslashes($_SERVER['REMOTE_ADDR'], '`\\')."'
                        )
                        "
                    );
                }

                ini_set('zlib.output_compression', '0');
                header(
                    'Location: '.$GLOBALS['_config']['base_url'].'/bestilling/?step=2',
                    true,
                    303
                );
                exit;
            }
        } else {
            $rejected = validate($_SESSION['faktura']);
        }

        //TODO set land to DK by default

        //TODO add enote
        $GLOBALS['generatedcontent']['crumbs'] = array();
        $GLOBALS['generatedcontent']['crumbs'][1] = array(
            'name' => _('Recipient'),
            'link' => '#',
            'icon' => null
        );
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
            <td colspan="2"><input name="tlf1" id="tlf1" style="width:157px" value="'.@$_SESSION['faktura']['tlf1'].'" /></td>
            <td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'tlf1\').value, get_address_r1);" /></td>
        </tr>
        <tr>
            <td> '._('Mobile:').'</td>
            <td colspan="2"><input name="tlf2" id="tlf2" style="width:157px" value="'.@$_SESSION['faktura']['tlf2'].'" /></td>
            <td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'tlf2\').value, get_address_r1);" /></td>
        </tr>
        <tr>
            <td>'._('Name:').'</td>
            <td colspan="2"><input name="navn" id="navn" style="width:157px" value="'.@$_SESSION['faktura']['navn'].'" /></td>
            <td>';
        if (!empty($rejected['navn'])) {
            $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
        }
        $GLOBALS['generatedcontent']['text'] .= '</td>
        </tr>
        <tr>
            <td> '._('Name:').'</td>
            <td colspan="2"><input name="att" id="att" style="width:157px" value="'.@$_SESSION['faktura']['att'].'" /></td>
            <td></td>
        </tr>
        <tr>
            <td> '._('Address:').'</td>
            <td colspan="2"><input name="adresse" id="adresse" style="width:157px" value="'.@$_SESSION['faktura']['adresse'].'" /></td>
            <td>';
        if (!empty($rejected['adresse'])) {
            $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
        }
        $GLOBALS['generatedcontent']['text'] .= '</td>
        </tr>
        <tr>
            <td> '._('Postbox:').'</td>
            <td colspan="2"><input name="postbox" id="postbox" style="width:157px" value="'.@$_SESSION['faktura']['postbox'].'" /></td>
            <td></td>
        </tr>
        <tr>
            <td> '._('Zipcode:').'</td>
            <td><input name="postnr" id="postnr" style="width:35px" value="'.@$_SESSION['faktura']['postnr'].'" onblur="chnageZipCode(this.value, \'land\', \'by\')" onkeyup="chnageZipCode(this.value, \'land\', \'by\')" onchange="chnageZipCode(this.value, \'land\', \'by\')" /></td>
            <td align="right">'._('City:').'
                <input name="by" id="by" style="width:90px" value="'.@$_SESSION['faktura']['by'].'" /></td>
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
            if ($_SESSION['faktura']['land'] == $code) {
                $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>'.htmlspecialchars($country, ENT_COMPAT | ENT_XHTML, 'UTF-8').'</option>';
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
            <td colspan="2"><input name="email" id="email" style="width:157px" value="'.@$_SESSION['faktura']['email'].'" /></td>
            <td>';
        if (!empty($rejected['email'])) {
            $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
        }
        $GLOBALS['generatedcontent']['text'] .= '</td>
        </tr>
        <tr>
            <td colspan="4"><input onclick="showhidealtpost(this.checked);" name="altpost" id="altpost" type="checkbox"';
        if (!empty($_SESSION['faktura']['altpost'])) {
            $GLOBALS['generatedcontent']['text'] .= ' checked="checked"';
        }
        $GLOBALS['generatedcontent']['text'] .= ' /><label for="altpost"> '._('Other delivery address').'</label></td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>
            <td> '._('Phone:').'</td>
            <td colspan="2"><input name="posttlf" id="posttlf" style="width:157px" value="'.@$_SESSION['faktura']['posttlf'].'" /></td>
            <td><input type="button" value="'._('Get address').'" onclick="get_address(document.getElementById(\'posttlf\').value, get_address_r2);" /></td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>
            <td>'._('Name:').'</td>
            <td colspan="2"><input name="postname" id="postname" style="width:157px" value="'.@$_SESSION['faktura']['postname'].'" /></td>
            <td>';
        if (!empty($rejected['postname'])) {
            $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
        }
        $GLOBALS['generatedcontent']['text'] .= '</td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>
            <td> '._('Attn.:').'</td>
            <td colspan="2"><input name="postatt" id="postatt" style="width:157px" value="'.@$_SESSION['faktura']['postatt'].'" /></td>
            <td></td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>
            <td> '._('Address:').'</td>
            <td colspan="2"><input name="postaddress" id="postaddress" style="width:157px" value="'.@$_SESSION['faktura']['postaddress'].'" /><br /><input name="postaddress2" id="postaddress2" style="width:157px" value="'.@$_SESSION['faktura']['postaddress2'].'" /></td>
            <td>';
        if (!empty($rejected['postaddress'])) {
            $GLOBALS['generatedcontent']['text'] .= '<img src="images/error.png" alt="" title="" >';
        }
        $GLOBALS['generatedcontent']['text'] .= '</td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>
            <td> '._('Postbox:').'</td>
            <td colspan="2"><input name="postpostbox" id="postpostbox" style="width:157px" value="'.@$_SESSION['faktura']['postpostbox'].'" /></td>
            <td></td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>
            <td> '._('Zipcode:').'</td>
            <td><input name="postpostalcode" id="postpostalcode" style="width:35px" value="'.@$_SESSION['faktura']['postpostalcode'].'" onblur="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onchange="chnageZipCode(this.value, \'postcountry\', \'postcity\')" /></td>
            <td align="right">'._('City:').'
                <input name="postcity" id="postcity" style="width:90px" value="'.@$_SESSION['faktura']['postcity'].'" /></td>
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
        if (empty($_SESSION['faktura']['altpost'])) {
            $GLOBALS['generatedcontent']['text'] .= ' style="display:none;"';
        }
        $GLOBALS['generatedcontent']['text'] .= '>
            <td> '._('Country:').'</td>
            <td colspan="2"><select name="postcountry" id="postcountry" style="width:157px" onblur="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onchange="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')">';

        include_once 'inc/countries.php';
        foreach ($countries as $code => $country) {
            $GLOBALS['generatedcontent']['text'] .= '<option value="'.$code.'"';
            if ($_SESSION['faktura']['postcountry'] == $code) {
                $GLOBALS['generatedcontent']['text'] .= ' selected="selected"';
            }
            $GLOBALS['generatedcontent']['text'] .= '>'.htmlspecialchars($country, ENT_COMPAT | ENT_XHTML, 'UTF-8').'</option>';
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
        $GLOBALS['generatedcontent']['text'] .= '</tbody></table><input style="font-weight:bold;" type="submit" value="'._('Send order').'" /></form>';
    } elseif ($_GET['step'] == 2) {
        if (!$_SESSION['faktura'] || !$_SESSION['faktura']['email']) {
            ini_set('zlib.output_compression', '0');
            header(
                'Location: '.$GLOBALS['_config']['base_url'].'/bestilling/',
                true,
                303
            );
            exit;
        }

        if ($_SESSION['faktura']['paymethod'] == 'creditcard') {
            $_SESSION['faktura']['note'] = _('I would like to pay via credit card.')."\n".$_SESSION['faktura']['note'];
        } elseif ($_SESSION['faktura']['paymethod'] == 'bank') {
            $_SESSION['faktura']['note'] = _('I would like to pay via bank transaction.')."\n".$_SESSION['faktura']['note'];
        } elseif ($_SESSION['faktura']['paymethod'] == 'cash') {
            $_SESSION['faktura']['note'] = _('I would like to pay via cash.')."\n".$_SESSION['faktura']['note'];
        }
        if ($_SESSION['faktura']['delevery'] == 'pickup') {
            $_SESSION['faktura']['note'] = _('I will pick up the goods in your shop.')."\n".$_SESSION['faktura']['note'];
        } elseif ($_SESSION['faktura']['delevery'] == 'postal') {
            $_SESSION['faktura']['note'] = _('Please send the goods by mail.')."\n".$_SESSION['faktura']['note'];
        } elseif ($_SESSION['faktura']['delevery'] == 'express') {
            $_SESSION['faktura']['note'] = _('Please send the order to by mail express.')."\n".$_SESSION['faktura']['note'];
        }

        $quantities = array_map('htmlspecialchars', $_SESSION['faktura']['quantities']);
        $products = array_map('htmlspecialchars', $_SESSION['faktura']['products']);
        $values = array_map('htmlspecialchars', $_SESSION['faktura']['values']);

        $sql = "INSERT `fakturas` SET";
        $sql .= " `quantities` = '".addcslashes(implode('<', $_SESSION['faktura']['quantities']), "'\\")."',";
        $sql .= " `products` = '".addcslashes(implode('<', $_SESSION['faktura']['products']), "'\\")."',";
        $sql .= " `values` = '".addcslashes(implode('<', $_SESSION['faktura']['values']), "'\\")."',";
        $sql .= " `amount` = ".$_SESSION['faktura']['amount'].",";

        $sql .= " `navn` = '".addcslashes($_SESSION['faktura']['navn'], "'\\")."',";
        $sql .= " `att` = '".addcslashes($_SESSION['faktura']['att'], "'\\")."',";
        $sql .= " `adresse` = '".addcslashes($_SESSION['faktura']['adresse'], "'\\")."',";
        $sql .= " `postbox` = '".addcslashes($_SESSION['faktura']['postbox'], "'\\")."',";
        $sql .= " `postnr` = '".addcslashes($_SESSION['faktura']['postnr'], "'\\")."',";
        $sql .= " `by` = '".addcslashes($_SESSION['faktura']['by'], "'\\")."',";
        $sql .= " `land` = '".addcslashes($_SESSION['faktura']['land'], "'\\")."',";
        $sql .= " `email` = '".addcslashes($_SESSION['faktura']['email'], "'\\")."',";
        $sql .= " `tlf1` = '".addcslashes($_SESSION['faktura']['tlf1'], "'\\")."',";
        $sql .= " `tlf2` = '".addcslashes($_SESSION['faktura']['tlf2'], "'\\")."',";

        $sql .= " `altpost` = ".$_SESSION['faktura']['altpost'].",";
        $sql .= " `posttlf` = '".addcslashes($_SESSION['faktura']['posttlf'], "'\\")."',";
        $sql .= " `postname` = '".addcslashes($_SESSION['faktura']['postname'], "'\\")."',";
        $sql .= " `postatt` = '".addcslashes($_SESSION['faktura']['postatt'], "'\\")."',";
        $sql .= " `postaddress` = '".addcslashes($_SESSION['faktura']['postaddress'], "'\\")."',";
        $sql .= " `postaddress2` = '".addcslashes($_SESSION['faktura']['postaddress2'], "'\\")."',";
        $sql .= " `postpostbox` = '".addcslashes($_SESSION['faktura']['postpostbox'], "'\\")."',";
        $sql .= " `postpostalcode` = '".addcslashes($_SESSION['faktura']['postpostalcode'], "'\\")."',";
        $sql .= " `postcity` = '".addcslashes($_SESSION['faktura']['postcity'], "'\\")."',";
        $sql .= " `postcountry` = '".addcslashes($_SESSION['faktura']['postcountry'], "'\\")."',";
        $sql .= " `note` = '".addcslashes($_SESSION['faktura']['note'], "'\\")."',";

        $sql .= " `date` = NOW()";

        //save order
        $mysqli->query($sql);
        $id = $mysqli->insert_id;

        //emailbody header
        $emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>'._('Online order #').$id.'</title><style type="text/css"><!--
        #faktura td {
          border: 1px solid #000;
          border-collapse: collapse;
        }
        .tal {
          text-align:right;
        }
        --></style></head><body><p>
        '.$_SESSION['faktura']['navn']._(' has placed an order for the following:').'</p>';

        //Table of goods
        $emailbody .= '<table id="faktura" cellspacing="0">
            <thead>
                <tr>
                    <td class="td1">'._('Quantity').'</td>
                    <td>'._('Title').'</td>
                    <td class="td3 tal">'._('unit price').'</td>
                    <td class="td4 tal">'._('Total').'</td>
                </tr>
            </thead>
            <tbody>';
        foreach ($_SESSION['faktura']['quantities'] as $i => $quantity) {
            $emailbody .= '<tr>
                <td class="tal">' . $quantity . '</td>
                <td>' . $_SESSION['faktura']['products'][$i] . '</td>
                <td class="tal">' . number_format($_SESSION['faktura']['values'][$i], 2, ',', '') . '</td>
                <td class="tal">' . number_format($_SESSION['faktura']['values'][$i] * $quantity, 2, ',', '') . '</td>
            </tr>';
        }
        $emailbody .= '</tbody></table>';

        //Note
        $emailbody .= '<p><b>' . _('Note:') . '</b><br />'
        . nl2br($_SESSION['faktura']['note'], true) . '</p>';

        //Address
        $emailbody .= '<p><b>' ._('Address:') .'</b>';
        if ($_SESSION['faktura']['navn']) {
            $emailbody .= '<br />'.$_SESSION['faktura']['navn'];
        }
        if ($_SESSION['faktura']['att']) {
            $emailbody .= '<br />'.$_SESSION['faktura']['att'];
        }
        if ($_SESSION['faktura']['adresse']) {
            $emailbody .= '<br />'.$_SESSION['faktura']['adresse'];
        }
        if ($_SESSION['faktura']['postbox']) {
            $emailbody .= '<br />'.$_SESSION['faktura']['postbox'];
        }
        if ($_SESSION['faktura']['by']) {
            $emailbody .= '<br />' . $_SESSION['faktura']['postnr']
            . ' ' . $_SESSION['faktura']['by'];
        }
        if ($_SESSION['faktura']['land'] != 'DK') {
            include_once 'inc/countries.php';
            $emailbody .= '<br />'._($countries[$_SESSION['faktura']['land']]);
        }
        $emailbody .= '</p>';

        //Delivery address
        if ($_SESSION['faktura']['altpost']) {
            $emailbody .= '<p><b>'._('Delivery address:').'</b>';
            if ($_SESSION['faktura']['postname']) {
                $emailbody .= '<br />'.$_SESSION['faktura']['postname'];
            }
            if ($_SESSION['faktura']['postatt']) {
                $emailbody .= '<br />'.$_SESSION['faktura']['postatt'];
            }
            if ($_SESSION['faktura']['postaddress']) {
                $emailbody .= '<br />'.$_SESSION['faktura']['postaddress'];
            }
            if ($_SESSION['faktura']['postaddress2']) {
                $emailbody .= '<br />'.$_SESSION['faktura']['postaddress2'];
            }
            if ($_SESSION['faktura']['postpostbox']) {
                $emailbody .= '<br />'.$_SESSION['faktura']['postpostbox'];
            }
            if ($_SESSION['faktura']['postcity']) {
                $emailbody .= '<br />' . $_SESSION['faktura']['postpostalcode']
                . ' ' . $_SESSION['faktura']['postcity'];
            }
            if ($_SESSION['faktura']['postcountry'] != 'DK') {
                include_once 'inc/countries.php';
                $emailbody .= '<br />'
                . _($countries[$_SESSION['faktura']['postcountry']]);
            }
            $emailbody .= '</p>';
        }

        //Admin link
        $msg = sprintf(
            _('Click <a href="%s">here</a> to expedite the order.'),
            $GLOBALS['_config']['base_url'] . '/admin/faktura.php?id=' . $id
        );
        $emailbody .= '<p>' . $msg . '</p>';

        //Contact
        $emailbody .= '<p>' . _('email:') .
        ' <a href="mailto:' . $_SESSION['faktura']['email'] . '">'
        . $_SESSION['faktura']['email'] . '</a>';
        if ($_SESSION['faktura']['tlf1']) {
            $emailbody .= ' '._('Phone:').' '.$_SESSION['faktura']['tlf1'];
        }
        if ($_SESSION['faktura']['tlf2']) {
            $emailbody .= ' '._('Mobil:').' '.$_SESSION['faktura']['tlf2'];
        }
        $emailbody .= '</p><p>' . _('Sincerely the computer')
        . '</p></body></html></body></html>';

        //Email headers
        include_once "inc/phpMailer/class.phpmailer.php";

        $mail = new PHPMailer();
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
        $mail->From       = $GLOBALS['_config']['email'][0];
        $mail->FromName   = $GLOBALS['_config']['site_name'];

        $mail->AddReplyTo(
            $_SESSION['faktura']['email'],
            $_SESSION['faktura']['navn']
        );

        $mail->Subject    = _('Online order #').$id;
        $mail->MsgHTML($emailbody, $_SERVER['DOCUMENT_ROOT']);

        //TODO allow other departments to revice orders
        $mail->AddAddress(
            $GLOBALS['_config']['email'][0],
            $GLOBALS['_config']['site_name']
        );

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
                INSERT INTO `emails` (`subject`, `from`, `to`, `body`, `date`)
                VALUES (
                    '" . $mail->Subject . "',
                    '" . $GLOBALS['_config']['site_name'] . "<" . $GLOBALS['_config']['email'][0] . ">',
                    '" . $GLOBALS['_config']['site_name'] . "<" . $GLOBALS['_config']['email'][0] . ">',
                    '" . $emailbody . "',
                    NOW()
                );
                "
            );
        }

        $GLOBALS['generatedcontent']['title'] = _('Order placed');
        $GLOBALS['generatedcontent']['headline'] = _('Order placed');
        $GLOBALS['generatedcontent']['text'] = _('Thank you for your order, you will recive an email with instructions on how to perform the payment as soon as we have validated that all goods are in stock.');

        session_destroy();
    }
} else {
    $GLOBALS['generatedcontent']['title'] = _('Place order');
    $GLOBALS['generatedcontent']['headline'] = _('Place order');
    $GLOBALS['generatedcontent']['text'] = _('Ther is no content in the basket!');
}

//Output page
require_once 'theme/index.php';

