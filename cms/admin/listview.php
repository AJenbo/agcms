<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Fra priser uden lister</title>
<style type="text/css"><!--
* {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
	line-height: 16px;
}
.tal {
	text-align:right;
	width:1px;
}
table {
	width: 100%;
}
thead {
	font-weight:bold;
	white-space:nowrap;
	background-color:#999999;
}
tr:hover {
	background-color:#67A3C1;
}
table, table * {
	border-collapse: collapse;
	color: black;
}
td {
	border: 1px solid white;
}
a {
	color: black;
}
img {
	border: 0;
}
.altrow {
	background-color:#CCCCCC;
}
.path {
	background-color:#999999;
}
--></style>
</head><?php

if ($_GET['sort'] == 'id') {
	$sort = 'id';
} elseif ($_GET['sort'] == 'navn') {
	$sort = 'navn';
} elseif ($_GET['sort'] == 'varenr') {
	$sort = 'varenr';
} elseif ($_GET['sort'] == 'for') {
	$sort = '`for`';
} elseif ($_GET['sort'] == 'pris') {
	$sort = 'pris';
} elseif ($_GET['sort'] == 'dato') {
	$sort = 'dato';
} elseif ($_GET['sort'] == 'maerke') {
	$sort = 'maerke';
} elseif ($_GET['sort'] == 'krav') {
	$sort = 'krav';
} elseif ($_GET['sort'] == '-id') {
	$sort = '-sider.`id`';
} elseif ($_GET['sort'] == '-navn') {
	$sort = '-navn';
} elseif ($_GET['sort'] == '-varenr') {
	$sort = '-varenr';
} elseif ($_GET['sort'] == '-for') {
	$sort = '-`for`';
} elseif ($_GET['sort'] == '-pris') {
	$sort = '-pris';
} elseif ($_GET['sort'] == '-dato') {
	$sort = '-dato';
} elseif ($_GET['sort'] == '-maerke') {
	$sort = '-maerke';
} elseif ($_GET['sort'] == '-krav') {
	$sort = '-krav';
} else {
	$sort = 'navn';
}

?>
<body><table><thead><tr>
      <td><a href="?sort=<?php if ($sort == 'id') echo('-'); ?>id<?php if (is_numeric($_GET['kat'])) echo('&amp;kat='.$_GET['kat']); ?>">ID</a></td>
      <td><a href="?sort=<?php if ($sort == 'navn') echo('-'); ?>navn<?php if (is_numeric($_GET['kat'])) echo('&amp;kat='.$_GET['kat']); ?>">Navn</a></td>
      <td><a href="?sort=<?php if ($sort == 'varenr') echo('-'); ?>varenr<?php if (is_numeric($_GET['kat'])) echo('&amp;kat='.$_GET['kat']); ?>">Varenummer</a></td>
      <td><a href="?sort=<?php if ($sort == '`for`') echo('-'); ?>for<?php if (is_numeric($_GET['kat'])) echo('&amp;kat='.$_GET['kat']); ?>">Før pris</a></td>
      <td><a href="?sort=<?php if ($sort == 'pris') echo('-'); ?>pris<?php if (is_numeric($_GET['kat'])) echo('&amp;kat='.$_GET['kat']); ?>">Nu Pris</a></td>
      <td><a href="?sort=<?php if ($sort == 'dato') echo('-'); ?>dato<?php if (is_numeric($_GET['kat'])) echo('&amp;kat='.$_GET['kat']); ?>">Sidst ændret</a></td>
      <td><a href="?sort=<?php if ($sort == 'maerke') echo('-'); ?>maerke<?php if (is_numeric($_GET['kat'])) echo('&amp;kat='.$_GET['kat']); ?>">Mærke</a></td>
      <td><a href="?sort=<?php if ($sort == 'krav') echo('-'); ?>krav<?php if (is_numeric($_GET['kat'])) echo('&amp;kat='.$_GET['kat']); ?>">Krav</a></td>
</tr></thead><tbody><?php
require_once '../inc/config.php';
require_once '../inc/mysqli.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

$maerker = $mysqli->fetch_array("SELECT id, navn FROM `maerke`");
foreach($maerker as $maerke) {
	$temp[$maerke['id']] = htmlspecialchars($maerke['navn']);
}
$maerker = $temp;
unset($temp);

$krav = $mysqli->fetch_array("SELECT id, navn FROM `krav`");
foreach($krav as $element) {
	$temp[$element['id']] = htmlspecialchars($element['navn']);
}
$krav = $temp;
unset($temp);

function print_kat($bind, $path_name) {
	global $mysqli;
	$kats = $mysqli->fetch_array("SELECT id, bind, navn FROM `kat` WHERE bind = ".$bind." ORDER BY navn");
	foreach($kats as $kat) {
		echo("\n".'  <tr class="path"><td colspan="8"><a href="?sort='.$_GET['sort'].'&amp;kat='.$kat['id'].'"><img src="images/find.png" alt="Vis" title="Vis kun denne kategori" /></a> '.$path_name.' &gt; <a href="/kat'.$kat['id'].'-">'.htmlspecialchars($kat['navn']).'</a></td></tr>');
		print_pages($kat['id']);
		print_kat($kat['id'], $path_name.' &gt; '.htmlspecialchars($kat['navn']));
	}
}

function print_pages($kat) {
	global $mysqli;
	global $maerker;
	global $krav;
	global $sort;
	$sider = $mysqli->fetch_array("SELECT sider.id, sider.navn, sider.varenr, sider.`for`, sider.pris, sider.dato, sider.maerke, sider.krav FROM `bind` JOIN sider ON bind.side = sider.id WHERE bind.kat = ".$kat." ORDER BY ".$sort);
	$altrow = 0;
	foreach($sider as $side) {
		echo("\n".'
    <tr');
	if ($altrow) {
		echo(' class="altrow"');
		$altrow = 0;
	} else {
		$altrow = 1;
	}

	echo('>
      <td class="tal"><a href="/admin/?side=redigerside&amp;id='.$side['id'].'">'.$side['id'].'</a></td>
      <td><a href="/side'.$side['id'].'-">'.htmlspecialchars($side['navn']).'</a></td>
      <td>'.htmlspecialchars($side['varenr']).'</td>
      <td class="tal">'.number_format($side['for'], 2, ',', '.').'</td>
      <td class="tal">'.number_format($side['pris'], 2, ',', '.').'</td>
      <td class="tal">'.$side['dato'].'</td>
      <td>');
	$side['maerke'] = explode(',', $side['maerke']);
	foreach($side['maerke'] as $maerke)
		echo($maerker[$maerke].' ');
	echo('</td>
      <td>'.$krav[$side['krav']].'</td>
</tr>');
	}
}

if (is_numeric($_GET['kat'])) {
	if ($_GET['kat'] > 0) {
		$kat = $mysqli->fetch_one("SELECT id, navn FROM `kat` WHERE id = ".$_GET['kat']);
	} elseif ($_GET['kat'] == 0) {
		$kat = array('id' => 0, 'navn' => 'Forside');
	} else {
		$kat = array('id' => -1, 'navn' => 'Indaktiv');
	}
	echo("\n".'  <tr class="path"><td colspan="8"><a href="?sort='.$_GET['sort'].'"><img src="images/find.png" alt="Vis" title="Vis alle kategorier" /></a> <a href="/kat'.$kat['id'].'-">'.htmlspecialchars($kat['navn']).'</a></td></tr>');
	print_pages($_GET['kat']);
} else {
	echo('<tr><td colspan="8" class="path"><a href="?sort='.$_GET['sort'].'&amp;kat=0"><img src="images/find.png" alt="Vis" title="Vis kun denne kategori" /></a> <a href="/">Forside</a></td></tr>');
	print_pages(0);
	print_kat(0, 'Forside');
	echo('<tr><td colspan="8" class="path"><a href="?sort='.$_GET['sort'].'&amp;kat=-1"><img src="images/find.png" alt="Vis" title="Vis kun denne kategori" /></a> Indaktiv</td></tr>');
	print_pages(-1);
	print_kat(-1, 'Indaktiv');
}
?></tbody></table></body></html>
