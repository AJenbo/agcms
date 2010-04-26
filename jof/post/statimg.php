<?php
require_once '../inc/mysqli.php';
require_once '../inc/config.php';
require_once 'config.php';


$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

//$post = $mysqli->fetch_array("SELECT count(*) FROM `post` WHERE `token` != '' AND `formSenderID` = '11856' AND `formDate` >= '".$_GET['y']."-".$_GET['m']."-01' AND `formDate` <= '".$_GET['y'].'-'.$_GET['m']."-31' AND deleted = 0 ORDER BY `formDate` DESC, `id` DESC");

function print_stat($formSenderID) {
	global $mysqli;
	$post = $mysqli->fetch_one("SELECT count(*) as count FROM `post` WHERE `token` != '' AND `formSenderID` = '".$formSenderID."' AND `formDate` >= '".($_GET['y']-1)."-12-01' AND `formDate` <= '".($_GET['y']-1)."-12-31' AND deleted = 0 ORDER BY `formDate` DESC, `id` DESC");
	$array[] = $post['count'];
	for($i=1; $i<13; $i++) {
		$post = $mysqli->fetch_array("SELECT count(*) as count FROM `post` WHERE `token` != '' AND `formSenderID` = '".$formSenderID."' AND `formDate` >= '".$_GET['y']."-".$i."-01' AND `formDate` <= '".$_GET['y']."-".$i."-31' AND deleted = 0 ORDER BY `formDate` DESC, `id` DESC");
		$array[] = $post['count'];
	}
	return $array;
}

$im = imagecreate(480, 554);
$background_color = imagecolorallocate($im, 0, 0, 0);

$text_color[] = imagecolorallocate($im, 255, 221, 136);
$text_color[] = imagecolorallocate($im, 255, 221, 136);
$text_color[] = imagecolorallocate($im, 136, 170, 255);
$text_color[] = imagecolorallocate($im, 47, 185, 0);
$text_color[] = imagecolorallocate($im, 47, 185, 0);
$text_color[] = imagecolorallocate($im, 255, 0, 51);
$total[0] = 0;
foreach($brugere as $id => $navn) {
	$grafs[$navn] = print_stat($id);
	$color = current($text_color);
	$total[0] += $grafs[$navn][0];
	for($x=1; $x<13; $x++) {
		if($grafs[$navn][$x-1] && $grafs[$navn][$x])
			imageline($im, ($x-1)*40, 554-$grafs[$navn][$x-1], $x*40, 554-$grafs[$navn][$x], $color);
		if(empty($total[$x]))
			$total[$x] = $grafs[$navn][$x];
		else
			$total[$x] += $grafs[$navn][$x];
	}
	next($text_color);
}

$text_color = imagecolorallocate($im, 255, 255, 255);
for($x=1; $x<13; $x++) {
	if($total[$x-1] && $total[$x])
		imageline($im, ($x-1)*40, 554-$total[$x-1], $x*40, 554-$total[$x], $text_color);
}
header ("Content-type: image/png");
imagepng($im);
?>