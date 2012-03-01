function x_get_table() {
	sajax_do_call('get_table', arguments, "GET", true, "/ajax.php");
	return false;
}

function get_address(phonenumber, function_name) {
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
	x_get_address(phonenumber, function_name);
}

function x_get_address() {
	sajax_do_call('get_address', arguments, "GET", true, "/ajax.php");
	return false;
}

function x_get_kat() {
	sajax_do_call('get_kat', arguments, "GET", true, "/ajax.php");
	return false;
}

function inject_html(data) {
	if(data['error'] || !data) {
		alert(data['error']);
	} else {
		document.getElementById(data['id']).innerHTML = data['html'];
	}
}
