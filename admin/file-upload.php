<?php

ini_set('display_errors', 1);
error_reporting(-1);
date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/sajax.php';

function fileExists($filename, $type = '')
{
    include_once 'inc/file-functions.php';
    $pathinfo = pathinfo($filename);
    $filePath = $_SERVER['DOCUMENT_ROOT'] . @$_COOKIE['admin_dir'] . '/' . genfilename($pathinfo['filename']);

    if ($type == 'image') {
        $filePath .= '.jpg';
    } elseif ($type == 'lineimage') {
        $filePath .= '.png';
    } else {
        $filePath .= '.'.$pathinfo['extension'];
    }

    return (bool) is_file($filePath);
}

$sajax_request_type = 'GET';

//$sajax_debug_mode = 1;
sajax_export(array('name' => 'fileExists', "asynchronous" => false));
$sajax_remote_uri = '/admin/file-upload.php';
sajax_handle_client_request();

if (!@$_COOKIE['admin_dir'] || !is_dir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'])) {
    @setcookie('admin_dir', '/images');
    @$_COOKIE['admin_dir'] = '/images';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/header.php';
doConditionalGet(filemtime($_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF']));

require_once 'inc/config.php';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('File upload'); ?></title>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript"><!--
<?php sajax_show_javascript(); ?>

var maxbyte = <?php
function returnBytes($val)
{
    $last = mb_strtolower($val{mb_strlen($val, 'UTF-8')-1}, 'UTF-8');
    switch($last) {
    // The 'G' modifier is available since PHP 5.1.0
    case 'g':
        $val *= 1024;
    case 'm':
        $val *= 1024;
    case 'k':
        $val *= 1024;
    }
    return $val;
}
$maxbyte = min(
    returnBytes(ini_get('post_max_size')),
    returnBytes(ini_get('upload_max_filesize'))
);
    echo $maxbyte;
?>;

function keepAspect(changed, change) {
    var value = document.getElementById(changed).value;
    value = parseInt(value);

    if (document.getElementById('aspect').value = '4-3') {
        if (change == 'x') {
            value = value / 3 * 4;
        } else {
            value = value / 4 * 3;
        }
    } else if (document.getElementById('aspect').value = '16-9') {
        if (change == 'x') {
            value = value / 9 * 16;
        } else {
            value = value / 16 * 9;
        }
    }

    document.getElementById(change).value = value;
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
    } else if (type == 'video') {
        description.style.display = 'none';
        videooptions.style.display = '';
        status('Vælg den film du vil sende');
    } else {
        description.style.display = 'none';
        videooptions.style.display = 'none';

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

var x;
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
        x = new window.XMLHttpRequest();
    } catch(e) {}
    if(x === null || typeof x.readyState !== "number") {
        return true;
    }
    x.onload = function() {
        document.getElementById('file').value = '';
        validate();
        status('Filen er sendt');
        document.getElementById('progress').style.display = 'none';
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

<select name="type" id="type" onchange="filetypeshow();">
    <option value=""><?php echo _('File type'); ?></option>
    <option value="image"><?php echo _('Image'); ?></option>
    <option value="lineimage"><?php echo _('Illustration'); ?></option>
    <option value="video"><?php echo _('Video'); ?></option>
    <option value="other"><?php echo _('Other files'); ?></option>
</select>
<input id="file" size="1" onchange="validate();" disabled="disabled" type="file" name="Filedata" accept="image/jpeg|image/gif|image/png|image/vnd.wap.wbmp" accept="video/*" />
<input type="submit" value="Send fil" id="submit" disabled="disabled" />

<progress id="progress" style="display:none;width:100%;"><?php echo _('File is being uploaded'); ?></progress>
<div id="status"><?php echo _('Select file type'); ?></div><br />

<div id="description" style="display:none;"><?php echo _('Short description'); ?><br /><input type="text" name="alt" id="alt" /></div>

<table id="videooptions" style="display:none;"><tr><td><?php echo _('Size'); ?><br />
<input type="text" name="x" id="x" value="320" onkeyup="keepAspect('x', 'y')" size="1" />x<input type="text" name="y" id="y" value="240" size="1" /><!--180-->
</td><td><?php echo _('Aspect'); ?><br />
<select name="aspect" id="aspect">
    <option value="4-3">4:3</option>
    <option value="16-9">16:9</option>
</select></td></tr></table>

</form>
</body>
</html>
