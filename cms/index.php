<?php
/*
ini_set('display_errors', 1);
error_reporting(-1);
*/

require_once 'inc/header.php'; 
//include the file   
require_once("inc/firephp.class.php");
//create the object
if(!isset($firephp))
	$firephp = FirePHP::getInstance(true);
/*
if(!headers_sent())
	foreach($GLOBALS as $key => $value)
		$firephp->fb($key);
*/

require_once 'inc/config.php';
require_once 'inc/mysqli.php';

//primitive runtime cache
$GLOBALS['cache'] = array();
$GLOBALS['cache']['updatetime'] = array();

//Open database
$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);

//If the database is older then the users cache, send 304 not modified
//WARNING: this results in the site not updating if new files are included later,
//the remedy is to update the database when new cms files are added.
if(!@$delayprint) {
	$tabels = $mysqli->fetch_array("SHOW TABLE STATUS");
	$updatetime = 0;
	foreach($tabels as $tabel)
		$updatetime = max($updatetime, strtotime($tabel['Update_time']));
	/*
	if(!headers_sent()) {
		$firephp->fb($updatetime);
		foreach($tabels as $tabel) {
			$firephp->fb($tabel['Update_time']);
			$firephp->fb(strtotime($tabel['Update_time']));
		}
	}
	*/
	$included_files = get_included_files();
	$GLOBALS['cache']['updatetime']['filemtime'] = 0;
	foreach($included_files as $filename)
		$GLOBALS['cache']['updatetime']['filemtime'] = max($GLOBALS['cache']['updatetime']['filemtime'], filemtime($filename));
	unset($included_files);
	unset($filename);
	foreach($GLOBALS['cache']['updatetime'] as $time) {
		$updatetime = max($updatetime, $time);
	}
	/*
	if(!headers_sent()) {
		foreach($GLOBALS['cache']['updatetime'] as $time) {
			$firephp->fb(date(DATE_RFC822, $time));
			$firephp->fb($time);
		}
		$firephp->fb($updatetime);
	}
	*/
	if($updatetime < 1)
		$updatetime = time();
	
	doConditionalGet($updatetime);
	$updatetime = 0;
}

require_once 'inc/functions.php';

//Tegn menuen
function menu($nr, $custom_sort_subs = false) {
	global $mysqli;

	//hent listen af kategorier til dette nivo og check samtidig om de har et under nivo og en side linket direkte.
	//TODO inner join or HAVING COUNT(pb.id) > 0 posible way to eliminate empty catagorys
	$kat = $mysqli->fetch_array("SELECT kat.id, kat.navn, kat.vis, kat.icon, kat.custom_sort_subs, MAX(bind.side) AS skriv, subkat.id AS sub FROM kat LEFT JOIN kat AS subkat ON kat.id = subkat.bind AND  subkat.vis != '0' LEFT JOIN bind ON kat.id = bind.kat WHERE kat.vis != '0' AND kat.bind = ".$GLOBALS['kats'][$nr]." GROUP BY kat.id ORDER BY kat.`order`, kat.navn");
	if($kat) {
		//TODO get the custome sort order to be via sql an the sort colum
		if(!$custom_sort_subs)
			$kat = array_natsort($kat, 'id', 'navn', 'asc');
	
		if(!$GLOBALS['cache']['kats'][$GLOBALS['kats'][$nr]]['navn']) {
			$katsnr_navn = $mysqli->fetch_array('SELECT navn, vis, icon FROM kat WHERE id = '.$GLOBALS['kats'][$nr]);
			$GLOBALS['cache']['kats'][$GLOBALS['kats'][$nr]]['navn'] = $katsnr_navn[0]['navn'];
			$GLOBALS['cache']['kats'][$GLOBALS['kats'][$nr]]['vis'] = $katsnr_navn[0]['vis'];
			$GLOBALS['cache']['kats'][$GLOBALS['kats'][$nr]]['icon'] = $katsnr_navn[0]['icon'];
		}
		
		foreach($kat as $value) {
			$subs = NULL;
			$GLOBALS['cache']['kats'][$value['id']]['skriv'] = @$GLOBALS['cache']['kats'][$value['id']]['skriv'] ? true : $value['skriv'] ? true : $value['sub'] ? NULL : false;
			$GLOBALS['cache']['kats'][$value['id']]['vis'] = $value['vis'];
			//Skriv viser kun om kategorin skal krives ikke om den ikke skal så hvis siden har subs skal de undersøges nermer
			if(skriv($value['id'])) {
				//Er katagorien aaben
				if(@$GLOBALS['kats'][$nr+1] == $value['id'])
					$subs = menu($nr+1, $value['custom_sort_subs']);

				//tegn under punkter
				$menu[] = array('id' => $value['id'],
					'name' => htmlspecialchars($value['navn']),
					'link' => '/kat'.$value['id'].'-'.clear_file_name($value['navn']).'/',
					'icon' => $value['icon'],
					'sub' => $value['sub'] ? true : false,
					'subs' => $subs);
			}
		}
	}
	if(!isset($menu))
		$menu = array();
	return $menu;
}

//sog efter katagorier
function search_menu($q, $wherekat) {
	global $mysqli;
	global $qext;

	if($qext)
		$qext = ' WITH QUERY EXPANSION';
	else
		$qext = '';

	if($q) {
		$kat = $mysqli->fetch_array("SELECT id, navn, icon, MATCH (navn) AGAINST ('".$q."'".$qext.") AS score FROM kat WHERE MATCH (navn) AGAINST('".$q."'".$qext.") > 0 ".$wherekat." AND `vis` != '0' ORDER BY score, navn");
		if(!$kat) {
			$qsearch = array ("/ /","/'/","//","/`/");
			$qreplace = array ("%","_","_","_");
			$simpleq = preg_replace($qsearch, $qreplace, $q);
			$kat = $mysqli->fetch_array("SELECT id, navn, icon FROM kat WHERE navn LIKE '%".$simpleq.'%\' '.$wherekat.' ORDER BY navn');
		}
		$maerke = $mysqli->fetch_array('SELECT id, navn FROM `maerke` WHERE MATCH (navn)AGAINST (\''.$q.'\''.$qext.') >0');
		if(!$maerke) {
			if(!@$simpleq) {
				$qsearch = array ("/ /","/'/","//","/`/");
				$qreplace = array ("%","_","_","_");
				$simpleq = preg_replace($qsearch, $qreplace, $q);
			}
			$maerke = $mysqli->fetch_array("SELECT id, navn FROM maerke WHERE navn LIKE '%".$simpleq."%' ORDER BY navn");
		}
	}
	if($maerke)
	foreach($maerke as $value) {
		$GLOBALS['generatedcontent']['search_menu'][] = array('id' => 0,
			'name' => htmlspecialchars($value['navn']),
			'link' => '/mærke'.$value['id'].'-'.clear_file_name($value['navn']).'/');
	}
	if($kat)
	foreach($kat as $value) {
		if(skriv($value['id'])) {
			$GLOBALS['generatedcontent']['search_menu'][] = array('id' => $value['id'],
				'name' => htmlspecialchars($value['navn']),
				'link' => '/kat'.$value['id'].'-'.clear_file_name($value['navn']).'/',
				'icon' => $value['icon'],
				'sub' => subs($value['id']));
		}
	}
}

//check if page is inactive
function is_inactive_page($id) {
	global $mysqli;
	$bind = $mysqli->fetch_array("SELECT `kat` FROM `bind` WHERE `side` = ".$id." LIMIT 1");
	if(binding($bind[0]['kat']) == -1) {
		return true;
	}
}

//secure input
function full_mysqli_escape($s) {
	if(is_array($s))
		return array_map('full_mysqli_escape', $s);

	global $mysqli;
	if(get_magic_quotes_gpc())
		$s = stripslashes($s);
	return $mysqli->escape_wildcards($mysqli->real_escape_string($s));
}

$_GET = array_map('full_mysqli_escape', $_GET);

//redirect af gamle urls
if(@$_GET['kat'] || @$_GET['side']) {

	//secure input
	if (get_magic_quotes_gpc()) {
		$kat_id = $mysqli->escape_wildcards($mysqli->real_escape_string(stripslashes($_GET['kat'])));
		$side_id = $mysqli->escape_wildcards($mysqli->real_escape_string(stripslashes($_GET['side'])));
	} else {
		$kat_id = $mysqli->escape_wildcards($mysqli->real_escape_string($_GET['kat']));
		$side_id = $mysqli->escape_wildcards($mysqli->real_escape_string($_GET['side']));
	}
	
	header('HTTP/1.1 301 Moved Permanently');
	if($side_id) {
		$bind = $mysqli->fetch_array("SELECT bind.kat, sider.navn AS side_navn, kat.navn AS kat_navn FROM bind JOIN sider ON bind.side = sider.id JOIN kat ON bind.kat = kat.id WHERE side =".$side_id." LIMIT 1 ");
		$side_navn = $bind[0]['side_navn'];
		$kat_id = $bind[0]['kat'];
		$kat_name = $bind[0]['kat_navn'];
		unset($bind);
	}

	if(($kat_id && !$kat_name) || ($_GET['kat'] && $_GET['kat'] != $kat_id)) {
		//get kat navn hvis der ikke var en side eller kat ikke var standard for siden.
		if (get_magic_quotes_gpc()) {
			$kat_id = $mysqli->escape_wildcards($mysqli->real_escape_string(stripslashes($_GET['kat'])));
		} else {
			$kat_id = $mysqli->escape_wildcards($mysqli->real_escape_string($_GET['kat']));
		}
		if(!$GLOBALS['cache']['kats'][$kat_id]['navn']) {
			$kats = $mysqli->fetch_array('SELECT navn, vis, icon FROM kat WHERE id = '.$kat_id.' LIMIT 1');
			
			getUpdateTime('kat');

			$GLOBALS['cache']['kats'][$kat_id]['navn'] = $kats[0]['navn'];
			$GLOBALS['cache']['kats'][$kat_id]['vis'] = $kats[0]['vis'];
			$GLOBALS['cache']['kats'][$kat_id]['icon'] = $kats[0]['icon'];
		}
		$kat_name = $GLOBALS['cache']['kats'][$kat_id]['navn'];
	}
	if($side_navn) {

		//TODO rawurlencode $url (PIE doesn't do it buy it self :(
		$url = '/kat'.$kat_id.'-'.rawurlencode(clear_file_name($kat_name)).'/side'.$side_id.'-'.rawurlencode(clear_file_name($side_navn)).'.html';

		//redirect til en side
		header('Location: '.$url);
		die();
	} elseif($kat_name) {

		//TODO rawurlencode $url (PIE doesn't do it buy it self :(
		$url = '/kat'.$kat_id.'-'.rawurlencode(clear_file_name($kat_name)).'/';

		//redirect til en kategori
		header('Location: '.$url);
		die();
	} else {
		//inted fundet redirect til søge siden
		header('Location: /?sog=1&q=&varenr=&sogikke=&minpris=&maxpris=&maerke=');
		die();
	}
}

//primitive runtime cache
$GLOBALS['cache']['kats'] = array();

//Always blank kads
if(@$_GET['sog'] || @$_GET['q'] || @$_GET['varenr'] || @$_GET['sogikke'] || @$_GET['minpris'] || @$_GET['maxpris'] || @$_GET['maerke'] || @$_GET['brod'])
	$GLOBALS['generatedcontent']['activmenu']=-1;

//Handle none existing pages
if(@$GLOBALS['side']['id'] > 0) {
	if(!$mysqli->fetch_array('SELECT id FROM sider WHERE id = '.$GLOBALS['side']['id'].' LIMIT 1')) {
																				 
		getUpdateTime('sider');
		
		$GLOBALS['side']['inactive'] = true;
		unset($GLOBALS['side']['id']);
		header('HTTP/1.1 404 Not Found');
	}
}

//Block inactive pages
if(@$GLOBALS['side']['id'] > 0 && is_inactive_page($GLOBALS['side']['id'])) {
	$GLOBALS['side']['inactive'] = true;
	header('HTTP/1.1 404 Not Found');
}

//Hvis siden er kendt men katagorien ikke så find den første passende katagori, hent også side indholdet.
if(@$GLOBALS['side']['id'] > 0 && !@$GLOBALS['generatedcontent']['activmenu'] && !@$GLOBALS['side']['inactive']) {
	$bind = $mysqli->fetch_array("SELECT bind.kat, sider.navn, sider.burde, sider.fra, sider.text, sider.pris, sider.for, sider.krav, sider.maerke, sider.varenr, UNIX_TIMESTAMP(sider.dato) AS dato FROM bind JOIN sider ON bind.side = sider.id WHERE side = ".$GLOBALS['side']['id']." LIMIT 1");
	
	getUpdateTime('bind');
	
	$GLOBALS['generatedcontent']['activmenu']		= $bind[0]['kat'];
	$GLOBALS['side']['navn']	= $bind[0]['navn'];
	$GLOBALS['side']['burde']	= $bind[0]['burde'];
	$GLOBALS['side']['fra']		= $bind[0]['fra'];
	$GLOBALS['side']['text']	= $bind[0]['text'];
	$GLOBALS['side']['pris']	= $bind[0]['pris'];
	$GLOBALS['side']['for']		= $bind[0]['for'];
	$GLOBALS['side']['krav']	= $bind[0]['krav'];
	$GLOBALS['side']['maerke']	= $bind[0]['maerke'];
	$GLOBALS['side']['varenr']	= $bind[0]['varenr'];
	$GLOBALS['side']['dato']	= $bind[0]['dato'];
	$GLOBALS['cache']['updatetime']['side']	= $bind[0]['dato'];
	unset($bind);
} elseif(@$GLOBALS['side']['id'] > 0 && !@$GLOBALS['side']['inactive']) {
	//Hent side indhold
	$sider = $mysqli->fetch_array("SELECT `navn`,`burde`,`fra`,`text`,`pris`,`for`,`krav`,`maerke`, varenr, UNIX_TIMESTAMP(dato) AS dato FROM sider WHERE id = ".$GLOBALS['side']['id']." LIMIT 1");
	$GLOBALS['side']['navn']	= $sider[0]['navn'];
	$GLOBALS['side']['burde']	= $sider[0]['burde'];
	$GLOBALS['side']['fra']		= $sider[0]['fra'];
	$GLOBALS['side']['text']	= $sider[0]['text'];
	$GLOBALS['side']['pris']	= $sider[0]['pris'];
	$GLOBALS['side']['for']		= $sider[0]['for'];
	$GLOBALS['side']['krav']	= $sider[0]['krav'];
	$GLOBALS['side']['maerke']	= $sider[0]['maerke'];
	$GLOBALS['side']['varenr']	= $sider[0]['varenr'];
	$GLOBALS['side']['dato']	= $sider[0]['dato'];
	$GLOBALS['cache']['updatetime']['side']	= $sider[0]['dato'];
	
	unset($sider);
}

if(@$GLOBALS['generatedcontent']['activmenu'] > 0) {
	$keywords = array();
	
	//get kat tree,
	$data = kats($GLOBALS['generatedcontent']['activmenu']);
	$nr = count($data);
	for($i=$nr-1; $i>=0; $i--){
		$kats[$i] = $data[$nr-$i-1];
	}
	
	$GLOBALS['kats'] = $kats;
	
	//Key words
	if($GLOBALS['kats'])
	foreach($GLOBALS['kats'] as $value) {
		if(!@$GLOBALS['cache']['kats'][$value]['navn']) {
			$temp = $mysqli->fetch_array("SELECT navn, vis, icon FROM kat WHERE id = ".$value." LIMIT 1");
			
			getUpdateTime('kat');
			
			$GLOBALS['cache']['kats'][$value]['navn'] = $temp[0]['navn'];
			$GLOBALS['cache']['kats'][$value]['vis'] = $temp[0]['vis'];
			$GLOBALS['cache']['kats'][$value]['icon'] = $temp[0]['icon'];
		}
		$keywords[] = trim(htmlspecialchars($GLOBALS['cache']['kats'][$value]['navn']));
	}
	$GLOBALS['generatedcontent']['keywords'] = implode(',',$keywords);
}

//crumbs start
if(@$GLOBALS['kats']) {
	foreach($GLOBALS['kats'] as $value) {
		if(!$GLOBALS['cache']['kats'][$value]['navn']) {
			$katsnr_navn = $mysqli->fetch_array("SELECT navn, vis, icon FROM kat WHERE id = ".$value);
			
			getUpdateTime('kat');

			$GLOBALS['cache']['kats'][$value]['navn'] = $katsnr_navn[0]['navn'];
			$GLOBALS['cache']['kats'][$value]['vis'] = $katsnr_navn[0]['vis'];
			$GLOBALS['cache']['kats'][$value]['icon'] = $katsnr_navn[0]['icon'];
		}

		$GLOBALS['generatedcontent']['crumbs'][] = array('name' => htmlspecialchars($GLOBALS['cache']['kats'][$value]['navn']), 'link' => '/kat'.$value.'-'.clear_file_name($GLOBALS['cache']['kats'][$value]['navn']).'/', 'icon' => $GLOBALS['cache']['kats'][$value]['icon']);
	}
	$GLOBALS['generatedcontent']['crumbs'] = array_reverse(array_values($GLOBALS['generatedcontent']['crumbs']));
}
//crumbs end

//Get list of top categorys on the site.
$kat_fpc = $mysqli->fetch_array("SELECT id, navn, vis, icon, custom_sort_subs, id IN (SELECT bind FROM kat WHERE vis != '0') AS sub, id IN (SELECT kat FROM bind) AS skriv FROM `kat` WHERE kat.vis != '0' AND kat.bind =0 AND (id IN (SELECT bind FROM kat WHERE vis != '0') OR id IN (SELECT kat FROM bind)) ORDER BY `order`, navn ASC");
getUpdateTime('kat');
foreach($kat_fpc as $value) {

	$GLOBALS['cache']['kats'][$value['id']]['navn'] = $value['navn'];
	$GLOBALS['cache']['kats'][$value['id']]['vis'] = $value['vis'];
	$GLOBALS['cache']['kats'][$value['id']]['skriv'] = $value['skriv'] ? true : NULL;
	
//TODO think about adding parent folders to url
	if(skriv($value['id'])) {
		$subs = NULL;
		if($value['id'] == @$GLOBALS['kats'][0]) {
			$subs = menu(0, $value['custom_sort_subs']);
		}

		$GLOBALS['generatedcontent']['menu'][] = array('id' => $value['id'],
		'name' => htmlspecialchars($value['navn']),
		'link' => '/kat'.$value['id'].'-'.clear_file_name($value['navn']).'/',
		'icon' => $value['icon'],
		'sub' => $value['sub'] ? true : false,
		'subs' => $subs);
	}
}

unset($kat_fpc);
unset($subs);
unset($value);

//Front page pages
$kat_fpp = $mysqli->fetch_array("SELECT sider.id, sider.navn FROM bind JOIN sider ON bind.side = sider.id WHERE kat = 0");
		
getUpdateTime('bind');
getUpdateTime('sider');

foreach($kat_fpp as $value) {
	$GLOBALS['generatedcontent']['sider'][] = array('id' => $value['id'], 'name' => htmlspecialchars($value['navn']), 'link' => '/side'.$value['id'].'-'.clear_file_name($value['navn']).'.html');
}
unset($kat_fpp);
unset($value);

//TODO catch none existing kats
//Get page content and type
if(@$_GET['sog'] || @$GLOBALS['side']['inactive']) {
	$GLOBALS['generatedcontent']['contenttype'] = 'search';

	$GLOBALS['generatedcontent']['text'] = '';

	if(@$GLOBALS['side']['inactive'])
		$GLOBALS['generatedcontent']['text'] .= '<p>Siden kunne ikke findes. Prøv eventuelt at søge efter en lignende side.</p>';
	
	$GLOBALS['generatedcontent']['text'] .= '<form action="/" method="get"><table><tr><td>Indeholder</td><td><input name="q" size="31" value="';
	if(@$GLOBALS['side']['inactive']) {
		$GLOBALS['generatedcontent']['text'] .= htmlspecialchars(preg_replace(array('/-/u', '/.*?side[0-9]+\s(.*?)[.]html/u'), array(' ', '\1'), urldecode($_SERVER['REQUEST_URI'])));
	}
	$GLOBALS['generatedcontent']['text'] .= '" /></td><td><input type="submit" value="Søg" /></td></tr><tr><td>Vare nummer</td><td><input name="varenr" size="31" value="" maxlength="63" /></td></tr><tr><td>Uden ordene</td><td><input name="sogikke" size="31" value="" /></td></tr><tr><td>Udvidet:</td><td><input name="qext" type="checkbox" value="1" /></td></tr><tr><td>Min pris</td><td><input name="minpris" size="5" maxlength="11" value="" />,-</td></tr><tr><td>Max pris&nbsp;</td><td><input name="maxpris" size="5" maxlength="11" value="" />,-</td></tr><tr><td>M&aelig;rke:</td><td><select name="maerke"><option value="0">Alle</option>';
	$maerker = $mysqli->fetch_array('SELECT `id`, `navn` FROM `maerke` ORDER BY `navn` ASC');
		
	getUpdateTime('maerke');
	
	$maerker_nr = count($maerker);
	foreach($maerker as $value) {
		$GLOBALS['generatedcontent']['text'] .= '<option value="'.$value['id'].'">'.htmlspecialchars($value['navn'], NULL, 'UTF-8').'</option>';
	}
	$GLOBALS['generatedcontent']['text'] .= '</select></td></tr></table></form>';
} elseif(@$_GET['q'] || @$_GET['varenr'] || @$_GET['sogikke'] || @$_GET['minpris'] || @$_GET['maxpris'] || @$_GET['maerke'] || @$maerke) {
	$GLOBALS['generatedcontent']['contenttype'] = 'tiles';
	
	if((@$_GET['maerke'] || @$maerke) && !@$_GET['q'] && !@$_GET['varenr'] && !@$_GET['sogikke'] && !@$_GET['minpris'] && !@$_GET['maxpris']) {
		//Brand only search
		$GLOBALS['generatedcontent']['contenttype'] = 'brand';
		if(@$_GET['maerke'] && !@$maerke)
			$maerke = $_GET['maerke'];
		$maerkeet = $mysqli->fetch_array('SELECT `id`, `navn`, `link`, ico FROM `maerke` WHERE id = '.$maerke);
		
		getUpdateTime('maerke');
		
		$GLOBALS['generatedcontent']['brand'] = array('id' => $maerkeet[0]['id'],
		'name' => htmlspecialchars($maerkeet[0]['navn']),
		'xlink' => $maerkeet[0]['link'],
		'icon' => $maerkeet[0]['ico']);

		require_once 'inc/liste.php';
		$wheresider = 'And (`maerke` LIKE \''.$maerkeet[0]['id'].'\' OR `maerke` LIKE \''.$maerkeet[0]['id'].',%\' OR `maerke` LIKE \'%,'.$maerkeet[0]['id'].',%\' OR `maerke` LIKE \'%,'.$maerkeet[0]['id'].'\')';
		search_liste(false, $wheresider);
	} else {
		//Full search
		$wheresider = '';
		if(@$_GET['varenr'])
			$wheresider .= ' AND varenr LIKE \''.$_GET['varenr'].'%\'';
		if(@$_GET['minpris'])
			$wheresider .= ' AND pris > '.$_GET['minpris'];
		if(@$_GET['maxpris'])
			$wheresider .= ' AND pris < '.$_GET['maxpris'];
		if(@$_GET['maerke'])
			$wheresider .= ' AND (`maerke` LIKE \'%,'.$_GET['maerke'].',%\' OR `maerke` LIKE \''.$_GET['maerke'].',%\' OR `maerke` LIKE \'%,'.$_GET['maerke'].'\' OR `maerke` LIKE \''.$_GET['maerke'].'\')';
		if(@$nmaerke)
			$wheresider .= ' AND (`maerke` NOT LIKE \'%,'.$nmaerke.',%\' AND `maerke` NOT LIKE \''.$nmaerke.',%\' AND `maerke` NOT LIKE \'%,'.$nmaerke.'\' AND `maerke` NOT LIKE \''.$nmaerke.'\')';
		if(@$_GET['sogikke'])
			$wheresider .= ' AND !MATCH (navn,text) AGAINST(\''.$_GET['sogikke'].'\') > 0';
	}

	if(!@$start)
		$start = "0";

	if(!@$num)
		$num = "10";

	$limit =  ' LIMIT '.$start.' , '.$num;
	require_once 'inc/liste.php';
	search_liste($_GET['q'], $wheresider);

	$wherekat = '';
	if($_GET['sogikke'])
		$wherekat .= ' AND !MATCH (navn) AGAINST(\''.$_GET['sogikke'].'\') > 0';
	search_menu($_GET['q'], $wherekat);
	
	if(!@$GLOBALS['generatedcontent']['list'] && !@$GLOBALS['generatedcontent']['search_menu']) {
		header('HTTP/1.1 404 Not Found');
	}
} elseif(@$GLOBALS['side']['id'] > 0) {
	$GLOBALS['generatedcontent']['contenttype'] = 'product';
	require_once 'inc/side.php';
	side();
} elseif(@$GLOBALS['generatedcontent']['activmenu'] > 0) {
	require_once 'inc/liste.php';
	liste();
	if(@$GLOBALS['side']['id'] > 0) {
		$GLOBALS['generatedcontent']['contenttype'] = 'product';
	} elseif(@$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['vis'] == 2) {
		$GLOBALS['generatedcontent']['contenttype'] = 'list';
	} elseif(@$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['vis'] == 1) {
		$GLOBALS['generatedcontent']['contenttype'] = 'tiles';
	}
} else {
	$special = $mysqli->fetch_array("SELECT text, UNIX_TIMESTAMP(dato) AS dato FROM special WHERE id = 1 LIMIT 1");
	$GLOBALS['cache']['updatetime']['special_1'] = $special['dato'];
	
	$GLOBALS['generatedcontent']['contenttype'] = 'front';
	$GLOBALS['generatedcontent']['text'] = $special[0]['text'];
	unset($special);
}

//Extract title for current page.
if(@$maerkeet) {
	$GLOBALS['generatedcontent']['title'] = $maerkeet[0]['navn'];
} elseif(isset($GLOBALS['side']['navn'])) {
	$GLOBALS['generatedcontent']['title'] = htmlspecialchars($GLOBALS['side']['navn']);
	//Add page title to keywords
	if(@$GLOBALS['generatedcontent']['keywords'])
		$GLOBALS['generatedcontent']['keywords'] .= ",".htmlspecialchars($GLOBALS['side']['navn']);
	else
		$GLOBALS['generatedcontent']['keywords'] = htmlspecialchars($GLOBALS['side']['navn']);
} elseif(@$GLOBALS['side']['id'] && !@$GLOBALS['side']['inactive']) {
	$sider_navn = $mysqli->fetch_array("SELECT navn, UNIX_TIMESTAMP(dato) AS dato FROM sider WHERE id = ".$GLOBALS['side']['id']." LIMIT 1");

	$GLOBALS['cache']['updatetime']['sider'] = $sider_navn[0]['dato'];
	
	$GLOBALS['generatedcontent']['title'] = htmlspecialchars($sider_navn[0]['navn']);
}

if(!$GLOBALS['generatedcontent']['title'] && @$GLOBALS['generatedcontent']['activmenu'] > 0) {
	if(!$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['navn']) {
		$kat_navn = $mysqli->fetch_array("SELECT navn, vis FROM kat WHERE id = ".$GLOBALS['generatedcontent']['activmenu']." LIMIT 1");
	
		getUpdateTime('kat');
		
		$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['navn'] = $kat_navn[0]['navn'];
		$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['vis'] = $kat_navn[0]['vis'];
	}
	
	$GLOBALS['generatedcontent']['title'] = htmlspecialchars($GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['navn']);
	
	
	//TODO add to url
	if($GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['icon'])
		$icon = $mysqli->fetch_array("SELECT `alt` FROM `files` WHERE path = '".$GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['icon']."' LIMIT 1");
	
	if($icon[0]['alt'] && $GLOBALS['generatedcontent']['title']) {
		$GLOBALS['generatedcontent']['title'] .= ' '.htmlspecialchars($icon[0]['alt']);
	} elseif($icon[0]['alt']) {
		$GLOBALS['generatedcontent']['title'] = htmlspecialchars($icon[0]['alt']);
	} elseif(!$GLOBALS['generatedcontent']['title']) {
		$icon[0]['path'] = pathinfo($GLOBALS['cache']['kats'][$GLOBALS['generatedcontent']['activmenu']]['icon']);
		$GLOBALS['generatedcontent']['title'] = htmlspecialchars(ucfirst(preg_replace('/-/ui', ' ', $icon[0]['path']['filename'])));
	}
} elseif(!$GLOBALS['generatedcontent']['title'] && @$_GET['sog'] == 1) {
	$GLOBALS['generatedcontent']['title'] = 'Søg på '.htmlspecialchars($GLOBALS['_config']['site_name']);
}

if(!$GLOBALS['generatedcontent']['title']) {
	$GLOBALS['generatedcontent']['title'] = htmlspecialchars($GLOBALS['_config']['site_name']);
}
//end title

//Get email
$GLOBALS['generatedcontent']['email'] = $GLOBALS['_config']['email'][0];
if(@$GLOBALS['generatedcontent']['activmenu'] > 0) {
	$email = $mysqli->fetch_array('SELECT `email` FROM `kat` WHERE id = '.$GLOBALS['generatedcontent']['activmenu']);
	
	getUpdateTime('kat');
	
	if($email[0]['email'])
		$GLOBALS['generatedcontent']['email'] = $email[0]['email'];
}

if(!@$delayprint) {
	$updatetime = 0;
	
	$included_files = get_included_files();
	foreach($included_files as $filename) {
		$GLOBALS['cache']['updatetime']['filemtime'] = max($GLOBALS['cache']['updatetime']['filemtime'], filemtime($filename));
	}
	unset($included_files);
	unset($filename);
	foreach($GLOBALS['cache']['updatetime'] as $time) {
		$updatetime = max($updatetime, $time);
	}
	unset($time);
	if($updatetime < 1)
		$updatetime = time();
	/*
	if(!headers_sent()) {
		foreach($GLOBALS['cache']['updatetime'] as $time) {
//			$firephp->fb(date(DATE_RFC822, $time));
			$firephp->fb($time);
		}
		$firephp->fb($updatetime);
	}
	*/
	doConditionalGet($updatetime);
	unset($updatetime);
	
	unset($cache);
	
	require_once 'theme/index.php';
}
/*
?><!--
<?php
print_r($GLOBALS['generatedcontent']);
print_r($GLOBALS['cache']['kats']);
?>
--><?php
/**/
?>
