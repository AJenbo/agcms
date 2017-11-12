function filetypeshow()
{
    var type = document.getElementById('type').value;
    var description = document.getElementById('description');
    var file = document.getElementById('file');

    var message = 'Vælg en filtype';

    description.style.display = 'none';
    file.setAttribute('accept', '');

    if(type == '') {
        message = 'Vælg den fil du vil sende';
    } else if(type == 'image' || type == 'lineimage') {
        description.style.display = '';
        message = 'Vælg de billeder du vil sende';
        file.setAttribute('accept', 'image/*');
    } else if(type == 'video') {
        message = 'Vælg den film du vil sende';
        file.setAttribute('accept', 'video/*');
    }
    status(message);

    if(type == '') {
        file.disabled = true;
        return;
    }
    file.disabled = false;
}

function validate()
{
    var files = document.getElementById('file').files;
    var submit = document.getElementById('submit');

    submit.disabled = true;
    if(!files.length) {
        return false;
    }

    for(var i = 0; i < files.length; i++) {
        if(files[i].size > maxbyte) {
            alert('Filen må max være på ' + Math.round(maxbyte / 1024 / 1024 * 10) / 10 + 'MB');
            return false;
        }

        status('Søger efter dubletter...');

        var path = activeDir + '/' + files[i].name;
        var type = document.getElementById('type').value;
        xHttp.request(
            '/admin/explorer/files/exists/?path=' + encodeURIComponent(path) + '&type=' + encodeURIComponent(type),
            fileExists_r);
    }
    status('');

    submit.disabled = false;
    return true;
}

function fileExists_r(data)
{
    if(data.error) {
        alert(data.error);
    } else if(data.exists) {
        alert('Bemærk en fil med navnet "' + data.name
            + '" allerede eksistere, og vil blive overskrevet hvis du fortsætter med denne upload!');
    }
    return data;
}

var totals = [];
var uploads = [];
function updateProgress()
{
    var totalSize = 0;
    for(var i = 0; i < totals.length; i++) {
        totalSize += totals[i]; // Set max before starting any uploads
    }

    var uploaded = 0;
    for(var i = 0; i < uploads.length; i++) {
        uploaded += uploads[i];
    }

    var progressBar = document.getElementById('progress');
    progressBar.max = parseInt(totalSize);

    var diff = totals.filter(function(element, index) {
        return element === uploads[index]
    });

    var statusText = " beregner upload...";
    if(uploaded) {
        progressBar.value = parseInt(uploaded);
        statusText = " uploader..."
    }
    status(diff.length + "/" + totals.length + statusText);

    if(progressBar.max === progressBar.value) {
        status('Filerne er sendt');
        window.opener.showfiles(window.opener.activeDir);
        document.getElementById('progress').style.display = 'none';
        document.getElementById('type').disabled = false;
        document.getElementById('type').value = '';
        document.getElementById('file').value = '';
        document.getElementById('alt').value = '';
        validate();
    }
}

function send()
{
    status("Klargøre data.");
    totals = [];
    uploads = [];

    document.getElementById('type').disabled = true;
    document.getElementById('file').disabled = true;
    document.getElementById('submit').disabled = true;
    document.getElementById('description').style.display = 'none';
    document.getElementById('description').style.display = 'none';

    var progress = document.getElementById('progress');
    progress.removeAttribute("value")
    progress.style.display = 'block';

    var file;
    var files = document.getElementById('file').files;
    var form = new FormData();
    form.append('type', document.getElementById('type').value);
    form.append('alt', document.getElementById('alt').value);
    form.append('dir', activeDir);

    for(var i = 0; i < files.length; i++) {
        form.append('upload', files[i]);
        try {
            x = new window.XMLHttpRequest();
        } catch(e) {
        }
        if(x === null || typeof x.readyState !== "number") {
            continue;
        }
        totals[i] = files[i].size;
        uploads[i] = NaN;
        x.onload = getOnLoadFunction(x, i);
        x.upload.onprogress = getOnProgressFunction(i);
        x.open('POST', '/admin/explorer/files/', true);
        x.send(form);
    }

    return false;
}

function getOnLoadFunction(x, i)
{
    return function(data) {
        uploads[i] = totals[i];
        updateProgress();
        var result;
        try {
            result = JSON.parse(x.responseText);
        } catch(err) {
            result = { "uploaded" : (x.status === 200 ? 1 : 0), "error" : { "message" : "Error: " + x.responseText } };
        }
        if(!result.uploaded) {
            alert(result.error.message);
        }
    };
}

function getOnProgressFunction(i)
{
    return function(evt) {
        if(evt.lengthComputable) {
            totals[i] = evt.total;
        }
        uploads[i] = evt.loaded;
        updateProgress();
    };
}

function status(text)
{
    document.getElementById('status').innerHTML = text;
}
