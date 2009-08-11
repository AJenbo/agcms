var H;
function setH() {
	H=document.documentElement.clientHeight;
	if(!H)
		H=document.body.clientHeight;
	document.getElementById('text').style.height = H-23+'px';
	document.getElementById('menu').style.height = H-90+'px';
}