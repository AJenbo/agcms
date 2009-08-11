<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo($GLOBALS['generatedcontent']['title']); ?></title>
<meta http-equiv="page-enter" content="blendTrans(Duration=0)" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link href="/theme/style.css" rel="stylesheet" type="text/css" />
<link href="/theme/style.css" rel="stylesheet" type="text/css" />
<!--[if lt IE 8]><style type="text/css" media="print">body {overflow-y: auto;}</style><![endif]-->
<link href="/theme/handheld.css" rel="stylesheet" type="text/css" media="handheld" />
<!--[if IE]><![if !IE]><![endif]-->
<style type="text/css">
@import url("/theme/handheld.css") all and (max-device-width: 748px);
</style>
<!--[if IE]><![endif]><![endif]-->
<meta id="viewport" name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=no;" />
<script type="text/javascript"><!--
if(/(NetFront|PlayStation|hiptop|IEMobile|Smartphone|iPhone|Opera Mobi|Opera Mini|BlackBerry|Series60)/i.test(navigator.userAgent))
	document.write('<link href="/theme/handheld.css" rel="stylesheet" type="text/css" />');
--></script>
<link href="/theme/print.css" rel="stylesheet" type="text/css" media="print" />
<script src="/javascript/serialize.js" type="text/javascript"></script>
<script src="/javascript/javascript.js" type="text/javascript"></script>
<script src="/theme/javascript.js" type="text/javascript"></script>
<link rel="alternate" type="application/rss+xml" title="Geoff Anderson updates" href="/rss.php" />
<link title="Geoff Anderson" type="application/opensearchdescription+xml" rel="search" href="/sog.php" />
<meta http-equiv="content-language" content="en" />
<meta name="Description" content="" />
<meta name="Author" content="Anders Jenbo" />
<meta name="Classification" content="" />
<?php if(@$GLOBALS['generatedcontent']['keywords']) echo('<meta name="Keywords" content="'.$GLOBALS['generatedcontent']['keywords'].'" />'); ?>
<meta name="Reply-to" content="info@geoffanderson.com" />
<meta name="Revisit-after" content="14 days" />
<meta http-equiv="imagetoolbar" content="no" />
<meta name="distribution" content="Global" />
<meta name="robots" content="index,follow" />
</head>
<body>
<div id="wrapper">
<div id="headder"> <a href="/"><img id="logo" src="/images/web/logo.gif" alt="Geoff Anderson" title="" /></a><img style="margin:24px 0 0 0" title="" alt="Clothing with a difference" src="/images/web/clothing-with-a-difference.png" /> 
<div id="headlines"><?php
	foreach($GLOBALS['generatedcontent']['sider'] as $side) {
		?><a href="<?php echo($side['link']); ?>"><?php echo($side['name']); ?></a> <?php
	}
?><img src="/images/web/gb.png" alt="English" title="English" /> <a href="http://www.geoffanderson.at/" rel="nofollow"><img src="/images/web/de.png" alt="German" title="German" /></a></div>
</div>
<div id="text"><?php
		
function echo_vare_tile($vare) {
	?><td class="vare"><?php
	if($vare['icon']) {
		?><div><a href="<?php echo($vare['link']); ?>">&nbsp;<img title="" alt="<?php echo(htmlspecialchars($vare['name'])); ?>" src="<?php echo($vare['icon']); ?>"/></a></div><?php
	}
	?>
    <h2><a href="<?php echo($vare['link']); ?>"><?php echo($vare['name']); ?></a></h2>
    <a href="<?php echo($vare['link']); ?>"><img class="web" src="/images/web/info.gif" alt="Info" title="" /></a>
	</td><?php
}

if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	?><marquee style="position:static" loop="-1" scrollamount="1" truespeed scrolldelay="15" onmouseover="this.scrollAmount=0" onmouseout="this.scrollAmount=1"><?php
	$special = $mysqli->fetch_array("SELECT `text` FROM `special` WHERE `id` = 2 LIMIT 1");
	echo($special[0]['text']);
	?></marquee><?php
	/*
	$files = scandir($_SERVER['DOCUMENT_ROOT'].'/images/front-shift/');
	?><img src="<?php echo('/images/front-shift/'.$files[array_rand($files)]); ?>" alt="" /><?php
	*/
	//echo(@$GLOBALS['generatedcontent']['text']);
	?><object id="flashshifter" width="575" height="575" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" align="middle">
		<param name="allowScriptAccess" value="sameDomain" />
		<param name="movie" value="/theme/flashshifter.swf" />
		<param name="allowFullScreen" value="true" />
		<param name="quality" value="high" />
		<param name="bgcolor" value="#FFFFFF" />
		<embed id="flashshifterns" src="/theme/flashshifter.swf" width="575" height="575" bgcolor="#FFFFFF" name="flash" quality="high" align="middle" allowscriptaccess="sameDomain" allowfullscreen="true" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</embed>
	</object><?php
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'product') {

	?><h1><?php echo(htmlspecialchars($GLOBALS['generatedcontent']['headline'])); ?></h1><?php

	echo($GLOBALS['generatedcontent']['text']);
	
	if(@$GLOBALS['generatedcontent']['requirement']['link']) {
		?><a id="krav" href="<?php echo($GLOBALS['generatedcontent']['requirement']['link']); ?>" onclick="return openkrav('<?php echo($GLOBALS['generatedcontent']['requirement']['link']); ?>')" target="krav"><img src="/images/web/advarsel.gif" alt="" title="" width="54" height="47" /><br /> <?php
		echo($GLOBALS['generatedcontent']['requirement']['name']);
        ?></a><?php
	}
	?><p style="text-align:right"><?php
	if($GLOBALS['generatedcontent']['serial']) {
	    ?> <strong>Varenr: <?php echo($GLOBALS['generatedcontent']['serial']); ?></strong><br /><?php
	}
	?></p><?php
	if(@$GLOBALS['generatedcontent']['brands']) {
		?> <p style="text-align:center">Se andre produkter af samme mærke</p>
                        <table cellspacing="0" id="liste"><?php
		$i = 0;
		$nr = count($GLOBALS['generatedcontent']['brands']);
		foreach($GLOBALS['generatedcontent']['brands'] as $value) {
			if(!$i % 3) {
				?><tr><?php
			}
			echo_vare_tile($value);
			if($i % 3 == 2 || $i+1 == $nr) {
				?></tr><?php
			}
			$i++;
		}
		?></table><?php
	}
  
	if($accessories_nr = count(@$GLOBALS['generatedcontent']['accessories'])) {
		?>
                    <p style="text-align:center">Tilbehør</p>
                    <table cellspacing="0" id="liste">
                        <?php
		for($i=0;$i<$accessories_nr;$i++) {
			if($i % 3 == 0) {
				?><tr><?php
			}
			echo_vare_tile($GLOBALS['generatedcontent']['accessories'][$i]);
			if($i % 3 == 2 || $i+1 == $accessories_nr) {
				?></tr><?php
			}
		}
		?></table><?php
	}
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'page') {

	?><h1><?php echo(htmlspecialchars($GLOBALS['generatedcontent']['headline'])); ?></h1><?php

	echo($GLOBALS['generatedcontent']['text']);
	
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'tiles' || $GLOBALS['generatedcontent']['contenttype'] == 'brand') {

	$GLOBALS['generatedcontent']['list'] = array_natsort($GLOBALS['generatedcontent']['list'], 'id', 'date', 'desc');
		
	if($GLOBALS['generatedcontent']['contenttype'] == 'brand') {
		?><p align="center"><?php
        if($GLOBALS['generatedcontent']['brand']['xlink']) {
			?><a rel="nofollow" target="_blank" href="<?php echo($GLOBALS['generatedcontent']['brand']['xlink']); ?>">Læs mere om<?php
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
		?><p style="text-align:center;" class="web">No content was found</p><?php
	}
	
	if($list_nr) {
		?><table cellspacing="0" id="liste"><?php
		for($i=0;$i<$list_nr;$i++) {
			if($i % 3 == 0) {
				?><tr><?php
			}
			echo_vare_tile($GLOBALS['generatedcontent']['list'][$i]);
			if($i % 3 == 2 || $i+1 == $list_nr) {
				?></tr><?php
			}
		}
		?></table><?php
	}
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'search') {
    ?><div><h1>Søg</h1><?php
	echo($GLOBALS['generatedcontent']['text']);
  ?></div><?php
}
if(isset($GLOBALS['generatedcontent']['datetime']) && $GLOBALS['generatedcontent']['datetime']) {
	?><div id="date">Updated <?php echo(date('l, d M Y H:i', $GLOBALS['generatedcontent']['datetime'])); ?></div><?php
}
?></div><div id="menu"><?php
	if(@$GLOBALS['generatedcontent']['menu'])
		foreach($GLOBALS['generatedcontent']['menu'] as $value) {
			?><div><a href="<?php echo($value['link']); ?>"<?php
				if($value['id'] == @$GLOBALS['generatedcontent']['activmenu'] || $value['subs']) {
					echo(' class="main_active"');
				}
				if($value['id'] == @$GLOBALS['generatedcontent']['activmenu']) {
					echo(' id="activmenu"');
				}
				?>><?php echo($value['name']); ?></a></div><?php
			if($value['subs']) {
				if(@$value['subs']) {
					foreach($value['subs'] as $valuesubs) {
						?>
			<div><a href="<?php echo($valuesubs['link']); ?>"<?php
						if($valuesubs['id'] == $GLOBALS['generatedcontent']['activmenu'] || $valuesubs['subs']) {
							?> class="active" id="activmenu"<?php
						}

						?>><?php echo($valuesubs['name']); ?></a></div><?php
					}
				}
			}
		}

?></div>
</div><?php
if($GLOBALS['generatedcontent']['contenttype'] != 'front') {
	?><marquee loop="-1" scrollamount="1" truespeed scrolldelay="15" onmouseover="this.scrollAmount=0" onmouseout="this.scrollAmount=1"><?php
	$special = $mysqli->fetch_array("SELECT `text` FROM `special` WHERE `id` = 2 LIMIT 1");
	echo($special[0]['text']);
	?></marquee><?php
}
//TODO also use https if we are being tunneled threw a https site (pay.scannet.dk).
if(@$_SERVER['HTTPS'] != 'on') { 
	?><script src="http://www.google-analytics.com/ga.js" type="text/javascript"></script><?php
} else {
	?><script src="https://ssl.google-analytics.com/ga.js" type="text/javascript"></script><?php
}

?><script type="text/javascript"><!--
var pageTracker = _gat._getTracker("UA-1037075-6");

var referrer = '<?php if(@$_SERVER['HTTP_REFERER']) echo($_SERVER['HTTP_REFERER']); ?>';
if(document.referrer == '' && referrer != '') {
	pageTracker._setReferrerOverride(referrer);
}
pageTracker._setAllowLinker(true);
pageTracker._trackPageview();
--></script><noscript>
<img style="display:none;" src="http://www.google-analytics.com/__utm.gif?utmwv=4.3.1&utmn=<?php
echo(rand(0, 2147483647));
?>&utmhn=<?php
echo($_SERVER['HTTP_HOST']);
?>&utmcs=utf-8&utmsr=-&utmsc=-&utmul=<?php
$browserLanguage = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
$browserLanguage = explode(';', $browserLanguage[0]);
echo($browserLanguage[0] ? $browserLanguage[0] : '-');
?>&utmje=0&utmfl=-<?php
//TODO investigate the true cause
if(!$_COOKIE['__utma'] || !$_COOKIE['__utmb'] || !$_COOKIE['__utmc'] || !$_COOKIE['__utmz'])
	echo('&utmcn=1');
?>&utmdt=<?php
echo(rawurlencode($GLOBALS['generatedcontent']['title']));
?>&utmhid=<?php
//Random number for this window
echo(rand(0, 2147483647));
?>&utmr=<?php
echo(!preg_match('/'.$_SERVER['HTTP_HOST'].'/', $_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-');
?>&utmp=<?php
echo($_SERVER['REQUEST_URI']);
//utmcid
//utmgclid
//utmctr
//utmcct


function strcryptnumber($string) {
	$number = 1;
	if ($string && $string != "-") {
		$number = 0;
		for($i = strlen($string)-1; $i >= 0; $i--) {
			$carcode = ord($string[$i]);
			$number = ($number << 6 & 268435455) + $carcode + ($carcode << 14);
			$c = $number & 266338304;
			$number = ($c != 0 ? $number ^ $c >> 21 : $number);
		}
	}
	return $number;
}

//$_COOKIE
$orgtime = time();
$lasttime = time();
$time = time();
$__utmc = strcryptnumber(strtolower(preg_replace('/www[.]/', '', parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST))));

//TODO get values from cookie
$__utma[0] = $__utmc;

foreach($_COOKIE as $key => $value) {
	$temp[] = $key.'='.$value;
}
$temp = $_SERVER['HTTP_USER_AGENT'].'x'.implode('; ', $temp).$_SERVER['HTTP_REFERER'];
$__utma[1] = sprintf("%.0f", (rand(0, 2147483647) ^ strcryptnumber($temp)) * 2147483647);
unset($temp);

$__utma[2] = $orgtime;
//TODO $__utma[3] = $__utma[4];
$__utma[3] = $lasttime;
$__utma[4] = time();
//TODO ++ for each session
$__utma[5] = 1;

$__utmb[0] = $__utmc;
//TODO ++ for each visit
$__utmb[1] = $__utmb[1] ? $__utmb[1] : 0;
//TODO how to change $__utmb[2]
$__utmb[2] = $__utmb[2] ? $__utmb[2] : 10;
$__utmb[3] = $__utmb[3] ? $__utmb[3] : $__utma[4];


$__utma = implode('.', $__utma);
$__utmb = implode('.', $__utmb);
//TODO set values from cookie
/*
$__utmk
$__utmv
$__utmx
$GASO
*/

$__utmz = $__utmc.'.'.$orgtime.'.1.1.'
	. 'utmcsr='.($_SERVER['HTTP_HOST'] && !preg_match('/'.$_SERVER['HTTP_HOST'].'/', $_SERVER['HTTP_REFERER']) ? preg_replace('/www[.]/', '', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)) : '(direct)')
	.'|utmccn='.($_SERVER['HTTP_HOST'] && !preg_match('/'.$_SERVER['HTTP_HOST'].'/', $_SERVER['HTTP_REFERER']) ? '(referral)' : '(direct)')
	.'|utmcmd='.($_SERVER['HTTP_HOST'] && !preg_match('/'.$_SERVER['HTTP_HOST'].'/', $_SERVER['HTTP_REFERER']) ? 'referral' : '(none)');
	if(!preg_match('/'.$_SERVER['HTTP_HOST'].'/', $_SERVER['HTTP_REFERER']))
		$__utmz .= '|utmcct='.parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);

?>&utmac=UA-1037075-6&utmcc=<?php echo(rawurlencode('__utma='.$__utma.';+__utmz='.$__utmz.';')); ?>" alt="Google Analytics" /><?php print($_SERVER['HTTP_REFERER']); ?>
</noscript><img style="display:none;" src="/images/web/button-selected.gif" alt="" /></body></html>