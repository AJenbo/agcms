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

function nl2br( str ) {
	// http://kevin.vanzonneveld.net
	return str.replace(/([^>])\n/g, '$1<br />\n');
}

// remote scripting library
// (c) copyright 2005 modernmethod, inc
var sajax_debug_mode = false;
var sajax_request_type = "POST";
var sajax_target_id = "";
var sajax_failure_redirect = "";

function sajax_debug(text) {
	if (sajax_debug_mode)
		alert(text);
}

function sajax_init_object() {
	sajax_debug("sajax_init_object() called..");

	var A;

	if(XMLHttpRequest == "undefined" && ActiveXObject != "undefined") {
		var msxmlhttp = new Array(
			'Msxml2.XMLHTTP.5.0',
			'Msxml2.XMLHTTP.4.0',
			'Msxml2.XMLHTTP.3.0',
			'Msxml2.XMLHTTP',
			'Microsoft.XMLHTTP');
		for (var i = 0; i < msxmlhttp.length; i++) {
			try {
				A = new ActiveXObject(msxmlhttp[i]);
				continue;
			} catch (e) {
				A = null;
			}
		}
	} else {
		A = new XMLHttpRequest();
	}
	if(!A)
		sajax_debug("Could not create connection object.");
	return A;
}

var sajax_requests = new Array();

function sajax_cancel() {
	for (var i = 0; i < sajax_requests.length; i++) 
		sajax_requests[i].abort();
}

function sajax_do_call(func_name, args) {
	var i, x, n;
	var uri;
	var post_data;
	var target_id;
	
	sajax_debug("in sajax_do_call().." + sajax_request_type + "/" + sajax_target_id);
	target_id = sajax_target_id;
	if (typeof(sajax_request_type) == "undefined" || sajax_request_type == "") 
		sajax_request_type = "GET";
	
	uri = window.location;
	if (sajax_request_type == "GET") {
	
		if (uri.indexOf("?") == -1) 
			uri += "?rs=" + encodeURIComponent(func_name);
		else
			uri += "&rs=" + encodeURIComponent(func_name);
		uri += "&rst=" + encodeURIComponent(sajax_target_id);
		uri += "&rsrnd=" + new Date().getTime();
		
		for (i = 0; i < args.length-1; i++) 
			uri += "&rsargs[]=" + encodeURIComponent(args[i]);

		post_data = null;
	} 
	else if (sajax_request_type == "POST") {
		post_data = "rs=" + escape(func_name);
		post_data += "&rst=" + escape(sajax_target_id);
		post_data += "&rsrnd=" + new Date().getTime();
		
		for (i = 0; i < args.length-1; i++) 
			post_data = post_data + "&rsargs[]=" + encodeURIComponent(args[i]);
	}
	else {
		alert("Illegal request type: " + sajax_request_type);
	}
	
	x = sajax_init_object();
	if (x == null) {
		if (sajax_failure_redirect != "") {
			location.href = sajax_failure_redirect;
			return false;
		} else {
			sajax_debug("NULL sajax object for user agent:\n" + navigator.userAgent);
			return false;
		}
	} else {
		x.open(sajax_request_type, uri, true);
		// window.open(uri);
		
		sajax_requests[sajax_requests.length] = x;
		
		if (sajax_request_type == "POST") {
			x.setRequestHeader("Method", "POST " + uri + " HTTP/1.1");
			x.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		}
	
		x.onreadystatechange = function() {
			if (x.readyState != 4) 
				return;

			sajax_debug("received " + x.responseText);
		
			var status;
			var data;
			var txt = x.responseText.replace(/^\s*|\s*$/g,"");
			status = txt.charAt(0);
			data = txt.substring(2);

			if (status == "") {
				// let's just assume this is a pre-response bailout and let it slide for now
			} else if (status == "-") 
				alert("Error: " + data);
			else {
				if (target_id != "") 
					document.getElementById(target_id).innerHTML = eval(data);
				else {
					try {
						var callback;
						var extra_data = false;
						if (typeof args[args.length-1] == "object") {
							callback = args[args.length-1].callback;
							extra_data = args[args.length-1].extra_data;
						} else {
							callback = args[args.length-1];
						}
						callback(eval(data), extra_data);
					} catch (e) {
						sajax_debug("Caught error " + e + ": Could not eval " + data );
					}
				}
			}
		}
	}
	
	sajax_debug(func_name + " uri = " + uri + "/post = " + post_data);
	x.send(post_data);
	sajax_debug(func_name + " waiting..");
	delete x;
	return true;
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

function htmlspecialchars (string, quote_style) {
    // http://kevin.vanzonneveld.net
    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: get_html_translation_table
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
 
    var histogram = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();
    
    if (false === (histogram = get_html_translation_table('HTML_SPECIALCHARS', quote_style))) {
        return false;
    }
    
    for (symbol in histogram) {
        entity = histogram[symbol];
        tmp_str = tmp_str.split(symbol).join(entity);
    }
    
    return tmp_str;
}

function get_html_translation_table(table, quote_style) {
    // http://kevin.vanzonneveld.net
    // +   original by: Philip Peterson
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: noname
    // +   bugfixed by: Alex
    // +   bugfixed by: Marco
    // +   bugfixed by: madipta
    // %          note: It has been decided that we're not going to add global
    // %          note: dependencies to php.js. Meaning the constants are not
    // %          note: real constants, but strings instead. integers are also supported if someone
    // %          note: chooses to create the constants themselves.
    // %          note: Table from http://www.the-art-of-web.com/html/character-codes/
    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
    // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
    
    var entities = {}, histogram = {}, decimal = 0, symbol = '';
    var constMappingTable = {}, constMappingQuoteStyle = {};
    var useTable = {}, useQuoteStyle = {};
    
    useTable      = (table ? table.toUpperCase() : 'HTML_SPECIALCHARS');
    useQuoteStyle = (quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT');
    
    // Translate arguments
    constMappingTable[0]      = 'HTML_SPECIALCHARS';
    constMappingTable[1]      = 'HTML_ENTITIES';
    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
    constMappingQuoteStyle[2] = 'ENT_COMPAT';
    constMappingQuoteStyle[3] = 'ENT_QUOTES';
    
    // Map numbers to strings for compatibilty with PHP constants
    if (!isNaN(useTable)) {
        useTable = constMappingTable[useTable];
    }
    if (!isNaN(useQuoteStyle)) {
        useQuoteStyle = constMappingQuoteStyle[useQuoteStyle];
    }
 
    if (useTable == 'HTML_SPECIALCHARS') {
        // ascii decimals for better compatibility
        entities['38'] = '&amp;';
        if (useQuoteStyle != 'ENT_NOQUOTES') {
            entities['34'] = '&quot;';
        }
        if (useQuoteStyle == 'ENT_QUOTES') {
            entities['39'] = '&#039;';
        }
        entities['60'] = '&lt;';
        entities['62'] = '&gt;';
    } else if (useTable == 'HTML_ENTITIES') {
        // ascii decimals for better compatibility
      entities['38']  = '&amp;';
        if (useQuoteStyle != 'ENT_NOQUOTES') {
            entities['34'] = '&quot;';
        }
        if (useQuoteStyle == 'ENT_QUOTES') {
            entities['39'] = '&#039;';
        }
      entities['60']  = '&lt;';
      entities['62']  = '&gt;';
      entities['160'] = '&nbsp;';
      entities['161'] = '&iexcl;';
      entities['162'] = '&cent;';
      entities['163'] = '&pound;';
      entities['164'] = '&curren;';
      entities['165'] = '&yen;';
      entities['166'] = '&brvbar;';
      entities['167'] = '&sect;';
      entities['168'] = '&uml;';
      entities['169'] = '&copy;';
      entities['170'] = '&ordf;';
      entities['171'] = '&laquo;';
      entities['172'] = '&not;';
      entities['173'] = '&shy;';
      entities['174'] = '&reg;';
      entities['175'] = '&macr;';
      entities['176'] = '&deg;';
      entities['177'] = '&plusmn;';
      entities['178'] = '&sup2;';
      entities['179'] = '&sup3;';
      entities['180'] = '&acute;';
      entities['181'] = '&micro;';
      entities['182'] = '&para;';
      entities['183'] = '&middot;';
      entities['184'] = '&cedil;';
      entities['185'] = '&sup1;';
      entities['186'] = '&ordm;';
      entities['187'] = '&raquo;';
      entities['188'] = '&frac14;';
      entities['189'] = '&frac12;';
      entities['190'] = '&frac34;';
      entities['191'] = '&iquest;';
      entities['192'] = '&Agrave;';
      entities['193'] = '&Aacute;';
      entities['194'] = '&Acirc;';
      entities['195'] = '&Atilde;';
      entities['196'] = '&Auml;';
      entities['197'] = '&Aring;';
      entities['198'] = '&AElig;';
      entities['199'] = '&Ccedil;';
      entities['200'] = '&Egrave;';
      entities['201'] = '&Eacute;';
      entities['202'] = '&Ecirc;';
      entities['203'] = '&Euml;';
      entities['204'] = '&Igrave;';
      entities['205'] = '&Iacute;';
      entities['206'] = '&Icirc;';
      entities['207'] = '&Iuml;';
      entities['208'] = '&ETH;';
      entities['209'] = '&Ntilde;';
      entities['210'] = '&Ograve;';
      entities['211'] = '&Oacute;';
      entities['212'] = '&Ocirc;';
      entities['213'] = '&Otilde;';
      entities['214'] = '&Ouml;';
      entities['215'] = '&times;';
      entities['216'] = '&Oslash;';
      entities['217'] = '&Ugrave;';
      entities['218'] = '&Uacute;';
      entities['219'] = '&Ucirc;';
      entities['220'] = '&Uuml;';
      entities['221'] = '&Yacute;';
      entities['222'] = '&THORN;';
      entities['223'] = '&szlig;';
      entities['224'] = '&agrave;';
      entities['225'] = '&aacute;';
      entities['226'] = '&acirc;';
      entities['227'] = '&atilde;';
      entities['228'] = '&auml;';
      entities['229'] = '&aring;';
      entities['230'] = '&aelig;';
      entities['231'] = '&ccedil;';
      entities['232'] = '&egrave;';
      entities['233'] = '&eacute;';
      entities['234'] = '&ecirc;';
      entities['235'] = '&euml;';
      entities['236'] = '&igrave;';
      entities['237'] = '&iacute;';
      entities['238'] = '&icirc;';
      entities['239'] = '&iuml;';
      entities['240'] = '&eth;';
      entities['241'] = '&ntilde;';
      entities['242'] = '&ograve;';
      entities['243'] = '&oacute;';
      entities['244'] = '&ocirc;';
      entities['245'] = '&otilde;';
      entities['246'] = '&ouml;';
      entities['247'] = '&divide;';
      entities['248'] = '&oslash;';
      entities['249'] = '&ugrave;';
      entities['250'] = '&uacute;';
      entities['251'] = '&ucirc;';
      entities['252'] = '&uuml;';
      entities['253'] = '&yacute;';
      entities['254'] = '&thorn;';
      entities['255'] = '&yuml;';
    } else {
        throw Error("Table: "+useTable+' not supported');
        return false;
    }
    
    // ascii decimals to real symbols
    for (decimal in entities) {
        symbol = String.fromCharCode(decimal);
        histogram[symbol] = entities[decimal];
    }
    
    return histogram;
}