<?php
/**
 * Theme file, responsible for outputting the generated content
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     http://www.arms-gallery.dk/
 */

/**
 * Print price and offers
 *
 * @param float $price  Current price
 * @param float $before Past price
 * @param int   $from   What type is the curren price
 * @param int   $should What type is the past price
 *
 * @return null
 */
function echoPrice($price, $before, $from, $should)
{
    if ($before) {
        if ($should == 1) {
            ?>Retail price: <span><?php
        } elseif ($should == 2) {
            ?>Should cost: <span><?php
        } else {
            ?>Before: <span class="XPris"><?php
        }
        echo str_replace(',00', ',-', number_format($before, 2, ',', '.'));
        ?></span><?php
    }

    if ($price) {
        if ($from == 1 && $before) {
            ?> <span class="NyPris">New price from: <?php
        } elseif ($from == 2 && $before) {
            ?> <span class="NyPris">Used: <?php
        } elseif ($from == 1) {
            ?> Price from: <span class="Pris"><?php
        } elseif ($from == 2) {
            ?> Used: <span class="Pris"><?php
        } elseif ($before) {
            ?> <span class="NyPris">Now: <?php
        } else {
            ?> Price: <span class="Pris"><?php
        }
        echo str_replace(',00', ',-', number_format($price, 2, ',', '.'));
        ?></span><?php
    }
}

/**
 * Print the menu
 *
 * @param array $menu Menu items as stored in generatedcontent
 *
 * @return null
 */
function echoMenu($menu)
{
    if ($menu) {
        ?><ul><?php
        foreach ($menu as $value) {
            ?><li><?php
            if ($value['id'] == @$GLOBALS['generatedcontent']['activmenu']) {
                ?><h4 id="activmenu"><?php
            }
            ?><a href="<?php
            echo $value['link'];
            ?>"><?php
            echo $value['name'];
            if ($value['icon']) {
                ?> <img src="<?php
                echo $value['icon'];
                ?>" alt="" /><?php
            }
            ?></a><?php

            if ($value['id'] == @$GLOBALS['generatedcontent']['activmenu']) {
                ?></h4><?php
            }
            if (!empty($value['subs'])) {
                echoMenu($value['subs']);
            }
            ?></li><?php
        }
        ?></ul><?php
    }
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php
echo $GLOBALS['generatedcontent']['title'];
?></title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link href="/theme/style.css" rel="stylesheet" type="text/css" />
<script src="/javascript/json2.stringify.js" type="text/javascript"></script>
<script src="/javascript/json_stringify.js" type="text/javascript"></script>
<script src="/javascript/json_parse_state.js" type="text/javascript"></script>
<script src="/javascript/sajax.js" type="text/javascript"></script>
<script src="/javascript/javascript.js" type="text/javascript"></script>
<link rel="alternate" type="application/rss+xml" title="News" href="/rss.php" />
<link title="Search" type="application/opensearchdescription+xml" rel="search" href="/sog.php" />
<meta name="author" content="Anders Jenbo" />
<?php
if (@$GLOBALS['generatedcontent']['keywords']) {
    echo '<meta name="Keywords" content="' . $GLOBALS['generatedcontent']['keywords']
        . '" />';
}
?>
</head>
<body>
<div id="wrapper">
    <ul id="crumbs"><li><a href="/">Home</a><?php
if (@$GLOBALS['generatedcontent']['crumbs']) {
    foreach ($GLOBALS['generatedcontent']['crumbs'] as $value) {
        ?><ul><li><b style="font-size:16px">-&gt;</b><a href="<?php
        echo $value['link'];
        ?>"> <?php
        echo $value['name'];
        if ($value['icon']) {
            ?> <img src="<?php
            echo $value['icon'];
            ?>" alt="" /><?php
        }
        ?></a><?php
    }
    foreach ($GLOBALS['generatedcontent']['crumbs'] as $value) {
        ?></li></ul><?php
    }
}

if (!empty($_SESSION['faktura']['quantities'])) {
    ?><div class="bar" id="cart">
    <ul><li><a href="/bestilling/">Shopping basket</a></li></ul>
    </div><?php
}

?></li></ul></div><div id="text"><a name="top"></a><?php

if ($GLOBALS['generatedcontent']['contenttype'] == 'front') {
    echo $GLOBALS['generatedcontent']['text'];

} elseif ($GLOBALS['generatedcontent']['contenttype'] == 'page') {

    ?><div id="innercontainer"><?php
    if ($GLOBALS['generatedcontent']['datetime']) {
        ?><div id="date"><?php
        echo date('d-m-Y H:i:s', $GLOBALS['generatedcontent']['datetime']);
        ?></div><?php
    }
    ?><h1><?php
    echo htmlspecialchars($GLOBALS['generatedcontent']['headline']);
    ?></h1><?php

    echo $GLOBALS['generatedcontent']['text'];
    ?></div><?php
} elseif ($GLOBALS['generatedcontent']['contenttype'] == 'product') {
    ?><div id="innercontainer"><div id="date"><?php
    echo date('d-m-Y H:i:s', $GLOBALS['generatedcontent']['datetime']);
    ?></div><h1><?php
    echo htmlspecialchars($GLOBALS['generatedcontent']['headline']);
    if ($GLOBALS['generatedcontent']['serial']) {
        ?> <span style="font-weight:normal; font-size:13px">SKU: <?php
        echo $GLOBALS['generatedcontent']['serial'];
        ?></span><?php
    }
    ?></h1><?php


    echo $GLOBALS['generatedcontent']['text'];

    if (@$GLOBALS['generatedcontent']['requirement']['link']) {
        ?><p><a href="<?php
        echo $GLOBALS['generatedcontent']['requirement']['link'];
        ?>" target="krav"><?php
        echo $GLOBALS['generatedcontent']['requirement']['name'];
        ?></a></p><?php
    }

    ?><p style="text-align:center"><?php
    echoPrice(
        $GLOBALS['generatedcontent']['price']['now'],
        $GLOBALS['generatedcontent']['price']['before'],
        $GLOBALS['generatedcontent']['price']['from'],
        $GLOBALS['generatedcontent']['price']['market']
    );
    if ($GLOBALS['generatedcontent']['price']['now']) {
        ?> <a href="/bestilling/?add=<?php
        echo $GLOBALS['side']['id'];
        ?>">+ Add to shopping cart</a> <?php
    }
    ?><br /></p>
    </div><?php

    if (@$GLOBALS['generatedcontent']['accessories']) {
        ?><p align="center" style="clear:both">Accessories</p>
        <table cellspacing="0" id="liste"><?php
        $i = 0;
        $nr = count($GLOBALS['generatedcontent']['accessories']) - 1;
        foreach ($GLOBALS['generatedcontent']['accessories'] as $value) {
            if ($i % 2 == 0) {
                ?><tr><?php
            }
            ?><td><a href="<?php
            echo $value['link'];
            ?>"><?php
            echo $value['name'];
            if ($value['icon']) {
                ?><br /><img src="<?php
                echo $value['icon'];
                ?>" alt="<?php
                echo htmlspecialchars($value['name'], null, 'UTF-8');
                ?>" title="" /><?php
            }
            ?></a><?php
            ?></td><?php
            if ($i % 2 || $i == $nr) {
                ?></tr><?php
            }
            $i++;
        }
        ?></table><?php
    }

    if (isset($GLOBALS['generatedcontent']['brands'])) {
        ?><p align="center" style="clear:both">View other product from the same brand</p>
        <table cellspacing="0" id="liste"><?php
        $i = 0;
        $nr = count($GLOBALS['generatedcontent']['brands'])-1;
        foreach ($GLOBALS['generatedcontent']['brands'] as $value) {
            if ($i % 2 == 0) {
                ?><tr><?php
            }

            ?><td><a href="<?php
            echo $value['link'];
            ?>"><?php
            echo $value['name'];
            if ($value['icon']) {
                ?><br /><img src="<?php
                echo $value['icon'];
                ?>" alt="<?php
                echo htmlspecialchars($value['name'], null, 'UTF-8');
                ?>" title="" /><?php
            }

            ?></a><?php
            ?></td><?php

            if ($i % 2 || $i == $nr) {
                ?></tr><?php
            }
            $i++;
        }
        ?></table><?php
    }
} elseif ($GLOBALS['generatedcontent']['contenttype'] == 'tiles'
    || $GLOBALS['generatedcontent']['contenttype'] == 'list'
    || $GLOBALS['generatedcontent']['contenttype'] == 'brand'
) {

    if ($GLOBALS['generatedcontent']['contenttype'] == 'brand') {
        ?><p align="center"><?php
        if ($GLOBALS['generatedcontent']['brand']['xlink']) {
            ?><a rel="nofollow" target="_blank" href="<?php
            echo $GLOBALS['generatedcontent']['brand']['xlink'];
            ?>">Read more about <?php
        }
        echo $GLOBALS['generatedcontent']['brand']['name'];
        if ($GLOBALS['generatedcontent']['brand']['icon']) {
            ?><br /><img src="<?php
            echo $GLOBALS['generatedcontent']['brand']['icon'];
            ?>" alt="<?php
            echo htmlspecialchars(
                $GLOBALS['generatedcontent']['brand']['name'],
                null,
                'UTF-8'
            );
            ?>" title="" /><?php
        }
        if ($GLOBALS['generatedcontent']['brand']['xlink']) {
            ?></a><?php
        }
        ?></p><?php
    }

    if (@$GLOBALS['generatedcontent']['list']) {

        ?><p align="center" class="web">Click on the product for additional information</p><?php

        if ($GLOBALS['generatedcontent']['contenttype'] == 'tiles') {
            ?><table cellspacing="0" id="liste"><?php
            $i = 0;
            $nr = count($GLOBALS['generatedcontent']['list'])-1;
            foreach ($GLOBALS['generatedcontent']['list'] as $value) {
                if ($i % 2 == 0) {
                    ?><tr><?php
                }
                ?><td><a href="<?php
                echo $value['link'];
                ?>"><?php
                if ($value['icon']) {
                    ?><img src="<?php
                    echo $value['icon'];
                    ?>" alt="<?php
                    echo htmlspecialchars($value['name'], null, 'UTF-8');
                    ?>" title="" /><br /><?php
                }
                echo $value['name'];
                ?><br /><?php
                echoPrice(
                    $value['price']['now'],
                    $value['price']['before'],
                    $value['price']['from'],
                    $value['price']['market']
                );
                ?></a></td><?php

                if ($i % 2 || $i == $nr) {
                    ?></tr><?php
                }
                $i++;
            }
            ?></table><?php
        } else {
            ?><div id="kat<?php
            echo $GLOBALS['generatedcontent']['activmenu'];
            ?>"><table class="tabel"><thead><tr>
            <td><a href="#" onClick="x_get_kat('<?php
            echo $GLOBALS['generatedcontent']['activmenu'];
            ?>', 'navn', inject_html);">Title</a></td>
            <td><a href="#" onClick="x_get_kat('<?php
            echo $GLOBALS['generatedcontent']['activmenu'];
            ?>', 'for', inject_html);">Previously</a></td>
            <td><a href="#" onClick="x_get_kat('<?php
            echo $GLOBALS['generatedcontent']['activmenu'];
            ?>', 'pris', inject_html);">Price</a></td>
            <td><a href="#" onClick="x_get_kat('<?php
            echo $GLOBALS['generatedcontent']['activmenu'];
            ?>', 'varenr', inject_html);">#</a></td>
            </tr></thead><tbody><?php
            $i = 0;
            foreach ($GLOBALS['generatedcontent']['list'] as $value) {
                ?><tr<?php
                if ($i % 2) {
                    echo ' class="altrow"';
                }
                ?>><td><a href="<?php
                echo $value['link'];
                ?>"><?php
                echo $value['name'];
                ?></a></td><?php
                ?><td class="XPris" align="right"><?php
                if ($value['price']['before']) {
                    echo number_format($value['price']['before'], 0, '', '.') . ',-';
                }
                ?></td><td class="Pris" align="right"><?php
                if ($value['price']['now']) {
                    echo number_format($value['price']['now'], 0, '', '.') . ',-';
                }
                ?></td><td align="right" style="font-size:11px"><?php
                echo $value['serial'];
                ?></td></tr><?php
                $i++;
            }
            ?></tbody></table></div><?php
        }

    } else {
        ?><p align="center" class="web">The search did not return any results</p><?php
    }

} elseif ($GLOBALS['generatedcontent']['contenttype'] == 'search') {
    ?><div id="innercontainer"><h1>Search</h1><?php
    echo $GLOBALS['generatedcontent']['text'];
    ?></div><?php
}

?></div>
<div id="menu"><?php

if (isset($GLOBALS['generatedcontent']['menu'])) {
    echoMenu($GLOBALS['generatedcontent']['menu']);
}

if (isset($GLOBALS['generatedcontent']['search_menu'])) {
    echoMenu($GLOBALS['generatedcontent']['search_menu']);
}

?></div>
</body>
</html>
