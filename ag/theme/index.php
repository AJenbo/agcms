<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo($GLOBALS['generatedcontent']['title']); ?></title>
<meta http-equiv="page-enter" content="blendTrans(Duration=0)" />
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
<!--[if lt IE 7]><script type="text/javascript" src="/theme/ie6fix.js"></script><![endif]-->
<!--[if IE]><![if gte IE 7]><![endif]--><script type="text/javascript" src="/theme/menufix.js"></script><!--[if IE]><![endif]><![endif]-->
<script type="text/javascript" src="/theme/javascript.js"></script>
<link rel="alternate" type="application/rss+xml" title="Nyheder i Arms Gallery" href="/rss.php" />
<link title="Arms Gallery" type="application/opensearchdescription+xml" rel="search" href="/sog.php" />
<meta http-equiv="content-language" content="da" />
<meta name="Description" content="" />
<meta name="Author" content="Anders Jenbo" />
<meta name="Classification" content="" />
<?php if(@$GLOBALS['generatedcontent']['keywords']) echo('<meta name="Keywords" content="'.$GLOBALS['generatedcontent']['keywords'].'" />'); ?>
<meta name="Reply-to" content="mail@arms-gallery.dk" />
<meta name="Revisit-after" content="14 days" />
<meta http-equiv="imagetoolbar" content="no" />
<meta name="distribution" content="Global" />
<meta name="robots" content="index,follow" />
</head>
<!--[if lt IE 7]><body onload="init();" onresize="setH();" scroll="no"><![endif]-->
<!--[if gte IE 7]><body onload="init();" scroll="auto"><![endif]-->
<!--[if IE]><![if !IE]><![endif]--><body onload="init();"><!--[if IE]><![endif]><![endif]-->
<div id="headder">
  <div class="clearfix"><a href="/"><img id="logo" src="/images/web/logo.gif" alt="Arms Gallery" width="428" title="" /></a>
    <form action="/" method="get">
      <input name="q" /><input name="sogikke" type="hidden" /><input name="minpris" type="hidden" /><input name="maxpris" type="hidden" /><input name="maerke" type="hidden" value="0" />
      <a href="" onclick="this.parentNode.submit(); return false;"><img src="/images/web/søg.gif" alt="Søg" width="15" height="15" title="Søg" /></a><br />
      <a href="/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=" class="note">Avanceret søgning.</a>
    </form>
  </div>
</div>
<div class="bar" id="crumb">
  <ul>
    <li><a href="/">Forside</a></li><?php
	if(@$GLOBALS['generatedcontent']['crumbs'])
		foreach($GLOBALS['generatedcontent']['crumbs'] as $value) {
			?><li> &gt; <a href="<?php echo($value['link']); ?>"><?php echo($value['name']); ?></a></li><?php
		}
    ?>
  </ul>
</div>
<div class="bar" id="submenu"><?php
	if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
		if($sider_nr = count($GLOBALS['generatedcontent']['sider'])) {
			?><ul><?php
			for($i=0;$i<$sider_nr;$i++) {
				?><li><a href="<?php echo($GLOBALS['generatedcontent']['sider'][$i]['link']); ?>"><?php echo($GLOBALS['generatedcontent']['sider'][$i]['name']); ?></a><?php
				if($i<$sider_nr-1) {
					?> <img src="/images/web/bar-space.gif" alt="|" title="" /> <?php
				}
				?></li><?php
			}
			?></ul><?php
		}
	}
	
	function echo_menuOverLvl2($menu) {
		$menu_nr = count($menu);
		for($i=0;$i<$menu_nr;$i++) {
			?><li><a href="<?php echo($menu[$i]['link']); ?>"><?php echo($menu[$i]['name']); ?></a><?php
			if($i<$menu_nr-1) {
				?> <img src="/images/web/bar-space.gif" alt="|" title="" /> <?php
			}
			?></li><?php
			if($menu[$i]['subs']) {
				echo_menuOverLvl2($menu[$i]['subs']);
			}
		}
	}
//	['menu'][$]['subs'][$]['subs'][$]['subs']
	$menu_nr = count($GLOBALS['generatedcontent']['menu']);
	for($i=0;$i<$menu_nr;$i++) {
		if($menu_nr2 = count($GLOBALS['generatedcontent']['menu'][$i]['subs'])) {
			for($i2=0;$i2<$menu_nr2;$i2++) {
				if($GLOBALS['generatedcontent']['menu'][$i]['subs'][$i2]['subs']) {
					?><ul><?php
						echo_menuOverLvl2($GLOBALS['generatedcontent']['menu'][$i]['subs'][$i2]['subs']);
					?></ul><?php
				}
				
			}
		}
	}
?></div>
<div id="container">
  <div id="container2">
    <div id="container3">
      <div id="container4">
        <div id="content"><?php
		
function echo_vare_tile($vare) {
	?><td><div class="vare"><h2><a href="<?php echo($vare['link']); ?>"><?php echo($vare['name']); ?></a></h2><?php
	if($vare['icon']) {
		?><div style="text-align:center"><a href="<?php echo($vare['link']); ?>"><img style="margin:10px 0" title="" alt="<?php echo(htmlspecialchars($vare['name'])); ?>" src="<?php echo($vare['icon']); ?>" /></a></div><?php
	}
	?><div style="padding:5px;"><?php echo(@$vare['text']); ?></div>
   <a href="<?php echo($vare['link']); ?>"><img class="laemere web" src="/images/web/info.gif" alt="Læs mere" width="83" height="15" /></a><div style="padding:5px; text-align:right;"><?php echo(@$vare['price']['now'] ? '<strong>'.str_replace(',00', ',-', number_format($vare['price']['now'], 2, ',', '.')).'</strong>' : '&nbsp;'); ?></div>
    </div></td><?php
}

if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	?><div id="text"><?php
	echo(@$GLOBALS['generatedcontent']['text']);
	?></div><?php
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'product') {

	?><div id="text" class="clearfix"><div id="date"><?php echo(date('d-m-Y H:i:s', $GLOBALS['generatedcontent']['datetime'])); ?></div>
  <h1><?php
	echo(htmlspecialchars($GLOBALS['generatedcontent']['headline']));
  ?></h1><?php

	echo($GLOBALS['generatedcontent']['text']);
	
	if($GLOBALS['generatedcontent']['price']['befor']) {
		?><div id="spar">Spar<br /><?php
		echo(round(100/$GLOBALS['generatedcontent']['price']['befor']*($GLOBALS['generatedcontent']['price']['befor']-$GLOBALS['generatedcontent']['price']['now'])));
        ?>%</div><?php
	}
	if($GLOBALS['generatedcontent']['requirement']['link']) {
		?><a id="krav" href="<?php echo($GLOBALS['generatedcontent']['requirement']['link']); ?>" onclick="return openkrav('<?php echo($GLOBALS['generatedcontent']['requirement']['link']); ?>')" target="krav"><img src="/images/web/advarsel.gif" alt="" title="" width="54" height="47" /><br /><?php
		echo($GLOBALS['generatedcontent']['requirement']['name']);
        ?></a><?php
	}
	?><p style="text-align:right"><?php
	if($GLOBALS['generatedcontent']['serial']) {
	    ?><strong>Varenr: <?php echo($GLOBALS['generatedcontent']['serial']); ?></strong><br /><?php
	}

	//Skriv prisen og tilbudet
	if($GLOBALS['generatedcontent']['price']['befor']) {
		if($GLOBALS['generatedcontent']['price']['market'] == 2) {
			?>Burde koste: <?php echo(str_replace(',00', ',-', number_format($GLOBALS['generatedcontent']['price']['befor'], 2, ',', '.')));
		} elseif($GLOBALS['generatedcontent']['price']['market'] == 1) {
			?>Vejledende: <?php echo(str_replace(',00', ',-', number_format($GLOBALS['generatedcontent']['price']['befor'], 2, ',', '.')));
		} else {
			?>Før: <span class="xpris"><?php
			 echo(str_replace(',00', ',-', number_format($GLOBALS['generatedcontent']['price']['befor'], 2, ',', '.')));
			?></span><?php
		}
	}

	if($GLOBALS['generatedcontent']['price']['now']) {
		if($GLOBALS['generatedcontent']['price']['from'] == 1 && $GLOBALS['generatedcontent']['price']['befor']) {
			?> <span class="nypris">Nu fra: <?php
		} elseif($GLOBALS['generatedcontent']['price']['from'] == 1) {
			?> Fra: <span class="pris"><?php
		} elseif($GLOBALS['generatedcontent']['price']['from'] == 2 && $GLOBALS['generatedcontent']['price']['befor']) {
			?> <span class="nypris">Brugt: <?php
		} elseif($GLOBALS['generatedcontent']['price']['from'] == 2) {
			?> Brugt: <span class="pris"><?php
		} elseif($GLOBALS['generatedcontent']['price']['befor']) {
			?> <span class="nypris">Nu: <?php
		} else {
			?> Pris: <span class="pris"><?php
		}
		echo(str_replace(',00', ',-', number_format($GLOBALS['generatedcontent']['price']['now'], 2, ',', '.'))); ?></span><?php 
	}
	
	?></p><?php
    
  
	if(@$GLOBALS['generatedcontent']['brands']) {
		?><p align="center" style="clear:both">Se andre produkter af samme mærke</p>
		<table cellspacing="0" class="liste"><?php
		$i = 0;
		$nr = count($GLOBALS['generatedcontent']['brands']);
		foreach($GLOBALS['generatedcontent']['brands'] as $value) {
			if(!$i % 2) {
				?><tr><?php
			}
			echo_vare_tile($value);
			if($i % 2 || $i+1 == $nr) {
				?></tr><?php
			}
			$i++;
		}
		?></table><?php
	}
	
	?></div><?php
  
	if($accessories_nr = count(@$GLOBALS['generatedcontent']['accessories'])) {
		?><p align="center" style="clear:both">Tilbehør</p>
		<table cellspacing="0" class="liste"><?php
		for($i=0;$i<$accessories_nr;$i++) {
			if($i % 2 == 0) {
				?><tr><?php
			}
			echo_vare_tile($GLOBALS['generatedcontent']['accessories'][$i]);
			if($i % 2 || $i == $accessories_nr-1) {
				?></tr><?php
			}
		}
		?></table><?php
	}
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'page') {

	?><div id="text" class="clearfix"><div id="date"><?php echo(date('d-m-Y H:i:s', $GLOBALS['generatedcontent']['datetime'])); ?></div>
  <h1><?php
	echo(htmlspecialchars($GLOBALS['generatedcontent']['headline']));
  ?></h1><?php

	echo($GLOBALS['generatedcontent']['text']);
	
	?></div><?php
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'tiles' || $GLOBALS['generatedcontent']['contenttype'] == 'brand') {

	if($GLOBALS['generatedcontent']['contenttype'] == 'brand') {
		?><p align="center"><?php
        if($GLOBALS['generatedcontent']['brand']['xlink']) {
			?><a rel="nofollow" target="_blank" href="<?php echo($GLOBALS['generatedcontent']['brand']['xlink']); ?>">Læs mere om <?php
		}
		echo($GLOBALS['generatedcontent']['brand']['name']); 
		if($GLOBALS['generatedcontent']['brand']['icon']) {
			?><br /><img src="<?php echo($GLOBALS['generatedcontent']['brand']['icon']); ?>" alt="<?php echo(htmlspecialchars($GLOBALS['generatedcontent']['brand']['name'])); ?>" title="" /><?php
		}
        if($GLOBALS['generatedcontent']['brand']['xlink']) {
			?></a><?php
		}
        ?></p><?php
	}
	
	if(!$list_nr = count(@$GLOBALS['generatedcontent']['list'])) {
		?><p align="center" class="web">Søgningen gav intet resultat</p><?php
	}
	
	if($list_nr) {
		?><table cellspacing="0" class="liste"><?php
		for($i=0;$i<$list_nr;$i++) {
			if($i % 2 == 0) {
				?><tr><?php
			}
			echo_vare_tile($GLOBALS['generatedcontent']['list'][$i]);
			if($i % 2 || $i == $list_nr-1) {
				?></tr><?php
			}
		}
		?></table><?php
	}
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'search') {
          ?><div id="text"><?php
  ?><div style="width:587px;padding-left:16px">
  <h1>Søg</h1><?php
	echo($GLOBALS['generatedcontent']['text']);
  ?></div></div><script src="/theme/ieupdate.js" type="text/javascript"></script><?php
}

?></div>
        <marquee loop="-1" scrollamount="1" truespeed scrolldelay="15" onmouseover="this.scrollAmount=0" onmouseout="this.scrollAmount=1"><?php
		$special = $mysqli->fetch_array("SELECT `text`, UNIX_TIMESTAMP(dato) AS dato FROM `special` WHERE `id` = 2 LIMIT 1");
		echo($special[0]['text']);
        ?></marquee>
        <div id="footer"> Nybrogade 26 - 30, 1203 København K, 3311 8338, E-mail: <a href="mailto:Arms Gallery &lt;mail@arms-gallery.dk&gt;"><strong>mail@arms-gallery.dk</strong></a> SE13081387.<br />
          <span>Alle priser er inkl. 25% moms. Der tages forbehold for:<br />
          Udsolgte varer, trykfejl og prisændringer, samt forhold vi ikke er herre over.</span></div>
      </div>
      <div id="menu"><?php
	 	if(@$GLOBALS['generatedcontent']['menu'])
			foreach($GLOBALS['generatedcontent']['menu'] as $value) {
				?><a href="<?php echo($value['link']); ?>"<?php
					if($value['id'] == @$GLOBALS['generatedcontent']['activmenu'] || $value['subs']) {
						echo(' class="main_active"');
					} else {
						?> class="main"<?php
					}
					if($value['id'] == @$GLOBALS['generatedcontent']['activmenu']) {
						echo(' id="activmenu"');
					}
					?>><?php echo($value['name']); ?></a><?php
				if($value['subs']) {
					if(@$value['subs']) {
						foreach($value['subs'] as $valuesubs) {
							?><a href="<?php echo($valuesubs['link']); ?>"<?php
							if($valuesubs['id'] == $GLOBALS['generatedcontent']['activmenu'] || $valuesubs['subs']) {
								?> class="active" id="activmenu"<?php
							}
							?>><?php echo($valuesubs['name']); ?></a><?php
						}
					}
				}
			}
  

?></div></div></div></div><?php
//TODO also use https if we are being tunneled threw a https site (pay.scannet.dk).
if(@$_SERVER['HTTPS'] != 'on') { 
	?><script src="http://www.google-analytics.com/ga.js" type="text/javascript"></script><?php
} else {
	?><script src="https://ssl.google-analytics.com/ga.js" type="text/javascript"></script><?php
}
?><script type="text/javascript"><!--
try {
var pageTracker = _gat._getTracker("UA-1037075-2");

var referrer = '<?php if(@$_SERVER['HTTP_REFERER']) echo($_SERVER['HTTP_REFERER']); ?>';
if(document.referrer == '' && referrer != '') {
	pageTracker._setReferrerOverride(referrer);
}
pageTracker._setDomainName("arms-gallery.dk");
pageTracker._setAllowLinker(true);
pageTracker._trackPageview();
<?php
if(!empty($GLOBALS['generatedcontent']['track'])) echo($GLOBALS['generatedcontent']['track']);
?>
} catch(err) {}
--></script>
</body>
</html>
