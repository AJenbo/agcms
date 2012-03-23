<?php
/*
ini_set('display_errors', 1);
error_reporting(-1);
/**/
date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

ini_set('zlib.output_compression', 1);

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
date_default_timezone_set('Europe/Copenhagen');
require_once 'inc/config.php';
require_once '../inc/sajax.php';
require_once '../inc/config.php';
require_once '../inc/mysqli.php';
require_once '../inc/functions.php';
require_once 'inc/emails.php';
$mysqli = new simple_mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);
$sajax_request_type = "POST";

function kattree($id)
{
    global $mysqli;

    $kat = $mysqli->fetch_array('SELECT id, navn, bind FROM `kat` WHERE id = '.$id.' LIMIT 1');

    if ($kat) {
        $id = $kat[0]['bind'];
        $kattree[0]['id'] = $kat[0]['id'];
        $kattree[0]['navn'] = $kat[0]['navn'];
    }

    while (@$kat[0]['bind'] > 0) {
        $kat = $mysqli->fetch_array('SELECT id, navn, bind FROM `kat` WHERE id = \''.$kat[0]['bind'].'\' LIMIT 1');
        $id = $kat[0]['bind'];
        $kattree[]['id'] = $kat[0]['id'];
        $kattree[count($kattree)-1]['navn'] = $kat[0]['navn'];
    }

    if (!$id) {
        $kattree[]['id'] = 0;
        $kattree[count($kattree)-1]['navn'] = _('Frontpage');
    } else {
        $kattree[]['id'] = -1;
        $kattree[count($kattree)-1]['navn'] = _('Inactive');
    }
    return array_reverse($kattree);
}

function katspath($id)
{
    $kattree = kattree($id);
    $nr = count($kattree);
    $html = _('Select location:').' ';
    for ($i=0;$i<$nr;$i++) {
        $html .= '/'.trim($kattree[$i]['navn']);
    }
    $html .= '/';
    return array('id' => 'katsheader', 'html' => $html);
}

function katlist($id)
{
    global $mysqli;
    global $kattree;

    $html = '<a class="menuboxheader" id="katsheader" style="width:'.$GLOBALS['_config']['text_width'].'px;clear:both" onclick="showhidekats(\'kats\',this);">';
    if (@$_COOKIE['hidekats']) {
        $temp = katspath($id);
        $html .= $temp['html'];
    } else {
        $html .= _('Select location:').' ';
    }
    $html .= '</a><div style="width:'.($GLOBALS['_config']['text_width']+24).'px;';
    if (@$_COOKIE['hidekats']) {
        $html .= 'display:none;';
    }
    $html .= '" id="kats"><div>';
    $kattree = kattree($id);
    foreach ($kattree as $i => $value) {
        $kattree[$i] = $value['id'];
    }

    $openkat = explode('<', @$_COOKIE['openkat']);
    if ($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = -1 LIMIT 1')) {
        $html .= '<img';
        if (array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
            $html .= ' style="display:none"';
        }
        $html .= ' src="images/+.gif" id="kat-1expand" onclick="kat_expand(-1, true, kat_expand_r);" height="16" width="16" alt="+" title="" /><img';
        if (!array_search(-1, $openkat) && false === array_search('-1', $kattree)) {
            $html .= ' style="display:none"';
        }
        $html .= ' src="images/-.gif" id="kat-1contract" onclick="kat_contract(-1);" height="16" width="16" alt="-" title="" /><a';
    } else {
        $html .= '<a style="margin-left:16px"';
    }
    $html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', -1, 360);"><input name="kat" type="radio" value="-1"';
    if ($kattree[count($kattree)-1] == -1) {
        $html .= ' checked="checked"';
    }
    $html .= ' /><img src="images/folder.png" width="16" height="16" alt="" /> '._('Inactive').'</a><div id="kat-1content" style="margin-left:16px">';
    if (array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
        $temp = kat_expand(-1, true);
        $html .= $temp['html'];
    }
    $html .= '</div></div><div>';
    if ($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = 0 LIMIT 1')) {
        $html .= '<img style="';
        if (array_search(0, $openkat) || false !== array_search('0', $kattree)) {
            $html .= 'display:none;';
        }
        $html .= '" src="images/+.gif" id="kat0expand" onclick="kat_expand(0, true, kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="';
        if (!array_search(0, $openkat) && false === array_search('0', $kattree)) {
            $html .= 'display:none;';
        }
        $html .= '" src="images/-.gif" id="kat0contract" onclick="kat_contract(\'0\');" height="16" width="16" alt="-" title="" /><a';
    } else {
        $html .= '<a style="margin-left:16px"';
    }
    $html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', 0, 360);"><input type="radio" name="kat" value="0"';
    if (!$kattree[count($kattree)-1]) {
        $html .= ' checked="checked"';
    }
    $html .= ' /><img src="images/folder.png" width="16" height="16" alt="" /> '._('Frontpage').'</a><div id="kat0content" style="margin-left:16px">';
    if (array_search(0, $openkat) || false !== array_search('0', $kattree)) {
        $temp = kat_expand(0, true);
        $html .= $temp['html'];
    }
    $html .= '</div></div></div>';
    return $html;
}

function siteList($id)
{
    global $mysqli;
    global $kattree;

    $html = '<div>';

    $kattree = array();
    if ($id !== null) {
        $kattree = kattree($id);
        foreach ($kattree as $i => $value) {
            $kattree[$i] = $value['id'];
        }
    }

    $openkat = explode('<', @$_COOKIE['openkat']);
    if ($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = -1 LIMIT 1') || $mysqli->fetch_array('SELECT id FROM `bind` WHERE kat = -1 LIMIT 1')) {
        $html .= '<img';
        if (array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
            $html .= ' style="display:none"';
        }
         $html .= ' src="images/+.gif" id="kat-1expand" onclick="siteList_expand(-1, kat_expand_r);" height="16" width="16" alt="+" title="" /><img';
        if (!array_search(-1, $openkat) && false === array_search('-1', $kattree)) {
            $html .= ' style="display:none"';
        }
        $html .= ' src="images/-.gif" id="kat-1contract" onclick="kat_contract(-1);" height="16" width="16" alt="-" title="" /><a';
    } else {
        $html .= '<a style="margin-left:16px"';
    }
    $html .= '><img src="images/folder.png" width="16" height="16" alt="" /> '._('Inactive').'</a><div id="kat-1content" style="margin-left:16px">';
    if (array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
        $temp = siteList_expand(-1);
        $html .= $temp['html'];
    }
    $html .= '</div></div><div>';
    if ($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = 0 LIMIT 1') || $mysqli->fetch_array('SELECT id FROM `bind` WHERE kat = 0 LIMIT 1')) {
        $html .= '<img style="';
        if (array_search(0, $openkat) || false !== array_search('0', $kattree)) {
            $html .= 'display:none;';
        }
        $html .= '" src="images/+.gif" id="kat0expand" onclick="siteList_expand(0, kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="';
        if (!array_search(0, $openkat) && false === array_search('0', $kattree)) {
            $html .= 'display:none;';
        }
        $html .= '" src="images/-.gif" id="kat0contract" onclick="kat_contract(\'0\');" height="16" width="16" alt="-" title="" /><a';
    } else {
        $html .= '<a style="margin-left:16px"';
    }
    $html .= ' href="?side=redigerFrontpage"><img src="images/page.png" width="16" height="16" alt="" /> '._('Frontpage').'</a><div id="kat0content" style="margin-left:16px">';
    if (array_search(0, $openkat) || false !== array_search('0', $kattree)) {
        $temp = siteList_expand(0);
        $html .= $temp['html'];
    }
    $html .= '</div></div>';
    return $html;
}

function pages_expand($id)
{
    global $mysqli;
    $html = '';

    $temp = kat_expand($id, false);
    $html .= $temp['html'];
    $sider = $mysqli->fetch_array('SELECT sider.id, sider.varenr, bind.id as bind, navn FROM `bind` LEFT JOIN sider on bind.side = sider.id WHERE `kat` = '.$id.' ORDER BY sider.navn');
    $nr = count($sider);
    foreach ($sider as $side) {
        $html .= '<div id="bind'.$side['bind'].'" class="side'.$side['id'].'"><a style="margin-left:16px" class="side">
        <a class="kat" onclick="this.firstChild.checked=true;"><input name="side" type="radio" value="'.$side['id'].'" />
        <img src="images/page.png" width="16" height="16" alt="" /> '.strip_tags($side['navn'], '<img>');
        if ($side['varenr']) {
            $html .= ' <em>#:'.$side['varenr'].'</em>';
        }
        $html .= '</a></div>';
    }
    return array('id' => $id, 'html' => $html);
}

function siteList_expand($id)
{
    global $mysqli;
    $html = '';

    $temp = kat_expand($id, false);
    $html .= $temp['html'];
    $sider = $mysqli->fetch_array('SELECT sider.id, sider.varenr, bind.id as bind, navn FROM `bind` LEFT JOIN sider on bind.side = sider.id WHERE `kat` = '.$id.' ORDER BY sider.navn');
    $nr = count($sider);
    for ($i=0; $i<$nr; $i++) {
        $html .= '<div id="bind'.$sider[$i]['bind'].'" class="side'.$sider[$i]['id'].'"><a style="margin-left:16px" class="side" href="?side=redigerside&amp;id='.$sider[$i]['id'].'"><img src="images/page.png" width="16" height="16" alt="" /> '.strip_tags($sider[$i]['navn'], '<img>');
        if ($sider[$i]['varenr']) {
            $html .= ' <em>#:'.$sider[$i]['varenr'].'</em>';
        }
        $html .= '</a></div>';
    }
    return array('id' => $id, 'html' => $html);
}

function getSiteTree()
{
    $html = '<div id="headline">'._('Overview').'</div><div>';
    $html .= siteList(@$_COOKIE['activekat']);

    global $mysqli;
    $specials = $mysqli->fetch_array('SELECT `id`, `navn` FROM `special` WHERE `id` > 1 ORDER BY `navn`');
    foreach ($specials as $special) {
        $html .= '<div style="margin-left: 16px;"><a href="?side=redigerSpecial&id='.$special['id'].'"><img height="16" width="16" alt="" src="images/page.png"/> '.$special['navn'].'</a></div>';
    }

    return $html.'</div>';
}

function kat_expand($id, $input=true)
{
    global $mysqli;
    global $kattree;
    $html = '';

    $kat = $mysqli->fetch_array('SELECT * FROM `kat` WHERE bind = '.$id.' ORDER BY `order`, `navn`');
    $nr = count($kat);
    for ($i=0;$i<$nr;$i++) {
        if ($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = '.$kat[$i]['id'].' LIMIT 1') || (!$input && $mysqli->fetch_array('SELECT id FROM `bind` WHERE kat = '.$kat[$i]['id'].' LIMIT 1'))) {
            $openkat = explode('<', @$_COOKIE['openkat']);
            $html .= '<div id="kat'.$kat[$i]['id'].'"><img style="display:';
            if (array_search($kat[$i]['id'], $openkat) || false !== array_search($kat[$i]['id'], $kattree)) {
                $html .= 'none';
            }
            $html .= '" src="images/+.gif" id="kat'.$kat[$i]['id'].'expand" onclick="';
            if ($input) {
                $html .= 'kat_expand('.$kat[$i]['id'].', \'true\'';
            } else {
                $html .= 'siteList_expand('.$kat[$i]['id'];
            }
            $html .= ', kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="display:';

            if (!array_search($kat[$i]['id'], $openkat) && false === array_search($kat[$i]['id'], $kattree)) {
                $html .= 'none';
            }
            $html .= '" src="images/-.gif" id="kat'.$kat[$i]['id'].'contract" onclick="kat_contract('.$kat[$i]['id'].');" height="16" width="16" alt="-" title="" /><a class="kat"';

            if ($input) {
                $html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', '.$kat[$i]['id'].', 360);"><input name="kat" type="radio" value="'.$kat[$i]['id'].'"';
                if (@$kattree[count($kattree)-1] == $kat[$i]['id']) {
                    $html .= ' checked="checked"';
                }
                $html .= ' />';
            } else {
                $html .= ' href="?side=redigerkat&id='.$kat[$i]['id'].'">';
            }

            $html .= '<img src="';
            if ($kat[$i]['icon']) {
                $html .= $kat[$i]['icon'];
            } else {
                $html .= 'images/folder.png';
            }
            $html .= '" alt="" /> '.strip_tags($kat[$i]['navn'], '<img>').'</a><div id="kat'.$kat[$i]['id'].'content" style="margin-left:16px">';
            if (array_search($kat[$i]['id'], $openkat) || false !== array_search($kat[$i]['id'], $kattree)) {
                if ($input) {
                    $temp = kat_expand($kat[$i]['id'], true);
                } else {
                    $temp = siteList_expand($kat[$i]['id']);
                }
                $html .= $temp['html'];
            }
            $html .= '</div></div>';
        } else {
            $html .= '<div id="kat'.$kat[$i]['id'].'"><a class="kat" style="margin-left:16px"';
            if ($input) {
                $html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', '.$kat[$i]['id'].', 360);"><input type="radio" name="kat" value="'.$kat[$i]['id'].'"';
                if (@$kattree[count($kattree)-1] == $kat[$i]['id']) {
                    $html .= ' checked="checked"';
                }
                $html .= ' />';
            } else {
                $html .= ' href="?side=redigerkat&id='.$kat[$i]['id'].'">';
            }
            $html .= '<img src="';
            if ($kat[$i]['icon']) {
                $html .= $kat[$i]['icon'];
            } else {
                $html .= 'images/folder.png';
            }
            $html .= '" alt="" /> '.strip_tags($kat[$i]['navn'], '<img>').'</a></div>';
        }
    }
    return array('id' => $id, 'html' => $html);
}

$kattree = array();

$sajax_debug_mode = 0;
sajax_export(
    array('name' => 'katspath', 'method' => 'GET'),
    array('name' => 'siteList_expand', 'method' => 'GET'),
    array('name' => 'kat_expand', 'method' => 'GET'),
    array('name' => 'getSiteTree', 'method' => 'GET')
);
//	$sajax_remote_uri = '/ajax.php';
sajax_handle_client_request();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style/style.css" rel="stylesheet" type="text/css" />
<link href="/theme/admin.css" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Liste af sider</title>
<script type="text/javascript"><!--
<?php sajax_show_javascript(); ?>
--></script>
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
var JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript" src="javascript/lib/scriptaculous.js"></script>
<script type="text/javascript" src="javascript/lib/protomenu/proto.menu.js"></script>
<link rel="stylesheet" href="style/proto.menu.css" type="text/css" media="screen" />
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="javascript/index.js"></script>
</head>
<body onload="init()">
<img id="loading" src="images/loading.gif" width="16" height="16" alt="Processing" />
<?php echo getSiteTree(); ?>
</body>
</html>
