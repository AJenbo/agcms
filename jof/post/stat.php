<?php
if(!isset($_GET['y'])) {
	$_GET['y'] = date('Y');
}

require_once '../inc/mysqli.php';
require_once '../inc/config.php';
require_once 'config.php';
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

//$post = $mysqli->fetch_array("SELECT count(*) FROM `post` WHERE `token` != '' AND `formSenderID` = '11856' AND `formDate` >= '".$_GET['y']."-".$_GET['m']."-01' AND `formDate` <= '".$_GET['y'].'-'.$_GET['m']."-31' AND deleted = 0 ORDER BY `formDate` DESC, `id` DESC");

$month_total = array();
function print_stat($formSenderID) {
	global $mysqli;
	global $month_total;
	$total = 0;
	for($i=1; $i<13; $i++) {
		$post = $mysqli->fetch_array("SELECT count(*) as count FROM `post` WHERE `token` != '' AND `formSenderID` = '".$formSenderID."' AND `formDate` >= '".$_GET['y']."-".$i."-01' AND `formDate` <= '".$_GET['y']."-".$i."-31' AND deleted = 0 ORDER BY `formDate` DESC, `id` DESC");
		$total += $post[0]['count'];
		echo('<td>'.$post[0]['count'].'</td>');
		$month_total[$i] += $post[0]['count'];
	}
	echo('<td>'.$total.'</td>');
	
}

?><style type="text/css">
table, table * {
	border:1px #FFF solid;
	border-collapse:collapse;
}
table td {
	text-align:right;
	width:37px;
}
a {
	color:#000;
	text-decoration:none;
}
</style>

<table border="1" bordercolor="#000000" cellspacing="0">
    <caption style="font-weight:bold; font-size:20px;">
    <a href="?y=<?php echo($_GET['y']-1); ?>">&lt;</a> <?php echo($_GET['y']); ?> <a href="?y=<?php echo($_GET['y']+1); ?>">&gt;</a>
    </caption>
    <tr style="font-weight:bold">
        <td style="width:auto"></td>
        <td>Jan</td>
        <td>Feb</td>
        <td>Mar</td>
        <td>Apr</td>
        <td>Maj</td>
        <td>Jun</td>
        <td>Jul</td>
        <td>Aug</td>
        <td>Sep</td>
        <td>Okt</td>
        <td>Nov</td>
        <td>Dec</td>
        <td>2008</td>
    </tr><?php
	
	$text_color[] = 'fd8';
	$text_color[] = 'fd8';
	$text_color[] = '8af';
	$text_color[] = '2b0';
	$text_color[] = '2b0';
	$text_color[] = 'f03';
	
    foreach($brugere as $id => $navn) {
		$color = current($text_color);
		?><tr style="background-color:#<?php echo($color); ?>">
			<td style="width:auto;font-weight:bold"><?php echo($navn); ?></td><?php
			print_stat($id);
		?></tr><?php
		next($text_color);
	}
    ?><tr style="background-color:#FFF">
        <td style="font-weight:bold;width:auto;">Totla</td>
        <?php
		foreach($month_total as $value) {
			echo('<td>'.$value.'</td>');
			$total += $value;
		}
		echo('<td>'.$total.'</td>');
		?>
    </tr>
</table>
<img style="margin:0 124px;" src="statimg.php?y=<?php echo($_GET['y']); ?>" alt="Statestik" title="Graf for <?php echo($_GET['y']); ?>" />
