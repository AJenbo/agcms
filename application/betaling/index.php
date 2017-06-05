<?php

use AGCMS\Config;
use AGCMS\Entity\CustomPage;
use AGCMS\ORM;
use AGCMS\Render;

/**
 * Pages for taking the user thew the payment process
 */

require_once __DIR__ . '/../inc/Bootstrap.php';
@include_once _ROOT_ . '/inc/countries.php';

$id = intval($_GET['id'] ?? null);
$checkid = $_GET['checkid'] ?? '';

Render::$pageType = 'custome';
Render::$crumbs = [
    [
        'name' => _('Payment'),
        'link' => '/betaling/' . ($id ? '?id=' . $id . '&checkid=' . rawurlencode($checkid) : ''),
        'icon' => null,
    ]
];

$productslines = 0;
if (!empty($id) && $checkid === getCheckid($id) && !isset($_GET['txnid'])) {
    $rejected = [];
    $faktura = db()->fetchOne(
        "
        SELECT *
        FROM `fakturas`
        WHERE `id` = ".$id
    );
    Render::addLoadedTable('fakturas');

    if (in_array($faktura['status'], ['new', 'locked', 'pbserror'])) {
        $faktura['quantities'] = explode('<', $faktura['quantities']);
        $faktura['products'] = explode('<', $faktura['products']);
        $faktura['products'] = array_map('htmlspecialchars_decode', $faktura['products']);
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

        if (empty($_GET['step'])) { //Show order
            db()->query(
                "
                UPDATE `fakturas`
                SET `status` = 'locked'
                WHERE `status` IN('new', 'pbserror')
                  AND `id` = " . $id
            );
            Render::addLoadedTable('fakturas');

            Render::$crumbs = [
                [
                    'name' => _('Order #') . $id,
                    'link' => urldecode($_SERVER['REQUEST_URI']),
                    'icon' => null,
                ]
            ];
            Render::$title = _('Order #') . $id;
            Render::$headline = _('Order #').$id;

            Render::$bodyHtml = '<table id="faktura" cellspacing="0"><thead><tr><td class="td1">' . _('Quantity')
                . '</td><td>' . _('Title') . '</td><td class="td3 tal">' . _('unit price') . '</td><td class="td4 tal">'
                . _('Total') . '</td></tr></thead><tfoot><tr style="height:auto;min-height:auto;max-height:auto;"><td>&nbsp;</td><td>&nbsp;</td><td class="tal">'
                . _('Net Amount') . '</td><td class="tal">' . number_format($netto, 2, ',', '')
                . '</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td><td class="tal">' . _('Freight')
                . '</td><td class="tal">' . number_format($faktura['fragt'], 2, ',', '')
                . '</td></tr><tr><td>&nbsp;</td><td style="text-align:right" class="tal">' . ($faktura['momssats']*100)
                . '%</td><td class="tal">' . _('VAT Amount') . '</td><td class="tal">'
                . number_format($netto*$faktura['momssats'], 2, ',', '')
                . '</td></tr><tr class="border"><td colspan="2">' . _('All figures are in DKK')
                . '</td><td style="text-align:center; font-weight:bold;">' . _('TO PAY') . '</td><td class="tal"><big>'
                . number_format($faktura['amount'], 2, ',', '') . '</big></td></tr></tfoot><tbody>';
            for ($i=0; $i<$productslines; $i++) {
                Render::$bodyHtml .= '<tr><td class="tal">' . $faktura['quantities'][$i] . '</td><td>'
                    . xhtmlEsc($faktura['products'][$i]) . '</td><td class="tal">'
                    . number_format($faktura['values'][$i]*(1+$faktura['momssats']), 2, ',', '')
                    . '</td><td class="tal">'
                    . number_format($faktura['values'][$i]*(1+$faktura['momssats'])*$faktura['quantities'][$i], 2, ',', '')
                    . '</td></tr>';
            }

            Render::$bodyHtml .= '</tbody></table>';

            if ($faktura['note']) {
                Render::$bodyHtml .= '<br /><strong>'._('Note:').'</strong><br /><p class="note">';
                Render::$bodyHtml .= nl2br(xhtmlEsc($faktura['note'])).'</p>';
            }
            Render::$bodyHtml .= '<form action="" method="get"><input type="hidden" name="id" value="' . $id
                . '" /><input type="hidden" name="checkid" value="' . xhtmlEsc($checkid)
                . '" /><input type="hidden" name="step" value="1" /><input style="font-weight:bold;" type="submit" value="'
                . _('Continue') . '" /></form>';
        } elseif ($_GET['step'] == 1) { //Fill out customer info
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
                    $sql .= " `" . addcslashes($key, '`\\') . "` = '" . addcslashes($value, "'\\") . "',";
                }
                $sql = substr($sql, 0, -1);

                $sql .= 'WHERE `id` = ' . $id;

                db()->query($sql);
                Render::addLoadedTable('fakturas');

                $faktura = array_merge($faktura, $updates);

                //TODO move down to skip address page if valid
                if (!count($rejected)) {
                    if (!empty($_POST['newsletter']) ? 1 : 0) {
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
                        Render::addLoadedTable('email');
                    }

                    redirect('/betaling/?id=' . $id . '&checkid=' . $checkid . '&step=2');
                }
            } else {
                $rejected = validate($faktura);
            }

            //TODO add enote
            Render::$crumbs[] = [
                'name' => _('Recipient'),
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ];
            Render::$title = _('Recipient');
            Render::$headline = _('Recipient');

            Render::$bodyHtml = '<script type="text/javascript"><!--
window.history.forward(1);
--></script><script type="text/javascript" src="javascript.js"></script><script type="text/javascript" src="/javascript/zipcodedk.js"></script><form action="" method="post" onsubmit="return validateaddres()"><table><tbody><tr><td> '
                . _('Phone:') . '</td><td colspan="2"><input name="tlf1" id="tlf1" style="width:157px" value="'
                . xhtmlEsc($faktura['tlf1']) . '" /></td><td><input type="button" value="' . _('Get address')
                . '" onclick="getAddress(document.getElementById(\'tlf1\').value, getAddress_r1);" /></td></tr><tr><td> '
                . _('Mobile:') . '</td><td colspan="2"><input name="tlf2" id="tlf2" style="width:157px" value="'
                . xhtmlEsc($faktura['tlf2']) . '" /></td><td><input type="button" value="' . _('Get address')
                . '" onclick="getAddress(document.getElementById(\'tlf2\').value, getAddress_r1);" /></td></tr><tr><td>'
                . _('Name:') . '</td><td colspan="2"><input name="navn" id="navn" style="width:157px" value="'
                .xhtmlEsc($faktura['navn']) . '" /></td><td>';
            if (!empty($rejected['navn'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr><td> ' . _('Name:')
                . '</td><td colspan="2"><input name="att" id="att" style="width:157px" value="'
                . xhtmlEsc($faktura['att']) . '" /></td><td></td></tr><tr><td> ' ._('Address:')
                . '</td><td colspan="2"><input name="adresse" id="adresse" style="width:157px" value="'
                . xhtmlEsc($faktura['adresse']) . '" /></td><td>';
            if (!empty($rejected['adresse'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr><td> ' . _('Postbox:')
                . '</td><td colspan="2"><input name="postbox" id="postbox" style="width:157px" value="'
                . xhtmlEsc($faktura['postbox']) . '" /></td><td></td></tr><tr><td> ' . _('Zipcode:')
                . '</td><td><input name="postnr" id="postnr" style="width:35px" value="' . xhtmlEsc($faktura['postnr'])
                . '" onblur="chnageZipCode(this.value, \'land\', \'by\')" onkeyup="chnageZipCode(this.value, \'land\', \'by\')" onchange="chnageZipCode(this.value, \'land\', \'by\')" /></td><td align="right">'
                . _('City:') . '<input name="by" id="by" style="width:90px" value="'
                . xhtmlEsc($faktura['by']) . '" /></td><td>';
            if (!empty($rejected['postnr'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            if (!empty($rejected['by'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr><td> ' . _('Country:')
                . '</td><td colspan="2"><select name="land" id="land" style="width:157px" onblur="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onkeyup="chnageZipCode($(\'postnr\').value, \'land\', \'by\')" onchange="chnageZipCode($(\'postnr\').value, \'land\', \'by\')">';

            foreach ($countries as $code => $country) {
                Render::$bodyHtml .= '<option value="'.$code.'"';
                if ($faktura['land'] == $code) {
                    Render::$bodyHtml .= ' selected="selected"';
                }
                Render::$bodyHtml .= '>' . xhtmlEsc($country).'</option>';
            }
            Render::$bodyHtml .= '</select></td><td>';
            if (!empty($rejected['land'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr><td> ' . _('E-mail:')
                . '</td><td colspan="2"><input name="email" id="email" style="width:157px" value="'
                . xhtmlEsc($faktura['email']) . '" /></td><td>';
            if (!empty($rejected['email'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr><td colspan="4"><input onclick="showhidealtpost(this.checked);" name="altpost" id="altpost" type="checkbox"';
            if (!empty($faktura['altpost'])) {
                Render::$bodyHtml .= ' checked="checked"';
            }
            Render::$bodyHtml .= ' /><label for="altpost"> ' . _('Other delivery address')
                . '</label></td></tr><tr class="altpost"';
            if (empty($faktura['altpost'])) {
                Render::$bodyHtml .= ' style="display:none;"';
            }
            Render::$bodyHtml .= '><td> ' . _('Phone:')
                . '</td><td colspan="2"><input name="posttlf" id="posttlf" style="width:157px" value="'
                . xhtmlEsc($faktura['posttlf']) . '" /></td><td><input type="button" value="' . _('Get address')
                . '" onclick="getAddress(document.getElementById(\'posttlf\').value, getAddress_r2);" /></td></tr><tr class="altpost"';
            if (empty($faktura['altpost'])) {
                Render::$bodyHtml .= ' style="display:none;"';
            }
            Render::$bodyHtml .= '><td>' . _('Name:')
                . '</td><td colspan="2"><input name="postname" id="postname" style="width:157px" value="'
                . xhtmlEsc($faktura['postname']) . '" /></td><td>';
            if (!empty($rejected['postname'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr class="altpost"';
            if (empty($faktura['altpost'])) {
                Render::$bodyHtml .= ' style="display:none;"';
            }
            Render::$bodyHtml .= '><td> ' . _('Attn.:')
                . '</td><td colspan="2"><input name="postatt" id="postatt" style="width:157px" value="'
                . xhtmlEsc($faktura['postatt']) . '" /></td><td></td></tr><tr class="altpost"';
            if (empty($faktura['altpost'])) {
                Render::$bodyHtml .= ' style="display:none;"';
            }
            Render::$bodyHtml .= '><td> ' . _('Address:')
                . '</td><td colspan="2"><input name="postaddress" id="postaddress" style="width:157px" value="'
                . xhtmlEsc($faktura['postaddress'])
                . '" /><br /><input name="postaddress2" id="postaddress2" style="width:157px" value="'
                . xhtmlEsc($faktura['postaddress2']) . '" /></td><td>';
            if (!empty($rejected['postaddress'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr class="altpost"';
            if (empty($faktura['altpost'])) {
                Render::$bodyHtml .= ' style="display:none;"';
            }
            Render::$bodyHtml .= '><td> ' . _('Postbox:')
                . '</td><td colspan="2"><input name="postpostbox" id="postpostbox" style="width:157px" value="'
                . xhtmlEsc($faktura['postpostbox']) . '" /></td><td></td></tr><tr class="altpost"';
            if (empty($faktura['altpost'])) {
                Render::$bodyHtml .= ' style="display:none;"';
            }
            Render::$bodyHtml .= '><td> ' . _('Zipcode:')
                . '</td><td><input name="postpostalcode" id="postpostalcode" style="width:35px" value="'
                . xhtmlEsc($faktura['postpostalcode'])
                . '" onblur="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode(this.value, \'postcountry\', \'postcity\')" onchange="chnageZipCode(this.value, \'postcountry\', \'postcity\')" /></td><td align="right">'
                . _('City:') . '<input name="postcity" id="postcity" style="width:90px" value="'
                . xhtmlEsc($faktura['postcity']) . '" /></td><td>';
            if (!empty($rejected['postpostalcode'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            if (!empty($rejected['postcity'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr class="altpost"';
            if (empty($faktura['altpost'])) {
                Render::$bodyHtml .= ' style="display:none;"';
            }
            Render::$bodyHtml .= '><td> '. _('Country:')
                . '</td><td colspan="2"><select name="postcountry" id="postcountry" style="width:157px" onblur="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onkeyup="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')" onchange="chnageZipCode($(\'postpostalcode\').value, \'postcountry\', \'postcity\')">';

            foreach ($countries as $code => $country) {
                Render::$bodyHtml .= '<option value="'.$code.'"';
                if ($faktura['postcountry'] == $code) {
                    Render::$bodyHtml .= ' selected="selected"';
                }
                Render::$bodyHtml .= '>'.xhtmlEsc($country).'</option>';
            }
            Render::$bodyHtml .= '</select></td><td>';
            if (!empty($rejected['postcountry'])) {
                Render::$bodyHtml .= '<img src="images/error.png" alt="" title="" >';
            }
            Render::$bodyHtml .= '</td></tr><tr>
                <td colspan="4"><input name="newsletter" id="newsletter" type="checkbox"';
            if (!empty($_POST['newsletter'])) {
                Render::$bodyHtml .= ' checked="checked"';
            }
            Render::$bodyHtml .= ' /><label for="newsletter"> ' . _('Please send me your newsletter.')
                . '</label></td></tr></tbody></table><input style="font-weight:bold;" type="submit" value="'
                ._('Proceed to the terms of trade') . '" /></form>';
        } elseif ($_GET['step'] == 2) { //Accept terms and continue to payment
            if (count(validate($faktura))) {
                redirect('/betaling/?id=' . $id . '&checkid=' . $checkid . '&step=1');
            }

            db()->query(
                "
                UPDATE `fakturas`
                SET `status` = 'locked'
                WHERE `status` IN('new', 'pbserror')
                AND `id` = " . $id
            );
            Render::addLoadedTable('fakturas');

            Render::$crumbs[] = [
                'name' => _('Trade Conditions'),
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ];
            Render::$title = _('Trade Conditions');
            Render::$headline = _('Trade Conditions');
            Render::$bodyHtml .= '<br />' . ORM::getOne(CustomPage::class, 3)->getHtml()
                . '<form style="text-align:center;" action="https://ssl.ditonlinebetalingssystem.dk/integration/ewindow/Default.aspx" method="post">';

            $submit = [
                'group'             => Config::get('pbsfix'),
                'merchantnumber'    => Config::get('pbsid'),
                'orderid'           => Config::get('pbsfix') . $faktura['id'],
                'currency'          => 208,
                'amount'            => number_format($faktura['amount'], 2, '', ''),
                'ownreceipt'        => 1,
                'accepturl'         => Config::get('base_url') . '/betaling/?id=' . $id . '&checkid=' . $checkid,
                'cancelurl'         => Config::get('base_url') . $_SERVER['REQUEST_URI'],
                'windowstate'       => 3,
                'windowid'          => Config::get('pbswindow'),
            ];
            foreach ($submit as $key => $value) {
                Render::$bodyHtml .= '<input type="hidden" name="'
                .$key.'" value="'.xhtmlEsc($value).'" />';
            }
            Render::$bodyHtml .= '<input type="hidden" name="hash" value="'
            .md5(implode('', $submit).Config::get('pbspassword')).'" />';

            Render::$bodyHtml .= '<input class="web" type="submit" value="' . _('I hereby agree to the terms of trade')
                . '" />';
            Render::$bodyHtml .= '</form>';
        }
    } else { //Show order status
        if (in_array($faktura['status'], ['pbsok', 'accepted'], true)) {
            Render::$crumbs[] = [
                'name' => _('Status'),
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ];
        } else {
            Render::$crumbs[] = [
                'name' => _('Error'),
                'link' => urldecode($_SERVER['REQUEST_URI']),
                'icon' => null,
            ];
        }
        Render::$title = _('Error');
        Render::$headline = _('Error');
        if ($faktura['status'] == 'pbsok') {
            Render::$title = _('Status');
            Render::$headline = _('Status');
            Render::$bodyHtml = _('Payment received.');
        } elseif ($faktura['status'] == 'accepted') {
            Render::$title = _('Status');
            Render::$headline = _('Status');
            Render::$bodyHtml = _('The payment was received and the package is sent.');
        } elseif ($faktura['status'] == 'giro') {
            Render::$bodyHtml = _('The payment is already received in cash.');
        } elseif ($faktura['status'] == 'cash') {
            Render::$bodyHtml = _('The payment is already received in cash.');
        } elseif ($faktura['status'] == 'canceled') {
            Render::$bodyHtml = _('The transaction is canceled.');
        } elseif ($faktura['status'] == 'rejected') {
            Render::$bodyHtml = _('Payment rejected.');
        } else {
            Render::$bodyHtml = _('An errror occured.');
        }
    }
} elseif (isset($_GET['txnid'])) {
    Render::$crumbs = [
        [
            'name' => _('Error'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ]
    ];
    Render::$title = _('Error');
    Render::$headline = _('Error');
    Render::$bodyHtml = _('An unknown error occured.');

    $tid = intval($_GET['txnid'] ?? 0);
    $amount = intval($_GET['amount'] ?? 0);

    $params = $_GET;
    unset($params['hash']);
    $eKey = md5(implode('', $params) . Config::get('pbspassword'));
    unset($params);

    $shopSubject = _('Payment code was tampered with!');
    $shopBody = '<br />'.sprintf(_('There was an error on the payment page of online invoice #%d!'), $id).'<br />';

    $faktura = db()->fetchOne("SELECT * FROM `fakturas` WHERE `id` = " . $id);
    Render::addLoadedTable('fakturas');

    if (!$faktura) {
        Render::$bodyHtml = '<p>' . _('The payment does not exist in our system.') . '</p>';
        $shopBody = '<br />' . sprintf(
            _('A user tried to pay online invoice #%d, which is not in the system!'),
            $id
        ) . '<br />';
    } elseif (in_array($faktura['status'], ['canceled', 'rejected'])) {
        Render::$crumbs[] = [
            'name' => _('Reciept'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ];
        Render::$title = _('Reciept');
        Render::$headline = _('Reciept');
        Render::$bodyHtml = '<p>'._('This trade has been canceled or refused.').'</p>';
        $shopBody = '<br />'
            . sprintf(
                _('A customer tried to see the status page for online invoice #%d which is canceled or rejected.'),
                $id
            )
            . '<br />';
    } elseif (!in_array($faktura['status'], ['locked', 'new', 'pbserror'])) {
        Render::$crumbs[] = [
            'name' => _('Reciept'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ];
        Render::$title = _('Reciept');
        Render::$headline = _('Reciept');
        Render::$bodyHtml = '<p>'._('Payment is registered and you ought to have received a receipt by email.').'</p>';
        $shopBody = '<br />' . sprintf(
            _('A customer tried to see the status page for online invoice #%d, which is already paid.'),
            $id
        ) . '<br />';
    } elseif ($eKey == $_GET['hash']) {
        Render::$crumbs[] = [
            'name' => _('Reciept'),
            'link' => urldecode($_SERVER['REQUEST_URI']),
            'icon' => null,
        ];
        Render::$title = _('Reciept');
        Render::$headline = _('Reciept');

        $cardtype = [
            1  => 'Dankort/Visa-Dankort',
            2  => 'eDankort',
            3  => 'Visa / Visa Electron',
            4  => 'MastercCard',
            6  => 'JCB',
            7  => 'Maestro',
            8  => 'Diners Club',
            9  => 'American Express',
            11 => 'Forbrugsforeningen',
            12 => 'Nordea e-betaling',
            13 => 'Danske Netbetalinger',
            14 => 'PayPal',
            17 => 'Klarna',
            18 => 'SveaWebPay',
            23 => 'ViaBill',
            24 => 'NemPay',
        ];

        db()->query(
            "
            UPDATE `fakturas`
            SET `status` = 'pbsok',
            `cardtype` = '" . $cardtype[$_GET['paymenttype']] . "',
            `paydate` = NOW()
            WHERE `status` IN('new', 'locked', 'pbserror')
            AND `id` = " . $id
        );
        Render::addLoadedTable('fakturas');

        $faktura = db()->fetchOne(
            "
            SELECT *
            FROM `fakturas`
            WHERE `id` = " . $id
        );
        Render::addLoadedTable('fakturas');

        Render::$bodyHtml = _(
            '<p style="text-align:center;"><img src="images/ok.png" alt="" /></p>

<p>Payment is now accepted. We will send your goods by mail as soon as possible.</p>

<p>A copy of your order is sent to your email.</p>'
        );

        $faktura['quantities'] = explode('<', $faktura['quantities']);
        $faktura['products'] = explode('<', $faktura['products']);
        $faktura['products'] = array_map('htmlspecialchars_decode', $faktura['products']);
        $faktura['values'] = explode('<', $faktura['values']);

        if ($faktura['premoms']) {
            foreach ($faktura['values'] as $key => $value) {
                $faktura['values'][(int) $key] = $value/1.25;
            }
        }

        $shopSubject = _('Payment complete');
        $shopBody = _(
            'The customer has approved the payment and the following order must be shipped to the customer.<br />
<br />
Remember to \'expedite\' the payment when the product is sent (The payment is first transferred from the customer\'s account once we hit \'Expedite\').'
        ) .'<br />';

        $withTax = $faktura['amount'] - $faktura['fragt'];
        $tax = $withTax * (1 - (1 / (1 + $faktura['momssats'])));

        Render::$track = "ga('ecommerce:addTransaction',{'id':'" . (int) $faktura['id']
        . "','revenue':'" . $faktura['amount']
        . "','shipping':'" . $faktura['fragt']
        . "','tax':'" . $tax . "'});";
        foreach ($faktura['products'] as $key => $product) {
            Render::$track .= "ga('ecommerce:addItem',{'id':'" . $faktura['id']
            . "','name':" . json_encode($product)
            . ",'price': '" . ($faktura['values'][$key] * (1 + $faktura['momssats']))
            . "','quantity': '" . $faktura['quantities'][$key] . "'});";
        }
        Render::$track .= "ga('ecommerce:send');";

    //Mail to customer start
        $emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>';
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
        . xhtmlEsc($faktura['navn']) . '</td>';
        if ($faktura['altpost']) {
            $emailbody_address .= '<td>' . xhtmlEsc($faktura['postname']) . '</td>';
        }
        $emailbody_address .= '</tr>';
        if ($faktura['tlf1'] || ($faktura['altpost'] && $faktura['posttlf'])) {
            $emailbody_address .= '<tr><td>' . _('Phone:') . '</td><td>'
            . xhtmlEsc($faktura['tlf1']) . '</td>';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>' . xhtmlEsc($faktura['posttlf']) . '</td>';
            }
            $emailbody_address .= '</tr>';
        }
        if ($faktura['att'] || ($faktura['altpost'] && $faktura['postatt'])) {
            $emailbody_address .= '<tr><td>' . _('Attn.:') . '</td><td>'
            . xhtmlEsc($faktura['att']) . '</td>';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>'.xhtmlEsc($faktura['postatt']).'</td>';
            }
            $emailbody_address .= '</tr>';
        }
        if ($faktura['adresse']
        || ($faktura['adresse'] && ($faktura['postaddress'] || $faktura['postaddress2']))
        ) {
            $emailbody_address .= '<tr><td>' . _('Address:') . '</td><td>'
            . xhtmlEsc($faktura['adresse']) . '</td>';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>' . xhtmlEsc($faktura['postaddress']) . '<br />'
                . xhtmlEsc($faktura['postaddress2']) . '</td>';
            }
            $emailbody_address .= '</tr>';
        }
        if ($faktura['postbox']
        || ($faktura['altpost'] && $faktura['postpostbox'])
        ) {
            $emailbody_address .= '<tr><td>' . _('Postbox:') . '</td><td>'
            . xhtmlEsc($faktura['postbox']) . '</td>';
            if ($faktura['altpost']) {
                $emailbody_address .= '<td>' . xhtmlEsc($faktura['postpostbox']) . '</td>';
            }
            $emailbody_address .= '</tr>';
        }

        $emailbody_address .= '<tr><td>' . _('Zipcode:') . '</td><td>'
        . $faktura['postnr'] . '</td>';
        if ($faktura['altpost']) {
            $emailbody_address .= '<td>' . xhtmlEsc($faktura['postpostalcode']) . '</td>';
        }
        $emailbody_address .= '</tr><tr><td>' . _('City:') . '</td><td>'
        . xhtmlEsc($faktura['by']) . '</td>';
        if ($faktura['altpost']) {
            $emailbody_address .= '<td>' . xhtmlEsc($faktura['postcity']) . '</td>';
        }
        $emailbody_address .= '</tr><tr><td>' . _('Country:') . '</td><td>'
        . $countries[$faktura['land']] . '</td>';
        if ($faktura['altpost']) {
            $emailbody_address .= '<td>' . $countries[$faktura['postcountry']]
            . '</td>';
        }
        if ($faktura['tlf2']) {
            $emailbody_address .= '</tr><tr><td>' . _('Mobile:') . '</td><td>'
            . xhtmlEsc($faktura['tlf2']).'</td>';
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
            . xhtmlEsc($faktura['quantities'][$i]) . '</td><td>'
            . xhtmlEsc($faktura['products'][$i])
            . '</td><td class="tal">'
            . number_format($plusTax, 2, ',', '') . '</td><td class="tal">'
            . number_format($plusTax * $faktura['quantities'][$i], 2, ',', '')
            . '</td></tr>';
        }

        $emailbody_nore = '';
        if ($faktura['note']) {
            $emailbody_nore = '<br /><strong>' . _('Note:')
            . '</strong><br /><p class="note">';
            $note = xhtmlEsc($faktura['note']);
            $emailbody_nore .= nl2br($note) . '</p>';
        }

        if (!valideMail($faktura['department'])) {
            $faktura['department'] = first(Config::get('emails'))['address'];
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
            xhtmlEsc($faktura['paydate']),
            $emailbody_address,
            xhtmlEsc($faktura['email']),
            xhtmlEsc($faktura['email']),
            xhtmlEsc($faktura['id']),
            number_format($netto, 2, ',', ''),
            number_format($faktura['fragt'], 2, ',', ''),
            $faktura['momssats']*100,
            number_format($netto*$faktura['momssats'], 2, ',', ''),
            number_format($faktura['amount'], 2, ',', ''),
            $emailbody_tablerows,
            $emailbody_nore,
            xhtmlEsc($faktura['clerk']),
            xhtmlEsc(Config::get('site_name')),
            xhtmlEsc(Config::get('address')),
            xhtmlEsc(Config::get('postcode')),
            xhtmlEsc(Config::get('city')),
            xhtmlEsc(Config::get('phone')),
            xhtmlEsc($faktura['department']),
            xhtmlEsc($faktura['department'])
        );

        $emailbody .= '</body></html>';

        sendEmails(
            sprintf(_('Order #%d - payment completed'), $faktura['id']),
            $emailbody,
            $faktura['department'],
            '',
            $faktura['email'],
            $faktura['navn']
        );
    }

    //To shop
    $faktura = db()->fetchOne("SELECT * FROM `fakturas` WHERE `id` = ".$id);
    Render::addLoadedTable('fakturas');
    if (!valideMail($faktura['department'])) {
        $faktura['department'] = first(Config::get('emails'))['address'];
    }
    if ($faktura) {
        $faktura['quantities'] = explode('<', $faktura['quantities']);
        $faktura['products'] = explode('<', $faktura['products']);
        $faktura['products'] = array_map('htmlspecialchars_decode', $faktura['products']);
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
        for ($i=0; $i<$productslines; $i++) {
            $netto += $faktura['values'][$i]*$faktura['quantities'][$i];
        }

        $emailbody_tablerows = '';
        for ($i = 0; $i < $productslines; $i++) {
            $emailbody_tablerows .= '<tr><td class="tal">'
            . (int) $faktura['quantities'][$i] . '</td><td>'
            . xhtmlEsc($faktura['products'][$i])
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
        $emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>';
        $emailbody .= sprintf(
            _('Attn.: %s - Online invoice #%d : %s'),
            xhtmlEsc($faktura['clerk']),
            $id,
            xhtmlEsc($shopSubject)
        );
        $emailbody .= '</title><style type="text/css">
td {
    border:1px solid #000;
    border-collapse:collapse;
}
</style></head><body>';

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
            xhtmlEsc(Config::get('base_url')),
            $id,
            xhtmlEsc($faktura['email']),
            xhtmlEsc($faktura['email']),
            xhtmlEsc($faktura['tlf2']),
            xhtmlEsc($faktura['tlf1']),
            xhtmlEsc($faktura['posttlf']),
            number_format($netto, 2, ',', ''),
            number_format($faktura['fragt'], 2, ',', ''),
            $faktura['momssats']*100,
            number_format($netto*$faktura['momssats'], 2, ',', ''),
            number_format($faktura['amount'], 2, ',', ''),
            $emailbody_tablerows
        );

        $emailbody .= '</body></html>';
    } else {
        $emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0'
            . ' Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html'
            . ' xmlns="http://www.w3.org/1999/xhtml"><head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>'
            . sprintf(_('Online Invoice #%d: does\'t exist'), $id) . '</title></head><body>' . $shopBody . '<br />'
            . _('Status:') . ' ' . xhtmlEsc($_GET['responseCode'])
            . '<p>' . _('Sincerely the computer') . '</p></body></html></body></html>';
    }

    if (!empty($faktura)) {
        sendEmails(
            sprintf(_('Attn.: %s - Online invoice #%d : %s'), $faktura['clerk'], $id, $shopSubject),
            $emailbody,
            $faktura['department'],
            '',
            $faktura['department']
        );
    }
} else {
    Render::$title = _('Payment');
    Render::$headline = _('Payment');

    Render::$bodyHtml = '<form action="" method="get">
      <table>
        <tbody>
          <tr>
            <td>'._('Order No:').'</td>
            <td><input name="id" value="' . ($id ?: '') . '" /></td>
          </tr>
          <tr>
            <td>'._('Code:').'</td>
            <td><input name="checkid" value="'.xhtmlEsc($checkid).'" /></td>
          </tr>
        </tbody>
      </table><input type="submit" value="'._('Continue').'" />
    </form>';
    if ($checkid) {
        Render::$bodyHtml = _('The code is not correct!');
    }
}

Render::outputPage();
