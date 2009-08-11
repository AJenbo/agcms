function $(id) {
	return document.getElementById(id);
}

function init() {
	var activmenu = $('activmenu');
	if(activmenu)
		$('menu').scrollTop = activmenu.offsetTop;
	//Internet explorer 7 render issue
	setTimeout("$('menu').style.bottom = '0px';", 10);
}