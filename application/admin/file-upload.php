<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/logon.php';

Sajax\Sajax::export(['fileExists' => ['method' => 'GET', 'asynchronous' => false, 'uri' => '/admin/file-upload.php']]);
Sajax\Sajax::handleClientRequest();

Render::sendCacheHeader(Render::getUpdateTime(false));

if (empty($_COOKIE['admin_dir']) || !is_dir(_ROOT_ . @$_COOKIE['admin_dir'])) {
    @setcookie('admin_dir', '/images');
    @$_COOKIE['admin_dir'] = '/images';
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('File upload'); ?></title>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript"><!--
<?php Sajax\Sajax::showJavascript(); ?>

var maxbyte = <?php

$maxbyte = min(
    returnBytes(ini_get('post_max_size')),
    returnBytes(ini_get('upload_max_filesize'))
);
    echo $maxbyte;
?>;

function keepAspect(changed, change) {
    var value = document.getElementById(changed).value;
    value = parseInt(value);

    if (document.getElementById('aspect').value == '4-3') {
        if (change == 'x') {
            value = value / 3 * 4;
        } else {
            value = value / 4 * 3;
        }
    } else if (document.getElementById('aspect').value == '16-9') {
        if (change == 'x') {
            value = value / 9 * 16;
        } else {
            value = value / 16 * 9;
        }
    }

    document.getElementById(change).value = Math.round(value);
}

function filetypeshow() {
    var type = document.getElementById('type').value;
    var description = document.getElementById('description');
    var videooptions = document.getElementById('videooptions');
    var file = document.getElementById('file');

    if (type == 'image' || type == 'lineimage') {
        videooptions.style.display = 'none';
        description.style.display = '';
        status('Vælg det billed du vil sende');
        file.setAttribute('accept', 'image/*');
    } else if (type == 'video') {
        description.style.display = 'none';
        videooptions.style.display = '';
        status('Vælg den film du vil sende');
        file.setAttribute('accept', 'video/*');
    } else {
        description.style.display = 'none';
        videooptions.style.display = 'none';
        file.setAttribute('accept', '');

        if (type == '') {
            status('Vælg den fil du vil sende');
        } else {
            status('Vælg en filtype');
        }
    }

    if (type == '') {
        file.disabled = true;
    } else {
        file.disabled = false;
    }
}

function validate() {
    var file = document.getElementById('file').files[0];
    var button = document.getElementById('submit');

    if (!file) {
        button.disabled = true;
        filetypeshow();
        return false;
    }

    if (file.size > maxbyte) {
        alert('Filen må max være på ' + Math.round(maxbyte/1024/1024*10)/10 + 'MB');
        button.disabled = true;
        return false;
    }

    x_fileExists(
        file.name,
        document.getElementById('type').value,
        fileExists_r
    );

    status('Fil: ' + file.name);

    button.disabled = false;
    return true;
}

function fileExists_r(data) {
    if (data['error']) {
        alert(data['error']);
    } else if (data) {
        alert('En fil med samme navn eksistere allerede');
    }
    return data;
}

function send() {
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
    form.append('x', document.getElementById('x').value);
    form.append('y', document.getElementById('y').value);
    form.append('aspect', document.getElementById('aspect').value);

    try {
        var x = new window.XMLHttpRequest();
    } catch(e) {}
    if(x === null || typeof x.readyState !== "number") {
        return true;
    }
    x.onload = function(data) {
        document.getElementById('progress').style.display = 'none';

        if (x.status != 200) {
            if (x.status == 401) {
                alert('Session udløbet, logind igen for at fortsætte.');
            } else if (x.status == 501) {
                alert('Mangler filfunctioner.');
            } else if (x.status == 503) {
                alert('Kunne ikke læse filnavn.');
            } else if (x.status == 504) {
                alert('Fejl under flytning af filen.');
            } else if (x.status == 505) {
                alert('Kunne ikke give tilladelse til filen.');
            } else if (x.status == 510) {
                alert('Mangler get_mime_type()');
            } else if (x.status == 512) {
                alert('Kunne ikke finde billed størelsen.');
            } else if (x.status == 520) {
                alert('Kunne ikke slette filen.');
            } else if (x.status == 521) {
                alert('Billedet er for stor.');
            } else if (x.status == 561) {
                alert('Fejl under billed behandling.');
            } else if (x.status == 542) {
                alert('Slette fejl i databasen!');
            } else if (x.status == 543) {
                alert('Fejl ved indsætning i database!');
            } else if (x.status == 404) {
                alert('Fil ikke sendt!');
            } else {
                alert('Ukendt fejl: ' + x.status);
            }
            return;
        }

        document.getElementById('file').value = '';
        validate();
        status('Filen er sendt');
        document.getElementById('status').style.display = '';
        window.opener.showfiles('', 1);
    };
    x.upload.onprogress = function(evt) {
        if (evt.lengthComputable) {
            var pct = evt.loaded / evt.total;
            if (pct < 1) {
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

function status(text) {
    document.getElementById('status').innerHTML = text;
}

--></script>
</head>
<body onload="window.focus();" bgcolor="#ffffff">
<form method="post" enctype="multipart/form-data" action="/admin/upload/" onsubmit="return send();">

<select name="type" id="type" onchange="filetypeshow();" onkeyup="filetypeshow();">
    <option value=""><?php echo _('File type'); ?></option>
    <option value="image"><?php echo _('Image'); ?></option>
    <option value="lineimage"><?php echo _('Illustration'); ?></option>
    <option value="video"><?php echo _('Video'); ?></option>
    <option value="other"><?php echo _('Other files'); ?></option>
</select>
<input id="file" size="1" onchange="validate();" disabled="disabled" type="file" name="Filedata" accept="image/*" />

<input type="submit" value="Send fil" id="submit" disabled="disabled" />

<progress id="progress" style="display:none;width:100%;"><?php echo _('File is being uploaded'); ?></progress>
<div id="status"><?php echo _('Select file type'); ?></div><br />

<div id="description" style="display:none;"><?php echo _('Short description'); ?><br /><input type="text" name="alt" id="alt" /></div>

<table id="videooptions" style="display:none;"><tr><td><?php echo _('Size'); ?><br />
<input type="text" name="x" id="x" value="320" onkeyup="keepAspect('x', 'y')" onblur="keepAspect('x', 'y')" size="1" />x<input type="text" name="y" id="y" value="240" onkeyup="keepAspect('y', 'x')" onblur="keepAspect('y', 'x')" size="1" />
</td><td><?php echo _('Aspect'); ?><br />
<select name="aspect" id="aspect" onchange="keepAspect('x', 'y')">
    <option value="4-3">4:3</option>
    <option value="16-9">16:9</option>
</select></td></tr></table>

</form>
</body>
</html>
