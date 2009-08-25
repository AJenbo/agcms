function x_get_table() {
	sajax_do_call('get_table', arguments, "GET", true, "/ajax.php");
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
