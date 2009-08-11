function $(id) {
	return document.getElementById(id);
}

var krav;
function openkrav(url) {
	krav = window.open(url, 'krav', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=512,height=395,left = '+(screen.width-512)/2+',top = '+ (screen.height-395)/2);
	return false;
}

function changeFlashHeight(height) {
	 $('flashshifter').style.height = height+'px';
	 $('flashshifterns').style.height = height+'px';
}