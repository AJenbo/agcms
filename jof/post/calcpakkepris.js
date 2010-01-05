function calcvolume(height, width, length) {
	//Calc Volume
	//Vi har dispensation til at sende pakker op til 2m
	
	//Vi har dispensation til at sende pakker op til 1.5m uden volume gebyr
	if(height > 150 || width > 150 || length > 150)
		return true;
	
	if(height > 50 && width > 50)
		return true;
	if(width > 50 && length > 50)
		return true;
	if(length > 50 && height > 50)
		return true;

	realLength = Math.max(height, Math.max(width, length));

	//pakker med længde over 1m er volume hvis en side er over 25cm
	//TODO det har flemming ikke oplyst
	if(realLength > 100) {
		if(realLength == length) {
			if(width > 25 || height > 25)
			return true;
		} else if(realLength == width) {
			if(length > 25 || height > 25)
				return true;
		} else if(realLength == height) {
			if(width > 25 || length > 25)
				return true;
		}
	}

	//pakker med længde + omkres over 300 er volume	
	if(realLength == length) {
		if(length+(width+height)*2 >= 300)
			return true;
	} else if(realLength == width) {
		if(width+(length+height)*2 >= 300)
			return true;
	} else if(realLength == height) {
		if(height+(width+length)*2 >= 300)
			return true;
	}
	
	return false;
}


function pakkepris(height, width, length, weight, packtype, ss1, ss46, ss5amount, volume) {
	
	if(isNaN(height))
		height = 0;
	if(isNaN(width))
		width = 0;
	if(isNaN(length))
		length = 0;
	if(isNaN(weight))
		weight = 0;
	if(isNaN(ss5amount))
		ss5amount = 0;

	if(ss5amount <= 4600)
		ss5amount = 0;

	if((height == 0 || width == 0 || length == 0) && weight == 0)
		return false;

	var grundpris = 0;
	var moms = false;
	
	var vWeight = Math.max(height*width*length/4000, weight);
	
	if(packtype == 'P') {
		
		if(weight <= 1) {
			grundpris = 62;
		} else if(weight <= 5) {
			grundpris = 66;
		} else if(weight <= 10) {
			grundpris = 84;
		} else if(weight <= 15) {
			grundpris = 125;
		} else if(weight <= 20) {
			grundpris = 135;
		} else if(weight <= 25) {
			moms = true;
			grundpris = 161;
		} else if(weight <= 30) {
			moms = true;
			grundpris = 209;
		} else if(weight <= 35) {
			moms = true;
			grundpris = 254;
		} else if(weight <= 40) {
			moms = true;
			grundpris = 298;
		} else if(weight <= 45) {
			moms = true;
			grundpris = 345;
		} else if(weight <= 50) {
			moms = true;
			grundpris = 392;
		} else {
			return false;
		}
	
		//Volume
		if(volume || calcvolume(height, width, length)) {
			grundpris += 79;
		}

	} else if(packtype == 'E') {
		
		moms = true;
		if(vWeight <= 1) {
			grundpris = 34;
		} else if(vWeight <= 5) {
			grundpris = 39.25;
		} else if(vWeight <= 10) {
			grundpris = 47;
		} else if(vWeight <= 15) {
			grundpris = 56;
		} else if(vWeight <= 20) {
			grundpris = 66;
		} else if(vWeight <= 25) {
			grundpris = 76;
		} else if(vWeight <= 30) {
			grundpris = 86;
		} else if(vWeight <= 35) {
			grundpris = 96;
		} else if(vWeight <= 40) {
			grundpris = 106;
		} else if(vWeight <= 45) {
			grundpris = 116;
		} else if(vWeight <= 50) {
			grundpris = 130;
		} else {
			grundpris += Math.round((vWeight-50)*5.5*100)/100+135;
		}

	} else if(packtype == 'O') {
		//Grund priser for volume vægt på Post Opkrævnings pakker
		moms = true;
		if(vWeight <= 1) {
			grundpris = 92;
		} else if(vWeight <= 5) {
			grundpris = 95;
		} else if(vWeight <= 10) {
			grundpris = 113;
		} else if(vWeight <= 15) {
			grundpris = 155;
		} else if(vWeight <= 20) {
			grundpris = 157;
		} else if(vWeight <= 25) {
			grundpris = 184;
		} else if(vWeight <= 30) {
			grundpris = 232;
		} else if(vWeight <= 35) {
			grundpris = 264;
		} else if(vWeight <= 40) {
			grundpris = 305;
		} else if(vWeight <= 45) {
			grundpris = 348;
		} else if(vWeight <= 50) {
			grundpris = 391;
		} else {
			grundpris += Math.round((vWeight-50)*5.5*100)/100+391;
		}
		// Yderliger 16kr bliver opkrævet af kunden ved betaling
	}
	
	//Forsigtig
	if(ss1) {
		grundpris += 79;
		moms = true;
	}

	//Lørdagsomdeling
	if(ss46) {
		grundpris += 66;
		moms = true;
	}

	//Værdipakke
	if(ss5amount) {
		grundpris += 88;
		grundpris += Math.ceil(ss5amount/1000)*2;
	}
	
	//Moms
	if(moms) {
		endeligPris = 1.25*grundpris;
	} else {
		endeligPris = grundpris;
	}
	
	return Math.round(endeligPris*100)/100;
}
