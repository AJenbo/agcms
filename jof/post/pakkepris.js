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
			$('ss2').checked = true;
		} else {
			$('ss2').checked = false;
		}
	}
	
	if((height != 0 && width != 0 && length != 0) || weight != 0)
		weight = Math.max(5, weight);

	var pakkeprisv = pakkepris(height, width, length, weight, getRadio('optRecipType'), $('ss1').checked, $('ss46').checked, parseFloat($('ss5amount').value.replace(/,/,'.')), $('ss2').checked);

	if(pakkeprisv > 60)
		$('porto').innerHTML = Math.ceil(pakkeprisv/5)*5;
	else
		$('porto').innerHTML = pakkeprisv;
}

function postdkloaded() {
}

function changeOptRecipType() {
	if(getRadio('optRecipType') == 'P') {
		$('trExpress').style.display = '';
		$('trVolume').style.display = '';
	} else if(getRadio('optRecipType') == 'E') {
		$('trExpress').style.display = 'none';
		$('ss46').checked = false;
		$('trVolume').style.display = 'none';
		$('ss2').checked = false;
	} else if(getRadio('optRecipType') == 'O') {
		$('trExpress').style.display = 'none';
		$('ss46').checked = false;
		$('trVolume').style.display = 'none';
		$('ss2').checked = false;
	}
	calc();
}