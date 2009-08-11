function trim(inputString) {
	//Remove white space at the start and end of a string
	if (typeof inputString != "string") {
		return inputString;
	}
	inputString = inputString.replace(/^\s+|\s+$/g, "").replace(/\s{2,}/g, "");
	return inputString;
}
function selectedCountry(country) {
	if(country != '') {
		document.getElementById('land').value = country;
		document.getElementById('hiddencountry').style.display = 'none';
	} else {
		document.getElementById('hiddencountry').style.display = '';
		document.getElementById('land').value = '';
	}
}
function validate() {
	document.getElementById('navn').value = trim(document.getElementById('navn').value);
	document.getElementById('att').value = trim(document.getElementById('att').value);
	document.getElementById('adresse').value = trim(document.getElementById('adresse').value);
	document.getElementById('postnr').value = trim(document.getElementById('postnr').value);
	document.getElementById('land').value = trim(document.getElementById('land').value);
	document.getElementById('tlf1').value = trim(document.getElementById('tlf1').value);
	document.getElementById('tlf2').value = trim(document.getElementById('tlf2').value);
	document.getElementById('email').value = trim(document.getElementById('email').value);
	if(!document.getElementById('by').value)
		chnageZipCode(document.getElementById('postnr').value);
	if(document.getElementById('navn').value.length < 3 || document.getElementById('navn').value.indexOf(' ') == -1) {
		alert('Feltet "Navn:" skal udfyldes.');
		document.getElementById('navn').focus();
		return false;
	}
	if(document.getElementById('adresse').value.length < 3 || document.getElementById('adresse').value.indexOf(' ') == -1) {
		alert('"Adresse:" skal indeholde både gadenavn og husnummer.');
		document.getElementById('adresse').focus();
		return false;
	}
	if(document.getElementById('postnr').value.length < 3) {
		alert('Feltet "Postnr:" skal udfyldes med et gyldigt postnr.');
		document.getElementById('postnr').focus();
		return false;
	}
	if(document.getElementById('by').value.length == '') {
		alert('Feltet "By:" skal udfyldes med et gyldigt by navn.');
		document.getElementById('by').focus();
		return false;
	}
	if(document.getElementById('land').value.length == '') {
		alert('Feltet "Land:" skal udfyldes med et gyldigt lande navn.');
		document.getElementById('land').focus();
		return false;
	}
	if(document.getElementById('email').value.match(/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i) == null && document.getElementById('tlf1').value.length == '' && document.getElementById('tlf2').value.length == '') {
		alert('Du skal indtaste en gyltig email eller et telefon nummer.');
		document.getElementById('email').focus();
		return false;
	}
	return true;
}

function chnageZipCode(zipcode) {
	if(!arrayZipcode[zipcode]) {
	} else {
		document.getElementById('by').value = arrayZipcode[zipcode];
	}
}