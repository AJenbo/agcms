
function calcPricePart(product) {
	
	var price = 0;
	var kg = Math.ceil(parseFloat($('kg').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	if(isNaN(kg)) {
		kg = 1;
	}
	
	var insurance = Math.ceil(parseFloat($('insurance').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	var l = Math.ceil(parseFloat($('l').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	var w = Math.ceil(parseFloat($('w').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	var h = Math.ceil(parseFloat($('h').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	
	//Budget
	if(product == 359) {
		if($('country').value == 'GL') {
			//Grønland
			price = 128;
			price += 20*Math.ceil(kg);
		} else if($('country').value == 'FO') {
			//Færøerne
			price = 136;
			price += 9*Math.ceil(kg);
		} else if($('country').value == 'IS') {
			//Island
			price = 156;
			price += 13*Math.ceil(kg);
		} else if(isAmerika($('country').value)) {
			//Amerika
			price = 156;
			price += 37*Math.ceil(kg);
		} else if(isAfricaAsienOrMiddeleast($('country').value)) {
			//Afrika, Asien og Mellemøsten
			price = 156;
			price += 39*Math.ceil(kg);
		} else {
			//Øvrige verden
			price = 156;
			price += 43*Math.ceil(kg);
		}
		
		if(insurance) {
			if(isEU($('country').value)) {
				//EU
				price += 70;
			} else {
				//Øvrige lande
				price += 100;
			}
		}
		
		if(isVolume(w, l, h))
			price += 80;
	}
	
	//HomeShopping
	if(product == 340) {
		if($('country').value == 'GL') {
			//Grønland
			price = 141;
			price += 75*Math.ceil(kg);
		} else if($('country').value == 'FO') {
			//Færøerne
			price = 156;
			price += 21*Math.ceil(kg);
		} else if($('country').value == 'FI' ||
		$('country').value == 'NO' ||
		$('country').value == 'SE' ||
		$('country').value == 'DE' ||
		$('country').value == 'AX') {
			//Priszone 1
			price = 156;
			price += 13*Math.ceil(kg);
		} else if(isEurop($('country').value)) {
			//Priszone 2
			price = 156;
			price += 21*Math.ceil(kg);
		} else if(isAmerika($('country').value)) {
			//Amerika
			price = 156;
			price += 61*Math.ceil(kg);
		} else if(isAfricaAsienOrMiddeleast($('country').value)) {
			//Afrika, Asien og Mellemøsten
			price = 156;
			price += 71*Math.ceil(kg);
		} else {
			//Øvrige verden
			price = 156;
			price += 81*Math.ceil(kg);
		}
		
		if(insurance) {
			if(isEU($('country').value)) {
				//EU
				price += 70;
			} else {
				//Øvrige lande
				price += 100;
			}
		}
		
		if(isVolume(w, l, h))
			price += 80;
	}
	
	//Business
	if(product == 330) {
		var vkg = Math.ceil(l*w*h/4000);
		if(vkg > kg)
			kg = vkg;
		
		if($('country').value == 'FI' ||
		$('country').value == 'NO' ||
		$('country').value == 'SE' ||
		$('country').value == 'DE' ||
		$('country').value == 'AX') {
			//Priszone 1
			price = 161;
			price += 13*Math.ceil(kg);
		} else if($('country').value == 'BE' ||
		$('country').value == 'IS' ||
		$('country').value == 'IE' ||
		$('country').value == 'LU' ||
		$('country').value == 'NL' ||
		$('country').value == 'GB') {
			//Priszone 2
			price = 171;
			price += 14*Math.ceil(kg);
		} else if($('country').value == 'FR' ||
		$('country').value == 'IT' ||
		$('country').value == 'MC' ||
		$('country').value == 'PT' ||
		$('country').value == 'CH' ||
		$('country').value == 'ES' ||
		$('country').value == 'AT') {
			//Priszone 3
			price = 181;
			price += 18*Math.ceil(kg);
		} else if($('country').value == 'EE' ||
		$('country').value == 'LV' ||
		$('country').value == 'LT' ||
		$('country').value == 'PL') {
			//Priszone 4
			price = 196;
			price += 33*Math.ceil(kg);
		} else if($('country').value == 'BG' ||
		$('country').value == 'CY' ||
		$('country').value == 'GR' ||
		$('country').value == 'BY' ||
		$('country').value == 'HR' ||
		$('country').value == 'MT' ||
		$('country').value == 'ME' ||
		$('country').value == 'RO' ||
		$('country').value == 'RU' ||
		$('country').value == 'RS' ||
		$('country').value == 'SK' ||
		$('country').value == 'SI' ||
		$('country').value == 'CZ' ||
		$('country').value == 'TR' ||
		$('country').value == 'UA' ||
		$('country').value == 'HU') {
			//Priszone 5
			price = 196;
			price += 48*Math.ceil(kg);
		} else if($('country').value == 'CA' ||
		$('country').value == 'ZA' ||
		$('country').value == 'US') {
			//Priszone 6
			price = 196;
			price += 55*Math.ceil(kg);
		} else if($('country').value == 'AR' ||
		$('country').value == 'AU' ||
		$('country').value == 'BR' ||
		$('country').value == 'CL' ||
		$('country').value == 'PH' ||
		$('country').value == 'HK' ||
		$('country').value == 'IN' ||
		$('country').value == 'ID' ||
		$('country').value == 'JP' ||
		$('country').value == 'CN' ||
		$('country').value == 'MY' ||
		$('country').value == 'NZ' ||
		$('country').value == 'SA' ||
		$('country').value == 'SG' ||
		$('country').value == 'KR' ||
		$('country').value == 'TW' ||
		$('country').value == 'TH') {
			//Priszone 7
			price = 201;
			price += 95*Math.ceil(kg);
		} else if($('country').value == 'FO') {
			//Priszone 8
			price = 166;
			price += 23*Math.ceil(kg);
		} else if($('country').value == 'GL') {
			//Priszone 9
			price = 126;
			price += 78*Math.ceil(kg);
		} else {
			//Øvrige lande
			price = 156;
			price += 71*Math.ceil(kg);
		}
		
		//Told
		if($('country').value == 'NO' ||
		$('country').value == 'AX' ||
		$('country').value == 'CH' ||
		$('country').value == 'IS' ||
		$('country').value == 'BY' ||
		$('country').value == 'HR' ||
		$('country').value == 'ME' ||
		$('country').value == 'RU' ||
		$('country').value == 'RS' ||
		$('country').value == 'TR' ||
		$('country').value == 'UA' ||
		$('country').value == 'CA' ||
		$('country').value == 'ZA' ||
		$('country').value == 'US' ||
		$('country').value == 'AR' ||
		$('country').value == 'AU' ||
		$('country').value == 'BR' ||
		$('country').value == 'CL' ||
		$('country').value == 'PH' ||
		$('country').value == 'HK' ||
		$('country').value == 'IN' ||
		$('country').value == 'ID' ||
		$('country').value == 'JP' ||
		$('country').value == 'CN' ||
		$('country').value == 'MY' ||
		$('country').value == 'NZ' ||
		$('country').value == 'SA' ||
		$('country').value == 'SG' ||
		$('country').value == 'FO' ||
		$('country').value == 'KR' ||
		$('country').value == 'TW' ||
		$('country').value == 'TH') {
			price += 125;
		}
		
		if(insurance) {
			if(isEU($('country').value)) {
				//EU
				price += 70;
			} else {
				//Øvrige verden
				price += 100;
			}
		}
	}
		
	if(isEU($('country').value)) {
		price = price*1.25;
	}
	
	return price;
}

function isRoll(w, l, h) {
	if(w >= l * 0.66 && w <= l * 1.5 && h > (l+w)*1.25)
		return true;
	if(w >= h * 0.66 && w <= h * 1.5 && l > (h+w)*1.25)
		return true;
	if(h >= l * 0.66 && h <= l * 1.5 && w > (l+h)*1.25)
		return true;
	
	return false;
}

function isVolume(w, l, h) {
	if(w > 100 || l > 100 || h > 100)	
		return true;
	if(isRoll(w, l, h)) {
		if(w > 25 && l > 25)	
			return true;
		if(w > 25 && h > 25)	
			return true;
		if(h > 25 && l > 25)	
			return true;
	} else {
		if(w > 50 && l > 50)	
			return true;
		if(w > 50 && h > 50)	
			return true;
		if(h > 50 && l > 50)	
			return true;
	}
	
	return false;
}

function isEU(country) {
	if(country == 'BE' ||
	country == 'FR' ||
	country == 'DE' ||
	country == 'IT' ||
	country == 'LU' ||
	country == 'HU' ||
	country == 'IE' ||
	country == 'GB' ||
	country == 'GR' ||
	country == 'PT' ||
	country == 'ES' ||
	country == 'AT' ||
	country == 'FI' ||
	country == 'SE' ||
	country == 'CY' ||
	country == 'CZ' ||
	country == 'EE' ||
	country == 'LV' ||
	country == 'LT' ||
	country == 'MT' ||
	country == 'PL' ||
	country == 'SK' ||
	country == 'SI' ||
	country == 'BG' ||
	country == 'RO') {
		return true;
	}
	return false;
}

function isEurop(country) {
	if(country == 'FI' ||
	country == 'NO' ||
	country == 'SE' ||
	country == 'DE' ||
	country == 'CH' ||
	country == 'BY' ||
	country == 'HR' ||
	country == 'ME' ||
	country == 'RU' ||
	country == 'RS' ||
	country == 'TR' ||
	country == 'AX' ||
	country == 'UA' ||
	country == 'AL' ||
	country == 'AD' ||
	country == 'AM' ||
	country == 'AT' ||
	country == 'BE' ||
	country == 'BA' ||
	country == 'BG' ||
	country == 'CY' ||
	country == 'EE' ||
	country == 'CZ' ||
	country == 'FR' ||
	country == 'GE' ||
	country == 'GR' ||
	country == 'HU' ||
	country == 'IS' ||
	country == 'IE' ||
	country == 'IT' ||
	country == 'KZ' ||
	country == 'LV' ||
	country == 'LI' ||
	country == 'LT' ||
	country == 'LU' ||
	country == 'MK' ||
	country == 'MT' ||
	country == 'MC' ||
	country == 'NL' ||
	country == 'PL' ||
	country == 'PT' ||
	country == 'RO' ||
	country == 'SM' ||
	country == 'SK' ||
	country == 'SI' ||
	country == 'ES' ||
	country == 'GB' ||
	country == 'VA' ||
	country == 'FX' ||
	country == 'GI' ||
	country == 'MD') {
		return true;
	}
	return false;
}

function isAmerika(country) {
	if(country == 'AG' ||
	country == 'GL' ||
	country == 'PA' ||
	country == 'NI' ||
	country == 'MQ' ||
	country == 'MX' ||
	country == 'JM' ||
	country == 'HN' ||
	country == 'HT' ||
	country == 'GT' ||
	country == 'GD' ||
	country == 'GP' ||
	country == 'SV' ||
	country == 'DM' ||
	country == 'DO' ||
	country == 'CU' ||
	country == 'CR' ||
	country == 'CA' ||
	country == 'BZ' ||
	country == 'BB' ||
	country == 'BS' ||
	country == 'US' ||
	country == 'PM' ||
	country == 'VC' ||
	country == 'LC' ||
	country == 'KN' ||
	country == 'AW' ||
	country == 'AN' ||
	country == 'AI' ||
	country == 'BM' ||
	country == 'VG' ||
	country == 'VI' ||
	country == 'KY' ||
	country == 'MS' ||
	country == 'TC' ||
	country == 'PR' ||
	country == 'TT' ||
	country == 'AR' ||
	country == 'BO' ||
	country == 'BR' ||
	country == 'CL' ||
	country == 'CO' ||
	country == 'EC' ||
	country == 'GY' ||
	country == 'GF' ||
	country == 'PY' ||
	country == 'PE' ||
	country == 'SR' ||
	country == 'UY' ||
	country == 'VE' ||
	country == 'FK' ||
	country == 'GS') {
		return true;
	}
	return false;
}

function isAfricaAsienOrMiddeleast(country) {
	if(country == 'DZ' ||
	country == 'AO' ||
	country == 'BJ' ||
	country == 'BW' ||
	country == 'BF' ||
	country == 'BI' ||
	country == 'CM' ||
	country == 'CV' ||
	country == 'CF' ||
	country == 'TD' ||
	country == 'KM' ||
	country == 'CG' ||
	country == 'DJ' ||
	country == 'EG' ||
	country == 'GQ' ||
	country == 'ER' ||
	country == 'ET' ||
	country == 'KH' ||
	country == 'GM' ||
	country == 'GH' ||
	country == 'GN' ||
	country == 'GW' ||
	country == 'KE' ||
	country == 'LS' ||
	country == 'LR' ||
	country == 'LY' ||
	country == 'MG' ||
	country == 'MW' ||
	country == 'ML' ||
	country == 'MR' ||
	country == 'MU' ||
	country == 'YT' ||
	country == 'MA' ||
	country == 'MZ' ||
	country == 'NA' ||
	country == 'NG' ||
	country == 'RE' ||
	country == 'RW' ||
	country == 'SH' ||
	country == 'ST' ||
	country == 'SN' ||
	country == 'SC' ||
	country == 'SL' ||
	country == 'SO' ||
	country == 'ZA' ||
	country == 'SD' ||
	country == 'SZ' ||
	country == 'TZ' ||
	country == 'TG' ||
	country == 'TN' ||
	country == 'UG' ||
	country == 'EH' ||
	country == 'ZM' ||
	country == 'ZW' ||
	country == 'GA' ||
	country == 'CI' ||
	country == 'ZR' ||
	country == 'KG' ||
	country == 'TM' ||
	country == 'UZ' ||
	country == 'CN' ||
	country == 'HK' ||
	country == 'MO' ||
	country == 'JP' ||
	country == 'KP' ||
	country == 'KR' ||
	country == 'MN' ||
	country == 'BN' ||
	country == 'TL' ||
	country == 'ID' ||
	country == 'LA' ||
	country == 'MY' ||
	country == 'MM' ||
	country == 'PH' ||
	country == 'SG' ||
	country == 'TH' ||
	country == 'VN' ||
	country == 'AF' ||
	country == 'BD' ||
	country == 'BT' ||
	country == 'IN' ||
	country == 'IR' ||
	country == 'MV' ||
	country == 'NP' ||
	country == 'PK' ||
	country == 'LK' ||
	country == 'AZ' ||
	country == 'BH' ||
	country == 'IQ' ||
	country == 'IL' ||
	country == 'JO' ||
	country == 'KW' ||
	country == 'LB' ||
	country == 'OM' ||
	country == 'QA' ||
	country == 'SA' ||
	country == 'SY' ||
	country == 'AE' ||
	country == 'YE' ||
	country == 'TJ' ||
	country == 'TW') {
		return true;
	}
	return false;
}

function $(id) {
	return document.getElementById(id);
}

function calcPrice() {
	if($('country').value == '' || $('country').value == '0') {
		$('rem1').style.display = 'none';
		if(typeof($('price').textContent) != 'undefined'){
			$('price').textContent = 'Vælge et modtager land!';
		} else{
			$('price').innerText = 'Vælge et modtager land!';
		}
		return false;
	}
	
	if(isEU($('country').value)) {
		$('rem1').style.display = 'none';
	} else {
		$('rem1').style.display = '';
	}
	
	//Set product
	if(!$('express').checked && calcPricePart(340) < calcPricePart(330) &&
	(calcPricePart(340) < calcPricePart(359) || 
	($('country').value == 'FI' ||
	$('country').value == 'NO' ||
	$('country').value == 'SE' ||
	$('country').value == 'DE' ||
	$('country').value == 'AL' ||
	$('country').value == 'AD' ||
	$('country').value == 'AM' ||
	$('country').value == 'AT' ||
	$('country').value == 'AZ' ||
	$('country').value == 'BY' ||
	$('country').value == 'BE' ||
	$('country').value == 'BA' ||
	$('country').value == 'BG' ||
	$('country').value == 'HR' ||
	$('country').value == 'CY' ||
	$('country').value == 'CZ' ||
	$('country').value == 'EE' ||
	$('country').value == 'FR' ||
	$('country').value == 'GE' ||
	$('country').value == 'GI' ||
	$('country').value == 'GB' ||
	$('country').value == 'GR' ||
	$('country').value == 'HU' ||
	$('country').value == 'IE' ||
	$('country').value == 'IT' ||
	$('country').value == 'KZ' ||
	$('country').value == 'KG' ||
	$('country').value == 'LV' ||
	$('country').value == 'LI' ||
	$('country').value == 'LT' ||
	$('country').value == 'LU' ||
	$('country').value == 'MK' ||
	$('country').value == 'MT' ||
	$('country').value == 'MD' ||
	$('country').value == 'MC' ||
	$('country').value == 'ME' ||
	$('country').value == 'NL' ||
	$('country').value == 'PL' ||
	$('country').value == 'PT' ||
	$('country').value == 'RO' ||
	$('country').value == 'RU' ||
	$('country').value == 'SM' ||
	$('country').value == 'RS' ||
	$('country').value == 'SK' ||
	$('country').value == 'SI' ||
	$('country').value == 'ES' ||
	$('country').value == 'CH' ||
	$('country').value == 'TJ' ||
	$('country').value == 'TR' ||
	$('country').value == 'TM' ||
	$('country').value == 'UZ' ||
	$('country').value == 'VA' ||
	$('country').value == 'AX'))) {
		//HomeShopping
		$('product').value = '340';
	} else if(!$('express').checked && calcPricePart(359) < calcPricePart(330)) {
		//Budget
		$('product').value = '359';
	} else {
		//Business
		$('product').value = '330';
	}
	
	if(typeof($('price').textContent) != 'undefined'){
		$('price').textContent = calcPricePart($('product').value).toFixed(2).replace(/\./,',');
	} else{
		$('price').innerText = calcPricePart($('product').value).toFixed(2).replace(/\./,',');
	}
}

function validate() {
	//Make values nice
	$('postcode').value = $('postcode').value.toUpperCase();
		
	calcPrice();
	
	if($('country').value == '' || $('country').value == '0') {
		alert('Du har ikke valgt et modtager land!');
		$('country').focus();
		return false;	
	}
	
	//Budget
	if($('product').value == '359') {
		//!GL !FO
		if($('kg').value > 20 && !($('country').value == 'GL' || $('country').value == 'FO')) {
			alert("Pakken må max veje 20Kg!");
			$('kg').focus();
			return false;
		}
		//GL FO
		if($('kg').value > 50 && ($('country').value == 'GL' || $('country').value == 'FO')) {
			alert("Pakken må max veje 50Kg!");
			$('kg').focus();
			return false;
		}
	}
	
	//HomeShopping
	if($('product').value == '340') {
		//!NO !FI !GL !FO
		if($('kg').value > 20 && !($('country').value == 'GL' || $('country').value == 'FO' || $('country').value == 'NO' || $('country').value == 'FI')) {
			alert("Pakken må max veje 20Kg!");
			$('kg').focus();
			return false;
		}
		//NO FI
		if($('kg').value > 35 && ($('country').value == 'NO' || $('country').value == 'FI')) {
			alert("Pakken må max veje 35Kg!");
			$('kg').focus();
			return false;
		}
		//GL FO
		if($('kg').value > 50 && ($('country').value == 'GL' || $('country').value == 'FO')) {
			alert("Pakken må max veje 50Kg!");
			$('kg').focus();
			return false;
		}
	}
	
	//Business
	if($('product').value == '330') {
		//!FI !NO !SE !AX !GL !FO
		if($('kg').value > 30 && !($('country').value == 'FI' || $('country').value == 'NO' || $('country').value == 'SE' || $('country').value == 'AX' || $('country').value == 'FO' || $('country').value == 'GL')) {
			alert("Pakken må max veje 20Kg!");
			$('kg').focus();
			return false;
		}
		//NO DE
		if($('kg').value > 35 && ($('country').value == 'FI' || $('country').value == 'NO' || $('country').value == 'SE' || $('country').value == 'AX')) {
			alert("Pakken må max veje 35Kg!");
			$('kg').focus();
			return false;
		}
		//GL FO
		if($('kg').value > 50 && ($('country').value == 'GL' || $('country').value == 'FO')) {
			alert("Pakken må max veje 50Kg!");
			$('kg').focus();
			return false;
		}
	
		if(l*w*h/1000000 > 0.28) {
			alert('Pakkens volume overstiger 0.28 kubik meter!');
			return false;	
		}
	}
	
	if($('sender').value == '') {
		alert('Hvor er du?');
		$('sender').focus();
		return false;	
	}
	
	if($('country').value == 'NO' && $('postcode').value.length != 4) {
		alert("Postnr skal være på 4 cifre!");
		$('postcode').focus();
		return false;	
	}
	
	if(($('country').value == 'SE' || $('country').value == 'FI') && $('postcode').value.length != 5) {
		alert("Postnr skal være på 5 cifre!");
		$('postcode').focus();
		return false;
	}
	
	if($('l').value > 150 || $('w').value > 150 || $('h').value > 150) {
		alert("Du kan ikke sende pakker over 1,5m med PNL!");
		return false;
	}
	
	if($('l').value == '') {
		alert("Du skal skrive målene på pakken!");
		$('l').focus();
		return false;
	}
	
	if($('h').value == '') {
		alert("Du skal skrive målene på pakken!");
		$('h').focus();
		return false;
	}
	
	if($('w').value == '') {
		alert("Du skal skrive målene på pakken!");
		$('w').focus();
		return false;
	}
	
	if($('kg').value == '') {
		alert("Du skal skrive pakkens vægt!");
		$('kg').focus();
		return false;
	}
	
	if($('text').value == '') {
		alert("Du skal beskrive pakkens indhold!");
		$('text').focus();
		return false;
	}
	
	if($('name').value == '') {
		alert("Du skal indtaste en modtager!");
		$('name').focus();
		return false;
	}
	
	if($('address').value == '') {
		alert("Du skal indtaste modtagerens adresse!");
		$('address').focus();
		return false;
	}
	
	if($('postcode').value == '') {
		alert("Du skal indtaste et post nummer!");
		$('postcode').focus();
		return false;
	}
	
	if($('city').value == '') {
		alert("Du skal indtaste en by!");
		$('city').focus();
		return false;
	}
	
	if($('insurance').value > 100000) {
		alert("Pakker kan max forsikres for 100.000,- Kr!");
		$('insurance').focus();
		return false;
	}
	
	$('kg').value = Math.ceil(parseFloat($('kg').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	$('l').value = Math.ceil(parseFloat($('l').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	$('w').value = Math.ceil(parseFloat($('w').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	$('h').value = Math.ceil(parseFloat($('h').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	var insurance = Math.ceil(parseFloat($('insurance').value.replace(/[^0-9,]/g, '').replace(/,/, '.')));
	if(!isNaN(insurance))
		$('insurance').value = insurance;
	else
		$('insurance').value = '';
	
	return true;
}
