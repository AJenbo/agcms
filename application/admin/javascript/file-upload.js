function keepAspect(changed, change)
{
    var value = document.getElementById(changed).value;
    value = parseInt(value);

    if(document.getElementById('aspect').value == '4-3') {
        if(change == 'x') {
            value = value / 3 * 4;
        } else {
            value = value / 4 * 3;
        }
    } else if(document.getElementById('aspect').value == '16-9') {
        if(change == 'x') {
            value = value / 9 * 16;
        } else {
            value = value / 16 * 9;
        }
    }

    document.getElementById(change).value = Math.round(value);
}

function filetypeshow()
{
    var type = document.getElementById('type').value;
    var description = document.getElementById('description');
    var videooptions = document.getElementById('videooptions');
    var file = document.getElementById('file');

    if(type == 'image' || type == 'lineimage') {
        videooptions.style.display = 'none';
        description.style.display = '';
        status('Vælg det billed du vil sende');
        file.setAttribute('accept', 'image/*');
    } else if(type == 'video') {
        description.style.display = 'none';
        videooptions.style.display = '';
        status('Vælg den film du vil sende');
        file.setAttribute('accept', 'video/*');
    } else {
        description.style.display = 'none';
        videooptions.style.display = 'none';
        file.setAttribute('accept', '');

        if(type == '') {
            status('Vælg den fil du vil sende');
        } else {
            status('Vælg en filtype');
        }
    }

    if(type == '') {
        file.disabled = true;
        return;
    }
    file.disabled = false;
}

function validate()
{
    var file = document.getElementById('file').files[0];
    var button = document.getElementById('submit');

    if(!file) {
        button.disabled = true;
        filetypeshow();
        return false;
    }

    if(file.size > maxbyte) {
        alert('Filen må max være på ' + Math.round(maxbyte / 1024 / 1024 * 10) / 10 + 'MB');
        button.disabled = true;
        return false;
    }

    x_fileExists(activeDir, file.name, document.getElementById('type').value, fileExists_r);

    status('Fil: ' + file.name);

    button.disabled = false;
    return true;
}

function fileExists_r(data)
{
    if(data['error']) {
        alert(data['error']);
    } else if(data) {
        alert('En fil med samme navn eksistere allerede');
    }
    return data;
}

function send()
{
    document.getElementById('description').style.display = 'none';
    document.getElementById('videooptions').style.display = 'none';
    document.getElementById('status').style.display = 'none';
    var progress = document.getElementById('progress');
    progress.style.display = 'block';
    var file = document.getElementById('file').files[0];

    var form = new FormData();
    form.append('type', document.getElementById('type').value);
    form.append('Filedata', file);
    form.append('alt', document.getElementById('alt').value);
    form.append('dir', activeDir);
    form.append('x', document.getElementById('x').value);
    form.append('y', document.getElementById('y').value);
    form.append('aspect', document.getElementById('aspect').value);

    try {
        var x = new window.XMLHttpRequest();
    } catch(e) {
    }
    if(x === null || typeof x.readyState !== "number") {
        return true;
    }
    x.onload = function(data) {
        document.getElementById('progress').style.display = 'none';

        if(x.status != 200) {
            alert('Error: ' + x.responseText);
            return;
        }

        document.getElementById('file').value = '';
        document.getElementById('alt').value = '';
        validate();
        status('Filen er sendt');
        document.getElementById('status').style.display = '';
        window.opener.showfiles(window.opener.activeDir);
    };
    x.upload.onprogress = function(evt) {
        if(evt.lengthComputable) {
            var pct = evt.loaded / evt.total;
            if(pct < 1) {
                progress.value = pct;
                return;
            }
        }

        progress.value = '';
    };
    x.open('POST', '/admin/upload/', true);
    x.send(form);
    return false;
}

function status(text)
{
    document.getElementById('status').innerHTML = text;
}
