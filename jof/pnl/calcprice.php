<?php

function calcPricePart($country, $product, $kg, $insurance, $l, $w, $h) {
	
	$price = 0;
	$kg = ceil($kg);
	if(!$kg) $kg = 1;
	
	$insurance = ceil($insurance);
	$l = ceil($l);
	$w = ceil($w);
	$h = ceil($h);
	
	//Budget
	if($product == 359) {
		if($country == 'GL') {
			//Grønland
			$price = 128;
			$price += 20*$kg;
		} elseif($country == 'FO') {
			//Færøerne
			$price = 136;
			$price += 9*$kg;
		} elseif($country == 'IS') {
			//Island
			$price = 156;
			$price += 13*$kg;
		} elseif(isAmerika($country)) {
			//Amerika
			$price = 156;
			$price += 37*$kg;
		} elseif(isAfricaAsienOrMiddeleast($country)) {
			//Afrika, Asien og Mellemøsten
			$price = 156;
			$price += 39*$kg;
		} else {
			//Øvrige verden
			$price = 156;
			$price += 43*$kg;
		}
		
		if($insurance) {
			if(isEU($country)) {
				//EU
				$price += 70;
			} else {
				//Øvrige lande
				$price += 100;
			}
		}
		
		if(isVolume($w, $l, $h))
			$price += 80;
	}
	
	//HomeShopping
	if($product == 340) {
		if($country == 'GL') {
			//Grønland
			$price = 141;
			$price += 75*$kg;
		} elseif($country == 'FO') {
			//Færøerne
			$price = 156;
			$price += 21*$kg;
		} elseif($country == 'FI' ||
		$country == 'NO' ||
		$country == 'SE' ||
		$country == 'DE' ||
		$country == 'AX') {
			//Priszone 1
			$price = 156;
			$price += 13*$kg;
		} elseif(isEurop($country)) {
			//Priszone 2
			$price = 156;
			$price += 21*$kg;
		} elseif(isAmerika($country)) {
			//Amerika
			$price = 156;
			$price += 61*$kg;
		} elseif(isAfricaAsienOrMiddeleast($country)) {
			//Afrika, Asien og Mellemøsten
			$price = 156;
			$price += 71*$kg;
		} else {
			//Øvrige verden
			$price = 156;
			$price += 81*$kg;
		}
		
		if($insurance) {
			if(isEU($country)) {
				//EU
				$price += 70;
			} else {
				//Øvrige lande
				$price += 100;
			}
		}
		
		if(isVolume($w, $l, $h))
			$price += 80;
	}
	
	//Business
	if($product == 330) {
		$vkg = ceil($l*$w*$h/4000);
		if($vkg > $kg)
			$kg = $vkg;
		
		if($country == 'FI' ||
		$country == 'NO' ||
		$country == 'SE' ||
		$country == 'DE' ||
		$country == 'AX') {
			//Priszone 1
			$price = 161;
			$price += 13*$kg;
		} elseif($country == 'BE' ||
		$country == 'IS' ||
		$country == 'IE' ||
		$country == 'LU' ||
		$country == 'NL' ||
		$country == 'GB') {
			//Priszone 2
			$price = 171;
			$price += 14*$kg;
		} elseif($country == 'FR' ||
		$country == 'IT' ||
		$country == 'MC' ||
		$country == 'PT' ||
		$country == 'CH' ||
		$country == 'ES' ||
		$country == 'AT') {
			//Priszone 3
			$price = 181;
			$price += 18*$kg;
		} elseif($country == 'EE' ||
		$country == 'LV' ||
		$country == 'LT' ||
		$country == 'PL') {
			//Priszone 4
			$price = 196;
			$price += 33*$kg;
		} elseif($country == 'BG' ||
		$country == 'CY' ||
		$country == 'GR' ||
		$country == 'BY' ||
		$country == 'HR' ||
		$country == 'MT' ||
		$country == 'ME' ||
		$country == 'RO' ||
		$country == 'RU' ||
		$country == 'RS' ||
		$country == 'SK' ||
		$country == 'SI' ||
		$country == 'CZ' ||
		$country == 'TR' ||
		$country == 'UA' ||
		$country == 'HU') {
			//Priszone 5
			$price = 196;
			$price += 48*$kg;
		} elseif($country == 'CA' ||
		$country == 'ZA' ||
		$country == 'US') {
			//Priszone 6
			$price = 196;
			$price += 55*$kg;
		} elseif($country == 'AR' ||
		$country == 'AU' ||
		$country == 'BR' ||
		$country == 'CL' ||
		$country == 'PH' ||
		$country == 'HK' ||
		$country == 'IN' ||
		$country == 'ID' ||
		$country == 'JP' ||
		$country == 'CN' ||
		$country == 'MY' ||
		$country == 'NZ' ||
		$country == 'SA' ||
		$country == 'SG' ||
		$country == 'KR' ||
		$country == 'TW' ||
		$country == 'TH') {
			//Priszone 7
			$price = 201;
			$price += 95*$kg;
		} elseif($country == 'FO') {
			//Priszone 8
			$price = 166;
			$price += 23*$kg;
		} elseif($country == 'GL') {
			//Priszone 9
			$price = 126;
			$price += 78*$kg;
		} else {
			//Øvrige lande
			$price = 156;
			$price += 71*$kg;
		}
		
		//Told
		if($country == 'NO' ||
		$country == 'AX' ||
		$country == 'CH' ||
		$country == 'IS' ||
		$country == 'BY' ||
		$country == 'HR' ||
		$country == 'ME' ||
		$country == 'RU' ||
		$country == 'RS' ||
		$country == 'TR' ||
		$country == 'UA' ||
		$country == 'CA' ||
		$country == 'ZA' ||
		$country == 'US' ||
		$country == 'AR' ||
		$country == 'AU' ||
		$country == 'BR' ||
		$country == 'CL' ||
		$country == 'PH' ||
		$country == 'HK' ||
		$country == 'IN' ||
		$country == 'ID' ||
		$country == 'JP' ||
		$country == 'CN' ||
		$country == 'MY' ||
		$country == 'NZ' ||
		$country == 'SA' ||
		$country == 'SG' ||
		$country == 'FO' ||
		$country == 'KR' ||
		$country == 'TW' ||
		$country == 'TH') {
			$price += 125;
		}
		
		if($insurance) {
			if(isEU($country)) {
				//EU
				$price += 70;
			} else {
				//Øvrige verden
				$price += 100;
			}
		}
	}
		
	if(isEU($country)) {
		$price = $price*1.25;
	}
	
	return $price;
}

function isRoll($w, $l, $h) {
	if($w >= $l * 0.66 && $w <= $l * 1.5 && $h > ($l+$w)*1.25)
		return true;
	if($w >= $h * 0.66 && $w <= $h * 1.5 && $l > ($h+$w)*1.25)
		return true;
	if($h >= $l * 0.66 && $h <= $l * 1.5 && $w > ($l+$h)*1.25)
		return true;
	
	return false;
}

function isVolume($w, $l, $h) {
	if($w > 100 || $l > 100 || $h > 100)	
		return true;
	if(isRoll($w, $l, $h)) {
		if($w > 25 && $l > 25)
			return true;
		if($w > 25 && $h > 25)
			return true;
		if($h > 25 && $l > 25)
			return true;
	} else {
		if($w > 50 && $l > 50)
			return true;
		if($w > 50 && $h > 50)
			return true;
		if($h > 50 && $l > 50)
			return true;
	}
	
	return false;
}

function isEU($country) {
	if($country == 'BE' ||
	$country == 'FR' ||
	$country == 'DE' ||
	$country == 'IT' ||
	$country == 'LU' ||
	$country == 'HU' ||
	$country == 'IE' ||
	$country == 'GB' ||
	$country == 'GR' ||
	$country == 'PT' ||
	$country == 'ES' ||
	$country == 'AT' ||
	$country == 'FI' ||
	$country == 'SE' ||
	$country == 'CY' ||
	$country == 'CZ' ||
	$country == 'EE' ||
	$country == 'LV' ||
	$country == 'LT' ||
	$country == 'MT' ||
	$country == 'PL' ||
	$country == 'SK' ||
	$country == 'SI' ||
	$country == 'BG' ||
	$country == 'RO') {
		return true;
	}
	return false;
}

function isEurop($country) {
	if($country == 'FI' ||
	$country == 'NO' ||
	$country == 'SE' ||
	$country == 'DE' ||
	$country == 'CH' ||
	$country == 'BY' ||
	$country == 'HR' ||
	$country == 'ME' ||
	$country == 'RU' ||
	$country == 'RS' ||
	$country == 'TR' ||
	$country == 'AX' ||
	$country == 'UA' ||
	$country == 'AL' ||
	$country == 'AD' ||
	$country == 'AM' ||
	$country == 'AT' ||
	$country == 'BE' ||
	$country == 'BA' ||
	$country == 'BG' ||
	$country == 'CY' ||
	$country == 'EE' ||
	$country == 'CZ' ||
	$country == 'FR' ||
	$country == 'GE' ||
	$country == 'GR' ||
	$country == 'HU' ||
	$country == 'IS' ||
	$country == 'IE' ||
	$country == 'IT' ||
	$country == 'KZ' ||
	$country == 'LV' ||
	$country == 'LI' ||
	$country == 'LT' ||
	$country == 'LU' ||
	$country == 'MK' ||
	$country == 'MT' ||
	$country == 'MC' ||
	$country == 'NL' ||
	$country == 'PL' ||
	$country == 'PT' ||
	$country == 'RO' ||
	$country == 'SM' ||
	$country == 'SK' ||
	$country == 'SI' ||
	$country == 'ES' ||
	$country == 'GB' ||
	$country == 'VA' ||
	$country == 'FX' ||
	$country == 'GI' ||
	$country == 'MD') {
		return true;
	}
	return false;
}

function isAmerika($country) {
	if($country == 'AG' ||
	$country == 'GL' ||
	$country == 'PA' ||
	$country == 'NI' ||
	$country == 'MQ' ||
	$country == 'MX' ||
	$country == 'JM' ||
	$country == 'HN' ||
	$country == 'HT' ||
	$country == 'GT' ||
	$country == 'GD' ||
	$country == 'GP' ||
	$country == 'SV' ||
	$country == 'DM' ||
	$country == 'DO' ||
	$country == 'CU' ||
	$country == 'CR' ||
	$country == 'CA' ||
	$country == 'BZ' ||
	$country == 'BB' ||
	$country == 'BS' ||
	$country == 'US' ||
	$country == 'PM' ||
	$country == 'VC' ||
	$country == 'LC' ||
	$country == 'KN' ||
	$country == 'AW' ||
	$country == 'AN' ||
	$country == 'AI' ||
	$country == 'BM' ||
	$country == 'VG' ||
	$country == 'VI' ||
	$country == 'KY' ||
	$country == 'MS' ||
	$country == 'TC' ||
	$country == 'PR' ||
	$country == 'TT' ||
	$country == 'AR' ||
	$country == 'BO' ||
	$country == 'BR' ||
	$country == 'CL' ||
	$country == 'CO' ||
	$country == 'EC' ||
	$country == 'GY' ||
	$country == 'GF' ||
	$country == 'PY' ||
	$country == 'PE' ||
	$country == 'SR' ||
	$country == 'UY' ||
	$country == 'VE' ||
	$country == 'FK' ||
	$country == 'GS') {
		return true;
	}
	return false;
}

function isAfricaAsienOrMiddeleast($country) {
	if($country == 'DZ' ||
	$country == 'AO' ||
	$country == 'BJ' ||
	$country == 'BW' ||
	$country == 'BF' ||
	$country == 'BI' ||
	$country == 'CM' ||
	$country == 'CV' ||
	$country == 'CF' ||
	$country == 'TD' ||
	$country == 'KM' ||
	$country == 'CG' ||
	$country == 'DJ' ||
	$country == 'EG' ||
	$country == 'GQ' ||
	$country == 'ER' ||
	$country == 'ET' ||
	$country == 'KH' ||
	$country == 'GM' ||
	$country == 'GH' ||
	$country == 'GN' ||
	$country == 'GW' ||
	$country == 'KE' ||
	$country == 'LS' ||
	$country == 'LR' ||
	$country == 'LY' ||
	$country == 'MG' ||
	$country == 'MW' ||
	$country == 'ML' ||
	$country == 'MR' ||
	$country == 'MU' ||
	$country == 'YT' ||
	$country == 'MA' ||
	$country == 'MZ' ||
	$country == 'NA' ||
	$country == 'NG' ||
	$country == 'RE' ||
	$country == 'RW' ||
	$country == 'SH' ||
	$country == 'ST' ||
	$country == 'SN' ||
	$country == 'SC' ||
	$country == 'SL' ||
	$country == 'SO' ||
	$country == 'ZA' ||
	$country == 'SD' ||
	$country == 'SZ' ||
	$country == 'TZ' ||
	$country == 'TG' ||
	$country == 'TN' ||
	$country == 'UG' ||
	$country == 'EH' ||
	$country == 'ZM' ||
	$country == 'ZW' ||
	$country == 'GA' ||
	$country == 'CI' ||
	$country == 'ZR' ||
	$country == 'KG' ||
	$country == 'TM' ||
	$country == 'UZ' ||
	$country == 'CN' ||
	$country == 'HK' ||
	$country == 'MO' ||
	$country == 'JP' ||
	$country == 'KP' ||
	$country == 'KR' ||
	$country == 'MN' ||
	$country == 'BN' ||
	$country == 'TL' ||
	$country == 'ID' ||
	$country == 'LA' ||
	$country == 'MY' ||
	$country == 'MM' ||
	$country == 'PH' ||
	$country == 'SG' ||
	$country == 'TH' ||
	$country == 'VN' ||
	$country == 'AF' ||
	$country == 'BD' ||
	$country == 'BT' ||
	$country == 'IN' ||
	$country == 'IR' ||
	$country == 'MV' ||
	$country == 'NP' ||
	$country == 'PK' ||
	$country == 'LK' ||
	$country == 'AZ' ||
	$country == 'BH' ||
	$country == 'IQ' ||
	$country == 'IL' ||
	$country == 'JO' ||
	$country == 'KW' ||
	$country == 'LB' ||
	$country == 'OM' ||
	$country == 'QA' ||
	$country == 'SA' ||
	$country == 'SY' ||
	$country == 'AE' ||
	$country == 'YE' ||
	$country == 'TJ' ||
	$country == 'TW') {
		return true;
	}
	return false;
}

?>