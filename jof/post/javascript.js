function changeUser(user) {
	x_changeUser(user, changeUser_r);
	return true;
}

function changeUser_r(data) {
	//Todo, open pdf as a popup here since thers no othere way to wait for the responce;
	delayclick = false;
}

function $(id) {
	return document.getElementById(id);
}

function trim(inputString) {
	//Remove white space at the start and end of a string
	if (typeof inputString != "string") {
		return inputString;
	}
	inputString = inputString.replace(/^\s+|\s+$/g, "").replace(/\s{2,}/g, "");
	return inputString;
}

function getRadio(name) {
	var objs = document.getElementsByName(name);
	for (var i=0; i < objs.length; i++) {
		if(objs[i].checked) {
			return objs[i].value;
		}
	}
}

function getSelectValue(id) {
	var objs = $(id).getElementsByTagName('option');
	for (var i=0; i < objs.length; i++) {
		if(objs[i].selected) {
			return objs[i].value;
		}
	}
}

function fixedLength(string){
	while(string.length<2){
		string = '0'+string;
	}
	return string;
}

function standard(pakke) {
	switch(pakke) {
		case 0:
			$('height').value = '';
			$('width').value = '';
			$('length').value = '';
			//$('porto').value = '';
		break
		//SB4
		case 1:
			$('height').value = 14;
			$('width').value = 14;
			$('length').value = 15;
			calc();
		break
		//SB5
		case 2:
			$('height').value = 17;
			$('width').value = 17;
			$('length').value = 18;
			calc();
		break
		//SB6
		case 3:
			$('height').value = 19;
			$('width').value = 19;
			$('length').value = 20;
			calc();
		break
		//SB27
		case 4:
			$('height').value = 19;
			$('width').value = 45;
			$('length').value = 45;
			calc();
		break
		//SB30
		case 5:
			$('height').value = 19;
			$('width').value = 10;
			$('length').value = 7;
			calc();
		break
		//SB34
		case 6:
			$('height').value = 38;
			$('width').value = 19;
			$('length').value = 17;
			calc();
		break
		//SB35
		case 7:
			$('height').value = 38;
			$('width').value = 29;
			$('length').value = 17;
			calc();
		break
		//1,5 meter r�r + SB4
		case 8:
			$('height').value = 14;
			$('width').value = 24;
			$('length').value = 150;
			calc();
		break
		//1,5 meter r�r + SB5
		case 9:
			$('height').value = 17;
			$('width').value = 27;
			$('length').value = 150;
			calc();
		break
		//1,5 meter r�r + SB6
		case 10:
			$('height').value = 19;
			$('width').value = 29;
			$('length').value = 150;
			calc();
		break
		//2 meter r�r + SB4
		case 11:
			$('height').value = 14;
			$('width').value = 24;
			$('length').value = 200;
			calc();
		break
		//2 meter r�r + SB5
		case 12:
			$('height').value = 17;
			$('width').value = 27;
			$('length').value = 200;
			calc();
		break
		//2 meter r�r + SB6
		case 13:
			$('height').value = 19;
			$('width').value = 29;
			$('length').value = 200;
			calc();
		break
		//1.5 meter r�r
		case 14:
			$('height').value = 9;
			$('width').value = 9;
			$('length').value = 150;
			calc();
		break
		//2 meter r�r
		case 15:
			$('height').value = 9;
			$('width').value = 9;
			$('length').value = 200;
			calc();
		break
	}
}