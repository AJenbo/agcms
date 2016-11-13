<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/logon.php';
include_once _ROOT_ . '/inc/countries.php';

if (!empty($_GET['function']) && $_GET['function'] == 'new') {
    redirect('faktura.php?id='.newfaktura());
}

$faktura = db()->fetchOne(
    "
    SELECT*,
        UNIX_TIMESTAMP(`date`) AS `date`,
        UNIX_TIMESTAMP(`paydate`) AS `paydate`
    FROM `fakturas` WHERE `id` = " . (int) $_GET['id']
);

$faktura['quantities'] = explode('<', $faktura['quantities']);
$faktura['products'] = explode('<', $faktura['products']);
$faktura['values'] = explode('<', $faktura['values']);

if ($faktura['premoms']) {
    foreach ($faktura['values'] as $key => $value) {
        $faktura['values'][$key] = $value / 1.25;
    }
}

if ($faktura['id'] && $faktura['status'] != 'new') {
    $epayment = new EpaymentAdminService(
        Config::get('pbsid'),
        Config::get('pbspwd'),
        Config::get('pbsfix') . $faktura['id']
    );

    if ($epayment->isAnnulled()) {
        //Annulled. The card payment has been deleted by the Merchant, prior to Acquisition.
        if (!in_array($faktura['status'], ['rejected', 'giro', 'cash', 'canceled'])) {
            $faktura['status'] = 'rejected';
            db()->query(
                "
                UPDATE `fakturas` SET `status` = 'rejected'
                WHERE `id` = " . $faktura['id']
            );
        } else {
            //TODO warning
        }
    } elseif ($epayment->getAmountCaptured()) {
        //The payment/order placement has been carried out: Paid.
        if ($epayment->getAmountCaptured() / 100 != $faktura['amount']) {
            //TODO 'Det betalte beløb er ikke svarende til det opkrævede beløb!';
        } elseif (!in_array($faktura['status'], ['accepted', 'giro', 'cash'])) {
            $faktura['status'] = 'accepted';
            db()->query(
                "
                UPDATE `fakturas` SET `status` = 'accepted'
                WHERE `id` = ".$faktura['id']
            );
        } else {
            //TODO warning
        }
    } elseif ($epayment->isAuthorized()) {
        //Authorised. The card payment is authorised and awaiting confirmation and Acquisition.
        if (!in_array($faktura['status'], ['pbsok', 'giro', 'cash'])) {
            $faktura['status'] = 'pbsok';
            db()->query(
                "
                UPDATE `fakturas` SET `status` = 'pbsok'
                WHERE `id` = " . $faktura['id']
            );
        } else {
            //TODO warning
        }
    } elseif (!$epayment->getId()) {
        if ($faktura['status'] == 'pbsok') {
            $faktura['status'] = 'locked';
            db()->query(
                "
                UPDATE `fakturas` SET `status` = 'locked'
                WHERE `id` = " . $faktura['id']
            );
        }
    }
}

Sajax\Sajax::export(
    [
        'getAddress'   => ['method' => 'GET'],
        'sendReminder' => ['method' => 'GET'],
        'valideMail'   => ['method' => 'GET'],
        'annul'        => ['method' => 'POST'],
        'copytonew'    => ['method' => 'POST'],
        'newfaktura'   => ['method' => 'POST'],
        'pbsconfirm'   => ['method' => 'POST'],
        'save'         => ['method' => 'POST'],
    ]
);
Sajax\Sajax::handleClientRequest();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link  href="style/calendar.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="/javascript/zipcodedk.js"></script>
<script type="text/javascript" src="javascript/calendar.js"></script>
<title><?php echo _('Online Invoice #').$faktura['id']; ?></title>
<link href="style/mainmenu.css" rel="stylesheet" type="text/css" />
<link href="style/faktura.css" rel="stylesheet" type="text/css" media="screen" />
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript"><!--
<?php Sajax\Sajax::showJavascript(); ?>

var id = <?php echo $faktura['id']; ?>;

function newfaktura()
{
    $('loading').style.visibility = '';
    x_newfaktura(newfaktura_r);
}
function copytonew()
{
    $('loading').style.visibility = '';
    x_copytonew(id, newfaktura_r);
}
function newfaktura_r(id)
{
     window.location.href = '?id='+id;
}

function removeRow(row)
{
    $('vareTable').removeChild(row.parentNode.parentNode);
    if ($('vareTable').childNodes.length == 0)
        addRow();
    prisUpdate();
}

function addRow()
{
    var tr = document.createElement('tr');
    var td = document.createElement('td');
    td.innerHTML = '<input name="quantitie" style="width:58px;" class="tal" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />';
    tr.appendChild(td);
    td = document.createElement('td');
    td.innerHTML = '<input name="product" style="width:303px;" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />';
    tr.appendChild(td);
    td = document.createElement('td');
    td.innerHTML = '<input name="value" style="width:69px;" class="tal" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />';
    tr.appendChild(td);
    td = document.createElement('td');
    td.className = 'tal total';
    tr.appendChild(td);
    td = document.createElement('td');
    td.className = 'web';
    td.style.border = '0';
    td.style.fontWeight = 'bold';
    td.innerHTML = '<a href="#" onclick="removeRow(this); return false"><img alt="X" src="images/cross.png" height="16" width="16" title="<?php echo _('Remove Line'); ?>" /></a>';
    tr.appendChild(td);
    $('vareTable').appendChild(tr);
}

function getAddress(tlf)
{
    $('loading').style.visibility = '';
    x_getAddress(tlf, getAddress_r);
}

function getAddress_r(data)
{
    if (data['error']) {
        alert(data['error']);
    } else {
        $('navn').value = data['recName1'];
        $('att').value = data['recAttPerson'];
        $('adresse').value = data['recAddress1'];
        $('postnr').value = data['recZipCode'];
        //TODO 'by' might not be danish!
        var zip = arrayZipcode[data['recZipCode']];
        if (zip != 'undefined') $('by').value = zip;
        $('postbox').value = data['recPostBox'];
        $('email').value = data['email'];
        //TODO support more values
        //TODO setEmailLink();
    }
    $('loading').style.visibility = 'hidden';
}

function getAltAddress(tlf)
{
    $('loading').style.visibility = '';
    x_getAddress(tlf, getAltAddress_r);
}

function getAltAddress_r(data)
{
    if (data['error']) {
        alert(data['error']);
    } else {
        $('postname').value = data['recName1'];
        $('postatt').value = data['recAttPerson'];
        $('postaddress').value = data['recAddress1'];
        $('postpostalcode').value = data['recZipCode'];
        //TODO 'by' might not be danish!
        var zip = arrayZipcode[data['recZipCode']];
        if (zip != 'undefined') $('postcity').value = zip;
        $('postpostbox').value = data['recPostBox'];
        //TODO support more values
        //TODO setEmailLink();
    }
    $('loading').style.visibility = 'hidden';
}

function prisUpdate()
{
    quantities = '';
    products = '';
    values = '';
    amount = 0;

    var quantitieObjs = document.getElementsByName('quantitie');
    var productObjs = document.getElementsByName('product');
    var valueObjs = document.getElementsByName('value');
    var totalObjs = $$('.total');
    var premoms = $('premoms').checked;
    var momssats = parseFloat($('momssats').value);

    var netto = 0;

    var quantitie;
    var value;
    var total;

    for(var i=0;i<quantitieObjs.length;i++) {
        quantitie = 0;
        value = 0;
        total = 0;
        quantitie = parseInt(quantitieObjs[i].value);
        if (isNaN(quantitie))
            quantitie = 0;

        value = parseFloat(parseFloat(valueObjs[i].value.replace(/[^-0-9,]/g,'').replace(/,/,'.')).toFixed(2));

        if (isNaN(value))
            value = 0;

        if (premoms)
            value = value/1.25;
        //  value = value/(1+momssats);

        total = quantitie*value;

        if (total != 0) {
            if (premoms)
                totalObjs[i].innerHTML = (total*1.25).toFixed(2).toString().replace(/\./,',');
            else
                totalObjs[i].innerHTML = total.toFixed(2).toString().replace(/\./,',');
        } else {
            totalObjs[i].innerHTML = '';
        }

        netto += total;

        if (quantitieObjs[i].value != '' || productObjs[i].value != '' || valueObjs[i].value != '') {
            if (quantities != '') {
                quantities += '<';
                products += '<';
                values += '<';
            }
            quantities +=  quantitie.toString();
            products +=  htmlspecialchars(productObjs[i].value.toString());
            if (premoms)
                values += (value*1.25).toString();
            else
                values += value.toString();
        }
    }

    $('netto').innerHTML = netto.toFixed(2).toString().replace(/\./,',');

    $('moms').innerHTML = (netto*momssats).toFixed(2).toString().replace(/\./,',');

    var fragt = parseFloat($('fragt').value.replace(/[^-0-9,]/g,'').replace(/,/,'.'));
    if (isNaN(fragt))
        fragt = 0;

    amount = parseFloat(fragt + netto + netto * momssats).toFixed(2).toString().replace(/\./,',');
    $('payamount').innerHTML = amount;

    if (quantitieObjs[quantitieObjs.length-1].value != '' || productObjs[productObjs.length-1].value != '' || valueObjs[valueObjs.length-1].value != '')
        addRow();

    return true;
}

function pbsconfirm()
{
    $('loading').style.visibility = '';
    //TODO save comment
    x_pbsconfirm(id, reload_r);
}

function annul()
{
    $('loading').style.visibility = '';
    //TODO save comment
    x_annul(id, reload_r);
}

function reload_r(date)
{
    if (date['error']) {
        alert(date['error']);
    } else {
        window.location.reload();
    }
    $('loading').style.visibility = 'hidden';
}

function save(type)
{
    if (type == null) {
        type = 'save';
    }

    if (type == 'cancel' && !confirm('<?php echo _('Are you sure you want to cancel this Invoice?'); ?>')) {
        return false;
    }

    $('loading').style.visibility = '';
    var update = {};
    if (status == 'new') {
        update['quantities'] = quantities;
        update['products'] = products;
        update['values'] = values;
        update['fragt'] = $('fragt').value.replace(/[^-0-9,]/g,'').replace(/,/,'.');
        update['amount'] = amount.replace(/[^-0-9,]/g,'').replace(/,/,'.');
        update['momssats'] = $('momssats').value;
        update['premoms'] = $('premoms').checked ? 1 : 0;
        update['date'] = $('date').value;
        update['iref'] = $('iref').value;
        update['eref'] = $('eref').value;
        update['navn'] = $('navn').value;
        update['att'] = $('att').value;
        update['adresse'] = $('adresse').value;
        update['postbox'] = $('postbox').value;
        update['postnr'] = $('postnr').value;
        update['by'] = $('by').value;
        update['land'] = $('land').value;
        update['email'] = $('email').value;
        update['tlf1'] = $('tlf1').value;
        update['tlf2'] = $('tlf2').value;
        update['altpost'] = $('altpost').checked ? 1 : 0;
        if ($('altpost').value) {
            update['posttlf'] = $('posttlf').value;
            update['postname'] = $('postname').value;
            update['postatt'] = $('postatt').value;
            update['postaddress'] = $('postaddress').value;
            update['postaddress2'] = $('postaddress2').value;
            update['postpostbox'] = $('postpostbox').value;
            update['postpostalcode'] = $('postpostalcode').value;
            update['postcity'] = $('postcity').value;
            update['postcountry'] = $('postcountry').value;
        }
    }

    update['note'] = $('note').value;

    if ($('clerk')) {
        update['clerk'] = getSelectValue('clerk');
    }
    if ($('department')) {
        update['department'] = getSelectValue('department');
    }

    if (type == 'giro')
        update['paydate'] = $('gdate').value;

    if (type == 'cash')
        update['paydate'] = $('cdate').value;

    x_save(id, type, update, save_r);
}

function sendReminder()
{
    x_sendReminder(id, sendReminder_r);
}

function sendReminder_r(data)
{
    alert(data['error']);
}

function save_r(date)
{
    if (date['error'])
        alert(date['error']);

    if (date['status'] != status ||
        date['type'] == 'faktura' ||
        date['type'] == 'lock' ||
        date['type'] == 'cancel' ||
        date['type'] == 'giro' ||
        date['type'] == 'cash') {
        window.location.reload();
    }

    if (date['status'] != 'new') {
        if ($('clerk'))
            $$('.clerk')[0].innerHTML = $('clerk').value;
        if ($('note').value) {
            $$('.note')[0].innerHTML += '<br />'+nl2br($('note').value);
            $$('.note')[1].innerHTML += '<br />'+nl2br($('note').value);
            $('note').value = '';
        }
    }

    $('loading').style.visibility = 'hidden';
}

var validemailajaxcall;
var lastemail;

function valideMail()
{
    if ($('emaillink')) {
        if ($('email').value.match('^[A-z0-9_.-]+@([A-z0-9-]+\.)+[A-z0-9-]+$')) {
            if ($('email').value != lastemail || $('emaillink').style.display == 'none') {
                lastemail = $('email').value;
                if (validemailajaxcall)
                    sajax_cancel(validemailajaxcall);
                $('loading').style.visibility = '';
                valideMail_r(false);
                validemailajaxcall = x_valideMail($('email').value, valideMail_r);
            }
        } else {
            valideMail_r(false);
        }
    }
}

function valideMail_r(valideMail)
{
    if (valideMail) {
        $('emaillink').style.display = '';
    } else {
        $('emaillink').style.display = 'none';
    }
    $('loading').style.visibility = 'hidden';
}

function showhidealtpost(status)
{
    var altpostTrs = $$('.altpost');
    if (status) {
        for(var i = 0; i<altpostTrs.length; i++) {
            altpostTrs[i].style.display = '';
        }
    } else {
        for(var i = 0; i<altpostTrs.length; i++) {
            altpostTrs[i].style.display = 'none';
        }
    }
}

function chnageZipCode(zipcode, country, city)
{
    if ($(country).value == 'DK')
        if (!arrayZipcode[zipcode]) {
            $(city).value = '';
        } else {
            $(city).value = arrayZipcode[zipcode];
        }
}

var quantities;
var products;
var values;
var amount;
var status = '<?php echo $faktura['status']; ?>';

--></script>
</head><?php
echo '<body onload="';
if ($faktura['status'] == 'new') {
    echo 'showhidealtpost($(\'altpost\').checked); prisUpdate(); valideMail();';
}
echo '$(\'loading\').style.visibility = \'hidden\';">';
?><div id="canvas"><div id="web"><table style="float:right;"><?php
if (!in_array($faktura['status'], ['giro', 'cash', 'accepted', 'canceled', 'pbsok'])) {
    ?><tr>
    <td><input type="button" value="<?php echo _('Paid via giro'); ?>" onclick="save('giro');" /></td>
    <td><input maxlength="10" name="gdate" id="gdate" size="11" value="<?php echo date(_('m/d/Y')); ?>" />
        <script type="text/javascript"><!--
        new tcal ({ 'controlid': 'gdate' });
        --></script></td>
    </tr>
    <tr>
    <td><input type="button" value="<?php echo _('Paid in cash'); ?>" onclick="save('cash');" /></td>
    <td><input maxlength="10" name="cdate" id="cdate" size="11" value="<?php echo date(_('m/d/Y')); ?>" />
        <script type="text/javascript"><!--
        new tcal ({ 'controlid': 'cdate' });
        --></script></td>
        </tr><?php
}
if ($faktura['status'] == 'accepted') {
    ?><tr>
        <td><input type="button" value="Krediter beløb:" /></td>
        <td><input value="0,00" size="9" /></td>
    </tr><?php
}
?><tr>
    <td colspan="2">
        <p><strong><?php echo _('Note:'); ?></strong></p>
        <p class="note" style="width:350px"><?php
        if ($faktura['status'] != 'new') {
            echo nl2br(xhtmlEsc($faktura['note']));
        }
?></p><?php
$rows = count(explode("\n", $faktura['note']));
$rows += 2;
?><textarea style="width:350px" name="note" id="note" rows="<?php echo $rows; ?>"><?php
if ($faktura['status'] == 'new') {
    echo xhtmlEsc($faktura['note']);
}
?></textarea>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td><?php echo _('ID:'); ?></td>
            <td><?php echo $faktura['id']; ?></td>
        </tr>
        <tr>
            <td><?php echo _('eCode:'); ?></td>
            <td><?php echo getCheckid($faktura['id']); ?></td>
        </tr>
        <tr>
            <td><?php echo _('Status:'); ?></td>
        <td><?php
        if ($faktura['status'] == 'new') {
            echo _('Newly created');
        } elseif ($faktura['status'] == 'locked' && $faktura['sendt']) {
            echo _('Is sent to the Customer.');
        } elseif ($faktura['status'] == 'locked') {
            echo _('Locked for editing');
        } elseif ($faktura['status'] == 'pbsok') {
            echo _('Ready to expedite');
        } elseif ($faktura['status'] == 'accepted') {
            echo _('Paid online');
            if ($faktura['paydate']) {
                echo' d. '.date(_('m/d/Y'), $faktura['paydate']);
            }
        } elseif ($faktura['status'] == 'giro') {
            echo _('Paid via giro');
            if ($faktura['paydate']) {
                echo ' d. '.date(_('m/d/Y'), $faktura['paydate']);
            }
        } elseif ($faktura['status'] == 'cash') {
            echo _('Paid in cash');
            if ($faktura['paydate']) {
                echo ' d. '.date(_('m/d/Y'), $faktura['paydate']);
            }
        } elseif ($faktura['status'] == 'pbserror') {
            echo _('An error occurred');
        } elseif ($faktura['status'] == 'canceled') {
            echo _('Canceled');
        } elseif ($faktura['status'] == 'rejected') {
            echo _('Payment declined');
        } else {
            echo _('Does not exist in the system');
        }
?></td>
        </tr>
        <tr>
            <td><?php echo _('Recived:'); ?></td>
            <td><?php echo $faktura['transferred'] ? _('Yes') : _('No'); ?>
            <?php
            if ($_SESSION['_user']['access'] == 1 && $faktura['transferred']) {
                echo ' (<a href="fakturasvalidate.php?undoid='.$faktura['id'].'">'._('Remove').'</a>)';
            }
            ?>
            </td>
        </tr>
        <tr>
            <td>Oprettet:</td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input maxlength="10" name="date" id="date" size="11" value="<?php echo date(_('d/m/Y'), $faktura['date']); ?>" />
                <script type="text/javascript"><!--
                new tcal ({ 'controlid': 'date' });
                --></script>
                <?php
} else {
    echo date(_('m/d/Y'), $faktura['date']);
}
?></td></tr><?php
$users = db()->fetchArray("SELECT `fullname`, `name` FROM `users` ORDER BY `fullname` ASC");
//TODO block save if ! admin
?><tr>
    <td>Ansvarlig:</td>
    <td><?php
    if (count($users) > 1 && $_SESSION['_user']['access'] == 1
    && !in_array($faktura['status'], ['giro', 'cash', 'accepted', 'canceled'])
    ) {
        ?><select name="clerk" id="clerk">
        <option value=""<?php
        if (!$faktura['clerk']) {
            echo ' selected="selected"';
        }
        ?>><?php echo _('No one'); ?></option><?php
    $userstest = [];
foreach ($users as $user) {
    ?><option value="<?php echo $user['fullname']; ?>"<?php
if ($faktura['clerk'] == $user['fullname']) {
    echo ' selected="selected"';
}
?>><?php echo $user['fullname']; ?></option><?php
$userstest[] = $user['fullname'];
}

if ($faktura['clerk'] && !in_array($faktura['clerk'], $userstest)) {
    ?><option value="<?php echo $faktura['clerk'] ?>" selected="selected"><?php echo $faktura['clerk'] ?></option><?php
}
    ?></select><?php
    } else {
        echo $faktura['clerk'];
    }
?></td>
        </tr>
        <tr>
            <td>Afdeling:</td>
            <td><?php
            if (!in_array($faktura['status'], ['giro', 'cash', 'accepted', 'canceled'])) {
                if (count(Config::get('emails')) > 1) {
                    ?><select name="department" id="department">
                        <option value=""<?php
                        if (!$faktura['department']) {
                            echo ' selected="selected"';
                        }
                    ?>>Ikke valgt</option><?php
foreach (Config::get('emails', []) as $department => $dummy) {
    ?><option<?php
if ($faktura['department'] == $department) {
    echo ' selected="selected"';
}
?>><?php echo $department ?></option><?php
}
        ?></select><?php
                } else {
                    $email = first(Config::get('emails'))['address'];
                    echo $email;
                    ?><input name="department" id="department" type="hidden" value="<?php echo $email; ?>" /><?php
                }
            } else {
                echo $faktura['department'];
            }
?></td>
    </tr>
    <tr>
        <td><?php echo _('Our ref.:'); ?></td>
        <td><?php if ($faktura['status'] == 'new') { ?>
            <input name="iref" id="iref" value="<?php echo $faktura['iref'] ?>" />
            <?php
} else {
    echo $faktura['iref'];
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('Their ref.:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="eref" id="eref" value="<?php echo $faktura['eref'] ?>" />
                <?php
} else {
    echo $faktura['eref'];
}
?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Faktureringsadressen:</strong></td>
        </tr>
        <tr>
            <td><?php echo _('Phone 1:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="tlf1" id="tlf1" value="<?php echo $faktura['tlf1'] ?>" />
                <input type="button" value="Hent" onclick="getAddress($('tlf1').value);" />
                <?php
} else {
    echo $faktura['tlf1'];
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('Phone 2:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="tlf2" id="tlf2" value="<?php echo $faktura['tlf2'] ?>" />
                <input type="button" value="Hent" onclick="getAddress($('tlf2').value);" />
                <?php
} else {
    echo $faktura['tlf2'];
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('E-mail:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="email" id="email" onchange="valideMail();" onkeyup="valideMail();" value="<?php echo $faktura['email'] ?>" />
                <?php
} else {
    echo '<a href="mailto:'.$faktura['email'].'">'.$faktura['email'].'</a>';
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('Name:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="navn" id="navn" value="<?php echo $faktura['navn'] ?>" />
                <?php
} else {
    echo $faktura['navn'];
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('Attn.:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="att" id="att" value="<?php echo $faktura['att'] ?>" />
                <?php
} else {
    echo $faktura['att'];
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('Address:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="adresse" id="adresse" value="<?php echo $faktura['adresse'] ?>" />
                <?php
} else {
    echo $faktura['adresse'];
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('Postbox:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="postbox" id="postbox" value="<?php echo $faktura['postbox'] ?>" />
            <?php
} else {
    echo $faktura['postbox'];
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('Zipcode:'); ?></td>
            <td><?php if ($faktura['status'] == 'new') { ?>
                <input name="postnr" id="postnr" value="<?php echo $faktura['postnr'] ?>" onblur="chnageZipCode(this.value, 'land', 'by')" onkeyup="chnageZipCode(this.value, 'land', 'by')" onchange="chnageZipCode(this.value, 'land', 'by')" />
                <?php echo _('City:'); ?>
                <input name="by" id="by" value="<?php echo $faktura['by'] ?>" />
                <?php
} else {
    echo $faktura['postnr'].' '._('City:').' '.$faktura['by'];
}
?></td>
        </tr>
        <tr>
            <td><?php echo _('Country:'); ?></td>
            <td><?php
            if ($faktura['status'] == 'new') {
                    ?><select name="land" id="land" onblur="chnageZipCode($('postnr').value, 'land', 'by')" onkeyup="chnageZipCode($('postnr').value, 'land', 'by')" onchange="chnageZipCode($('postnr').value, 'land', 'by')">
                        <option value=""<?php
                        if (!$faktura['land']) {
                            echo ' selected="selected"';
                        }
                            ?>></option><?php
foreach ($countries as $code => $country) {
    ?><option value="<?php echo $code ?>"<?php
if ($faktura['land'] == $code) {
    echo ' selected="selected"';
}
?>><?php echo xhtmlEsc($country); ?></option><?php
}
    ?></select><?php
            } else {
                echo $countries[$faktura['land']];
            }
?></td></tr><?php
if (($faktura['status'] != 'new' && $faktura['altpost']) || $faktura['status'] == 'new') {
    ?><tr>
    <td colspan="2"><?php
    if ($faktura['status'] == 'new') {
        ?><input onclick="showhidealtpost(this.checked);" name="altpost" id="altpost" type="checkbox"<?php
if ($faktura['altpost']) {
    echo ' checked="checked"';
}
        ?> /><?php
    }
    ?><label for="altpost"> <strong><?php echo _('Other delivery address'); ?></strong></label></td></tr><tr class="altpost"<?php
if (!$faktura['altpost']) {
    echo ' style="display:none;"';
}
    ?>>
    <td>Tlf:</td>
    <td><?php
    if ($faktura['status'] == 'new') {
        ?><input name="posttlf" id="posttlf" value="<?php echo $faktura['posttlf'] ?>" />
        <input type="button" value="Hent" onclick="getAltAddress($('posttlf').value);" /><?php
    } else {
        echo $faktura['posttlf'];
    }
    ?></td></tr><tr class="altpost"<?php
if (!$faktura['altpost']) {
    echo ' style="display:none;"';
}
    ?>>
    <td><?php echo _('Name:'); ?></td>
    <td><?php
    if ($faktura['status'] == 'new') {
        ?><input name="postname" id="postname" value="<?php echo $faktura['postname'] ?>" /><?php
    } else {
        echo $faktura['postname'];
    }
    ?></td></tr><tr class="altpost"<?php
if (!$faktura['altpost']) {
    echo ' style="display:none;"';
}
    ?>>
    <td><?php echo _('Attn.:'); ?></td>
    <td><?php
    if ($faktura['status'] == 'new') {
        ?><input name="postatt" id="postatt" value="<?php echo $faktura['postatt'] ?>" />
        <?php
    } else {
        echo $faktura['postatt'];
    }
    ?></td></tr><tr class="altpost"<?php
if (!$faktura['altpost']) {
    echo ' style="display:none;"';
}
    ?>>
    <td><?php echo _('Address:'); ?></td>
    <td><?php
    if ($faktura['status'] == 'new') {
        ?><input name="postaddress" id="postaddress" value="<?php echo $faktura['postaddress'] ?>" /><?php
    } else {
        echo $faktura['postaddress'];
    }
    ?></td></tr><tr class="altpost"<?php
if (!$faktura['altpost']) {
    echo ' style="display:none;"';
}
    ?>>
    <td></td>
    <td><?php
    if ($faktura['status'] == 'new') {
        ?><input name="postaddress2" id="postaddress2" value="<?php echo $faktura['postaddress2'] ?>" /><?php
    } else {
        echo $faktura['postaddress2'];
    }
    ?></td></tr><tr class="altpost"<?php
if (!$faktura['altpost']) {
    echo ' style="display:none;"';
}
    ?>><td><?php
    echo _('Postbox:');
    ?></td><td><?php
if ($faktura['status'] == 'new') {
    ?><input name="postpostbox" id="postpostbox" value="<?php echo $faktura['postpostbox'] ?>" /><?php
} else {
    echo $faktura['postpostbox'];
}
    ?></td></tr><tr class="altpost"<?php
if (!$faktura['altpost']) {
    echo ' style="display:none;"';
}
    ?>>
    <td><?php
    echo _('Zipcode:');
    ?></td>
    <td><?php
    if ($faktura['status'] == 'new') {
        ?><input name="postpostalcode" id="postpostalcode" value="<?php echo $faktura['postpostalcode'] ?>" onblur="chnageZipCode(this.value, 'postcountry', 'postcity')" onkeyup="chnageZipCode(this.value, 'postcountry', 'postcity')" onchange="chnageZipCode(this.value, 'postcountry', 'postcity')" />
        <?php echo _('City:'); ?>
        <input name="postcity" id="postcity" value="<?php echo $faktura['postcity'] ?>" /><?php
    } else {
        echo $faktura['postpostalcode'].' '._('City:').' '.$faktura['postcity'];
    }
    ?></td>
    </tr>
    <tr class="altpost"<?php
    if (!$faktura['altpost']) {
        echo ' style="display:none;"';
    }
    ?>>
    <td><?php echo _('Country:'); ?></td>
    <td><?php
    if ($faktura['status'] == 'new') {
        ?><select name="postcountry" id="postcountry" onblur="chnageZipCode($('postpostalcode').value, 'postcountry', 'postcity')" onkeyup="chnageZipCode($('postpostalcode').value, 'postcountry', 'postcity')" onchange="chnageZipCode($('postpostalcode').value, 'postcountry', 'postcity')">
            <option value=""<?php
            if (!$faktura['postcountry']) {
                echo ' selected="selected"';
            }
        ?>></option><?php
foreach ($countries as $code => $country) {
    ?><option value="<?php echo $code ?>"<?php
if ($faktura['postcountry'] == $code) {
    echo ' selected="selected"';
}
?>><?php
echo xhtmlEsc($country); ?></option><?php
}
        ?></select><?php
    } else {
        echo $countries[$faktura['postcountry']];
    }
    ?></td></tr><?php
}
if ($faktura['status'] == 'new') {
    ?><tr>
        <td colspan="2"><input type="checkbox"<?php
        if ($faktura['premoms']) {
            echo ' checked="checked"';
        }
    ?> id="premoms" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" onclick="prisUpdate()" />
    <label for="premoms"><?php echo _('Entered amount includes VAT'); ?></label></td>
    </tr><?php
}
?></table>
<table id="data" cellspacing="0">
<thead>
<tr>
    <td><?php echo _('Quantity'); ?></td>
    <td><?php echo _('Title'); ?></td>
    <td class="tal"><?php echo _('unit price'); ?></td>
    <td class="tal"><?php echo _('Total'); ?></td>
</tr>
</thead>
<tfoot>
<tr style="height:auto;min-height:auto;max-height:auto;">
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td class="tal"><?php echo _('Net Amount'); ?></td>
    <?php
    $productslines = max(count($faktura['quantities']), count($faktura['products']), count($faktura['values']));

    $netto = 0;
    for ($i=0; $i<$productslines; $i++) {
        $netto += $faktura['values'][$i]*$faktura['quantities'][$i];
    }
?>
    <td class="tal" id="netto"><?php echo number_format($netto, 2, ',', ''); ?></td>
</tr>
<tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td class="tal"><?php echo _('Freight'); ?></td>
    <td class="tal"><?php
    if ($faktura['status'] == 'new') {
        ?><input maxlength="7" name="fragt" id="fragt" style="width:80px;" class="tal" value="<?php echo number_format($faktura['fragt'], 2, ',', ''); ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />
        <?php
    } else {
        echo number_format($faktura['fragt'], 2, ',', '');
    }
?></td>
</tr>
<tr>
    <td>&nbsp;</td>
    <td style="text-align:right"><?php
    if ($faktura['status'] == 'new') {
        ?><select name="momssats" id="momssats" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()">
        <option value="0.25"<?php
        if ($faktura['momssats'] == 0.25) {
            echo ' selected="selected"';
        }
        ?>>25%</option>
        <option value="0"<?php
        if (!$faktura['momssats']) {
            echo ' selected="selected"';
        }
    ?>>0%</option></select><?php
    } else {
        echo ($faktura['momssats']*100).'%';
    }
        ?></td>
    <td class="tal"><?php echo _('VAT Amount'); ?></td>
    <td class="tal" id="moms"><?php echo number_format($netto*$faktura['momssats'], 2, ',', ''); ?></td>
</tr>
<tr class="border">
    <td colspan="2">&nbsp;</td>
    <td style="text-align:center; font-weight:bold;"><?php echo _('TO PAY'); ?></td>
    <td class="tal" id="payamount"><?php echo number_format($netto*(1+$faktura['momssats'])+$faktura['fragt'], 2, ',', ''); ?></td>
</tr>
</tfoot>
<tbody id="vareTable"><?php
if ($faktura['status'] == 'new') {
    for ($i=0; $i<$productslines; $i++) {
        ?><tr>
        <td><input name="quantitie" style="width:58px;" class="tal" value="<?php echo $faktura['quantities'][$i] ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
        <td><input name="product" style="width:303px;" value="<?php echo $faktura['products'][$i] ?>" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /></td>
        <td><?php
        echo '<input name="value" style="width:69px;" class="tal" value="';
        if ($faktura['values'][$i]) {
            echo number_format($faktura['premoms'] ? $faktura['values'][$i]*1.25 : $faktura['values'][$i], 2, ',', '');
        }
        echo '" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />';
        ?></td>
        <td class="tal total"></td>
        <td style="border:0; font-weight:bold;"><a href="#" onclick="removeRow(this); return false"><img alt="X" src="images/cross.png" height="16" width="16" title="<?php echo _('Remove Line'); ?>" /></a></td></tr><?php
    }
} else {
    for ($i=0; $i<$productslines; $i++) {
        ?><tr>
        <td class="tal"><?php echo $faktura['quantities'][$i] ?></td>
        <td><?php echo htmlspecialchars_decode($faktura['products'][$i]); ?></td>
        <td class="tal"><?php echo number_format($faktura['values'][$i], 2, ',', ''); ?></td>
        <td class="tal"><?php echo number_format($faktura['values'][$i]*$faktura['quantities'][$i], 2, ',', ''); ?></td></tr><?php
    }
}
?></tbody>
</table>
</div>
</div><?php
if (!in_array($faktura['status'], ['canceled', 'new', 'accepted'])) {
    if ((!$faktura['altpost'] && $faktura['land'] == 'DK') || ($faktura['postcountry'] == 'DK' && $faktura['altpost'])) {
        $activityButtons[] = '<li><a href="http://www.jagtogfiskerimagasinet.dk/post/?type='.($faktura['status'] == 'locked' ? 'O&amp;value='.number_format($faktura['amount'], 2, ',', '') : 'P').(!$faktura['altpost'] ? '&amp;tlf1='.rawurlencode($faktura['tlf1']).'&amp;postbox='.rawurlencode($faktura['postbox']).'&amp;tlf2='.rawurlencode($faktura['tlf2']).'&amp;name='.rawurlencode($faktura['navn']).'&amp;att='.rawurlencode($faktura['att']).'&amp;address='.rawurlencode($faktura['adresse']).'&amp;zipcode='.rawurlencode($faktura['postnr']) : '&amp;tlf1='.rawurlencode($faktura['posttlf']).'&amp;postbox='.rawurlencode($faktura['postpostbox']).'&amp;name='.rawurlencode($faktura['postname']).'&amp;att='.rawurlencode($faktura['postatt']).'&amp;address='.rawurlencode($faktura['postaddress']).'&amp;address2='.rawurlencode($faktura['postaddress2']).'&amp;zipcode='.rawurlencode($faktura['postpostalcode'])).'&amp;email='.rawurlencode($faktura['email']).'&amp;porto='.number_format($faktura['fragt'], 2, ',', '').'" target="_blank"><img src="images/package.png" alt="" title="Opret pakke lable" width="16" height="16" /> Opret pakke lable</a></li>';
    } else {
        $activityButtons[] = '<li><a href="http://www.jagtogfiskerimagasinet.dk/pnl/?email='.rawurlencode($faktura['email']).(!$faktura['altpost'] ? '&amp;name='.rawurlencode($faktura['navn']).'&amp;att='.rawurlencode($faktura['att']).'&amp;address='.rawurlencode($faktura['adresse'] ? $faktura['adresse'] : $faktura['postbox']).'&amp;postcode='.rawurlencode($faktura['postnr']).'&amp;city='.rawurlencode($faktura['by']).'&amp;country='.rawurlencode($faktura['land']) : '&amp;name='.rawurlencode($faktura['postname']).'&amp;att='.rawurlencode($faktura['postatt']).'&amp;address='.rawurlencode($faktura['postaddress'] ? $faktura['postaddress'] : $faktura['postpostbox']).'&amp;address='.rawurlencode($faktura['postaddress2']).'&amp;postcode='.rawurlencode($faktura['postpostalcode']).'&amp;city='.rawurlencode($faktura['postcity']).'&amp;country='.rawurlencode($faktura['postcountry'])).'" target="_blank"><img src="images/package.png" alt="" title="Opret pakke lable" width="16" height="16" /> Opret pakke lable</a></li>';
    }
}

if ($faktura['status'] == 'pbsok') {
    $activityButtons[] = '<li><a onclick="pbsconfirm(); return false;"><img src="images/money.png" alt="" width="16" height="16" /> '._('Expedite').'</a></li>';
    $activityButtons[] = '<li><a onclick="annul(); return false;"><img src="images/bin.png" alt="" width="16" height="16" /> '._('Reject').'</a></li>';
}
$activityButtons[] = '<li><a onclick="save(); return false;"><img src="images/table_save.png" alt="" width="16" height="16" /> '._('Save').'</a></li>';
if ($faktura['status'] == 'new') {
    $activityButtons[] = '<li><a onclick="save(\'lock\'); return false;"><img src="images/lock.png" alt="" width="16" height="16" /> '._('Lock').'</a></li>';
}

if ($faktura['status'] != 'new') {
    $activityButtons[] = '<li><a href="faktura-pdf.php?id='.$faktura['id'].'"><img height="16" width="16" title="" src="images/printer.png"/> '._('Print').'</a></li>';
}
$activityButtons[] = '<li><a onclick="newfaktura(); return false;"><img src="images/table_add.png" alt="" width="16" height="16" /> '._('Create new').'</a></li>';
$activityButtons[] = '<li><a onclick="copytonew(); return false;"><img src="images/table_multiple.png" alt="" width="16" height="16" /> '._('Copy to new').'</a></li>';

if (!in_array($faktura['status'], ['canceled', 'pbsok', 'accepted', 'giro', 'cash'])) {
    $activityButtons[] = '<li><a onclick="save(\'cancel\'); return false;" href="#"><img src="images/bin.png" alt="" width="16" height="16" /> '._('Cancel').'</a></li>';
}

if (!in_array($faktura['status'], ['giro', 'cash', 'pbsok', 'accepted', 'canceled', 'rejected'])) {
    if (!$faktura['sendt']) {
        if (valideMail($faktura['email'])) {
            $activityButtons[] = '<li id="emaillink"><a href="#" onclick="save(\'email\'); return false;"><img height="16" width="16" title="'._('Send to customer').'" alt="" src="images/email_go.png"/> '._('Send').'</a></li>';
        } else {
            $activityButtons[] = '<li id="emaillink" style="display:none;"><a href="#" onclick="save(\'email\'); return false;"><img height="16" width="16" title="'._('Send to customer').'" alt="" src="images/email_go.png"/> '._('Send').'</a></li>';
        }
    } else {
        $activityButtons[] = '<li><a href="#" onclick="sendReminder(); return false;"><img height="16" width="16" alt="" src="images/email_go.png"/> '._('Send reminder!').'</a></li>';
    }
}

require 'mainmenu.php';
?>
</body>
</html>
