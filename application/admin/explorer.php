<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/logon.php';


/*
$mode
0 = exploere
1 = filemove

$return
rtef = inserthtml
thb = returnid.value, returnid+'thb'.src, thb limit.
icon = returnid.value, returnid+'thb'.src, 16x16 limit.
*/

//load path from cookie, else default to /images
if (empty($_COOKIE['admin_dir']) || !is_dir(_ROOT_ . @$_COOKIE['admin_dir'])) {
    @setcookie('admin_dir', '/images');
    @$_COOKIE['admin_dir'] = '/images';
}

Sajax\Sajax::export(
    [
        'listdirs'     => ['method' => 'GET'],
        'searchfiles'  => ['method' => 'GET'],
        'showfiles'    => ['method' => 'GET'],
        'deletefile'   => ['method' => 'POST'],
        'deletefolder' => ['method' => 'POST'],
        'edit_alt'     => ['method' => 'POST'],
        'makedir'      => ['method' => 'POST'],
        'renamefile'   => ['method' => 'POST'],
    ]
);
Sajax\Sajax::handleClientRequest();

if (@$_COOKIE['qpath'] || @$_COOKIE['qalt'] || @$_COOKIE['qtype']) {
    $showfiles = searchfiles(@$_COOKIE['qpath'], @$_COOKIE['qalt'], @$_COOKIE['qtype']);
} else {
    $showfiles = showfiles($_COOKIE['admin_dir'] ?? '');
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('Explorer'); ?></title>
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript" src="javascript/lib/protomenu/proto.menu.js"></script>
<link rel="stylesheet" href="style/proto.menu.css" type="text/css" media="screen" />

<link href="style/explorer.css" rel="stylesheet" type="text/css" />
<!--[if IE]><link href="style/explorer-ie.css" rel="stylesheet" type="text/css" /><![endif]-->
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="javascript/explorer.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript"><!--
var rte = '<?php echo @$_GET['rte']; ?>';
var returnid = '<?php echo @$_GET['returnid']; ?>';
<?php Sajax\Sajax::showJavascript(); ?>

<?php echo $showfiles['javascript']; ?>

//--></script>
<style type="text/css">
#files .filetile div, #files .videotile div, #files .swftile div, #files .flvtile div, #files .imagetile div {
    background-color:#<?php echo Config::get('bgcolor'); ?>;
}
</style>
</head>
<body scroll="auto">
picture_error
<div id="menu"><img id="loading" src="images/loading.gif" width="16" height="16" alt="<?php echo _('Loading'); ?>" title="<?php echo _('Loading'); ?>" /><a id="dir_bn" class="<?php
if (empty($_COOKIE['qpath']) && empty($_COOKIE['qalt']) && empty($_COOKIE['qtype'])) {
    echo 'down';
} ?>" title="<?php echo _('Folders'); ?>" onclick="return swap_pannel('dir');"><img width="16" height="16" src="images/folder.png" alt="" /> Mapper</a> <a id="search_bn" title="Søg" class="<?php if (@$_COOKIE['qpath'] || @$_COOKIE['qalt'] || @$_COOKIE['qtype']) {
    echo 'down';
} ?>" onclick="return swap_pannel('search');"><img width="16" height="16" src="images/magnifier.png" alt="" /> <?php echo _('Search'); ?></a> <a title="<?php echo _('New folder'); ?>" onclick="makedir();return false"><img width="16" height="16" src="images/folder_add.png" alt="" /> <?php echo _('New folder'); ?></a> <a title="<?php echo _('Delete folder'); ?>" onclick="deletefolder();return false"><img width="16" height="16" src="images/folder_delete.png" alt="" /> <?php echo _('Delete folder'); ?></a> <a title="<?php echo _('Add File'); ?>" onclick="open_file_upload();return false;"><img width="16" height="16" src="images/folder_page_white.png" alt="" /> <?php echo _('Add File'); ?></a></div>
<div id="dir"<?php if (@$_COOKIE['qpath'] || @$_COOKIE['qalt'] || @$_COOKIE['qtype']) {
    echo ' style="display:none"';
} ?>>
  <div id="dir_.images"><img<?php if (@$_COOKIE['/images']) {
        echo ' style="display:none"';
} ?> src="images/+.gif" onclick="dir_expand(this, 0);" height="16" width="16" alt="+" title="" /><img<?php if (empty($_COOKIE['/images'])) {
    echo ' style="display:none"';
} ?> src="images/-.gif" onclick="dir_contract(this);" height="16" width="16" alt="-" title="" /><a<?php
if ('/images' == @$_COOKIE['admin_dir']) {
    echo ' class="active"';
}
    ?> onclick="showfiles('/images', 0);this.className='active'"><img src="images/folder.png" height="16" width="16" alt="" /> <?php echo _('Pictures'); ?> </a>
    <div><?php
    if (@$_COOKIE['/images']) {
        $listdirs = listdirs('/images', 0);
        echo $listdirs['html'];
    }
    ?></div></div>
  <div id="dir_.files"><img<?php if (@$_COOKIE['/files']) {
        echo ' style="display:none"';
} ?> src="images/+.gif" onclick="dir_expand(this, 0);" height="16" width="16" alt="+" title="" /><img<?php if (empty($_COOKIE['/files'])) {
    echo ' style="display:none"';
} ?> src="images/-.gif" onclick="dir_contract(this);" height="16" width="16" alt="-" title="" /><a<?php
if ('/files' == @$_COOKIE['admin_dir']) {
    echo ' class="active"';
}
    ?> onclick="showfiles('/files', 0);this.className='active'"><img src="images/folder.png" height="16" width="16" alt="" /> <?php echo _('Files'); ?> </a><div><?php
if (@$_COOKIE['/files']) {
    $listdirs = listdirs('/files', 0);
    echo $listdirs['html'];
} ?></div></div>
</div>
<form id="search"<?php if (empty($_COOKIE['qpath']) && empty($_COOKIE['qalt']) && empty($_COOKIE['qtype'])) {
    echo ' style="display:none"';
} ?> action="" onsubmit="searchfiles();return false;"><div>
    <?php echo _('Name:'); ?><br />
  <input name="searchpath" id="searchpath" value="<?php echo @$_COOKIE['qpath']; ?>" />
  <br />
  <br />
    <?php echo _('Description:'); ?><br />
  <input name="searchalt" id="searchalt" value="<?php echo @$_COOKIE['qalt']; ?>" />
  <br />
  <br />
    <?php echo _('Type:'); ?><br />
  <select name="searchtype" id="searchtype">
    <option value="" selected="selected">alle</option>
    <option value="image"<?php if (@$_COOKIE['qtype'] == 'image') {
        echo ' selected="selected"';
} ?>><?php echo _('Pictures'); ?></option>
    <option value="imagefile"<?php if (@$_COOKIE['qtype'] == 'imagefile') {
        echo ' selected="selected"';
} ?>><?php echo _('Image files'); ?></option>
    <option value="video"<?php if (@$_COOKIE['qtype'] == 'video') {
        echo ' selected="selected"';
} ?>><?php echo _('Videos'); ?></option>
    <option value="audio"<?php if (@$_COOKIE['qtype'] == 'audio') {
        echo ' selected="selected"';
} ?>><?php echo _('Sounds'); ?></option>
    <option value="text"<?php if (@$_COOKIE['qtype'] == 'text') {
        echo ' selected="selected"';
} ?>><?php echo _('Documents'); ?></option>
    <option value="sysfile"<?php if (@$_COOKIE['qtype'] == 'sysfile') {
        echo ' selected="selected"';
} ?>><?php echo _('System files'); ?></option>
    <option value="compressed"<?php if (@$_COOKIE['qtype'] == 'compressed') {
        echo ' selected="selected"';
} ?>><?php echo _('Compressed files'); ?></option>
    <option value="unused"<?php
    if (@$_COOKIE['qtype'] == 'unused') {
        echo ' selected="selected"';
    }
    ?>><?php echo _('Unused files'); ?></option>
  </select>
  <br />
  <br />
  <input type="submit" value="Søg nu" accesskey="f" />
</div></form>
<div id="files"><?php
echo $showfiles['html'];
//TODO mappper = reload files
?></div>
<script type="text/javascript"><!--
init();
--></script>
</body>
</html>
