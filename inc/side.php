<?php

function side()
{
    global $mysqli;

    if (!isset($GLOBALS['side']['navn'])) {
        $sider = $mysqli->fetch_array("SELECT `navn`,`burde`,`fra`,`text`,`pris`,`for`,`krav`,`maerke`, varenr, UNIX_TIMESTAMP(dato) AS dato FROM sider WHERE id = ".$GLOBALS['side']['id']);

        if (!$sider) {
            header('HTTP/1.1 404 Not Found');
            //TODO lav en søgning
        }
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

    $GLOBALS['generatedcontent']['headline'] = $GLOBALS['side']['navn'];
    $GLOBALS['generatedcontent']['serial'] = $GLOBALS['side']['varenr'];
    $GLOBALS['generatedcontent']['datetime'] = $GLOBALS['side']['dato'];
    $GLOBALS['generatedcontent']['text'] = $GLOBALS['side']['text'];

    if ($GLOBALS['side']['krav']) {
        $krav = $mysqli->fetch_array("SELECT navn FROM krav WHERE id = ".$GLOBALS['side']['krav']);

        getUpdateTime('krav');

        $GLOBALS['generatedcontent']['requirement']['icon'] = '';
        $GLOBALS['generatedcontent']['requirement']['name'] = $krav[0]['navn'];
        $GLOBALS['generatedcontent']['requirement']['link'] = '/krav/'.$GLOBALS['side']['krav'].'/'.clear_file_name($krav[0]['navn']).'.html';
    }

    $GLOBALS['generatedcontent']['price']['befor'] = $GLOBALS['side']['for'];
    $GLOBALS['generatedcontent']['price']['now'] = $GLOBALS['side']['pris'];
    $GLOBALS['generatedcontent']['price']['from'] = $GLOBALS['side']['fra'];
    $GLOBALS['generatedcontent']['price']['market'] = $GLOBALS['side']['burde'];

    unset($GLOBALS['side']['text']);

    //TODO Pump all this in to an array instead of dumping a bunch of html
    //TODO and figure out how to do the sorting ajax and js style
    $GLOBALS['generatedcontent']['text'] .= echo_table($GLOBALS['side']['id'], null, 'asc');

    $GLOBALS['generatedcontent']['price']['old'] = $GLOBALS['side']['for'];
    $GLOBALS['generatedcontent']['price']['market'] = $GLOBALS['side']['burde'];
    $GLOBALS['generatedcontent']['price']['new'] = $GLOBALS['side']['pris'];
    $GLOBALS['generatedcontent']['price']['from'] = $GLOBALS['side']['fra'];

    if (!@$GLOBALS['generatedcontent']['email']) {
        $kat = $mysqli->fetch_array("SELECT `email` FROM `kat` WHERE id = ".$GLOBALS['generatedcontent']['activmenu']);
    }

    getUpdateTime('kat');

    if (!@$kat[0]['email']) {
        $GLOBALS['generatedcontent']['email'] = $GLOBALS['_config']['email'];
    } else {
        $GLOBALS['generatedcontent']['email'] = $kat[0]['email'];
    }

    if ($GLOBALS['side']['maerke']) {
        $maerker = $mysqli->fetch_array("SELECT `id`, `navn`, `link`, `ico` FROM `maerke` WHERE `id` IN(".$GLOBALS['side']['maerke'].") AND `ico` != '' ORDER BY `navn`");
        $temp = $mysqli->fetch_array("SELECT `id`, `navn`, `link`, `ico` FROM `maerke` WHERE `id` IN(".$GLOBALS['side']['maerke'].") AND `ico` = '' ORDER BY `navn`");
        $maerker = array_merge($maerker, $temp);

        getUpdateTime('maerke');

        foreach ($maerker as $value) {
            $GLOBALS['generatedcontent']['brands'][] = array('name' => $value['navn'],
            'link' => '/mærke'.$value['id'].'-'.clear_file_name($value['navn']).'/',
            'xlink' => $value['link'],
            'icon' => $value['ico']);
        }
    }

    $tilbehor = $mysqli->fetch_array("SELECT sider.id, bind.kat, sider.`navn` , billed, `burde` , `fra` , `pris` , `for` , UNIX_TIMESTAMP( dato ) AS dato FROM tilbehor JOIN sider ON tilbehor.tilbehor = sider.id JOIN bind ON bind.side = sider.id WHERE tilbehor.`side` = ".$GLOBALS['side']['id']);

    getUpdateTime('tilbehor');
    getUpdateTime('sider');

    foreach ($tilbehor as $value) {
        if ($value['kat']) {
            $kat = $mysqli->fetch_array("SELECT id, navn FROM kat WHERE id = ".$value['kat']);
            getUpdateTime('kat');
            $kat = '/kat'.$kat[0]['id'].'-'.clear_file_name($kat[0]['navn']);
        } else {
            $kat = '';
        }
        //TODO beskrivelse
        $GLOBALS['generatedcontent']['accessories'][] = array('name' => $value['navn'],
            'link' => $kat.'/side'.$value['id'].'-'.clear_file_name($value['navn']).'.html',
            'icon' => $value['billed'],
            'text' => '',
            'price' => array('befor' => $value['for'],
            'now' => $value['pris'],
            'from' => $value['fra'],
            'market' => $value['burde']));
    }
}
?>
