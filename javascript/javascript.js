function x_getTable() {
    sajax_do_call('getTable', arguments, "GET", true, "/ajax.php");
    return false;
}

function getAddress(phonenumber, function_name) {
    phonenumber = phonenumber.replace('/\s/', '');
    phonenumber = phonenumber.replace('/^[+]45/', '');
    if(!phonenumber) {
        alert('De skal udfylde telefon nummeret først.');
        return false;
    }
    if(phonenumber.length != 8) {
        alert('Telefonnummeret skal være på 8 cifre!');
        return false;
    }
    x_getAddress(phonenumber, function_name);
}

function x_getAddress() {
    sajax_do_call('getAddress', arguments, "GET", true, "/ajax.php");
    return false;
}

function x_getKat() {
    sajax_do_call('getKat', arguments, "GET", true, "/ajax.php");
    return false;
}

function inject_html(data) {
    if(data['error'] || !data) {
        alert(data['error']);
    } else {
        document.getElementById(data['id']).innerHTML = data['html'];
    }
}
