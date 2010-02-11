<?php
header("Content-Type:text/xml;charset=utf-8");
echo('<?xml version="1.0" encoding="utf-8" ?>
');

require_once 'inc/config.php';
require_once 'inc/mysqli.php';
require_once 'inc/functions.php';

//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);


function ListKats($id) {
	global $mysqli;
	
	$kats = $mysqli->fetch_array('SELECT id, navn FROM kat WHERE bind = '.$id);
	
	for($ki=0;$ki<count($kats);$ki++) {
	//print xml
	?><url>
		<loc><?php echo($GLOBALS['_config']['base_url']); ?>/kat<?php echo($kats[$ki]['id']); ?>-<?php echo(clear_file_name($kats[$ki]['navn'])); ?>/</loc>
		<changefreq>weekly</changefreq>
		<priority>0.5</priority>
	</url><?php
		ListPages($kats[$ki]['id'], 'kat'.$kats[$ki]['id'].'-'.clear_file_name($kats[$ki]['navn']).'');
		ListKats($kats[$ki]['id']);
	}
}

function ListPages($id,$katname) {
	global $mysqli;

	$bind = $mysqli->fetch_array("SELECT side FROM bind WHERE kat = $id");
	if(count($bind) > 1) {
		for($si=0;$si<count($bind);$si++) {
			$sider = $mysqli->fetch_array("SELECT navn, dato FROM sider WHERE id = ".$bind[$si]['side']." LIMIT 1");
		//print xml
		?><url>
			<loc><?php echo($GLOBALS['_config']['base_url']); ?>/<?php echo($katname); ?>/side<?php echo($bind[$si]['side']); ?>-<?php echo(clear_file_name($sider[0]['navn'])); ?>.html</loc>
			<lastmod><?php echo(mb_substr($sider[0]['dato'], 0, -9, 'UTF-8')); ?></lastmod>
			<changefreq>monthly</changefreq>
			<priority>0.6</priority>
		</url><?php
		}
	}
}


$special = $mysqli->fetch_array("SELECT dato FROM special WHERE id = 1");
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
	<url>
		<loc><?php echo($GLOBALS['_config']['base_url']); ?>/</loc>
		<lastmod><?php echo(mb_substr($special[0]['dato'], 0, -9, 'UTF-8')); ?></lastmod>
		<changefreq>monthly</changefreq>
		<priority>0.7</priority>
	</url>
	<url>
		<loc><?php echo($GLOBALS['_config']['base_url']); ?>/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=</loc>
		<lastmod>2007-02-02</lastmod>
		<changefreq>monthly</changefreq>
		<priority>0.8</priority>
	</url>
<?php
	ListKats("0");
?>
</urlset>
