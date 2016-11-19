<?php
/**
 * Theme file, responsible for outputting the generated content
 * @license  MIT http://opensource.org/licenses/MIT
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
function echoPrice(float $price, float $before, int $from, int $should)
{
    if ($before) {
        if ($should == 1) {
            echo 'Retail price: <span>';
        } elseif ($should == 2) {
            echo 'Should cost: <span>';
        } else {
            echo 'Before: <span class="XPris">';
        }
        echo str_replace(',00', ',-', number_format($before, 2, ',', '.')) . '</span>';
    }

    if ($price) {
        if ($from == 1 && $before) {
            echo ' <span class="NyPris">New price from: ';
        } elseif ($from == 2 && $before) {
            echo ' <span class="NyPris">Used: ';
        } elseif ($from == 1) {
            echo ' Price from: <span class="Pris">';
        } elseif ($from == 2) {
            echo ' Used: <span class="Pris">';
        } elseif ($before) {
            echo ' <span class="NyPris">Now: ';
        } else {
            echo ' Price: <span class="Pris">';
        }
        echo str_replace(',00', ',-', number_format($price, 2, ',', '.')) . '</span>';
    }
}

/**
 * Print the menu
 *
 * @param array $menu Menu items as stored in generatedcontent
 *
 * @return null
 */
function echoMenu(array $menu, Category $activeCategory = null)
{
    if ($menu) {
        echo '<ul>';
        foreach ($menu as $value) {
            echo '<li>';
            if ($activeCategory && $value['id'] == $activeCategory->getId()) {
                echo '<h4 id="activmenu">';
            }
            echo '<a href="' . xhtmlEsc($value['link']) . '">' . $value['name'];
            if ($value['icon']) {
                echo ' <img src="' . xhtmlEsc($value['icon']) . '" alt="" />';
            }
            echo '</a>';

            if ($activeCategory && $value['id'] == $activeCategory->getId()) {
                echo '</h4>';
            }
            if (!empty($value['subs'])) {
                echoMenu($value['subs']);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head><title><?php
echo xhtmlEsc(self::$title);
?></title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link href="/theme/style.css" rel="stylesheet" type="text/css" />
<script src="/javascript/json2.stringify.js" type="text/javascript"></script>
<script src="/javascript/json_stringify.js" type="text/javascript"></script>
<script src="/javascript/json_parse_state.js" type="text/javascript"></script>
<script src="/javascript/sajax.js" type="text/javascript"></script>
<script src="/javascript/javascript.js" type="text/javascript"></script>
<link rel="alternate" type="application/rss+xml" title="News" href="/rss.php" />
<link title="Search" type="application/opensearchdescription+xml" rel="search" href="/sog.php" /><?php
if (self::$canonical) {
    echo '<link rel="canonical" href="' . xhtmlEsc(self::$canonical) . '" />';
}
if (self::$keywords) {
    echo '<meta name="Keywords" content="' . xhtmlEsc(implode(', ', self::$keywords)) . '" />';
}
?></head><body><div id="wrapper"><ul id="crumbs"><li><a href="/">Home</a><?php

foreach (self::$crumbs as $crumb) {
    echo '<ul><li><b style="font-size:16px">-&gt;</b><a href="' . xhtmlEsc($crumb['link']) . '"> ' . xhtmlEsc($crumb['name']);
    if ($crumb['icon']) {
        echo '<img src="' . xhtmlEsc($crumb['icon']) . '" alt="" />';
    }
    echo '</a>';
}
foreach (self::$crumbs as $crumb) {
    echo '</li></ul>';
}

if (!empty($_SESSION['faktura']['quantities'])) {
    echo '<div class="bar" id="cart"><ul><li><a href="/bestilling/">Shopping basket</a></li></ul></div>';
}

?></li></ul></div><div id="text"><a name="top"></a><?php

if (in_array(self::$pageType, ['front', 'custome'], true)) {
    echo self::$bodyHtml;
} elseif (self::$pageType === 'page') {
    echo '<div id="innercontainer">';
    if (self::$timeStamp) {
        echo '<div id="date">' . date('d-m-Y H:i:s', self::$timeStamp) . '</div>';
    }
    echo '<h1>' . xhtmlEsc(self::$headline) . '</h1>' . self::$bodyHtml . '</div>';
} elseif (self::$pageType === 'product') {
    echo '<div id="innercontainer"><div id="date">'
        . date('d-m-Y H:i:s', self::$timeStamp)
        . '</div><h1>' . xhtmlEsc(self::$headline);
    if (self::$serial) {
        echo ' <span style="font-weight:normal; font-size:13px">SKU: ' . xhtmlEsc(self::$serial) . '</span>';
    }
    echo '</h1>' . self::$bodyHtml;

    $requirement = self::$activePage->getRequirement();
    if ($requirement) {
        echo '<p><a href="/' . xhtmlEsc($requirement->getSlug()) . '" target="krav">'
            . xhtmlEsc($requirement->getTitle()) . '</a></p>';
    }
    echo '<p style="text-align:center">';
    echoPrice(
        self::$price['now'],
        self::$price['before'],
        self::$price['from'],
        self::$price['market']
    );
    if (self::$price['now']) {
        echo ' <a href="/bestilling/?add=' . self::$activePage->getId() . '">+ Add to shopping cart</a> ';
    }
    echo '<br /></p></div>';

    if (self::$accessories) {
        echo '<p align="center" style="clear:both">Accessories</p><table cellspacing="0" id="liste">';
        $i = 0;
        $nr = count(self::$accessories) - 1;
        foreach (self::$accessories as $value) {
            if ($i % 2 == 0) {
                echo '<tr>';
            }
            echo '<td><a href="' . xhtmlEsc($value['link']) . '">' . xhtmlEsc($value['name']);
            if ($value['icon']) {
                echo '<br /><img src="' . xhtmlEsc($value['icon']) . '" alt="' . xhtmlEsc($value['name']) . '" title="" />';
            }
            echo '</a></td>';
            if ($i % 2 || $i == $nr) {
                echo '</tr>';
            }
            $i++;
        }
        echo '</table>';
    }

    if (self::$brand) {
        echo '<p align="center" style="clear:both">View other product from the same brand</p><a href="'
            . xhtmlEsc(self::$brand['link']) . '">'. xhtmlEsc(self::$brand['name']);
        if (self::$brand['icon']) {
            echo '<br /><img src="' . xhtmlEsc(self::$brand['icon']) . '" alt="' . xhtmlEsc(self::$brand['name']) . '" title="" />';
        }
        echo '</a>';
    }
} elseif (in_array(self::$pageType, ['tiles', 'list'], true)) {
    if (self::$brand) {
        echo '<p align="center">';
        if (self::$brand['xlink']) {
            echo '<a rel="nofollow" target="_blank" href="' . xhtmlEsc(self::$brand['xlink']) . '">Read more about ';
        }
        echo xhtmlEsc(self::$brand['name']);
        if (self::$brand['icon']) {
            echo '<br /><img src="' . xhtmlEsc(self::$brand['icon']) . '" alt="'
                . xhtmlEsc(self::$brand['name']) . '" title="" />';
        }
        if (self::$brand['xlink']) {
            echo '</a>';
        }
        echo '</p>';
    }

    if (self::$pageList) {
        echo '<p align="center" class="web">Click on the product for additional information</p>';

        if (self::$pageType === 'list') {
            echo '<div id="kat' . self::$activeCategory->getId()
                . '"><table class="tabel"><thead><tr><td><a href="#" onClick="x_get_kat("'
                . self::$activeCategory->getId()
                . ', \'navn\', inject_html);">Title</a></td><td><a href="#" onClick="x_get_kat(\''
                . self::$activeCategory->getId()
                . '\', \'for\', inject_html);">Previously</a></td><td><a href="#" onClick="x_get_kat(\''
                . self::$activeCategory->getId()
                . '\', \'pris\', inject_html);">Price</a></td><td><a href="#" onClick="x_get_kat(\''
                . self::$activeCategory->getId()
                . '\', \'varenr\', inject_html);">#</a></td></tr></thead><tbody>';
            $altRow = false;
            foreach (self::$pageList as $items) {
                echo '<tr';
                if ($altRow) {
                    echo ' class="altrow"';
                }
                echo '><td><a href="' . xhtmlEsc($items['link']) . '">' . xhtmlEsc($items['name'])
                    . '</a></td><td class="XPris" align="right">';
                if ($items['price']['before']) {
                    echo number_format($items['price']['before'], 0, '', '.') . ',-';
                }
                echo '</td><td class="Pris" align="right">';
                if ($items['price']['now']) {
                    echo number_format($items['price']['now'], 0, '', '.') . ',-';
                }
                echo '</td><td align="right" style="font-size:11px">' . xhtmlEsc($items['serial']) . '</td></tr>';
                $altRow = !$altRow;
            }
            echo '</tbody></table></div>';
        } else {
            echo '<table cellspacing="0" id="liste">';
            $i = 0;
            $nr = count(self::$pageList) - 1;
            foreach (self::$pageList as $items) {
                if ($i % 2 === 0) {
                    echo '<tr>';
                }
                echo '<td><a href="' . xhtmlEsc($items['link']) . '">';
                if ($items['icon']) {
                    echo '<img src="' . xhtmlEsc($items['icon']) . '" alt="' . xhtmlEsc($items['name']) . '" title="" /><br />';
                }
                echo $items['name'] . '<br />';
                echoPrice(
                    $items['price']['now'],
                    $items['price']['before'],
                    $items['price']['from'],
                    $items['price']['market']
                );
                echo '</a></td>';

                if ($i % 2 || $i == $nr) {
                    echo '</tr>';
                }
                $i++;
            }
            echo '</table>';
        }
    } else {
        echo '<p align="center" class="web">The search did not return any results</p>';
    }
} elseif (self::$pageType === 'search') {
    echo '<div id="innercontainer"><h1>Search</h1>' . self::$bodyHtml . '</div>';
}

?></div><div id="menu"><?php

echoMenu(self::$menu, self::$activeCategory);
echoMenu(self::$searchMenu);

?></div></body></html>
