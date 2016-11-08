<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/inc/logon.php';

if (!empty($_POST)) {
    db()->query(
        "
        INSERT INTO `lists` (
            `page_id`,
            `title`,
            `cells`,
            `cell_names`,
            `sort`,
            `sorts`,
            `link`
        )
        VALUES (
            '".$_POST['id']."',
            '".$_POST['title']."',
            '".$_POST['cells']."',
            '".$_POST['cell_names']."',
            '".$_POST['dsort']."',
            '".$_POST['sorts']."',
            ".($_POST['link'] ? 1 : 0)."
        );
        "
    );

    ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title><?php
    echo _('Add list');
    ?></title><script type="text/javascript"><!--
    window.opener.location.reload();
    window.close();
    --></script></head><body></body></html><?php
    die();
}

$tablesorts = db()->fetchArray("SELECT id, navn FROM `tablesort`");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('Add list'); ?></title>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript"><!--
function asdsasgwtgernytymifdsbs()
{
    var cells = '';
    var cell_names = '';
    var sorts = '';
    var cellsObjs = document.getElementsByName('cell');
    var cell_namesObjs = document.getElementsByName('cell_name');
    var sortsObjs = document.getElementsByName('sort');
    $('dsort').value = parseInt($('dsort').value).toString();

    for(var i=0;i<cellsObjs.length;i++) {
        if (cellsObjs[i].value != '' || cell_namesObjs[i].value != '' || sortsObjs[i].value != '') {
            if (cells != '') {
                cells += '<';
                cell_names += '<';
                sorts += '<';
            }
            cells +=  cellsObjs[i].value.toString();
            cell_names +=  htmlspecialchars(cell_namesObjs[i].value.toString());
            sorts +=  sortsObjs[i].value.toString();
        }
    }

    $('cells').value = cells;
    $('cell_names').value = cell_names;
    $('sorts').value = sorts;

    return true;
}

function addcolumn()
{
    var td = document.createElement('td');
    td.innerHTML = '<td><select name="cell"><option value="0">Tekst</option><option value="1">Tal</option><option value="2">Pris</option><option value="4">Før pris</option><option value="3">Tilbud</option></select><br /><select name="sort"><option value="0">Alfanumerisk</option><?php
    foreach ($tablesorts as $tablesort) {
        ?><option value="<?php echo $tablesort['id'];
    ?>"><?php echo $tablesort['navn'];
    ?></option><?php
    } ?></select><br /><input name="cell_name" style="width:102px;" /></td>';
    var addbuttonrow = $('addbuttonrow');
    addbuttonrow.parentNode.insertBefore(td, addbuttonrow);

}
--></script>
<link href="style/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
<form method="post" action="" onsubmit="return asdsasgwtgernytymifdsbs();"><input id="id" name="id" type="hidden" value="<?php echo $_GET['id']; ?>" />
Title: <input id="title" name="title" /><br />
Links: <input type="checkbox" name="link" id="link" value="1" /><br />
Sorter efter colonne: <input size="1" name="dsort" id="dsort" value="0" />

<table><tbody>
    <tr id="therow">
        <td>
            Type:<br />
            Sortering:<br />
            Navn:
        </td>
        <td>
            <select name="cell">
                <option value="0">Tekst</option>
                <option value="1">Tal</option>
                <option value="2">Pris</option>
                <option value="4">Før pris</option>
                <option value="3">Tilbud</option>
            </select><br />
            <select name="sort">
                <option value="0">Alfanumerisk</option><?php
                foreach ($tablesorts as $tablesort) {
                    ?><option value="<?php echo $tablesort['id']; ?>"><?php echo $tablesort['navn']; ?></option><?php
                }
            ?></select><br />
            <input name="cell_name" style="width:102px;" />
        </td>
        <td id="addbuttonrow">
            <input type="button" value="Tilføj" style="height: 48px;" onclick="addcolumn();" />
        </td>
    </tr>
</tbody></table>
<br />
<input name="cells" id="cells" type="hidden" />
<input name="cell_names" id="cell_names" type="hidden" />
<input name="sorts" id="sorts" type="hidden" />
<input type="submit" value="Gem" />
</form><?php
?>
</body>
</html>
