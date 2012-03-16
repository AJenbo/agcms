<?php

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
if (@$_POST['filename']) {
    include_once 'inc/file-functions.php';
    $pathinfo = pathinfo($_POST['filename']);

    if ($_POST['type'] == 'image') {
        //If it is being forced to .jpg
        if (is_file($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'].'/'.genfilename($pathinfo['filename']).'.jpg')) {
            $isfile = 'true';
        } else {
            $isfile = 'false';
        }
    } elseif ($_POST['type'] == 'lineimage') {
        //If it is being forced to .png
        if (is_file($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'].'/'.genfilename($pathinfo['filename']).'.png')) {
            $isfile = 'true';
        } else {
            $isfile = 'false';
        }
    } else {
        //Test if file exists
        if (is_file($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'].'/'.genfilename($pathinfo['filename']).'.'.$pathinfo['extension'])) {
            $isfile = 'true';
        } else {
            $isfile = 'false';
        }
    }

    die('isfile='.$isfile);
}

if (!@$_COOKIE['admin_dir'] || !is_dir($_SERVER['DOCUMENT_ROOT'].@$_COOKIE['admin_dir'])) {
    @setcookie('admin_dir', '/images');
    @$_COOKIE['admin_dir'] = '/images';
}
// pass word transmit via html rathere then http here
// else doConditionalGet(filemtime($_SERVER['PHP_SELF']));

require_once 'inc/config.php';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('File upload') ?></title>
<style type="text/css"><!--
* {
    margin:0;
}
--></style><script type="text/javascript"><!--
function refreshFolder()
{
    window.opener.showfiles('', 1);
}
--></script>
</head>
<body onload="window.focus();" bgcolor="#ffffff"><?php

function return_bytes($val)
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
    $maxbyte = min(return_bytes(ini_get('post_max_size')), return_bytes(ini_get('upload_max_filesize')));
?><object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="245" height="127" id="fileUpload" align="middle">
    <param name="allowScriptAccess" value="sameDomain" />
    <param name="movie" value="file-upload.swf?maxbyte=<?php echo $maxbyte; ?>&amp;admin_dir=<?php echo @$_COOKIE['admin_dir']; ?>&amp;text_width=<?php echo $GLOBALS['_config']['text_width']; ?>&amp;session_name=<?php echo session_name() ?>&amp;session_id=<?php echo session_id() ?>" />
    <param name="quality" value="high" />
    <param name="bgcolor" value="#ffffff" />
    <embed src="file-upload.swf?maxbyte=<?php echo $maxbyte; ?>&amp;admin_dir=<?php echo @$_COOKIE['admin_dir']; ?>&amp;text_width=<?php echo $GLOBALS['_config']['text_width']; ?>&amp;session_name=<?php echo session_name() ?>&amp;session_id=<?php echo session_id() ?>" quality="high" bgcolor="#ffffff" width="245" height="127" name="fileUpload" align="middle" allowscriptaccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object>
<!--[if lt IE 8]><![if gte IE 6]><script type="text/javascript" src="javascript/ieupdate.js"></script><![endif]><![endif]-->
</body>
</html>
