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


function pakkepris($height, $width, $length, $weight, $packtype, $ss1, $ss46, $ss5amount, $domoms = true) {

	$ss1 = ($ss1 == 'false' ? false : true);
	$ss46 = ($ss46 == 'false' ? false : true);

	if($ss5amount <= 4600)
		$ss5amount = 0;
	
	$grundpris = 0;
	$moms = false;
	$vWeight = max($height*$width*$length/4000, $weight);
	
	if($packtype == 'P') {
		
		if($weight <= 1) {
			$grundpris = 60;
			@$GLOBALS['p1'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 5) {
			$grundpris = 64;
			@$GLOBALS['p5'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 10) {
			$grundpris = 82;
			@$GLOBALS['p10'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 15) {
			$grundpris = 121;
			@$GLOBALS['p15'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 20) {
			$grundpris = 131;
			@$GLOBALS['p20'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 25) {
			$moms = true;
			$grundpris = 156;
			@$GLOBALS['p25'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 30) {
			$moms = true;
			$grundpris = 203;
			@$GLOBALS['p30'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 35) {
			$moms = true;
			$grundpris = 247;
			@$GLOBALS['p35'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 40) {
			$moms = true;
			$grundpris = 289;
			@$GLOBALS['p40'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 45) {
			$moms = true;
			$grundpris = 335;
			@$GLOBALS['p45'.($ss46 ? 'l' : '')]++;
		} elseif($weight <= 50) {
			$moms = true;
			$grundpris = 381;
			@$GLOBALS['p50'.($ss46 ? 'l' : '')]++;
		} else {
			return false;
		}
	
		//Volume
		if(calcvolume($height, $width, $length)) {
			$grundpris += 76;
		}

	} elseif($packtype == 'E') {
		
		$moms = true;
		if($vWeight <= 1) {
			$grundpris = 34;
			@$GLOBALS['e1']++;
		} elseif($vWeight <= 5) {
			$grundpris = 39.25;
			@$GLOBALS['e5']++;
		} elseif($vWeight <= 10) {
			$grundpris = 47;
			@$GLOBALS['e10']++;
		} elseif($vWeight <= 15) {
			$grundpris = 56;
			@$GLOBALS['e15']++;
		} elseif($vWeight <= 20) {
			$grundpris = 66;
			@$GLOBALS['e20']++;
		} elseif($vWeight <= 25) {
			$grundpris = 76;
			@$GLOBALS['e25']++;
		} elseif($vWeight <= 30) {
			$grundpris = 86;
			@$GLOBALS['e30']++;
		} elseif($vWeight <= 35) {
			$grundpris = 96;
			@$GLOBALS['e35']++;
		} elseif($vWeight <= 40) {
			$grundpris = 106;
			@$GLOBALS['e40']++;
		} elseif($vWeight <= 45) {
			$grundpris = 116;
			@$GLOBALS['e45']++;
		} elseif($vWeight <= 50) {
			$grundpris = 130;
			@$GLOBALS['e50']++;
		} else {
			$grundpris += round(($vWeight-50)*5.5, 2)+135;
			@$GLOBALS['e50b']++;
		}

	} elseif($packtype == 'O') {
		//Grund priser for volume vægt på Post Opkrævnings pakker
		$moms = true;
		if($vWeight <= 1) {
			$grundpris = 89;
			@$GLOBALS['o1']++;
		} elseif($vWeight <= 5) {
			$grundpris = 92;
			@$GLOBALS['o5']++;
		} elseif($vWeight <= 10) {
			$grundpris = 110;
			@$GLOBALS['o10']++;
		} elseif($vWeight <= 15) {
			$grundpris = 150;
			@$GLOBALS['o15']++;
		} elseif($vWeight <= 20) {
			$grundpris = 152;
			@$GLOBALS['o20']++;
		} elseif($vWeight <= 25) {
			$grundpris = 179;
			@$GLOBALS['o25']++;
		} elseif($vWeight <= 30) {
			$grundpris = 225;
			@$GLOBALS['o30']++;
		} elseif($vWeight <= 35) {
			$grundpris = 256;
			@$GLOBALS['o35']++;
		} elseif($vWeight <= 40) {
			$grundpris = 296;
			@$GLOBALS['o40']++;
		} elseif($vWeight <= 45) {
			$grundpris = 338;
			@$GLOBALS['o45']++;
		} elseif($vWeight <= 50) {
			$grundpris = 380;
			@$GLOBALS['o50']++;
		} else {
			$grundpris += round(($vWeight-50)*5.5, 2)+380;
			@$GLOBALS['o50b']++;
		}
		// 16kr bliver opkrævet af kunden ved betaling
	}
	
	//Forsigtig
	if($ss1) {
		$grundpris += 76;
		$moms = true;
		@$GLOBALS['forsigtig']++;
	}

	//Lørdagsomdeling
	if($ss46) {
		$grundpris += 64;
		$moms = true;
		@$GLOBALS['lørdag']++;
	}

	//Værdipakke
	if($ss5amount) {
		$grundpris += 85;
		$grundpris += ceil($ss5amount/1000)*2;
		if($moms)
			@$GLOBALS['valuem'][ceil($ss5amount/1000)*2+85]++;
		else
			@$GLOBALS['valuenm'][ceil($ss5amount/1000)*2+85]++;
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