var H;
function setH() {
	H=document.documentElement.clientHeight
	if(!H)
		H=document.body.clientHeight;
	H -= 74;
	document.getElementById('text').style.height = H+'px';
	document.getElementById('menu').style.height = H+'px';
}