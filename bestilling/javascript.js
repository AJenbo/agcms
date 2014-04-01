document.getElementsByClassName = getElementsByClassName;

function getElementsByClassName(name) {
    var allelems, retary, i;
    retary = new Array();
    allelems = document.getElementsByTagName("*");
    for (i = 0; i < allelems.length; i++) {
        if (allelems[i].className) {
            if (allelems[i].className.indexOf(name) != -1) {
                retary.push(allelems[i]);
            }
        }
    }
    return retary;
}

function updateprice() {
    var total = document.getElementsByClassName('total');
    var input = document.getElementsByName('quantity[ ]');
    var subtotal = 0;
    for (var i=0;i<values.length;i++) {
        value = values[i]*input[i].value;
        if (values[i] > 0) {
            total[i].innerHTML = value.toFixed(2).toString().replace(/\./,',');
        }
        subtotal = subtotal+value;
    }
    document.getElementById('total').innerHTML = subtotal.toFixed(2).toString().replace(/\./,',');
}

function showhidealtpost(status) {
    var Trs = document.getElementsByTagName('TR');
    if (status) {
        for (var i = 0; i<Trs.length; i++) {
            if (Trs[i].className == 'altpost') {
                Trs[i].style.display = '';
            }
        }
    } else {
        for (var i = 0; i<Trs.length; i++) {
            if (Trs[i].className == 'altpost') {
                Trs[i].style.display = 'none';
            }
        }
    }
}

function getAddress_r1(responce) {
    if (responce['error']) {
        alert(responce['error']);
        return false;
    }
    if (responce['recName1']) {
        document.getElementById('navn').value = responce['recName1'];
    }
    if (responce['recAttPerson']) {
        document.getElementById('att').value = responce['recAttPerson'];
    }
    if (responce['recAddress1']) {
        document.getElementById('adresse').value = responce['recAddress1'];
    }
    if (responce['recPostBox']) {
        document.getElementById('postbox').value = responce['recPostBox'];
    }
    if (responce['recZipCode']) {
        document.getElementById('postnr').value = responce['recZipCode'];
    }
    if (responce['email']) {
        document.getElementById('email').value = responce['email'];
    }
    if (document.getElementById('land').value == 'DK' && responce['recZipCode'] && arrayZipcode[responce['recZipCode']]) {
        document.getElementById('by').value = arrayZipcode[responce['recZipCode']];
    }
}

function getAddress_r2(responce) {
    if (responce['error']) {
        alert(responce['error']);
        return false;
    }
    if (responce['recName1']) {
        document.getElementById('postname').value = responce['recName1'];
    }
    if (responce['recAttPerson']) {
        document.getElementById('postatt').value = responce['recAttPerson'];
    }
    if (responce['recAddress1']) {
        document.getElementById('postaddress').value = responce['recAddress1'];
    }
    if (responce['recAddress2']) {
        document.getElementById('postaddress2').value = responce['recAddress2'];
    }
    if (responce['recPostBox']) {
        document.getElementById('postpostbox').value = responce['recPostBox'];
    }
    if (responce['recZipCode']) {
        document.getElementById('postpostalcode').value = responce['recZipCode'];
    }
    if (document.getElementById('postcountry').value == 'DK' && responce['recZipCode']) {
        document.getElementById('postcity').value = arrayZipcode[responce['recZipCode']];
    }
}

function chnageZipCode(zipcode, countryid, cityid) {
    if (document.getElementById(countryid).value != 'DK') {
        return false;
    }
    if (arrayZipcode[zipcode]) {
        document.getElementById(cityid).value = arrayZipcode[zipcode];
    }
}
