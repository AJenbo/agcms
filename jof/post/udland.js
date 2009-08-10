

function sajax_do_call(func_name, args) {
	var i, x, n;
	var uri;
	var post_data;
	var target_id;
	
	sajax_debug("in sajax_do_call().." + sajax_request_type + "/" + sajax_target_id);
	target_id = sajax_target_id;
	if (typeof(sajax_request_type) == "undefined" || sajax_request_type == "") 
		sajax_request_type = "GET";
	
	uri = "/post/udland.php";
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
		post_data = "rs=" + encodeURIComponent(func_name);
		post_data += "&rst=" + encodeURIComponent(sajax_target_id);
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

// wrapper for changeUser		
function x_getAdress() {
	sajax_do_call("getAdress",
		x_getAdress.arguments);
}

		
// wrapper for getPDFURL		
function x_getPDFURL() {
	sajax_do_call("getPDFURL",
		x_getPDFURL.arguments);
}

function calc() {
	var height = parseFloat($('height').value.replace(/,/,'.'));
	if(isNaN(height))
		height = 0;
	var width = parseFloat($('width').value.replace(/,/,'.'));
	if(isNaN(width))
		width = 0;
	var length = parseFloat($('length').value.replace(/,/,'.'));
	if(isNaN(length))
		length = 0;
	var weight = parseFloat($('weight').value.replace(/,/,'.'));
	if(isNaN(weight))
		weight = 0;
		
	var calcvolumev = calcvolume(height, width, length);

	//Volume
	if($('height').value || $('width').value || $('length').value){
		if(calcvolumev && getRadio('optRecipType') == 'P') {
			$('volumeIcon').style.display = '';
			$('ss2').checked = true;
		} else {
			$('volumeIcon').style.display = 'none';
			$('ss2').checked = false;
		}
	}
	
	if((height != 0 && width != 0 && length != 0) || weight != 0)
		weight = Math.max(5, weight);

	var pakkeprisv = pakkepris(height, width, length, weight, getRadio('optRecipType'), $('ss1').checked, $('ss46').checked, parseFloat($('ss5amount').value.replace(/,/,'.')), false);

	if(!pakkeprisv)
		return false;
	if(pakkeprisv > 60)
		$('porto').value = Math.ceil(pakkeprisv/5)*5;
	else
		$('porto').value = pakkeprisv;
}

function validate() {
	if($('loading').style.display == '') {
		alert('Du er allerede i gang med at generer en PDF');
		return false;
	}
	
	calc();
	chnageZipCode($('recZipCode').value);
	$('recName1').value = trim($('recName1').value);
	$('recAddress1').value = trim($('recAddress1').value);
	
	if($('formSenderID').value == 0) {
		alert('Du skal vælge en afsender');
		$('formSenderID').focus();
		return false;
	}
	
	if($('porto').value < 1 && !$('ub').checked) {
		alert('Du skal indtaste den beregnet fragt pris.');
		$('porto').focus();
		return false;
	}
	
	if($('recName1').value == "") {
		alert('Første del af feltet "Navn" skal udfyldes.');
		$('recName1').focus();
		return false;
	}
	if($('recAddress1').value.indexOf(' ') == -1 && $('recPostBox').value == "") {
		alert('"Adresse" skal indeholde både gadenavn og husnummer.');
		$('recAddress1').focus();
		return false;
	}
	
	if($('recCityName').value == "") {
		alert('Feltet "Postnr." skal udfyldes med et gyldigt postnr.');
		$('recZipCode').focus();
		return false;
	}

	if(getRadio('optRecipType') == 'O') {
		if(isNaN(parseInt($('recPoValue').value)) || parseInt($('recPoValue').value) < 1 || parseInt($('recPoValue').value) > 1000000) {
			alert("Ved valg af postopkrævning skal beløb være mellem 1 & 999999.");
			$('recPoValue').focus();
			return false;
		}
		if(parseInt($('recPoValue').value) < parseInt($('porto').value)) {
			alert("Fragt omkostningerne må ikke overstige postopkrævnings beløbet.");
			$('recPoValue').focus();
			return false;
		}
	}
	if(parseInt($('weight').value) > 50) {
		alert('Bruttovægt må maksimalt være 50 kg.');
		$('weight').focus();
		return false;
	}
	$('loading').style.display = '';
	x_changeUser($('formSenderID').value, getPDFURL);
}

function getPDFURL(checkAddress) {
	if(checkAddress != false)
		checkAddress = true;
	x_getPDFURL(
		getRadio('optRecipType'),
		$('recZipCode').value,
		$('recCityName').value,
		parseInt($('recPoValue').value),
		$('recPoPostOffice').value,
		$('recipientID').value,
		$('recCVR').value,
		$('recAttPerson').value,
		$('recName1').value,
		$('recName2').value,
		$('orderID').value,
		$('recAddress1').value,
		$('recAddress2').value,
		$('recPostBox').value,
		$('remarks').value,
		$('formDate').value,
		parseInt($('weight').value),
		$('emailChecked').checked,
		$('email').value,
		$('emailTxt').value,
		$('c_no').value,
		'',//c_w
		'',//c_rem
		$('ss1').checked,
		$('ss2').checked,
		$('ss46').checked,
		parseInt($('ss5amount').value),
		checkAddress,
		$('height').value,
		$('width').value,
		$('length').value,
		$('porto').value,
		$('ub').checked,
		$('formSenderID').value,
		openPDF);
}

function openPDF(data) {
	$('loading').style.display = 'none';
	if(data['error']) {
		alert(data['error']);
	} else if(data['url']) {
		window.open('http://www.postdanmark.dk/pfs/PfsLabelServlet?buttonPressed=Print&clientID='+data['clientID']+'&userID=admin&token=&programID=&sessionID=&accessCode=&exTime=&forsID='+data['url'], '_blank');
	} else if(data['yesno']) {
		if(confirm('Adressen blev ikke godkendt, ønsker du alligevel at udskrive?')) {
			getPDFURL(false);
		}
	} else {
		alert('Der opstod en fejl i programmet.');
	}
}

function cancel() {
	sajax_cancel();
	$('loading').style.display = 'none';
}

function removerow(row) {
	$('kolliTable').removeChild(row.parentNode.parentNode);
	for(i=0;i<$('kolliTable').childNodes.length;i++) {
		$('kolliTable').childNodes[i].firstChild.innerHTML = 1+i+'.';
		$('kolliTable').childNodes[i].childNodes[1].firstChild.name = 'c_'+i+'_w';
		$('kolliTable').childNodes[i].childNodes[2].firstChild.name = 'c_'+i+'_rem';
	}
	$('c_no').value = $('kolliTable').childNodes.length;
}

function addrow() {
	var tr = document.createElement('tr');
	var td = document.createElement('td');
	td.innerHTML = 1+$('kolliTable').childNodes.length+'.';
	tr.appendChild(td);
	td = document.createElement('td');
	td.innerHTML = '<input style="width:42px" maxlength="5" name="c_'+$('kolliTable').childNodes.length+'_w" size="2" /> kg';
	tr.appendChild(td);
	td = document.createElement('td');
	td.innerHTML = '<input name="c_'+$('kolliTable').childNodes.length+'_rem" />';
	tr.appendChild(td);
	td = document.createElement('td');
	td.innerHTML = '<a href="" onclick="removerow(this);return false">X</a></td>';
	tr.appendChild(td);
	$('kolliTable').appendChild(tr);
	$('c_no').value = $('kolliTable').childNodes.length;
}

function chnageZipCode(zipcode) {
	if(!arrayZipcode[zipcode]) {
		$('recCityName').value = '';
	} else {
		$('recCityName').value = arrayZipcode[zipcode];
	}
}

function changeReturPakkeRadio(value) {
	if(value) {
		if(value == 1) {
			$('returPakkeRadio2').checked = false;
			$('returPakkeRadio3').checked = false;
		} else if(value == 2) {
			$('returPakkeRadio1').checked = false;
			$('returPakkeRadio3').checked = false;
		} else if(value == 3) {
			$('returPakkeRadio1').checked = false;
			$('returPakkeRadio2').checked = false;
		}
	}
}

function init() {
	standard(0);
	changeOptRecipType();
	clickEmailChecked($('emailChecked').checked)
	var month=new Array(12);
	month[0]="Jan";
	month[1]="Feb";
	month[2]="Mar";
	month[3]="Apr";
	month[4]="May";
	month[5]="Jun";
	month[6]="Jul";
	month[7]="Aug";
	month[8]="Sep";
	month[9]="Okt";
	month[10]="Nov";
	month[11]="Dec";
	
	var obj = $('formDate');
	var today = new Date();
	for(i=0;i<7;i++) {
		var d = new Date(today.getTime()+86400000*i);
		obj.options[i].text = fixedLength(d.getDate().toString())+'. '+month[d.getMonth()]+' '+d.getFullYear().toString();
		obj.options[i].value = fixedLength(d.getDate().toString())+'.'+fixedLength((d.getMonth()+1).toString())+'.'+d.getFullYear().toString();
	}
	obj.selectedIndex = 0;
	chnageZipCode($('recZipCode').value);
}

function changeRecAddress1(value) {
	if(value != "") {
		$('recPostBox').value = '';
		$('recPostBox').disabled = true;
	} else {
		$('recPostBox').disabled = false;
	}
}

function changeRecPostBox(value) {
	if(value != "") {
		$('recAddress1').value = '';
		$('recAddress1').disabled = true;
	} else {
		$('recAddress1').disabled = false;
	}
}

function clickEmailChecked(checked) {
	if(checked)
		$('emailTxt').style.display = '';
	else
		$('emailTxt').style.display = 'none';
}

function getAdress(id) {
	if(id)
		x_getAdress(id, getAdress_r);
}

function getAdress_r(data) {
	if(data['error'])
		alert(data['error']);
	else {
		$('recCVR').value = data['recCVR'];
		$('recName1').value = data['recName1'];
		$('recAttPerson').value = data['recAttPerson'];
		$('recAddress1').value = data['recAddress1'];
		$('recAddress2').value = data['recAddress2'];
		$('recPostBox').value = data['recPostBox'];
		$('recZipCode').value = data['recZipCode'];
		$('email').value = data['email'];
		chnageZipCode(data['recZipCode']);
			
		if(data['recPostBox'])
			changeRecPostBox(data['recPostBox']);
		else if(data['recAddress1'])
			changeRecAddress1(data['recAddress1']);
	}
}

function postdkloaded() {
	$('postdkloading').style.display = 'none';
}

function changeOptRecipType() {
	if(getRadio('optRecipType') == 'P') {
//		$('trCvr').childNodes[0].style.color = '#999999';
//		$('trCvr').childNodes[1].firstChild.disabled = true;
		$('trCvr').style.display = 'none';
		$('trCvr').childNodes[1].firstChild.value = '';
//		$('trReturPakkeRadio1').style.display = '';
//		$('trReturPakkeRadio2').style.display = '';
//		$('trReturPakkeRadio3').style.display = '';
//		$('trRecPo1').style.display = 'none';
//		$('trRecPo2').style.display = 'none';
		$('recPoValue').value = '';
		$('trRecPo3').style.display = 'none';
		$('trExpress').style.display = '';
		$('trVolume').style.display = '';
	} else if(getRadio('optRecipType') == 'E') {
//		$('trCvr').childNodes[0].style.color = '#000000';
//		$('trCvr').childNodes[1].firstChild.disabled = false;
		$('trCvr').style.display = '';
//		$('trReturPakkeRadio1').style.display = '';
//		$('trReturPakkeRadio2').style.display = '';
//		$('trReturPakkeRadio3').style.display = '';
//		$('trRecPo1').style.display = 'none';
//		$('trRecPo2').style.display = 'none';
		$('recPoValue').value = '';
		$('trRecPo3').style.display = 'none';
		$('trExpress').style.display = 'none';
		$('ss46').checked = false;
		$('trVolume').style.display = 'none';
		$('ss2').checked = false;
	} else if(getRadio('optRecipType') == 'O') {
//		$('trCvr').childNodes[0].style.color = '#000000';
//		$('trCvr').childNodes[1].firstChild.disabled = false;
		$('trCvr').style.display = '';
//		$('trReturPakkeRadio1').style.display = 'none';
//		$('trReturPakkeRadio2').style.display = 'none';
//		$('trReturPakkeRadio3').style.display = 'none';
//		$('trRecPo1').style.display = '';
//		$('trRecPo2').style.display = '';
		$('trRecPo3').style.display = '';
		$('trExpress').style.display = 'none';
		$('ss46').checked = false;
		$('trVolume').style.display = 'none';
		$('ss2').checked = false;
	}
	calc();
}