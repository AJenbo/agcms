//if(document.compatMode != 'CSS1Compat' || IE6)

function $(id) {
	return document.getElementById(id);
}

var Top = 93;
var marquee;

function init() {

	Top = $('submenu').offsetTop+$('submenu').offsetHeight;
	$('container').style.top = Top+'px';
	marquee = document.getElementsByTagName('marquee');
	marquee[0].style.bottom = '';
	$('footer').style.bottom = '';
	
	setH();
	var activmenu = $('activmenu');
	if(activmenu)
		$('menu').scrollTop = activmenu.offsetTop;
}

var H;

function setH() {
	H=document.documentElement.clientHeight;
	
	//IE 5 and 5.5
	if(!H) {
		H=document.body.clientHeight;
		$('menu').style.width = '154px';
	}
	H = H - Top;
	
	//IE 5, 5.5 dies
	$('content').style.height = H-57+'px';
	$('menu').style.height = H+'px';
	//IE6 is slugish at updating css layout so remind it.
	marquee[0].style.top = H-57+'px';
	$('footer').style.top = H-37+'px';
}