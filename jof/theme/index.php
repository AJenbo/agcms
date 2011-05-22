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
	$menu_nr = count($menu);
	if($menu_nr) {
		?><ul><?php
		for($i=0;$i<$menu_nr;$i++) {
			?><li<?php
			if(!empty($menu[$i]['subs'])) {
				?> class="open"<?php
			} elseif(!empty($menu[$i]['sub'])) {
				?> class="close"<?php
			} elseif($menu[$i]['id'] == $GLOBALS['generatedcontent']['activmenu']) {
				?> class="activ"<?php
			}
			?>><a href="<?php echo($menu[$i]['link']); ?>"><?php
			if(!empty($menu[$i]['icon'])) {
				?><img src="<?php echo($menu[$i]['icon']); ?>" alt="" /> <?php
			}
			echo($menu[$i]['name']);
			?></a></li><?php
			
			if(!empty($menu[$i]['subs'])) {
				?><li style="display:inline"><?php
					echo_menu($menu[$i]['subs']);
				?></li><?php
			}
		}
		?></ul><?php
	}
}
header('Content-Type: text/html; charset=utf-8');
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
<link rel="alternate" type="application/rss+xml" title="Nyheder p&aring; Jagt og Fiskerimagasinet" href="/rss.php" />
<link title="Jagt og Fiskerimagasinet" type="application/opensearchdescription+xml" rel="search" href="/sog.php" />
<meta http-equiv="content-language" content="da" />
<meta name="Description" content="Alt du har brug for i frilufts livet" />
<meta name="Author" content="Anders Jenbo" />
<meta name="Classification" content="Jagt og fiskeri" />
<?php if(@$GLOBALS['generatedcontent']['keywords']) echo('<meta name="Keywords" content="'.$GLOBALS['generatedcontent']['keywords'].'" />'); ?>
<meta name="Reply-to" content="mail@jagtogfiskerimagasinet.dk" />
<meta name="Revisit-after" content="14 days" />
<meta http-equiv="imagetoolbar" content="no" />
<meta name="distribution" content="Global" />
<meta name="robots" content="index,follow" />
</head><?php
if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	?><!--[if lt IE 8]><body scroll="auto"><![endif]--><?php
} else {
	?><!--[if lt IE 7]><body onload="setH();setH();" onresize="setH();" scroll="auto"><![endif]-->
	<!--[if IE 7]><body scroll="auto"><![endif]--><?php
}
?>
<!--[if IE]><![if gte IE 8]><![endif]--><body><!--[if IE]><![endif]><![endif]-->
<div id="wrapper"><?php

if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	?><a href="/"><img src="/images/web/jagt-og-fiskerimagasinet-logo-hover.gif" alt="Jagt og Fiskerimagasinet" width="148" height="37" id="jof" title="" /></a><?php
} else {
	?><a href="/" onmouseout="setImg('jof','/images/web/jagt-og-fiskerimagasinet-logo.png')" onmouseover="setImg('jof','/images/web/jagt-og-fiskerimagasinet-logo-hover.gif')"><img src="/images/web/jagt-og-fiskerimagasinet-logo.png" alt="Jagt og Fiskerimagasinet" width="148" height="37" id="jof" title="" /></a><?php
}

?>
  <h2><?php
	foreach($GLOBALS['generatedcontent']['menu'] as $value) {
		if($value['id'] == @$GLOBALS['generatedcontent']['activmenu'] || $value['subs']) {
	  		?><a style="color:#e0e0e0" href="<?php echo($value['link']); ?>"><img src="/images/web/link-a.gif" alt="" width="32" height="16" /> <?php echo(trim($value['name'])); ?></a> <?php
		} else {
	  		?><a onmouseout="setImg('fpc<?php echo($value['id']); ?>','/images/web/link.gif')" onmouseover="setImg('fpc<?php echo($value['id']); ?>','/images/web/link-a.gif')" href="<?php echo($value['link']); ?>"><img src="/images/web/link.gif" alt="" width="32" height="16" id="fpc<?php echo($value['id']); ?>" /> <?php echo(trim($value['name'])); ?></a> <?php
		}
    }
	?><?php

if($GLOBALS['generatedcontent']['contenttype'] == 'search' || ((!@$GLOBALS['generatedcontent']['activmenu'] || $GLOBALS['generatedcontent']['activmenu'] == -1) && $GLOBALS['generatedcontent']['contenttype'] != 'front')) {
	?><a href="/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=" style="color:#e0e0e0"><img src="/images/web/link-a.gif" alt="" width="32" height="16" id="sog" /> Søg</a><?php
} else {
	?><a href="/?sog=1&amp;q=&amp;sogikke=&amp;minpris=&amp;maxpris=&amp;maerke=" onmouseover="setImg('sog','/images/web/link-a.gif')" onmouseout="setImg('sog','/images/web/link.gif')"><img src="/images/web/link.gif" alt="" width="32" height="16" id="sog" /> Søg</a><?php
}

?></h2>
  <img src="/images/web/mainfade-l.gif" alt="" width="15" height="15" id="mainfade-l" /><img src="/images/web/mainfade-r.gif" alt="" width="17" height="15" id="mainfade-r" /></div>
<div id="text"<?php

if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	?> class="h510"<?php
} else {
	?> style="bottom:0px"<?php
}

?>><?php


if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	?><img src="/images/web/main.jpg" alt="" width="620" height="510" style="display:block;" /><?php
	
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'page') {

  ?><div id="innercontainer"><div id="date"><?php echo(date('d-m-Y H:i:s', $GLOBALS['generatedcontent']['datetime'])); ?></div>
  <h1><?php
	echo(htmlspecialchars($GLOBALS['generatedcontent']['headline']));
  ?></h1><?php

	echo($GLOBALS['generatedcontent']['text']);
	  ?></div><?php
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'product') {

  ?><div id="innercontainer"><div id="date"><?php echo(date('d-m-Y H:i:s', $GLOBALS['generatedcontent']['datetime'])); ?></div>
  <h1><?php
	echo(htmlspecialchars($GLOBALS['generatedcontent']['headline']));
  ?></h1><?php

	echo($GLOBALS['generatedcontent']['text']);

if(
	(
		//If it has a value
		$GLOBALS['generatedcontent']['price']['now'] > 0 || $GLOBALS['generatedcontent']['price']['befor'] > 0
	) && (
		//if it is not a from price that has a table
		!@$GLOBALS['generatedcontent']['has_product_table']
	)
) {
	?><p style="text-align:center"><?php
	echo_pris($GLOBALS['generatedcontent']['price']['now'], $GLOBALS['generatedcontent']['price']['befor'], $GLOBALS['generatedcontent']['price']['from'], $GLOBALS['generatedcontent']['price']['market']);
	?><br />
	<img src="/images/web/nocolors.gif" alt="" width="196" height="5" /><br />
	<span class="web"><a href="/bestilling/?add=<?php echo($GLOBALS['side']['id']); ?>" class="Pris" style="color:#000;">Tilføj til indkøbsliste</a></span><?php } ?><span class="Pris, print">mail@jagtogfiskerimagasinet.dk</span></p>
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
					?><br /><img src="<?php echo($value['icon']); ?>" alt="<?php echo($value['name']); ?>" title="" /><?php
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
				?><br /><img src="<?php echo($value['icon']); ?>" alt="<?php echo($value['name']); ?>" title="" /><?php
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
} elseif($GLOBALS['generatedcontent']['contenttype'] == 'tiles' || $GLOBALS['generatedcontent']['contenttype'] == 'brand') {

	if($GLOBALS['generatedcontent']['contenttype'] == 'brand') {
		?><p align="center"><?php
        if($GLOBALS['generatedcontent']['brand']['xlink']) {
			?><a rel="nofollow" target="_blank" href="<?php echo($GLOBALS['generatedcontent']['brand']['xlink']); ?>">Læs mere om <?php
		}
		echo($GLOBALS['generatedcontent']['brand']['name']); 
		if($GLOBALS['generatedcontent']['brand']['icon']) {
			?><br /><img src="<?php echo($GLOBALS['generatedcontent']['brand']['icon']); ?>" alt="<?php echo($GLOBALS['generatedcontent']['brand']['name']); ?>" title="" /><?php
		}
        if($GLOBALS['generatedcontent']['brand']['xlink']) {
			?></a><?php
		}
        ?></p><?php
	}
	
	if(@$GLOBALS['generatedcontent']['list']) {

		?><p align="center" class="web">Klik på produktet for yderligere information</p><?php


		?><table cellspacing="0" id="liste"><?php
		$i = 0;
		$nr = count($GLOBALS['generatedcontent']['list'])-1;
		foreach($GLOBALS['generatedcontent']['list'] as $value) {
			if($i % 2 == 0) {
				?><tr><?php
			}
				?><td><a href="<?php echo($value['link']); ?>"><?php echo($value['name']); ?><br /><img src="<?php echo($value['icon'] ? $value['icon'] : '/images/web/intet-foto.jpg'); ?>" alt="<?php echo($value['name']); ?>" title="" /></a><br /><?php
                echo_pris($value['price']['now'], $value['price']['befor'], $value['price']['from'], $value['price']['market']);
                ?></td><?php
			
			if($i % 2 || $i == $nr) {
				?></tr><?php
			}
			$i++;
		}
		?></table><?php
		
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
<div id="menu"<?php

if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	?> class="h510"<?php
} else {
	?> style="bottom:0px"<?php
}

?>><a name="top"></a>
	<?php

if(@$_SESSION['faktura']['quantities']) {
	?><a style="display:inline;margin-left:5px;" href="/bestilling/"><img alt="" src="/theme/images/cart.png" /> Indkøbsliste</a><br /><br /><?php
}

if($GLOBALS['generatedcontent']['contenttype'] == 'front') {
	echo($GLOBALS['generatedcontent']['text']);
} elseif($GLOBALS['generatedcontent']['contenttype'] != 'front') {
	foreach($GLOBALS['generatedcontent']['menu'] as $menu) {
		if($menu['subs']) {
			?><span class="print" style="font-weight:bold"><?php echo($menu['name']); ?><br />
			</span><?php
				echo_menu($menu['subs']);
		}
	}
	if(isset($GLOBALS['generatedcontent']['search_menu']))
		echo_menu($GLOBALS['generatedcontent']['search_menu']);
}

?>
</div>
<div style="display:none"><img alt="" src="/images/web/jagt-og-fiskerimagasinet-logo.png" /> <img alt="" src="/images/web/jagt-og-fiskerimagasinet-logo-hover.gif" /> <img alt="" src="/images/web/link-a.gif" /> <img alt="" src="/images/web/close-h.gif" /><img alt="" src="/images/web/dod-h.gif" /></div><?php
//TODO also use https if we are being tunneled threw a https site (pay.scannet.dk).
if(@$_SERVER['HTTPS'] != 'on') { 
	?><script src="http://www.google-analytics.com/ga.js" type="text/javascript"></script><?php
} else {
	?><script src="https://ssl.google-analytics.com/ga.js" type="text/javascript"></script><?php
}
?><script type="text/javascript"><!--
try {
var pageTracker = _gat._getTracker("UA-1037075-1");

var referrer = '<?php if(@$_SERVER['HTTP_REFERER']) echo($_SERVER['HTTP_REFERER']); ?>';
if(document.referrer == '' && referrer != '') {
	pageTracker._setReferrerOverride(referrer);
}
pageTracker._addIgnoredOrganic("jof.dk");
pageTracker._addIgnoredOrganic("www.jof.dk");
pageTracker._addIgnoredOrganic("jogf.dk");
pageTracker._addIgnoredOrganic("www.jogf.dk");
pageTracker._addIgnoredOrganic("jagtogfiskerimagasinet.dk");
pageTracker._addIgnoredOrganic("www.jagtogfiskerimagasinet.dk");
pageTracker._setDomainName("www.jagtogfiskerimagasinet.dk");
pageTracker._setAllowLinker(true);
pageTracker._trackPageview();
<?php
if(!empty($GLOBALS['generatedcontent']['track'])) echo($GLOBALS['generatedcontent']['track']);
?>
} catch(err) {}
--></script></body>
</html>
