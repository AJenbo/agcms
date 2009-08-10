//Load a JSON passer if the browser doesn't have a native one.
if(typeof(JSON) != 'object' || typeof(JSON.parse) != 'function') {
	var headID = document.getElementsByTagName("head")[0];         
	var newScript = document.createElement('script');
	newScript.type = 'text/javascript';
	newScript.src = '/javascript/json_parse.js';
	headID.appendChild(newScript);
}

var sajax_requests = new Array();
function sajax_do_call(func_name, args) {
	
	var i, x, n;
	var uri;
	var post_data;
	
	uri = '/ajax.php';

	if (uri.indexOf("?") == -1) 
		uri += "?rs=" + encodeURIComponent(func_name);
	else
		uri += "&rs=" + encodeURIComponent(func_name);
	
	for (i = 0; i < args.length-1; i++) 
		uri += "&rsargs[]=" + encodeURIComponent(serialize(args[i]));
	
	if (typeof(XMLHttpRequest) == "undefined") {
		XMLHttpRequest = function() {
			var msxmlhttp = Array(
				'Msxml2.XMLHTTP.6.0',
				'Msxml2.XMLHTTP.5.0',
				'Msxml2.XMLHTTP.4.0',
				'Msxml2.XMLHTTP.3.0',
				'Msxml2.XMLHTTP',
				'Microsoft.XMLHTTP');
			for (var i = 0; i < msxmlhttp.length; i++) {
				try { return new ActiveXObject(msxmlhttp[i]); }
				catch(e) {}
			}
			throw new Error("This browser does not support XMLHttpRequest.");
			return null;
		};
	}
	
	x = new XMLHttpRequest();
	if(x == null)
		return false;
	
	x.open('GET', uri, true);
	// window.open(uri);
	
	x.onreadystatechange = function() {
		if (x.readyState != 4) 
			return false;
		
		var status;
		var data;
		var txt = x.responseText.replace(/^\s*|\s*$/g,"");
		status = txt.charAt(0);
		if(status == '-' || status == '+')
			data = txt.substring(2);
		else
			data = txt;

		if(status == "" && (x.status == 200 || x.status == "")) {
			// let's just assume this is a pre-response bailout and let it slide for now
			return false;
		} else if(status != '+' || x.status != 200) {
			alert("Error " + x.status + ": " + data);
			return false;
		} else {
			try {
				var callback;
				var extra_data = false;
				if (typeof args[args.length-1] == "object") {
					callback = args[args.length-1].callback;
					extra_data = args[args.length-1].extra_data;
				} else {
					callback = args[args.length-1];
				}
				if(typeof(JSON) != 'undefined' && typeof(JSON.parse) != 'undefined')
					callback(JSON.parse(data), extra_data);
				else {
					eval('var res = '+data+'; res;')
					callback(res, extra_data);
				 }
			} catch(e) {
				return false;
			}
		}
		return true;
	}
	for (var i = 0; i < sajax_requests.length; i++)
		if(sajax_requests[i]) {
			sajax_requests[i].abort();
			sajax_requests.splice(i, 1, null);
		}
	
	sajax_requests[sajax_requests.length] = x;
	
	x.send();
	delete x;
}

function x_get_table() {
	sajax_do_call('get_table', x_get_table.arguments);
	return false;
}

function x_get_kat() {
	sajax_do_call('get_kat', x_get_kat.arguments);
	return false;
}

function inject_html(data) {
	if(data['error'] || !data) {
		alert(data['error']);
	} else {
		document.getElementById(data['id']).innerHTML = data['html'];
	}
}
