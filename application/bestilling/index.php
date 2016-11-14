<?php
/**
 * Page for sending an order request
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/functions.php';
include_once _ROOT_ . '/inc/countries.php';

if (is_numeric(@$_GET['add']) || is_numeric(@$_GET['add_list_item'])) {
    if ($_SERVER['HTTP_REFERER']) {
        $goto_uri = $_SERVER['HTTP_REFERER'];
    } else {
        $goto_uri = '';
    }

    $productTitle = '';
    $productPrice = null;
    $productOldPrice = 0;
    if (is_numeric(@$_GET['add_list_item'])) {
        $list_row = db()->fetchOne(
            "
            SELECT *
            FROM `list_rows`
            WHERE id = " . (int) $_GET['add_list_item']
        );
        if ($list_row['link']) {
            $product = ORM::getOne(Page::class, $list_row['link']);
            if ($product) {
                $productTitle = $product->getTitle();
                $productPrice = $product->getPrice();
                $productOldPrice = $product->getOldPrice();
            }
            if (!$goto_uri) {
                $goto_uri = '/?side=' . (int) $_GET['add'];
            }
        } else {
            $list = db()->fetchOne(
                "
                SELECT `page_id`, `cells`
                FROM `lists` WHERE id = " . (int) $list_row['list_id']
            );
            $list['cells'] = explode('<', $list['cells']);
            $list_row['cells'] = explode('<', $list_row['cells']);
            foreach ($list['cells'] as $i => $celltype) {
                if ($celltype == 0 || $celltype == 1) {
                    $productTitle .= ' '.@$list_row['cells'][$i];
                } elseif ($celltype == 2 || $celltype == 3) {
                    $productPrice = @$list_row['cells'][$i];
                }
            }

            if (!$goto_uri) {
                $goto_uri = '/?side='.$list['page_id'];
            }
        }
    } elseif (is_numeric(@$_GET['add'])) {
        $product = ORM::getOne(Page::class, $_GET['add']);
        if ($product) {
            $productTitle = $product->getTitle();
            $productPrice = $product->getPrice();
            $productOldPrice = $product->getOldPrice();
        }

        if (!$goto_uri) {
            $goto_uri = '/?side=' . (int) $_GET['add'];
        }
    }

    session_start();
    $product_exists = false;
    if (!empty($_SESSION['faktura']['quantities'])) {
        foreach ($_SESSION['faktura']['products'] as $i => $product_name) {
            if ($product_name == $productTitle) {
                $_SESSION['faktura']['quantities'][$i]++;
                $product_exists = true;
                break;
            }
        }
    }
    if (!$product_exists) {
        $_SESSION['faktura']['quantities'][] = 1;
        $_SESSION['faktura']['products'][] = $productTitle;
        if ($productOldPrice == 1) {
            $productPrice = null;
        }
        $_SESSION['faktura']['values'][] = $productPrice;
    }

    if (!empty($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER['HTTP_REFERER'];
    } else {
        $url = '/?side=' . (int) $_GET['add'];
    }
    redirect($url);
}

session_start();
Render::$pageType = 'custome';

unset($_POST['values']);
unset($_POST['products']);
if (count($_POST)) {
    foreach ($_POST as $key => $value) {
        $_SESSION['faktura'][(int) $key] = $value;
    }
}

//Generate return page
Render::$crumbs = [
    [
        'name' => _('Payment'),
        'link' => '/',
        'icon' => null,
    ]
];

Render::$pageType = 'custome';

if (!empty($_SESSION['faktura']['quantities'])) {
    $rejected = [];

    if (empty($_GET['step'])) {
        if (!empty($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $i => $quantiy) {
                if ($quantiy < 1) {
                    unset($_SESSION['faktura']['quantities'][$i]);
                    unset($_SESSION['faktura']['products'][$i]);
                    unset($_SESSION['faktura']['values'][$i]);
                } else {
                    $_SESSION['faktura']['quantities'][$i] = (int) $quantiy;
                }
            }
            $_SESSION['faktura']['quantities'] = array_values($_SESSION['faktura']['quantities']);
            $_SESSION['faktura']['products'] = array_values($_SESSION['faktura']['products']);
            $_SESSION['faktura']['values'] = array_values($_SESSION['faktura']['values']);

            redirect('/bestilling/?step=1');
        }

        $_SESSION['faktura']['amount'] = 0;
        foreach ($_SESSION['faktura']['quantities'] as $i => $quantity) {
            $_SESSION['faktura']['amount'] += $_SESSION['faktura']['values'][$i] * $quantity;
        }

        Render::$crumbs = [
            [
                'name' => _('Place order'),
                'link' => '#',
                'icon' => null,
            ]
        ];
        Render::$title = _('Place order');
        Render::$headline = _('Place order');


        Render::$bodyHtml = '<script type="text/javascript" src="javascript.js"></script>
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
            Render::$bodyHtml .= '<tr>
                <td class="tal"><input onkeyup="updateprice();" onchange="updateprice();" class="tal" value="'.$quantity.'" name="quantity[ ]" size="3" /></td>
                <td>'.xhtmlEsc($_SESSION['faktura']['products'][$i]).'</td>
                <td class="tal">';
            if (is_numeric($_SESSION['faktura']['values'][$i])) {
                Render::$bodyHtml .= number_format($_SESSION['faktura']['values'][$i], 2, ',', '');
                $javascript .= "\n".'values['.$i.'] = '.$_SESSION['faktura']['values'][$i].';';
            } else {
                Render::$bodyHtml .= '*';
                $javascript .= "\n".'values['.$i.'] = 0;';
                $unknownvalue = true;
            }
            Render::$bodyHtml .= '</td><td class="tal total">';
            if (is_numeric($_SESSION['faktura']['values'][$i])) {
                Render::$bodyHtml .= number_format($_SESSION['faktura']['values'][$i] * $quantity, 2, ',', '');
            } else {
                Render::$bodyHtml .= '*';
            }
            Render::$bodyHtml .= '</td></tr>';
        }
        Render::$bodyHtml .= '</tbody></table>';
        Render::$bodyHtml .= '<script type="text/javascript"><!--
        ' . $javascript . '
        --></script>';
        if ($unknownvalue) {
            Render::$bodyHtml .= '<small>'
            . _('* The price cannot be determined automatically, please make sure to describe the exact type in the note field.')
            . '</small></p>';
        }
        if (empty($_SESSION['faktura']['paymethod'])) {
            $_SESSION['faktura']['paymethod'] = '';
        }
        Render::$bodyHtml .= '<p>' . _('Prefered payment method:')
        . ' <select name="paymethod" style="float:right;">
            <option value="creditcard"';
        if ($_SESSION['faktura']['paymethod'] == 'creditcard') {
            Render::$bodyHtml .= ' selected="selected"';
        }
        Render::$bodyHtml .= '>'._('Credit Card').'</option>
            <option value="bank"';
        if ($_SESSION['faktura']['paymethod'] == 'bank') {
            Render::$bodyHtml .= ' selected="selected"';
        }
        Render::$bodyHtml .= '>'._('Bank transaction').'</option>
            <option value="cash"';
        if ($_SESSION['faktura']['paymethod'] == 'cash') {
            Render::$bodyHtml .= ' selected="selected"';
        }
        Render::$bodyHtml .= '>'._('Cash').'</option>
        </select></p>';

        if (empty($_SESSION['faktura']['delevery'])) {
            $_SESSION['faktura']['delevery'] = '';
        }
        Render::$bodyHtml .= '<p>' . _('Delevery:')
        . ' <select style="float:right;" name="delevery">
            <option value="postal"';
        if ($_SESSION['faktura']['delevery'] == 'postal') {
            Render::$bodyHtml .= ' selected="selected"';
        }
        Render::$bodyHtml .= '>'._('Mail').'</option>
            <option value="express"';
        if ($_SESSION['faktura']['delevery'] == 'express') {
            Render::$bodyHtml .= ' selected="selected"';
        }
        Render::$bodyHtml .= '>'._('Mail express').'</option>
            <option value="pickup"';
        if ($_SESSION['faktura']['delevery'] == 'pickup') {
            Render::$bodyHtml .= ' selected="selected"';
        }
        Render::$bodyHtml .= '>'._('Pickup in store').'</option>
        </select><small id="shipping"><br />'
        . _('The excact shipping cost will be calculcated as the goods are packed.')
        . '</small></p>';


        if (empty($_SESSION['faktura']['note'])) {
            $_SESSION['faktura']['note'] = '';
        }
        Render::$bodyHtml .= '<p>' . _('Note:')
        . '<br /><textarea style="width:100%;" name="note">'
        . xhtmlEsc($_SESSION['faktura']['note'])
        . '</textarea><p>';

        Render::$bodyHtml .= '<input value="' . _('Continue')
        . '" type="submit" /></form>';
    } elseif ($_GET['step'] == 1) {
        if (empty($_SESSION['faktura']['postcountry'])) {
            $_SESSION['faktura']['postcountry'] = 'DK';
        }
        if (empty($_SESSION['faktura']['land'])) {
            $_SESSION['faktura']['land'] = 'DK';
        }

        if ($_POST) {
            $updates = [];
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
            $updates['altpost'] = (int) !empty($_POST['altpost']);
            $updates['posttlf'] = $_POST['posttlf'];
            $updates['postname'] = $_POST['postname'];
            $updates['postatt'] = $_POST['postatt'] != $_POST['postname'] ? $_POST['postatt'] : '';
            $updates['postaddress'] = $_POST['postaddress'];
            $updates['postaddress2'] = $_POST['postaddress2'];
            $updates['postpostbox'] = $_POST['postpostbox'];
            $updates['postpostalcode'] = $_POST['postpostalcode'];
            $updates['postcity'] = $_POST['postcity'];
            $updates['postcountry'] = $_POST['postcountry'];
            $updates['enote'] = $_POST['enote'] ?? '';
            $updates = array_map('trim', $updates);

            $_SESSION['faktura'] = array_merge($_SESSION['faktura'], $updates);
            $rejected = validate($updates);

            if (!count($rejected)) {
                if (!empty($_POST['newsletter'])) {
                    db()->query(
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

                redirect('/bestilling/?step=2');
            }
        } else {
            $rejected = validate($_SESSION['faktura']);
        }

        //TODO set land to DK by default

        //TODO add enote
        Render::$crumbs = [
            [
                'name' => _('Recipient'),
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ]
        ];
        Render::$title = _('Recipient');
        Render::$headline = _('Recipient');

        Render::$bodyHtml = '
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
            <td colspan="2"><input name="tlf1" id="tlf1" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['tlf1'] ?? '').'" /></td>
            <td><input type="button" value="'._('Get address').'" onclick="getAddress(document.getElementById(\'tlf1\').value, getAddress_r1);" /></td>
        </tr>
        <tr>
            <td> '._('Mobile:').'</td>
            <td colspan="2"><input name="tlf2" id="tlf2" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['tlf2'] ?? '').'" /></td>
            <td><input type="button" value="'._('Get address').'" onclick="getAddress(document.getElementById(\'tlf2\').value, getAddress_r1);" /></td>
        </tr>
        <tr>
            <td>'._('Name:').'</td>
            <td colspan="2"><input name="navn" id="navn" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['navn'] ?? '').'" /></td>
            <td>';
        if (!empty($rejected['navn'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td>
        </tr>
        <tr>
            <td> '._('Attn:').'</td>
            <td colspan="2"><input name="att" id="att" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['att'] ?? '').'" /></td>
            <td></td>
        </tr>
        <tr>
            <td> '._('Address:').'</td>
            <td colspan="2"><input name="adresse" id="adresse" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['adresse'] ?? '').'" /></td>
            <td>';
        if (!empty($rejected['adresse'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td>
        </tr>
        <tr>
            <td> '._('Postbox:').'</td>
            <td colspan="2"><input name="postbox" id="postbox" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['postbox'] ?? '').'" /></td>
            <td></td>
        </tr>
        <tr>
            <td> '._('Zipcode:').'</td>
            <td><input name="postnr" id="postnr" style="width:35px" value="'.xhtmlEsc($_SESSION['faktura']['postnr'] ?? '').'" onblur="chnageZipCode(this.value, \'land\', \'by\')" onkeyup="chnageZipCode(this.value, \'land\', \'by\')" onchange="chnageZipCode(this.value, \'land\', \'by\')" /></td>
            <td align="right">'._('City:').'
                <input name="by" id="by" style="width:90px" value="'.xhtmlEsc($_SESSION['faktura']['by'] ?? '').'" /></td>
            <td>';
        if (!empty($rejected['postnr'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        if (!empty($rejected['by'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td>
        </tr>
        <tr>
            <td> '._('Country:').'</td>
            <td colspan="2"><select name="land" id="land" style="width:157px" onblur="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onkeyup="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onchange="chnageZipCode($(\'postnr\').value, \'land\', \'by\')">';
        foreach ($countries as $code => $country) {
            Render::$bodyHtml .= '<option value="'.$code.'"';
            if ($_SESSION['faktura']['land'] == $code) {
                Render::$bodyHtml .= ' selected="selected"';
            }
            Render::$bodyHtml .= '>'.xhtmlEsc($country).'</option>';
        }
        Render::$bodyHtml .= '</select></td>
            <td>';
        if (!empty($rejected['land'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td>
        </tr>
        <tr>
            <td> '._('E-mail:').'</td>
            <td colspan="2"><input name="email" id="email" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['email'] ?? '').'" /></td>
            <td>';
        if (!empty($rejected['email'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td>
        </tr>
        <tr>
            <td colspan="4"><input onclick="showhidealtpost(this.checked);" name="altpost" id="altpost" type="checkbox"';
        if (!empty($_SESSION['faktura']['altpost'])) {
            Render::$bodyHtml .= ' checked="checked"';
        }
        Render::$bodyHtml .= ' /><label for="altpost"> '._('Other delivery address').'</label></td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            Render::$bodyHtml .= ' style="display:none;"';
        }
        Render::$bodyHtml .= '>
            <td> '._('Phone:').'</td>
            <td colspan="2"><input name="posttlf" id="posttlf" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['posttlf'] ?? '').'" /></td>
            <td><input type="button" value="'._('Get address').'" onclick="getAddress(document.getElementById(\'posttlf\').value, getAddress_r2);" /></td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            Render::$bodyHtml .= ' style="display:none;"';
        }
        Render::$bodyHtml .= '>
            <td>'._('Name:').'</td>
            <td colspan="2"><input name="postname" id="postname" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['postname'] ?? '').'" /></td>
            <td>';
        if (!empty($rejected['postname'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            Render::$bodyHtml .= ' style="display:none;"';
        }
        Render::$bodyHtml .= '>
            <td> '._('Attn.:').'</td>
            <td colspan="2"><input name="postatt" id="postatt" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['postatt'] ?? '').'" /></td>
            <td></td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            Render::$bodyHtml .= ' style="display:none;"';
        }
        Render::$bodyHtml .= '>
            <td> '._('Address:').'</td>
            <td colspan="2"><input name="postaddress" id="postaddress" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['postaddress'] ?? '').'" /><br /><input name="postaddress2" id="postaddress2" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['postaddress2'] ?? '').'" /></td>
            <td>';
        if (!empty($rejected['postaddress'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            Render::$bodyHtml .= ' style="display:none;"';
        }
        Render::$bodyHtml .= '>
            <td> '._('Postbox:').'</td>
            <td colspan="2"><input name="postpostbox" id="postpostbox" style="width:157px" value="'.xhtmlEsc($_SESSION['faktura']['postpostbox'] ?? '').'" /></td>
            <td></td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            Render::$bodyHtml .= ' style="display:none;"';
        }
        Render::$bodyHtml .= '>
            <td> '._('Zipcode:').'</td>
            <td><input name="postpostalcode" id="postpostalcode" style="width:35px" value="'.xhtmlEsc($_SESSION['faktura']['postpostalcode'] ?? '').'" onblur="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onchange="chnageZipCode(this.value, \'postcountry\', \'postcity\')" /></td>
            <td align="right">'._('City:').'
                <input name="postcity" id="postcity" style="width:90px" value="'.xhtmlEsc($_SESSION['faktura']['postcity'] ?? '').'" /></td>
            <td>';
        if (!empty($rejected['postpostalcode'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        if (!empty($rejected['postcity'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td>
        </tr>
        <tr class="altpost"';
        if (empty($_SESSION['faktura']['altpost'])) {
            Render::$bodyHtml .= ' style="display:none;"';
        }
        Render::$bodyHtml .= '>
            <td> '._('Country:').'</td>
            <td colspan="2"><select name="postcountry" id="postcountry" style="width:157px" onblur="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onchange="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')">';

        foreach ($countries as $code => $country) {
            Render::$bodyHtml .= '<option value="'.$code.'"';
            if ($_SESSION['faktura']['postcountry'] == $code) {
                Render::$bodyHtml .= ' selected="selected"';
            }
            Render::$bodyHtml .= '>'.xhtmlEsc($country).'</option>';
        }
        Render::$bodyHtml .= '</select></td><td>';
        if (!empty($rejected['postcountry'])) {
            Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
        }
        Render::$bodyHtml .= '</td></tr>';
        Render::$bodyHtml .= '<tr>
            <td colspan="4"><input name="newsletter" id="newsletter" type="checkbox"';
        if (!empty($_POST['newsletter'])) {
            Render::$bodyHtml .= ' checked="checked"';
        }
        Render::$bodyHtml .= ' /><label for="newsletter"> '._('Please send me your newsletter.').'</label></td>
        </tr>';
        Render::$bodyHtml .= '</tbody></table><input style="font-weight:bold;" type="submit" value="'._('Send order').'" /></form>';
    } elseif ($_GET['step'] == 2) {
        if (!$_SESSION['faktura'] || !$_SESSION['faktura']['email']) {
            redirect('/bestilling/');
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

        $quantities = array_map('xhtmlEsc', $_SESSION['faktura']['quantities']);
        $products = array_map('xhtmlEsc', $_SESSION['faktura']['products']);
        $values = array_map('xhtmlEsc', $_SESSION['faktura']['values']);

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
        db()->query($sql);
        $id = db()->insert_id;

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
        '.xhtmlEsc($_SESSION['faktura']['navn'] . _(' has placed an order for the following:')) . '</p>';

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
                <td class="tal">' . (int) $quantity . '</td>
                <td>' . xhtmlEsc($_SESSION['faktura']['products'][$i]) . '</td>
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
            $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['navn']);
        }
        if ($_SESSION['faktura']['att']) {
            $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['att']);
        }
        if ($_SESSION['faktura']['adresse']) {
            $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['adresse']);
        }
        if ($_SESSION['faktura']['postbox']) {
            $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['postbox']);
        }
        if ($_SESSION['faktura']['by']) {
            $emailbody .= '<br />' . xhtmlEsc($_SESSION['faktura']['postnr'] . ' ' . $_SESSION['faktura']['by']);
        }
        if ($_SESSION['faktura']['land'] != 'DK') {
            $emailbody .= '<br />'._($countries[$_SESSION['faktura']['land']]);
        }
        $emailbody .= '</p>';

        //Delivery address
        if ($_SESSION['faktura']['altpost']) {
            $emailbody .= '<p><b>'._('Delivery address:').'</b>';
            if ($_SESSION['faktura']['postname']) {
                $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['postname']);
            }
            if ($_SESSION['faktura']['postatt']) {
                $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['postatt']);
            }
            if ($_SESSION['faktura']['postaddress']) {
                $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['postaddress']);
            }
            if ($_SESSION['faktura']['postaddress2']) {
                $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['postaddress2']);
            }
            if ($_SESSION['faktura']['postpostbox']) {
                $emailbody .= '<br />'.xhtmlEsc($_SESSION['faktura']['postpostbox']);
            }
            if ($_SESSION['faktura']['postcity']) {
                $emailbody .= '<br />' . xhtmlEsc($_SESSION['faktura']['postpostalcode'] . ' ' . $_SESSION['faktura']['postcity']);
            }
            if ($_SESSION['faktura']['postcountry'] != 'DK') {
                $emailbody .= '<br />'
                . _($countries[$_SESSION['faktura']['postcountry']]);
            }
            $emailbody .= '</p>';
        }

        //Admin link
        $msg = sprintf(
            _('Click <a href="%s">here</a> to expedite the order.'),
            Config::get('base_url') . '/admin/faktura.php?id=' . $id
        );
        $emailbody .= '<p>' . $msg . '</p>';

        //Contact
        $emailbody .= '<p>' . _('email:') .
        ' <a href="mailto:' . xhtmlEsc($_SESSION['faktura']['email']) . '">'
        . xhtmlEsc($_SESSION['faktura']['email']) . '</a>';
        if ($_SESSION['faktura']['tlf1']) {
            $emailbody .= ' '.xhtmlEsc(_('Phone:')).' '.xhtmlEsc($_SESSION['faktura']['tlf1']);
        }
        if ($_SESSION['faktura']['tlf2']) {
            $emailbody .= ' '._('Mobil:').' '.xhtmlEsc($_SESSION['faktura']['tlf2']);
        }
        $emailbody .= '</p><p>' . _('Sincerely the computer')
        . '</p></body></html></body></html>';

        sendEmails(
            _('Online order #') . $id,
            $emailbody,
            $_SESSION['faktura']['email'],
            $_SESSION['faktura']['navn']
        );

        Render::$title = _('Order placed');
        Render::$headline = _('Order placed');
        Render::$bodyHtml = _('Thank you for your order, you will recive an email with instructions on how to perform the payment as soon as we have validated that all goods are in stock.');

        session_destroy();
    }
} else {
    Render::$title = _('Place order');
    Render::$headline = _('Place order');
    Render::$bodyHtml = _('Ther is no content in the basket!');
}

Render::outputPage();
