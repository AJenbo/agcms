<?php 

//Skriv prisen og tilbudet
function echo_pris($pris,$for,$fra,$burde) {
	if($for) {
		if($burde == 1) {
			?>Vejl.pris: <?php echo(str_replace(',00', ',-', number_format($for, 2, ',', '.'))); ?><?php
		} elseif($burde == 2) {
			?>Burde koste: <?php echo(str_replace(',00', ',-', number_format($for, 2, ',', '.'))); ?><?php
		} else {
			?>Før: <span class="XPris"><?php echo(str_replace(',00', ',-', number_format($for, 2, ',', '.'))); ?></span><?php
		}
	}

	if($pris) {
		if($fra == 1 && $for) {
			?> <span class="NyPris">Nu fra: <?php
		} elseif($fra == 2 && $for) {
			?> <span class="NyPris">Brugt: <?php
		} elseif($fra == 1) {
			?> Fra: <span class="Pris"><?php
		} elseif($fra == 2) {
			?> Brugt: <span class="Pris"><?php
		} elseif($for) {
			?> <span class="NyPris">Nu: <?php
		} else {
			?> Pris: <span class="Pris"><?php
		}
		echo(str_replace(',00', ',-', number_format($pris, 2, ',', '.')));
		?></span><?php 
	}
}

function echo_menu($menu) {
	if($menu) {
		?><ul><?php
		foreach($menu as $value) {
			?><li><?php
			if($value['id'] == @$GLOBALS['generatedcontent']['activmenu']) {
				?><h4 id="activmenu"><?php
			}
			?><a href="<?php echo($value['link']); ?>"><?php echo($value['name']);
			if($value['icon']) {
				?> <img src="<?php echo($value['icon']); ?>" alt="" /><?php
			}
			?></a><?php
			
			if($value['id'] == @$GLOBALS['generatedcontent']['activmenu']) {
				?></h4><?php
			}
			if($value['subs']) {
				echo_menu($value['subs']);
			}
			?></li><?php
		}
		?></ul><?php
	}
}


//Get number of inactive pages
function katsup($id, $main = true) {
	global $mysqli;
	
	static $siderChecked;
	
	$sider = $mysqli->fetch_array('SELECT side FROM bind WHERE kat = '.$id);
	foreach($sider as $side) {
		$siderChecked[] = $side['side'];
	}
	
	$kats = $mysqli->fetch_array('SELECT id FROM kat WHERE bind = '.$id);
	foreach($kats as $kat) {
		katsup($kat['id'], false);
	}
	
	if($main) {
		$siderChecked = array_unique($siderChecked);
		return count($siderChecked);
	}
	return true;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo($GLOBALS['generatedcontent']['title']); ?></title>
<meta http-equiv="page-enter" content="blendTrans(Duration=0)" />
<meta http-equiv="X-UA-Compatible" content="IE=7" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link href="/theme/style.css" rel="stylesheet" type="text/css" />
<link href="/theme/handheld.css" rel="stylesheet" type="text/css" media="handheld" />
<!--[if IE]><![if !IE]><![endif]--><style type="text/css"> @import url("/theme/handheld.css") all and (max-device-width: 748px); </style><!--[if IE]><![endif]><![endif]-->
<meta id="viewport" name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=no;" />
<script type="text/javascript"><!--
if(/(NetFront|PlayStation|hiptop|IEMobile|Smartphone|iPhone|Opera Mobi|Opera Mini|BlackBerry|Series60)/i.test(navigator.userAgent))
	document.write('<link href="/theme/handheld.css" rel="stylesheet" type="text/css" />');
--></script>
<link href="/theme/print.css" rel="stylesheet" type="text/css" media="print" />
<script src="/javascript/json2.stringify.js" type="text/javascript"></script>
<script src="/javascript/json_stringify.js" type="text/javascript"></script>
<script src="/javascript/json_parse_state.js" type="text/javascript"></script>
<script src="/javascript/sajax.js" type="text/javascript"></script>
<script src="/javascript/javascript.js" type="text/javascript"></script>
<script src="/theme/javascript.js" type="text/javascript"></script>
<!--[if lt IE 7]><script src="/theme/ie6fix.js" type="text/javascript"></script><style type="text/css" media="print">#menu {position:absolute}</style><![endif]-->
<link rel="alternate" type="application/rss+xml" title="Nyheder på Hunters House" href="/rss.php" />
<link title="Hunters House" type="application/opensearchdescription+xml" rel="search" href="/sog.php" />
<meta http-equiv="content-language" content="da" />
<meta name="Description" content="Alt du har brug for i frilufts livet" />
<meta name="Author" content="Anders Jenbo" />
<meta name="Classification" content="Jagt og fiskeri" />
<?php if(@$GLOBALS['generatedcontent']['keywords']) echo('<meta name="Keywords" content="'.$GLOBALS['generatedcontent']['keywords'].'" />'); ?>
<meta name="Reply-to" content="mail@huntershouse.dk" />
<meta name="Revisit-after" content="14 days" />
<meta http-equiv="imagetoolbar" content="no" />
<meta name="distribution" content="Global" />
<meta name="robots" content="index,follow" />
</head>
<!--[if lt IE 7]><body onload="setH();setTimeout('init();', 10);" onresize="setH();" scroll="no"><![endif]-->
<!--[if gte IE 7]><body onload="init();" scroll="auto"><![endif]-->
<!--[if IE]><![if !IE]><![endif]--><body onload="init();"><!--[if IE]><![endif]><![endif]-->
<div id="wrapper"><div style="text-align:center; width:128px; font-weight:bold"><a href="/"><img src="/images/web/logo.gif" alt="Hunters House logo" width="128" height="72" title="" /></a><br /><?php
	//Get number of pages
	$count = $mysqli->fetch_array('SELECT count(id) as count FROM sider');
	echo($count[0]['count']-katsup(-1)); ?> emner</div>
    <ul id="crumbs"><li><a href="/">Forside</a><?php
	if(@$GLOBALS['generatedcontent']['crumbs']) {
		foreach($GLOBALS['generatedcontent']['crumbs'] as $value) {
			?><ul><li><b style="font-size:16px">-&gt;</b><a href="<?php echo($value['link']); ?>"> <?php
			echo($value['name']);
			if($value['icon']) {
				?> <img src="<?php echo($value['icon']); ?>" alt="" /><?php
			}
			?></a><?php
		}
		foreach($GLOBALS['generatedcontent']['crumbs'] as $value) {
			?></li></ul><?php
		}
	}
?></li></ul></div><div id="text"><a name="top"></a><?php

if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	echo($GLOBALS['generatedcontent']['text']);
	
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'page') {

  ?><div id="innercontainer"><?php
  if($GLOBALS['generatedcontent']['datetime']) {
	  ?><div id="date"><?php echo(date('d-m-Y H:i:s', $GLOBALS['generatedcontent']['datetime'])); ?></div><?php
	}
  ?><h1><?php
	echo(htmlspecialchars($GLOBALS['generatedcontent']['headline']));
  ?></h1><?php

	echo($GLOBALS['generatedcontent']['text']);
	  ?></div><?php
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'product') {

  ?><div id="innercontainer"><div id="date"><?php echo(date('d-m-Y H:i:s', $GLOBALS['generatedcontent']['datetime'])); ?></div><h1><?php
	echo(htmlspecialchars($GLOBALS['generatedcontent']['headline']));
	if($GLOBALS['generatedcontent']['serial']) {
	?> <span style="font-weight:normal; font-size:13px">Varenr.: <?php echo($GLOBALS['generatedcontent']['serial']); ?></span><?php
	}
  ?></h1><?php
  

	echo($GLOBALS['generatedcontent']['text']);

	?><p style="text-align:center"><?php
	echo_pris($GLOBALS['generatedcontent']['price']['now'], $GLOBALS['generatedcontent']['price']['befor'], $GLOBALS['generatedcontent']['price']['from'], $GLOBALS['generatedcontent']['price']['market']);
	?><br />
	<span class="web"><a href="mailto:<?php echo($GLOBALS['generatedcontent']['email']); ?>?subject=Angiv emne:" class="Pris">Kontakt os via e-mail</a></span> <span class="Pris, print"><?php echo($GLOBALS['generatedcontent']['email']); ?></span></p>
    </div><?php
  
	if(@$GLOBALS['generatedcontent']['accessories']) {
		?><p align="center" style="clear:both">Tilbehør</p>
		<table cellspacing="0" id="liste"><?php
		$i = 0;
		$nr = count($GLOBALS['generatedcontent']['accessories'])-1;
		foreach($GLOBALS['generatedcontent']['accessories'] as $value) {
			if($i % 2 == 0) {
				?><tr><?php
			}
				?><td><a href="<?php echo($value['link']); ?>"><?php echo($value['name']); 
				if($value['icon']) {
					?><br /><img src="<?php echo($value['icon']); ?>" alt="<?php echo(htmlspecialchars($value['name'], NULL, 'UTF-8')); ?>" title="" /><?php
				}
				?></a><?php
			?></td><?php
			if($i % 2 || $i == $nr) {
				?></tr><?php
			}
			$i++;
		}
		?></table><?php
	}
  
	if(isset($GLOBALS['generatedcontent']['brands'])) {
		?><p align="center" style="clear:both">Se andre produkter af samme mærke</p>
		<table cellspacing="0" id="liste"><?php
		$i = 0;
		$nr = count($GLOBALS['generatedcontent']['brands'])-1;
		foreach($GLOBALS['generatedcontent']['brands'] as $value) {
			if($i % 2 == 0) {
				?><tr><?php
			}

			?><td><a href="<?php echo($value['link']); ?>"><?php echo($value['name']); 
			if($value['icon']) {
				?><br /><img src="<?php echo($value['icon']); ?>" alt="<?php echo(htmlspecialchars($value['name'], NULL, 'UTF-8')); ?>" title="" /><?php
			}
		
			?></a><?php
			?></td><?php
			
			if($i % 2 || $i == $nr) {
				?></tr><?php
			}
			$i++;
		}
		?></table><?php
	}
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'tiles' || $GLOBALS['generatedcontent']['contenttype'] == 'list' || $GLOBALS['generatedcontent']['contenttype'] == 'brand') {

	if($GLOBALS['generatedcontent']['contenttype'] == 'brand') {
		?><p align="center"><?php
        if($GLOBALS['generatedcontent']['brand']['xlink']) {
			?><a rel="nofollow" target="_blank" href="<?php echo($GLOBALS['generatedcontent']['brand']['xlink']); ?>">Læs mere om <?php
		}
		echo($GLOBALS['generatedcontent']['brand']['name']); 
		if($GLOBALS['generatedcontent']['brand']['icon']) {
			?><br /><img src="<?php echo($GLOBALS['generatedcontent']['brand']['icon']); ?>" alt="<?php echo(htmlspecialchars($GLOBALS['generatedcontent']['brand']['name'], NULL, 'UTF-8')); ?>" title="" /><?php
		}
        if($GLOBALS['generatedcontent']['brand']['xlink']) {
			?></a><?php
		}
        ?></p><?php
	}
	
	if(@$GLOBALS['generatedcontent']['list']) {

		?><p align="center" class="web">Klik på produktet for yderligere information</p><?php

		if($GLOBALS['generatedcontent']['contenttype'] == 'tiles') {
			?><table cellspacing="0" id="liste"><?php
			$i = 0;
			$nr = count($GLOBALS['generatedcontent']['list'])-1;
			foreach($GLOBALS['generatedcontent']['list'] as $value) {
				if($i % 2 == 0) {
					?><tr><?php
				}
					?><td><a href="<?php echo($value['link']); ?>"><img src="<?php echo($value['icon'] ? $value['icon'] : '/images/web/intet-foto.jpg'); ?>" alt="<?php echo(htmlspecialchars($value['name'], NULL, 'UTF-8')); ?>" title="" /><br /><?php echo($value['name']); ?><br /><?php
					echo_pris($value['price']['now'], $value['price']['befor'], $value['price']['from'], $value['price']['market']);
					?></a></td><?php
				
				if($i % 2 || $i == $nr) {
					?></tr><?php
				}
				$i++;
			}
			?></table><?php
		} else {
			?><div id="kat<?php echo($GLOBALS['generatedcontent']['activmenu']); ?>"><table class="tabel"><thead><tr>
<td><a href="#" onclick="x_get_kat('<?php echo($GLOBALS['generatedcontent']['activmenu']); ?>', 'navn', inject_html);">Titel</a></td>
<td><a href="#" onclick="x_get_kat('<?php echo($GLOBALS['generatedcontent']['activmenu']); ?>', 'for', inject_html);">Før</a></td>
<td><a href="#" onclick="x_get_kat('<?php echo($GLOBALS['generatedcontent']['activmenu']); ?>', 'pris', inject_html);">Pris</a></td>
<td><a href="#" onclick="x_get_kat('<?php echo($GLOBALS['generatedcontent']['activmenu']); ?>', 'varenr', inject_html);">#</a></td>
</tr></thead><tbody><?php
			$i = 0;
			foreach($GLOBALS['generatedcontent']['list'] as $value) {
				?><tr<?php
				if($i % 2)
					echo(' class="altrow"');
                ?>><td><a href="<?php echo($value['link']); ?>"><?php echo($value['name']); ?></a></td><?php
				?><td class="XPris" align="right"><?php if($value['price']['befor']) echo(number_format($value['price']['befor'], 0, '', '.').',-'); ?></td><?php
				?><td class="Pris" align="right"><?php if($value['price']['now']) echo(number_format($value['price']['now'], 0, '', '.').',-'); ?></td><?php
				?><td align="right" style="font-size:11px"><?php echo($value['serial']); ?></td></tr><?php
				$i++;
			}
			?></tbody></table></div><?php
		}
		
	} else {
		?><p align="center" class="web">Søgningen gav intet resultat</p><?php
	}
	
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'search') {
  ?><div id="innercontainer">
  <h1>Søg</h1><?php
	echo($GLOBALS['generatedcontent']['text']);
  ?></div><?php
}

?></div>
<script src="/theme/ieupdate.js" type="text/javascript"></script>
<div id="menu"><?php

echo_menu($GLOBALS['generatedcontent']['menu']);

if(isset($GLOBALS['generatedcontent']['search_menu']))
	echo_menu($GLOBALS['generatedcontent']['search_menu']);

?><ul>
<li><a href="http://www.geoffanderson.com/" target="_blank">Geoff Anderson</a></li>
<li><a href="/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=">S&oslash;g og find</a></li>
</ul>
</div><?php
//TODO also use https if we are being tunneled threw a https site (pay.scannet.dk).
if(@$_SERVER['HTTPS'] != 'on' && @$_GET['step'] != 3) { 
	?><script src="http://www.google-analytics.com/ga.js" type="text/javascript"></script><?php
} else {
	?><script src="https://ssl.google-analytics.com/ga.js" type="text/javascript"></script><?php
}
?><script type="text/javascript"><!--
try {
var pageTracker = _gat._getTracker("UA-1037075-3");

var referrer = '<?php if(@$_SERVER['HTTP_REFERER']) echo($_SERVER['HTTP_REFERER']); ?>';
if(document.referrer == '' && referrer != '') {
	pageTracker._setReferrerOverride(referrer);
}
pageTracker._setDomainName("www.huntershouse.dk");
pageTracker._setAllowLinker(true);
pageTracker._trackPageview();
<?php
if(!empty($GLOBALS['generatedcontent']['track'])) echo($GLOBALS['generatedcontent']['track']);
?>
} catch(err) {}
--></script></body>
</body>
</html>
