function getContextMenuTarget(object, className) {
	while(object.className != className) {
		object = object.parentNode;
	}
	return object;
}

function prisHighlight() {
	if($('for').value - $('pris').value < 0)
		$('pris').className='Pris';
	else
		$('pris').className='NyPris';
}

function setThb(id, value, imgvalue) {
	if (arguments.length == 2) {
		imgvalue = value;
	}
	$(id).value = value;
	$(id+'thb').src = imgvalue;	
}

function explorer(returntype, returnid) {
	window.open('explorer.php?return='+returntype+'&returnid='+returnid, 'explorer','status=1,resizable=1,toolbar=0,menubar=0,location=0,scrollbars=0');
}

function explorer_unused() {
	window.open('explorer-unused.php', 'explorer','status=1,resizable=1,toolbar=0,menubar=0,location=0,scrollbars=0');
}

function generic_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	}
}

function opretSide() {
	updateRTEs();
	x_opretSide(
		getRadio('kat'),
		$('navn').value,
		$('keywords').value,
		$('pris').value,
		$('billed').value,
		$('beskrivelse').value,
		$('for').value,
		$('text').value,
		$('varenr').value,
		getSelectValue('burde'),
		getSelectValue('fra'),
		getSelectValue('krav'),
		getSelectMultiValues('maerke'),
		opretSide_r);
	return false;
}

function opretSide_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error'])
		alert(data['error']);
	else window.location.href = '?side=redigerside&id='+data['id'];
}

function checkForInt(evt) {
	if(isIE) {
		var charCode = event.keyCode;
	} else {
		var charCode = evt.which
	}
	return (( charCode >= 48 && charCode <= 57) || charCode == 8 || charCode == 0 || charCode == 13);
}

function inject_html(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	} else {
		$(data['id']).innerHTML = data['html'];
	}
}

function isInteger(s) {
	var i;
	for (i = 0; i < s.length; i++) {   
		// Check that current character is number.
		var c = s.charAt(i);
		if (((c < "0") || (c > "9"))) return false;
	}
	// All characters are numbers.
	return true;
}

function setCookie(name, value, expires) {
	var today = new Date();
	today.setTime(today.getTime());
	if(expires)
		expires = expires * 1000 * 60 * 60 * 24;
		
	var expires_date = new Date(today.getTime() + (expires));
	document.cookie = encodeURIComponent(name)+"="+encodeURIComponent(value) + ((expires) ? "; expires="+expires_date.toGMTString() : "");
}

function getCookie(cookieName) {
	cookieName = encodeURIComponent(cookieName);
	if (document.cookie.length > 0) {
		begin = document.cookie.indexOf(cookieName+"=");
		if (begin != -1) {
			begin += cookieName.length+1; 
			end = document.cookie.indexOf(";", begin);
			if (end == -1) end = document.cookie.length;
				return decodeURIComponent(document.cookie.substring(begin, end));
		} 
	}
	return null; 
}

function showhide(id) {
	var obj = $(id);
	if(obj.style.display == '') {
		obj.style.display = 'none';
		setCookie('hide'+id, '1', 360);
	} else {
		obj.style.display = '';
		setCookie('hide'+id, '', 0);
	}
}

function showhidekats(id,thisobj) {
	var obj = $(id);
	if(obj.style.display == '') {
		obj.style.display = 'none';
		setCookie('hide'+id, '1', 360);
		$('loading').style.visibility = '';
		x_katspath(getRadio('kat'),inject_html);
	} else {
		obj.style.display = '';
		thisobj.innerHTML = 'Vælg placering:';
		setCookie('hide'+id, '', 0);
	}
}

function getRadio(name) {
	var objs = document.getElementsByName(name);
	for (var i=0; i < objs.length; i++) {
		if(objs[i].checked) {
			return objs[i].value;
		}
	}
	return null;
}

function getSelectValue(id) {
	var objs = $(id).getElementsByTagName('option');
	for (var i=0; i < objs.length; i++) {
		if(objs[i].selected) {
			return objs[i].value;
		}
	}
	return null;
}

function getSelectMultiValues(id) {
	var value = '';
	var objs = $(id).getElementsByTagName('option');
	for (var i=0; i < objs.length; i++) {
		if(objs[i].selected) {
			if(!value)
				value = objs[i].value;
			else
				value = value+','+objs[i].value;
		}
	}
	return value;
}

function showimage(obj,img) {
	$('imagelogo').innerHTML = '<img src="'+img+'" />';
	$('imagelogo').style.left = obj.offsetLeft+17+'px';
	$('imagelogo').style.top = obj.offsetTop+17+'px';
	$('imagelogo').style.display = '';
}

function kat_contract(id) {
	$('kat'+id+'content').style.display = 'none';
	$('kat'+id+'contract').style.display = 'none';
	$('kat'+id+'expand').style.display = '';
	var openkat = getCookie('openkat');
	openkat = openkat.split('<');
	openkat.splice(openkat.indexOf(id), 1);
	openkat = openkat.join('<');
	setCookie('openkat', openkat, 360);
}

function kat_expand(id, input) {
	if($('kat'+id+'content').innerHTML == '') {
		$('loading').style.visibility = '';
		x_kat_expand(id, input, kat_expand_r);
	} else {
		$('kat'+id+'content').style.display = '';
		$('kat'+id+'expand').style.display = 'none';
		$('kat'+id+'contract').style.display = '';
		setCookie('openkat', getCookie('openkat')+'<'+id, 360);
	}
}

function siteList_expand(id) {
	setCookie('activekat', id, 360);
	if($('kat'+id+'content').innerHTML == '') {
		$('loading').style.visibility = '';
		x_siteList_expand(id, kat_expand_r);
	} else {
		$('kat'+id+'content').style.display = '';
		$('kat'+id+'expand').style.display = 'none';
		$('kat'+id+'contract').style.display = '';
		
		var openkat = getCookie('openkat');
		openkat = openkat.split('<');
		openkat.splice(openkat.indexOf(id), 1);
		openkat = openkat.join('<');
		setCookie('openkat', openkat, 360);
	}
}

function kat_expand_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	} else {
		$('kat'+data['id']+'expand').style.display = 'none';
		$('kat'+data['id']+'contract').style.display = '';
		$('kat'+data['id']+'content').innerHTML = data['html'];
		$('kat'+data['id']+'content').style.display = '';
		setCookie('openkat', getCookie('openkat')+'<'+data['id'], 360);
		reattachContextMenus();
	}
}

function init() {
	$('loading').style.visibility = 'hidden';
	attachContextMenus();
}

function save_krav() {
	updateRTEs();
	x_savekrav($('id').value, $('navn').value, $('text').value, inject_html);
	return false
}

function bind(id) {
	$('loading').style.visibility = '';
	x_bind(id, getRadio('kat'), bind_r);
	return false;
}

function objToArray(obj) {
	var r = [], x;
	for(x in obj)
		if(obj.hasOwnProperty(x) && !isNaN(parseInt(x)))
			r[x] = obj[x];
	return r;
}

function bindTree_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	} else {
		removeTagById('bind'+data['deleted'][0]['id']);

		if(data['added'] && $('kat'+data['added']['kat']+'content').innerHTML != '') {
			var display = $('kat'+data['added']['kat']+'content').style.display;
			x_siteList_expand(data['added']['kat'], kat_expand_r);
			$('kat'+data['added']['kat']+'content').style.display = display;
		}
	}
}

function bind_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	} else {
		data['deleted'] = objToArray(data['deleted']);
		for(i=0;i<data['deleted'].length;i++)
			removeTagById('bind'+data['deleted'][i]['id']);
//TODO check that all function that returns to this, sends in  [i]['id'] format
		
		if(data['added']) {
			var p = document.createElement('p');
			p.setAttribute('id', 'bind'+data['added']['id']);
			var img = document.createElement('img');
			img.setAttribute('src', 'images/cross.png');
			img.setAttribute('alt', 'X');
			img.setAttribute('height', '16');
			img.setAttribute('width', '16');
			img.setAttribute('title', 'Fjern binding');
			img.onclick = function() { slet('bind',data['added']['path'],data['added']['id']); };
			p.appendChild(img);
			p.appendChild(document.createTextNode(' '+data['added']['path']));
			$('bindinger').appendChild(p);
		}
	}
}

function removeTagById(id) {
	var obj=$(id);
	obj.parentNode.removeChild(obj);
}

function removeTagByClass(className) {
	var objs=$$('.'+className);
	for(var i=0;i<objs.length;i++) {
		objs[i].parentNode.removeChild(objs[i]);
	}
}

function slet(type, navn, id) {
	switch(type) {
		case 'side':
			if(confirm("Vil du slette '"+navn+"'?")==true){
				$('loading').style.visibility = '';
				x_sletSide(id, sletClass_r);
			}
		break;
		case 'bind':
			if(confirm("Vil du fjerne siden fra '"+navn+"'?")==true) {
				$('loading').style.visibility = '';
				x_sletbind(id, bind_r);
			}
		break;
		case 'bindtree':
			if(confirm("Vil du fjerne siden fra '"+navn+"'?")==true) {
				$('loading').style.visibility = '';
				x_sletbind(id, bindTree_r);
			}
		break;
		case 'maerke':
			if(confirm("Vil du slette mærket '"+navn+"'?")==true) {
				$('loading').style.visibility = '';
				x_sletmaerke(id, slet_r);
			}
		break;
		case 'krav':
			if(confirm("Vil du slette kravet '"+navn+"'?")==true) {
				$('loading').style.visibility = '';
				x_sletkrav(id, slet_r);
			}
		break;
		case 'kat':
			if(confirm("Vil du slette katagorien '"+navn+"'?")==true) {
				$('loading').style.visibility = '';
				x_sletkat(id, slet_r);
			}
		break;
	}
}


function movekat(navn, id, toId, confirmMove) {
	if(!confirmMove || confirm("Vil du fjerne kategorien '"+navn+"'?")==true) {
		$('loading').style.visibility = '';
		x_movekat(id, toId, movekat_r);
	}
}

function movekat_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	} else if(data) {
		removeTagById(data['id']);
		//TODO if kat_expand use kat_expand instead of siteList_expand
		if($('kat'+data['update']+'content').innerHTML != '') {
			var display = $('kat'+data['update']+'content').style.display;
			x_siteList_expand(data['update'], kat_expand_r);
			$('kat'+data['update']+'content').style.display = display;
		}
	}
}

function renamekat(id, name) {
	var newname = prompt('Omdøb kategori', name);
	if(newname != null && newname != name) {
		$('loading').style.visibility = '';
		x_renamekat(id, newname, renamekat_r);
	}
}

function renamekat_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	} else {
		if($(data['id']).childNodes.length == 4)
			$(data['id']).childNodes[2].lastChild.nodeValue = ' '+data['name'];
		else
			$(data['id']).firstChild.lastChild.nodeValue = ' '+data['name'];
	}
}

function sletClass_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	} else {
		removeTagByClass(data['class']);
	}
}

function slet_r(data) {
	$('loading').style.visibility = 'hidden';
	if(data['error']) {
		alert(data['error']);
	} else {
		removeTagById(data['id']);
	}
}

function jumpto() {
	var jumptoid = $('jumptoid').value;
	if(jumptoid && isInteger(jumptoid)) {
		location.href='./?side=redigerside&id='+jumptoid;
	} else
		alert('Du skal indtaste et korrekt side nummer');
	return false;
}

function sogsearch() {
	var sogtext = $('sogtext').value;
	if(sogtext) {
		$('loading').style.visibility = '';
		//TODO make page independant!
		x_search(sogtext, inject_html);
	} else
		alert('Du skal indtaste et søge ord.');
	return false;
}
