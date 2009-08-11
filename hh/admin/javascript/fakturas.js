function getAddress(tlf) {
	x_getAddress(tlf, getAddress_r);
}

function getAddress_r(data) {
	if(data['error']) {
		alert(data['error']);
	} else {
		$('navn').value = data['recName1'];
		$('att').value = data['recAttPerson'];
		$('adresse').value = data['recAddress1'];
		$('postnr').value = data['recZipCode'];
		var zip = arrayZipcode[data['recZipCode']];
		if(zip != 'undefined') $('by').value = zip;
		$('postbox').value = data['recPostBox'];
		$('email').value = data['email'];
		
		cloneToPrint();
		setEmailLink();
	}
}

function prisUpdate() {
	quantities = '';
	products = '';
	values = '';

	var quantitieObjs = document.getElementsByName('quantitie');
	var productObjs = document.getElementsByName('product');
	var valueObjs = document.getElementsByName('value');
	var quantitiePrintObjs = $$('.quantitie');
	var productPrintObjs = $$('.product');
	var valuePrintObjs = $$('.value');
	var totalPrintObjs = $$('.total.printinline');
	var totalWebObjs = $$('.total.web');
	var premoms = $('premoms').checked;
	var momssats = parseFloat($('momssats').value);
	
	var netto = 0;
	
	var quantitie;
	var value;
	var total;

	for(var i=0;i<quantitieObjs.length;i++) {
		quantitiePrintObjs[i].innerHTML = htmlspecialchars(quantitieObjs[i].value);
		productPrintObjs[i].innerHTML = htmlspecialchars(productObjs[i].value);
	
		quantitie = 0;
		value = 0;
		total = 0;
		quantitie = parseInt(quantitieObjs[i].value);
		if(isNaN(quantitie))
			quantitie = 0;
			
		value = parseFloat(parseFloat(valueObjs[i].value.replace(/[^-0-9,]/g,'').replace(/,/,'.')).toFixed(2));

		if(isNaN(value))
			value = 0;
		if(premoms)
			value = value/(1+momssats);
		
		if(value != 0)
			valuePrintObjs[i].innerHTML = value.toFixed(2).toString().replace(/\./,',');
		else
			valuePrintObjs[i].innerHTML = '';
		
		total = quantitie*value;
		
		if(total != 0) {
			totalPrintObjs[i].innerHTML = total.toFixed(2).toString().replace(/\./,',');
			if(premoms)
				totalWebObjs[i].innerHTML = (total*(1+momssats)).toFixed(2).toString().replace(/\./,',');
			else
				totalWebObjs[i].innerHTML = total.toFixed(2).toString().replace(/\./,',');
		} else {
			totalPrintObjs[i].innerHTML = '';
			totalWebObjs[i].innerHTML = '';
		}
			
		netto += total;
		
		if(quantitieObjs[i].value != '' || productObjs[i].value != '' || valueObjs[i].value != '') {
			if(quantities != '') {
				quantities += '<';
				products += '<';
				values += '<';
			}
			quantities +=  quantitie.toString();
			products +=  htmlspecialchars(productObjs[i].value.toString());
			if(premoms)
				values +=  value*(1+momssats).toString();
			else
				values +=  value.toString();
		}
	}
	
	$('netto').innerHTML = netto.toFixed(2).toString().replace(/\./,',');
	
	$('moms').innerHTML = (netto*momssats).toFixed(2).toString().replace(/\./,',');
	
	var fragt = parseFloat($('fragt').value.replace(/[^-0-9,]/g,'').replace(/,/,'.'));
	if(isNaN(fragt))
		fragt = 0;
	
	$('payamount').innerHTML = parseFloat(fragt + netto + netto * momssats).toFixed(2).toString().replace(/\./,',');

	if(quantitieObjs[quantitieObjs.length-1].value != '' || productObjs[productObjs.length-1].value != '' || valueObjs[valueObjs.length-1].value != '')
		addRow();
	
	cloneToPrint();
	setEmailLink();
	
	return true;
}

function setEmailLink() {
	if($('email').value) {
		$('emaillink').href = 'mailto:'+$('email').value+'?subject=Elektronisk faktura vedr. ordre&body=Tak for ordren. Vi vedlægger her en elektronisk faktura.%0A%0AKlik venligst pånedenstående link og udfyld formularen.%0A%0Ahttp%3A%2F%2Fhuntershouse.dk%2Ffaktura%2F%3Fid%3D'+id+'%26checkid%3D'+checkid+'%0A%0AMed venlig hilsen%2C Hunters House.';
		$('emaillink').style.display = '';
	} else {
		$('emaillink').style.display = 'none';
	}
}

function cloneToPrint() {
	var postadresse = $('navn').value;
	if($('att').value != '') postadresse += '<br />Att.: '+$('att').value;
	if($('adresse').value != '') postadresse += '<br />'+$('adresse').value;
	if($('postbox').value != '') postadresse += '<br />'+$('postbox').value;
	if($('postnr').value != ''){
		var zip = parseInt($('postnr').value);
		if(isNaN(zip) == true)
			zip = 0; 
		var bynavn = arrayZipcode[zip];
		if(typeof(bynavn) != 'undefined')
			$('by').value = bynavn;
		postadresse += '<br />'+$('postnr').value+' '+$('by').value;
	}
	else postadresse += '<br />'+$('by').value;
	if($('land').value != '') postadresse += '<br />'+$('land').value;
	$('postadressetd').innerHTML = postadresse;
	$$('.date')[0].innerHTML = $('date').value;
	$$('.iref')[0].innerHTML = $('iref').value;
	$$('.eref')[0].innerHTML = $('eref').value;
	$$('.fragt')[0].innerHTML = parseFloat($('fragt').value).toFixed(2).toString().replace(/\./,',');
	$$('.momssats')[0].innerHTML = (parseFloat($('momssats').value)*100).toString()+'%';
	$$('.note')[0].innerHTML = nl2br($('note').value);
	$$('.clerk')[0].innerHTML = $('clerk').value;
}

function save(type) {
	$('loading').style.display = '';
	if(type != 'cancel')
		prisUpdate();
	if(quantities.length > 64 || products.length > 65000 || values.length > 128) {
		alert('Kan ikke genne alle vare på listen!');
		return false;
	}
	
	if($('premoms').checked)
		var premoms = 1;
	else
		var premoms = 0;
	
	x_save(type, id, quantities, products, values, parseFloat($('fragt').value.replace(/[^-0-9,]/g,'').replace(/,/,'.')).toFixed(2), $('momssats').value, premoms, $('date').value, $('iref').value, $('eref').value, $('navn').value, $('att').value, $('adresse').value, $('postbox').value, $('postnr').value, $('by').value, $('land').value, $('email').value, $('tlf1').value, $('tlf2').value, $('note').value, $('department').value, save_r);
}

function save_r(data) {
	$('loading').style.display = 'none';
	
	if(data['error']) {
		alert(data['error']);
		return false;
	} else if(!data) {
		alert('Kunne ikke gemme siden!');
		return false;
	}
	
	if(data['type'] == 'cancel') {
		location.href = 'fakturas.php';
	}
	if(data['type'] == 'lock') {
		$('main').className = '';
		$('savebn').style.display = 'none';
		$('lockbn').style.display = 'none';
		$('printbn').style.display = '';
		$('postadresse').className = '';
		
		var webobjs = $$('.web');
		for(var i=0;i<webobjs.length;i++) {
			webobjs[i].style.display = 'none';
		}
		$('menu').style.display = '';
		
		var printobjs = $$('.printinline');
		for(var i=0;i<printobjs.length;i++) {
			printobjs[i].className = printobjs[i].className.replace(/[\s]*printinline[\s]*/, '');
		}
		$$('.note')[0].className = 'note';
		var printobjs = $$('#spec .printblock');
		for(var i=0;i<printobjs.length;i++) {
			printobjs[i].className = printobjs[i].className.replace(/[\s]*printblock[\s]*/, '');
		}
		//TODO remove when it is moved to the menu
		$('webfunction').style.display = '';
	}
}

function ny_r(id) {
	 location.href='?id='+id;
}

function removeRow(row) {
	$('vareTable').removeChild(row.parentNode.parentNode);
	if($('vareTable').childNodes.length == 0)
		addRow();
	prisUpdate();
}

function addRow() {
	var tr = document.createElement('tr');
	var td = document.createElement('td');
	td.innerHTML = '<input name="quantitie" style="width:58px;" class="tal web" value="" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /><p class="printblock quantitie tal"></p>';
	tr.appendChild(td);
	td = document.createElement('td');
	td.innerHTML = '<input name="product" style="width:303px;" class="web" value="" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /><p class="printblock product"></p>';
	tr.appendChild(td);
	td = document.createElement('td');
	td.innerHTML = '<input name="value" style="width:69px;" class="tal web" value="" onkeyup="prisUpdate()" onchange="prisUpdate()" onblur="prisUpdate()" /><p class="printblock value tal"></p>';
	tr.appendChild(td);
	td = document.createElement('td');
	td.className = 'tal';
	td.innerHTML = '<span class="total web"></span><span class="total printinline"></span>';
	tr.appendChild(td);
	td = document.createElement('td');
	td.className = 'web';
	td.style.border = '0';
	td.style.fontWeight = 'bold';
	td.innerHTML = '<a href="#" onclick="removeRow(this); return false"><img alt="X" src="images/cross.png" height="16" width="16" title="Fjern linje" /></a>';
	tr.appendChild(td);
	$('vareTable').appendChild(tr);
}

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
	
	uri = window.location;

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

		
// wrapper for save		
function x_save() {
	sajax_do_call("save", arguments);
}
		
// wrapper for lock		
function x_lock() {
	sajax_do_call("lock", arguments);
}
		
// wrapper for save		
function x_cancel() {
	sajax_do_call("cancel", arguments);
}

		
// wrapper for ny		
function x_ny() {
	$('loading').style.display = '';
	sajax_do_call("ny", arguments);
}
		
// wrapper for getAddress		
function x_getAddress() {
	sajax_do_call("getAddress", arguments);
}