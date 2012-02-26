<?php

require_once 'inc/config.php';
require_once 'inc/mysqli.php';
require_once 'inc/functions.php';
require_once 'inc/header.php';

$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

$tabels = $mysqli->fetch_array("SHOW TABLE STATUS");
$updatetime = 0;
foreach ($tabels as $tabel) {
	$updatetime = max($updatetime, strtotime($tabel['Update_time']));
}

if ($updatetime < 1) {
	$updatetime = time();
}

doConditionalGet($updatetime);

$sider = $mysqli->fetch_array(
	"
	SELECT sider.id,
		`pris`,
		varenr,
		sider.maerke,
		sider.navn,
		billed,
		kat.id AS kat_id,
		kat.navn AS kat_navn
	FROM sider
	JOIN bind ON (side = sider.id)
	JOIN kat ON (kat.id = kat)
	WHERE `pris` >0
	  AND sider.`navn` != ''
	GROUP BY id
	ORDER BY `sider`.`varenr` DESC
	"
);

//check for inactive
if ($sider) {
	for ($i=0; $i<count($sider); $i++) {
		if (binding($sider[$i]['kat_id']) == -1) {
			array_splice($sider, $i, 1);
			$i--;
		}
	}
}

header("Content-Type: application/xml");
doConditionalGet($sider[0]['dato']);

$search = array (
	'@<script[^>]*?>.*?</script>@si', // Strip out javascript
	'@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
	'@([\r\n])[\s]+@',                // Strip out white space
	'@&(&|#197);@i'
);

$replace = array (
	' ',
	' ',
	'\1',
	' '
);

require_once 'inc/config.php';

echo '<?xml version="1.0" encoding="utf-8"?><products>';
for ($i=0; $i<count($sider); $i++) {
	if (!$sider[$i]['navn'] = trim(htmlspecialchars($sider[$i]['navn']))) {
		continue;
	}

	echo '
    <product>
        <sku>'.$sider[$i]['id'].'</sku>
        <title>'.$sider[$i]['navn'].'</title>';
	if ($sider[$i]['varenr'] = trim(htmlspecialchars($sider[$i]['varenr']))) {
		echo '<companysku>'.$sider[$i]['varenr'].'</companysku>';
	}
    echo '<price>'.$sider[$i]['pris'].',00</price>
    <img>'.$GLOBALS['_config']['base_url'].$sider[$i]['billed'].'</img>
    <link>'.$GLOBALS['_config']['base_url'].'/kat'.$sider[$i]['kat_id'].'-'.rawurlencode(clear_file_name($sider[$i]['kat_navn'])).'/side'.$sider[$i]['id'].'-'.rawurlencode(clear_file_name($sider[$i]['navn'])).'.html</link>';
	$bind = $mysqli->fetch_array("SELECT `kat` FROM bind WHERE side = ".$sider[$i]['id']);
	
	$category = array();
	if ($sider[$i]['maerke']) {
		$maerker = explode(',', $sider[$i]['maerke']);
		$maerker_nr = count($maerker);
		$where = '';
		for ($imaerker=0; $imaerker<$maerker_nr; $imaerker++) {
			if ($imaerker > 0) {
				$where .= ' OR';
			}
			$where .= ' id = '.$maerker[$imaerker];
		}
		$maerker = $mysqli->fetch_array(
			"
			SELECT `navn`
			FROM maerke
			WHERE".$where."
			LIMIT ".$maerker_nr
		);
		$maerker_nr = count($maerker);
		for ($imaerker=0; $imaerker<$maerker_nr; $imaerker++) {
			if ($category2 = trim(preg_replace($search, $replace, $maerker[$imaerker]['navn']))) {
				$category[] = htmlspecialchars($category2, ENT_NOQUOTES);
			}
		}
		echo '<company>'.htmlspecialchars($category2, ENT_NOQUOTES).'</company>';
	}
	
	$kats = '';
	for ($ibind=0; $ibind<count($bind); $ibind++) {
		$kats[] = $bind[$ibind]['kat'];
		
		$temp = $mysqli->fetch_array("SELECT bind FROM `kat` WHERE id = '".$bind[$ibind]['kat']."' LIMIT 1");
		if (@$temp[0]) {
			while ($temp && !in_array($temp[0]['bind'], $kats)) {
				$kats[] = $temp[0]['bind'];
				$temp = $mysqli->fetch_array("SELECT bind FROM `kat` WHERE id = '".$temp[0]['bind']."' LIMIT 1");
			}
		}
	}

	//$kats = array_unique($kats);

	for ($icategory=0; $icategory<count($kats); $icategory++) {
		if ($kats[$icategory]) {
			$kat = $mysqli->fetch_array("SELECT `navn` FROM kat WHERE id = ".$kats[$icategory]." LIMIT 1");
			if ($category2 = trim(preg_replace($search, $replace, @$kat[0]['navn']))) {
				$category[] = htmlspecialchars($category2, ENT_NOQUOTES);
			}
		}
	}

	$category = array_unique(array_reverse($category));

	echo '<category>'.implode(' &gt; ', $category).'</category>';	
	echo '</product>';
}
$mysqli->close();
echo '</products>';

