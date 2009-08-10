// remote scripting library
// (c) copyright 2005 modernmethod, inc
var sajax_debug_mode = false;
var sajax_target_id = "";
var sajax_failure_redirect = "";

function sajax_debug(text) {
	if (sajax_debug_mode)
		alert(text);
}

var sajax_requests = new Array();

function sajax_cancel(id) {
	 if(arguments.length == 0) {
		for (var i = 0; i < sajax_requests.length; i++)
			if(sajax_requests[i]) {
				sajax_requests[i].abort();
				sajax_requests.splice(i, 1, null);
			}
	} else if(sajax_requests[id]) {
		sajax_requests[id].abort();
		sajax_requests.splice(id, 1, null);
	}
}

function sajax_init_object() {
	sajax_debug("sajax_init_object() called..");
	
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
	
	var A = new XMLHttpRequest();

	if (!A)
		sajax_debug("Could not create connection object.");
	
	return A;
}

function sajax_do_call(func_name, args, method, asynchronous) {
	
	if(arguments.length == 2) {
		var method = 'POST';
		var asynchronous = true;
	} else if(arguments.length == 2) {
		var asynchronous = true;
	}
	
	if(method != 'GET')
		method = 'POST';
	
	var i, x, n;
	var uri;
	var post_data;
	var target_id = sajax_target_id;
	
	sajax_debug("in sajax_do_call().." + method + "/" + sajax_target_id);
	uri = '/post/';
	if (method == "GET") {
	
		var geturi = uri;
	
		if (geturi.indexOf("?") == -1) 
			geturi += "?rs=" + encodeURIComponent(func_name);
		else
			geturi += "&rs=" + encodeURIComponent(func_name);
		
		for (i = 0; i < args.length-1; i++) 
			geturi += "&rsargs[]=" + encodeURIComponent(serialize(args[i]));

		if(geturi.length > 512){
			method = "POST";
			sajax_debug("Data to long for GET switching to POST");
		} else {
			uri = geturi;
			post_data = null;
		}
	}
	if (method == "POST") {
		post_data = "rs=" + encodeURIComponent(func_name);
		
		for(i = 0; i < args.length-1; i++) {
			post_data = post_data + "&rsargs[]=" + encodeURIComponent(serialize(args[i]));
		}
	}
	
	x = sajax_init_object();
	if(x == null) {
		//TODO support iframe ajaxing
		//document.getElementsByTagName("pre")[0].innerHTML
		if(sajax_failure_redirect != "") {
			window.location.href = sajax_failure_redirect;
			return false;
		} else {
			sajax_debug("NULL sajax object for user agent:\n" + navigator.userAgent);
			return false;
		}
	}
	
	x.open(method, uri, asynchronous);
	// window.open(uri);
	
	if (method == "POST") {
		x.setRequestHeader("Method", "POST " + uri + " HTTP/1.1");
		x.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	}
	
	var responcefunc = function() {
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
					res = JSON.parse(data);
				else {
					eval('var res = '+data+'; res;');
				 }
				if(target_id) {
					document.getElementById(target_id).innerHTML = res;
				} else {
					callback(res, extra_data);
				}
			} catch(e) {
				sajax_debug("Caught error " + e + ": Could not parse " + data );
				return false;
			}
		}
		return true;
	}
	if(asynchronous) {
		x.onreadystatechange = responcefunc;
		
		var id = sajax_requests.length;
		sajax_requests[id] = x;
	}
	
	sajax_debug(func_name + " uri = " + uri + "/post = " + post_data);
	x.send(post_data);
	sajax_debug(func_name + " waiting..");
	
	if(asynchronous) {
		delete x;
		return id;
	} else {
		return responcefunc();
	}
}

function changeUser(user) {
	x_changeUser(user, changeUser_r);
	return true;
}

function changeUser_r(data) {
	//Todo, open pdf as a popup here since thers no othere way to wait for the responce;
	delayclick = false;
}

function $(id) {
	return document.getElementById(id);
}

function trim(inputString) {
	//Remove white space at the start and end of a string
	if (typeof inputString != "string") {
		return inputString;
	}
	inputString = inputString.replace(/^\s+|\s+$/g, "").replace(/\s{2,}/g, "");
	return inputString;
}

function getRadio(name) {
	var objs = document.getElementsByName(name);
	for (var i=0; i < objs.length; i++) {
		if(objs[i].checked) {
			return objs[i].value;
		}
	}
}

function getSelectValue(id) {
	var objs = $(id).getElementsByTagName('option');
	for (var i=0; i < objs.length; i++) {
		if(objs[i].selected) {
			return objs[i].value;
		}
	}
}

function fixedLength(string){
	while(string.length<2){
		string = '0'+string;
	}
	return string;
}

function standard(pakke) {
	switch(pakke) {
		case 0:
			$('height').value = '';
			$('width').value = '';
			$('length').value = '';
			//$('porto').value = '';
		break
		//SB5
		case 1:
			$('height').value = 17;
			$('width').value = 17;
			$('length').value = 18;
			calc();
		break
		//SB6
		case 2:
			$('height').value = 19;
			$('width').value = 19;
			$('length').value = 20;
			calc();
		break
		//SB27
		case 3:
			$('height').value = 19;
			$('width').value = 45;
			$('length').value = 45;
			calc();
		break
		//SB30
		case 4:
			$('height').value = 19;
			$('width').value = 10;
			$('length').value = 7;
			calc();
		break
		//SB34
		case 5:
			$('height').value = 38;
			$('width').value = 19;
			$('length').value = 17;
			calc();
		break
		case 6:
			$('height').value = 14;
			$('width').value = 24;
			$('length').value = 150;
			calc();
		break
		case 7:
			$('height').value = 17;
			$('width').value = 27;
			$('length').value = 150;
			calc();
		break
		case 8:
			$('height').value = 19;
			$('width').value = 29;
			$('length').value = 150;
			calc();
		break
		case 9:
			$('height').value = 14;
			$('width').value = 24;
			$('length').value = 200;
			calc();
		break
		case 10:
			$('height').value = 17;
			$('width').value = 27;
			$('length').value = 200;
			calc();
		break
		case 11:
			$('height').value = 19;
			$('width').value = 29;
			$('length').value = 200;
			calc();
		break
		case 12:
			$('height').value = 9;
			$('width').value = 9;
			$('length').value = 150;
			calc();
		break
		case 13:
			$('height').value = 9;
			$('width').value = 9;
			$('length').value = 200;
			calc();
		break
	}
}