<?php

function calcvolume($height, $width, $length) {
	//Calc Volume
	//Vi har dispensation til at sende pakker op til 2m
	
	//Vi har dispensation til at sende pakker op til 1.5m uden volume gebyr
	if($height > 150 || $width > 150 || $length > 150)
		return true;
	
	if($height > 50 && $width > 50)
		return true;
	if($width > 50 && $length > 50)
		return true;
	if($length > 50 && $height > 50)
		return true;

	$realLength = max($height, $width, $length);

	//pakker med længde over 1m er volume hvis en side er over 25cm
	//TODO det har flemming ikke oplyst
	if($realLength > 100) {
		if($realLength == $length) {
			if($width > 25 || $height > 25)
			return true;
		} elseif($realLength == $width) {
			if($length > 25 || $height > 25)
				return true;
		} elseif($realLength == $height) {
			if($width > 25 || $length > 25)
				return true;
		}
	}

	//pakker med længde + omkres over 300 er volume	
	if($realLength == $length) {
		if($length+($width+$height)*2 >= 300)
			return true;
	} elseif($realLength == $width) {
		if($width+($length+$height)*2 >= 300)
			return true;
	} elseif($realLength == $height) {
		if($height+($width+$length)*2 >= 300)
			return true;
	}
	
	return false;
}


function pakkepris($height, $width, $length, $weight, $packtype, $ss1, $ss46, $ss5amount, $domoms = true, $volume = false) {
	$ss1 = ($ss1 == 'false' ? false : true);
	$ss46 = ($ss46 == 'false' ? false : true);

	if($ss5amount <= 4600)
		$ss5amount = 0;
	
	$grundpris = 0;
	$moms = false;
	$vWeight = max($height*$width*$length/4000, $weight);
	
	if($packtype == 'P') {

		if($weight > 20)
			$weight = $vWeight;
		
		if($weight <= 1) {
			$grundpris = 62;
			@$GLOBALS['p1'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 5) {
			$grundpris = 66;
			@$GLOBALS['p5'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 10) {
			$grundpris = 84;
			@$GLOBALS['p10'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 15) {
			$grundpris = 125;
			@$GLOBALS['p15'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 20) {
			$grundpris = 135;
			@$GLOBALS['p20'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 25) {
			$moms = true;
			$grundpris = 271;
			@$GLOBALS['p25'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 30) {
			$moms = true;
			$grundpris = 319;
			@$GLOBALS['p30'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 35) {
			$moms = true;
			$grundpris = 364;
			@$GLOBALS['p35'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 40) {
			$moms = true;
			$grundpris = 408;
			@$GLOBALS['p40'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 45) {
			$moms = true;
			$grundpris = 445;
			@$GLOBALS['p45'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 50) {
			$moms = true;
			$grundpris = 502;
			@$GLOBALS['p50'.($ss46 ? 'l' : '')]++;
		} else {
			$moms = true;
			$grundpris = 502 + ($weight-50) * 5.5;
			
			@$GLOBALS['p50b'.($ss46 ? 'l' : '')]++;
		}
	
		//Volume
		if($volume || calcvolume($height, $width, $length)) {
			$grundpris += 79;
		}

	} elseif($packtype == 'E') {
		
		$moms = true;
		if($vWeight <= 1) {
			$grundpris = 39.75;
			@$GLOBALS['e1']++;
		} elseif($vWeight <= 5) {
			$grundpris = 45;
			@$GLOBALS['e5']++;
		} elseif($vWeight <= 10) {
			$grundpris = 52.5;
			@$GLOBALS['e10']++;
		} elseif($vWeight <= 15) {
			$grundpris = 60.75;
			@$GLOBALS['e15']++;
		} elseif($vWeight <= 20) {
			$grundpris = 69.5;
			@$GLOBALS['e20']++;
		} elseif($vWeight <= 25) {
			$grundpris = 79.5;
			@$GLOBALS['e25']++;
		} elseif($vWeight <= 30) {
			$grundpris = 88.5;
			@$GLOBALS['e30']++;
		} elseif($vWeight <= 35) {
			$grundpris = 97;
			@$GLOBALS['e35']++;
		} elseif($vWeight <= 40) {
			$grundpris = 106;
			@$GLOBALS['e40']++;
		} elseif($vWeight <= 45) {
			$grundpris = 116;
			@$GLOBALS['e45']++;
		} elseif($vWeight <= 50) {
			$grundpris = 140;
			@$GLOBALS['e50']++;
		} else {
			$grundpris += round(($vWeight-50)*5.5, 2)+140;
			@$GLOBALS['e50b']++;
		}

	} elseif($packtype == 'O') {
		//Grund priser for volume vægt på Post Opkrævnings pakker
		$moms = true;
		if($vWeight <= 1) {
			$grundpris = 92;
			@$GLOBALS['o1']++;
		} elseif($vWeight <= 5) {
			$grundpris = 95;
			@$GLOBALS['o5']++;
		} elseif($vWeight <= 10) {
			$grundpris = 113;
			@$GLOBALS['o10']++;
		} elseif($vWeight <= 15) {
			$grundpris = 155;
			@$GLOBALS['o15']++;
		} elseif($vWeight <= 20) {
			$grundpris = 157;
			@$GLOBALS['o20']++;
		} elseif($vWeight <= 25) {
			$grundpris = 184;
			@$GLOBALS['o25']++;
		} elseif($vWeight <= 30) {
			$grundpris = 232;
			@$GLOBALS['o30']++;
		} elseif($vWeight <= 35) {
			$grundpris = 264;
			@$GLOBALS['o35']++;
		} elseif($vWeight <= 40) {
			$grundpris = 305;
			@$GLOBALS['o40']++;
		} elseif($vWeight <= 45) {
			$grundpris = 348;
			@$GLOBALS['o45']++;
		} elseif($vWeight <= 50) {
			$grundpris = 391;
			@$GLOBALS['o50']++;
		} else {
			$grundpris += round(($vWeight-50)*5.5, 2)+391;
			@$GLOBALS['o50b']++;
		}
		// 16kr bliver opkrævet af kunden ved betaling
	}
	
	//Forsigtig
	if($ss1) {
		$grundpris += 79;
		$moms = true;
		@$GLOBALS['forsigtig']++;
	}

	//Lørdagsomdeling
	if($ss46) {
		$grundpris += 66;
		$moms = true;
		@$GLOBALS['lørdag']++;
	}

	//Værdipakke
	if($ss5amount) {
		$grundpris += 88;
		$grundpris += ceil($ss5amount/1000)*2;
		if($moms)
			@$GLOBALS['valuem'][ceil($ss5amount/1000)*2+88]++;
		else
			@$GLOBALS['valuenm'][ceil($ss5amount/1000)*2+88]++;
	}
	
	//Moms
	if($moms && $domoms) {
		$endeligPris = 1.25*$grundpris;
		@$GLOBALS['moms'] += $endeligPris-$grundpris;
	} else {
		$endeligPris = $grundpris;
	}
	
	return round($endeligPris, 2);
}

?>
