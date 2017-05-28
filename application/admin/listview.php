<?php

require_once __DIR__ . '/logon.php';

Render::addLoadedTable('bind');
Render::addLoadedTable('kat');
Render::addLoadedTable('krav');
Render::addLoadedTable('maerke');
Render::addLoadedTable('sider');
Render::sendCacheHeader();

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

if (@$_GET['sort'] == 'id') {
    $sort = 'id';
} elseif (@$_GET['sort'] == 'navn') {
    $sort = 'navn';
} elseif (@$_GET['sort'] == 'varenr') {
    $sort = 'varenr';
} elseif (@$_GET['sort'] == 'for') {
    $sort = '`for`';
} elseif (@$_GET['sort'] == 'pris') {
    $sort = 'pris';
} elseif (@$_GET['sort'] == 'dato') {
    $sort = 'dato';
} elseif (@$_GET['sort'] == 'maerke') {
    $sort = 'maerke';
} elseif (@$_GET['sort'] == 'krav') {
    $sort = 'krav';
} elseif (@$_GET['sort'] == '-id') {
    $sort = '-sider.`id`';
} elseif (@$_GET['sort'] == '-navn') {
    $sort = '-navn';
} elseif (@$_GET['sort'] == '-varenr') {
    $sort = '-varenr';
} elseif (@$_GET['sort'] == '-for') {
    $sort = '-`for`';
} elseif (@$_GET['sort'] == '-pris') {
    $sort = '-pris';
} elseif (@$_GET['sort'] == '-dato') {
    $sort = '-dato';
} elseif (@$_GET['sort'] == '-maerke') {
    $sort = '-maerke';
} elseif (@$_GET['sort'] == '-krav') {
    $sort = '-krav';
} else {
    $sort = 'navn';
}

?>
<body><table><thead><tr>
      <td><a href="?sort=<?php if ($sort == 'id') {
            echo '-';
} ?>id<?php if (is_numeric($_GET['kat'])) {
    echo '&amp;kat='.$_GET['kat'];
} ?>">ID</a></td>
      <td><a href="?sort=<?php if ($sort == 'navn') {
            echo '-';
} ?>navn<?php if (is_numeric($_GET['kat'])) {
    echo '&amp;kat='.$_GET['kat'];
} ?>">Navn</a></td>
      <td><a href="?sort=<?php if ($sort == 'varenr') {
            echo '-';
} ?>varenr<?php if (is_numeric($_GET['kat'])) {
    echo '&amp;kat='.$_GET['kat'];
} ?>">Varenummer</a></td>
      <td><a href="?sort=<?php if ($sort == '`for`') {
            echo '-';
} ?>for<?php if (is_numeric($_GET['kat'])) {
    echo '&amp;kat='.$_GET['kat'];
} ?>">Før pris</a></td>
      <td><a href="?sort=<?php if ($sort == 'pris') {
            echo '-';
} ?>pris<?php if (is_numeric($_GET['kat'])) {
    echo '&amp;kat='.$_GET['kat'];
} ?>">Nu Pris</a></td>
      <td><a href="?sort=<?php if ($sort == 'dato') {
            echo '-';
} ?>dato<?php if (is_numeric($_GET['kat'])) {
    echo '&amp;kat='.$_GET['kat'];
} ?>">Sidst ændret</a></td>
      <td><a href="?sort=<?php if ($sort == 'maerke') {
            echo '-';
} ?>maerke<?php if (is_numeric($_GET['kat'])) {
    echo '&amp;kat='.$_GET['kat'];
} ?>">Mærke</a></td>
      <td><a href="?sort=<?php if ($sort == 'krav') {
            echo '-';
} ?>krav<?php if (is_numeric($_GET['kat'])) {
    echo '&amp;kat='.$_GET['kat'];
} ?>">Krav</a></td>
</tr></thead><tbody><?php

$maerker = [];
foreach (db()->fetchArray("SELECT id, navn FROM `maerke`") as $maerke) {
    $maerker[$maerke['id']] = $maerke['navn'];
}

$krav = [];
foreach (db()->fetchArray("SELECT id, navn FROM `krav`") as $element) {
    $krav[$element['id']] = $element['navn'];
}

if (is_numeric(@$_GET['kat'])) {
    if (@$_GET['kat'] > 0) {
        $kat = db()->fetchOne("SELECT id, navn FROM `kat` WHERE id = ".$_GET['kat']);
    } elseif (@$_GET['kat'] == 0) {
        $kat = ['id' => 0, 'navn' => 'Forside'];
    } else {
        $kat = ['id' => -1, 'navn' => 'Indaktiv'];
    }
    echo "\n".'  <tr class="path"><td colspan="8"><a href="?sort='
        . @$_GET['sort'] . '"><img src="images/find.png" alt="Vis" title="Vis alle kategorier" /></a> <a href="/kat'
        . $kat['id'] . '-">' . xhtmlEsc($kat['navn']) . '</a></td></tr>';
    print_pages($_GET['kat']);
} else {
    echo '<tr><td colspan="8" class="path"><a href="?sort='.@$_GET['sort'].'&amp;kat=0"><img src="images/find.png" alt="Vis" title="Vis kun denne kategori" /></a> <a href="/">Forside</a></td></tr>';
    print_pages(0);
    print_kat(0, 'Forside');
    echo '<tr><td colspan="8" class="path"><a href="?sort='.@$_GET['sort'].'&amp;kat=-1"><img src="images/find.png" alt="Vis" title="Vis kun denne kategori" /></a> Indaktiv</td></tr>';
    print_pages(-1);
    print_kat(-1, 'Indaktiv');
}
?></tbody></table></body></html>
