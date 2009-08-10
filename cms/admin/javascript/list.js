var listlink = Array();
function listInsertRow(listid) {
	var footer = $('list'+listid+'footer');
	
	var cells = '';
	if(listlink[listid] == 1) {
		for(i=0; i<footer.childNodes.length-2; i++) {
			if(cells)
				cells += '<';
			cells += htmlspecialchars(footer.childNodes[i].firstChild.value);
		}
		var rowlink = footer.childNodes[footer.childNodes.length-2].firstChild.value;
	} else {
		for(i=0; i<footer.childNodes.length-1; i++) {
			if(cells)
				cells += '<';
			cells += htmlspecialchars(footer.childNodes[i].firstChild.value);
		}
	}
	//todo rowlink = undefined if no link
	x_listSavetRow(listid, cells, rowlink, 0, listInsertRow_r);
}

function listInsertRow_r(data) {
	$('loading').style.display = 'none';
	if(data['error']) {
		alert(data['error']);
	}
	
	var footer = $('list'+data['listid']+'footer');
	
	var tr = document.createElement('tr');
	tr.setAttribute('id', 'list_row'+data['rowid']);
	
	var td;
	var span;
	var input;
	for(i=0; i<footer.childNodes.length-1; i++) {
		td = document.createElement('td');
		td.style.textAlign = footer.childNodes[i].firstChild.style.textAlign;
		span = document.createElement('span');
		span.appendChild(document.createTextNode(footer.childNodes[i].firstChild.value));
		td.appendChild(span);
		input = document.createElement('input');
		input.setAttribute('value', footer.childNodes[i].firstChild.value);
		input.style.display = 'none';
		td.appendChild(input);
		tr.appendChild(td);
		
		footer.childNodes[i].firstChild.value = '';
	}
	
	td = document.createElement('td');
	td.innerHTML = '<img onclick="listEditRow('+data['listid']+', '+data['rowid']+');" src="images/application_edit.png" alt="Rediger" title="Rediger" width="16" height="16" /><img onclick="listUpdateRow('+data['listid']+', '+data['rowid']+');" style="display:none" src="images/disk.png" alt="Rediger" title="Rediger" width="16" height="16" /><img src="images/cross.png" alt="X" title="Slet række" onclick="listRemoveRow('+data['listid']+', '+data['rowid']+')" />';
	tr.appendChild(td);
	
	rows = $('list'+data['listid']+'rows');
	rows.appendChild(tr);
	
	if(!(rows.childNodes.length % 2))
		rows.lastChild.className = 'altrow';
}

function listEditRow(listid, rowid) {
	$('list'+listid+'footer').style.display = 'none';
	
	var row = $('list_row'+rowid);
	
	for(var i=0; i<row.childNodes.length-1; i++) {
		row.childNodes[i].firstChild.style.width = row.childNodes[i].clientWidth-2+'px';
	}
	for(i=0; i<row.childNodes.length-1; i++) {
		row.childNodes[i].lastChild.style.display = 'none';
		row.childNodes[i].firstChild.style.display = '';
	}

	rows = $('list'+listid+'rows');
	for(i=0; i<rows.childNodes.length; i++) {
		rows.childNodes[i].childNodes[row.childNodes.length-1].style.display = 'none';
	}
	row.childNodes[row.childNodes.length-1].style.display = '';
	
	row.childNodes[row.childNodes.length-1].firstChild.style.display = 'none';
	row.childNodes[row.childNodes.length-1].childNodes[1].style.display = '';
	row.childNodes[row.childNodes.length-1].childNodes[2].style.display = 'none';
	//Firefox scrolls :(
	row.firstChild.firstChild.focus();
}

function listUpdateRow(listid, rowid) {
	var row = $('list_row'+rowid);
	
	var cells = '';
	if(listlink[listid] == 1) {
		for(i=0; i<row.childNodes.length-2; i++) {
			if(cells)
				cells += '<';
			cells += htmlspecialchars(row.childNodes[i].firstChild.value);
		}
		var rowlink = row.childNodes[row.childNodes.length-2].firstChild.value;
	} else {
		for(i=0; i<row.childNodes.length-1; i++) {
			if(cells)
				cells += '<';
			cells += htmlspecialchars(row.childNodes[i].firstChild.value);
		}
	}
	x_listSavetRow(listid, cells, rowlink, rowid, listUpdateRow_r);
}

function listUpdateRow_r(data) {
	$('loading').style.display = 'none';
	if(data['error']) {
		alert(data['error']);
	}
	var row = $('list_row'+data['rowid']);
	
	for(i=0; i<row.childNodes.length-1; i++) {
		if(typeof(row.childNodes[i].lastChild.textContent) == 'string'){
			row.childNodes[i].lastChild.textContent = row.childNodes[i].firstChild.value;
		} else{
			row.childNodes[i].lastChild.innerText = row.childNodes[i].firstChild.value;
		}

		row.childNodes[i].lastChild.style.display = '';
		row.childNodes[i].firstChild.style.display = 'none';
	}
	
	row.childNodes[row.childNodes.length-1].firstChild.style.display = '';
	row.childNodes[row.childNodes.length-1].childNodes[1].style.display = 'none';
	row.childNodes[row.childNodes.length-1].childNodes[2].style.display = '';
	
	rows = $('list'+data['listid']+'rows');
	for(i=0; i<rows.childNodes.length; i++) {
		rows.childNodes[i].childNodes[row.childNodes.length-1].style.display = '';
	}
	$('list'+data['listid']+'footer').style.display = '';
}

function listSizeFooter(listid) {
	var row = $('list'+listid+'footer');
	
	for(var i=0; i<row.childNodes.length-1; i++) {
		row.childNodes[i].firstChild.style.width = row.childNodes[i].clientWidth-2+'px';
	}
	for(i=0; i<row.childNodes.length-1; i++) {
		row.childNodes[i].lastChild.style.display = 'none';
		row.childNodes[i].firstChild.style.display = '';
	}
}

function listRemoveRow(listid, rowid) {
	if(confirm('Vil du virkelig slette denne linje.')) {
		x_listRemoveRow(listid, rowid, listRemoveRow_r);
		$('loading').style.display = '';
	}
}

function listRemoveRow_r(data) {
	$('loading').style.display = 'none';
	if(data['error']) {
		alert(data['error']);
	}
	
	removeTagById($('list_row'+data['rowid']));
	
	rows = $('list'+data['listid']+'rows');
	for(i=0;i<rows.childNodes.length;i++) {
		if(i % 2)
			rows.childNodes[i].className = 'altrow';
		else
			rows.childNodes[i].className = '';
	}
}