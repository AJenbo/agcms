function init() {
	$('container').style.top = $('submenu').offsetTop+$('submenu').offsetHeight+'px';
	//Fighting odd bug in IE8b1E7 when pressing back or refresh
	setTimeout("$('container').style.bottom = '0px';", 10);
	var activmenu = $('activmenu');
	if(activmenu)
		$('menu').scrollTop = activmenu.offsetTop;
}