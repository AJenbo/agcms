function newfaktura()
{
    $('loading').style.visibility= '';
    x_newfaktura(newfaktura_r);
}
function copytonew(id)
{
    $('loading').style.visibility= '';
    x_copytonew(id, newfaktura_r);
}
function newfaktura_r(id)
{
    window.location.href= '?id=' + id;
}

function removeRow(row)
{
    $('vareTable').removeChild(row.parentNode.parentNode);
    if($('vareTable').childNodes.length == 0) {
        addRow();
    }
    prisUpdate();
}

function addRow()
{
    var tr= document.createElement('tr');
    var td= document.createElement('td');
    td.innerHTML=
        '<input name="quantitie" style="width:58px;" class="tal" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />';
    tr.appendChild(td);
    td= document.createElement('td');
    td.innerHTML=
        '<input name="product" style="width:303px;" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />';
    tr.appendChild(td);
    td= document.createElement('td');
    td.innerHTML=
        '<input name="value" style="width:69px;" class="tal" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" />';
    tr.appendChild(td);
    td= document.createElement('td');
    td.className= 'tal total';
    tr.appendChild(td);
    td= document.createElement('td');
    td.className= 'web';
    td.style.border= '0';
    td.style.fontWeight= 'bold';
    td.innerHTML=
        '<a href="#" onclick="removeRow(this); return false"><img alt="X" src="images/cross.png" height="16" width="16" title="Remove Line" /></a>';
    tr.appendChild(td);
    $('vareTable').appendChild(tr);
}

function getAddress(tlf)
{
    $('loading').style.visibility= '';
    x_getAddress(tlf, getAddress_r);
}

function getAddress_r(data)
{
    if(data['error']) {
        alert(data['error']);
    } else {
        $('navn').value= data['recName1'];
        $('att').value= data['recAttPerson'];
        $('adresse').value= data['recAddress1'];
        $('postnr').value= data['recZipCode'];
        // TODO 'by' might not be danish!
        var zip= arrayZipcode[data['recZipCode']];
        if(zip != 'undefined') {
            $('by').value= zip;
        }
        $('postbox').value= data['recPostBox'];
        $('email').value= data['email'];
        // TODO support more values
        // TODO setEmailLink();
    }
    $('loading').style.visibility= 'hidden';
}

function getAltAddress(tlf)
{
    $('loading').style.visibility= '';
    x_getAddress(tlf, getAltAddress_r);
}

function getAltAddress_r(data)
{
    if(data['error']) {
        alert(data['error']);
    } else {
        $('postname').value= data['recName1'];
        $('postatt').value= data['recAttPerson'];
        $('postaddress').value= data['recAddress1'];
        $('postpostalcode').value= data['recZipCode'];
        // TODO 'by' might not be danish!
        var zip= arrayZipcode[data['recZipCode']];
        if(zip != 'undefined') {
            $('postcity').value= zip;
        }
        $('postpostbox').value= data['recPostBox'];
        // TODO support more values
        // TODO setEmailLink();
    }
    $('loading').style.visibility= 'hidden';
}

function prisUpdate()
{
    quantities= '';
    products= '';
    values= '';
    amount= 0;

    var quantitieObjs= document.getElementsByName('quantitie');
    var productObjs= document.getElementsByName('product');
    var valueObjs= document.getElementsByName('value');
    var totalObjs= $$('.total');
    var premoms= $('premoms').checked;
    var momssats= parseFloat($('momssats').value);

    var netto= 0;

    var quantitie;
    var value;
    var total;

    for(var i= 0; i < quantitieObjs.length; i++) {
        quantitie= 0;
        value= 0;
        total= 0;
        quantitie= parseInt(quantitieObjs[i].value);
        if(isNaN(quantitie)) {
            quantitie= 0;
        }

        value= parseFloat(parseFloat(valueObjs[i].value.replace(/[^-0-9,]/g, '').replace(/,/, '.')).toFixed(2));

        if(isNaN(value)) {
            value= 0;
        }

        if(premoms) {
            value= value / 1.25; // VAT
        }

        total= quantitie * value;

        totalObjs[i].innerHTML= '';
        if(total != 0) {
            if(premoms) {
                totalObjs[i].innerHTML= (total * 1.25).toFixed(2).toString().replace(/\./, ',');
            } else {
                totalObjs[i].innerHTML= total.toFixed(2).toString().replace(/\./, ',');
            }
        }

        netto+= total;

        if(quantitieObjs[i].value != '' || productObjs[i].value != '' || valueObjs[i].value != '') {
            if(quantities != '') {
                quantities+= '<';
                products+= '<';
                values+= '<';
            }
            quantities+= quantitie.toString();
            products+= htmlspecialchars(productObjs[i].value.toString());
            if(premoms) {
                values+= (value * 1.25).toString();
            } else {
                values+= value.toString();
            }
        }
    }

    $('netto').innerHTML= netto.toFixed(2).toString().replace(/\./, ',');

    $('moms').innerHTML= (netto * momssats).toFixed(2).toString().replace(/\./, ',');

    var fragt= parseFloat($('fragt').value.replace(/[^-0-9,]/g, '').replace(/,/, '.'));
    if(isNaN(fragt)) {
        fragt= 0;
    }

    amount = parseFloat(fragt + netto + netto * momssats).toFixed(2);
    $('payamount').innerHTML= amount.toString().replace(/\./, ',');

    if(!quantitieObjs.length || quantitieObjs[quantitieObjs.length - 1].value != '' ||
        productObjs[productObjs.length - 1].value != '' || valueObjs[valueObjs.length - 1].value != '') {
        addRow();
    }

    return true;
}

function pbsconfirm(id)
{
    $('loading').style.visibility= '';
    // TODO save comment
    x_pbsconfirm(id, reload_r);
}

function annul(id)
{
    $('loading').style.visibility= '';
    // TODO save comment
    x_annul(id, reload_r);
}

function reload_r(date)
{
    if(date['error']) {
        alert(date['error']);
    } else {
        window.location.reload();
    }
    $('loading').style.visibility= 'hidden';
}

function save(id, type)
{
    if(type == null) {
        type= 'save';
    }

    if(type == 'cancel' && !confirm('Are you sure you want to cancel this Invoice?')) {
        return false;
    }

    $('loading').style.visibility= '';
    var update= {};
    if(status == 'new') {
        update['quantities']= quantities;
        update['products']= products;
        update['values']= values;
        update['fragt']= $('fragt').value.replace(/[^-0-9,]/g, '').replace(/,/, '.');
        update['amount']= amount;
        update['momssats']= $('momssats').value;
        update['premoms']= $('premoms').checked ? 1 : 0;
        update['date']= $('date').value;
        update['iref']= $('iref').value;
        update['eref']= $('eref').value;
        update['navn']= $('navn').value;
        update['att']= $('att').value;
        update['adresse']= $('adresse').value;
        update['postbox']= $('postbox').value;
        update['postnr']= $('postnr').value;
        update['by']= $('by').value;
        update['land']= $('land').value;
        update['email']= $('email').value;
        update['tlf1']= $('tlf1').value;
        update['tlf2']= $('tlf2').value;
        update['altpost']= $('altpost').checked ? 1 : 0;
        if($('altpost').value) {
            update['posttlf']= $('posttlf').value;
            update['postname']= $('postname').value;
            update['postatt']= $('postatt').value;
            update['postaddress']= $('postaddress').value;
            update['postaddress2']= $('postaddress2').value;
            update['postpostbox']= $('postpostbox').value;
            update['postpostalcode']= $('postpostalcode').value;
            update['postcity']= $('postcity').value;
            update['postcountry']= $('postcountry').value;
        }
    }

    update['note']= $('note').value;

    if($('clerk')) {
        update['clerk']= getSelectValue('clerk');
    }
    if($('department')) {
        update['department']= getSelectValue('department');
    }

    if(type == 'giro') {
        update['paydate']= $('gdate').value;
    }

    if(type == 'cash') {
        update['paydate']= $('cdate').value;
    }

    x_save(id, type, update, save_r);
}

function sendReminder(id)
{
    x_sendReminder(id, sendReminder_r);
}

function sendReminder_r(data)
{
    alert(data['error']);
}

function save_r(date)
{
    if(date['error']) {
        alert(date['error']);
    }

    if(date['status'] != status || date['type'] == 'faktura' || date['type'] == 'lock' || date['type'] == 'cancel' ||
        date['type'] == 'giro' || date['type'] == 'cash') {
        window.location.reload();
    }

    if(date['status'] != 'new') {
        if($('clerk')) {
            $$('.clerk')[0].innerHTML= $('clerk').value;
        }
        if($('note').value) {
            $$('.note')[0].innerHTML+= '<br />' + nl2br($('note').value);
            $('note').value= '';
        }
    }

    $('loading').style.visibility= 'hidden';
}

var validemailajaxcall;
var lastemail;

function valideMail()
{
    if($('emaillink')) {
        if($('email').value.match('^[A-z0-9_.-]+@([A-z0-9-]+\.)+[A-z0-9-]+$')) {
            if($('email').value != lastemail || $('emaillink').style.display == 'none') {
                lastemail= $('email').value;
                if(validemailajaxcall) {
                    sajax.cancel(validemailajaxcall);
                }
                $('loading').style.visibility= '';
                valideMail_r(false);
                validemailajaxcall= x_valideMail($('email').value, valideMail_r);
            }
        } else {
            valideMail_r(false);
        }
    }
}

function valideMail_r(valideMail)
{
    $('emaillink').style.display= valideMail ? '' : 'none';
    $('loading').style.visibility= 'hidden';
}

function showhidealtpost(status)
{
    var altpostTrs= $$('.altpost');
    for(var i= 0; i < altpostTrs.length; i++) {
        altpostTrs[i].style.display= status ? '' : 'none';
    }
}

function chnageZipCode(zipcode, country, city)
{
    if($(country).value != 'DK') {
        return;
    }

    $(city).value= arrayZipcode[zipcode] ? arrayZipcode[zipcode] : '';
}

var quantities;
var products;
var values;
var amount;
