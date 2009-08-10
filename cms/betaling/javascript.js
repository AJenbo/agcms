function showhidealtpost(status) {
	var Trs = document.getElementsByTagName('TR');
	if(status) {
		for(var i = 0; i<Trs.length; i++) {
			if(Trs[i].className == 'altpost')
				Trs[i].style.display = '';
		}
	} else {
		for(var i = 0; i<Trs.length; i++) {
			if(Trs[i].className == 'altpost')
				Trs[i].style.display = 'none';
		}
	}
}


