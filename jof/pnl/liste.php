<?php
mb_language("uni");
mb_internal_encoding('UTF-8');

require_once 'countries.php';
require_once 'calcprice.php';

if(!isset($_GET['y'])) {
	$_GET['y'] = date('Y');
	$_GET['m'] = date('n');
}
if(!isset($_GET['m'])) {
	$_GET['m'] = date('n');
}

function getList($sender) {
	require_once '../inc/mysqli.php';
	require_once '../inc/config.php';
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	
	$where = ''; 
	
	if(@$_GET['y'] && @$_GET['m']) {
		$where .= ' AND `bookingDate` >= \''.$_GET['y'].'-'.$_GET['m'].'-01\'';
		$where .= ' AND `bookingDate` <= \''.$_GET['y'].'-'.$_GET['m'].'-31\'';
	} elseif(@$_GET['y'] && !@$_GET['m']) {
		$where .= ' AND `bookingDate` >= \''.$_GET['y'].'-01-01\'';
		$where .= ' AND `bookingDate` <= \''.$_GET['y'].'-12-31\'';
	}
	
	if(@$_GET['user'])
		$where .= ' AND `sender` = \''.@$_GET['user'].'\'';
	
	if(@$_GET['name'])
		$where .= ' AND `name` LIKE \'%'.@$_GET['name'].'%\'';
	
	if(@$_GET['id'])
		$where .= ' AND `id` = \''.@$_GET['id'].'\'';
	
	if(@$_GET['packageId'])
		$where .= ' AND `packageId` LIKE \'%'.@$_GET['packageId'].'%\'';
	
	if(@$_GET['arrived'] === '0')
		$where .= ' AND `arrived` = 0';
	else if(@$_GET['arrived'] == 1)
		$where .= ' AND `arrived` = 1';
	
	//TODO limit
	$PNL = $mysqli->fetch_array("SELECT * FROM `PNL` WHERE `sender` = '".$sender."' ".$where." ORDER BY -`bookingDate`");
	if($PNL) {
		//Get track and trace
		echo('<tr><td colspan="9"><big><b>'.$sender.'</b></big></td></tr><tr><td></td><td><strong>Id</strong></td><td><strong>Modtager</strong></td><td><strong>Adresse</strong></td><td><strong>Post nr.</strong></td><td><strong>Land</strong></td><td><strong>Beskrivelse</strong></td><td><strong>Reference</strong></td><td><strong>Dag</strong></td><td><strong>Fragt</strong></td></tr>');
		foreach($PNL as $i => $pakke) {
				echo('<tr');
				if($i%2==0)
					echo(' style="background-color:#e6eff4"');
				echo('><td style="white-space:nowrap;">');
				echo('<a target="_blank" href="http://www.jagtogfiskerimagasinet.dk/pnl/refetchpdf.php?labelType='.$pakke['labelType'].'&bookingTime='.$pakke['bookingTime'].'&bookingDate='.$pakke['bookingDate'].'"><img src="/post/pdf-icon.gif" alt="PDF" title="Hent PDF" /></a> ');
				echo('<a target="_blank" href="http://online.pannordic.com/pn_logistics/index_tracking_email.jsp?id='.$pakke['packageId'].'&Search=search"><img src="/post/magnifier');
				if($pakke['arrived']) echo('_zoom_in');
				echo('.png" alt="T&amp;T" title="Track &amp; Trace: '.$pakke['packageId'].'" /></a> ');
				if($pakke['insurance']) echo('<img src="/post/coins.png" alt="$" title="Værdi: '.$pakke['insurance'].'" /> ');
				if($pakke['product'] == 330) echo('<img src="/post/lightning.png" alt="Z" title="Express" /> ');
				if(!$pakke['arrived'] && !$pakke['inmotion']) echo('<a href="gettnt.php?bookingDate='.$pakke['bookingDate'].'"><img src="/post/arrow_refresh.png" alt="S" title="Sync med T&amp;T" /></a> <a href="delete.php?id='.$pakke['id'].'"><img src="/post/bin.png" alt="X" title="Slet '.$pakke['packageId'].'" /></a> ');
				
				echo('</td>
				 <td style="text-align:right">'.$pakke['id'].'</td>
					<td>'.$pakke['name'].'</td>
					<td>'.$pakke['address'].'</td>
					<td style="text-align:right">'.$pakke['postcode'].'</td>
					<td><img src="flags/'.strtolower($pakke['country']).'.png" alt="'.$pakke['country'].'" title="'.$GLOBALS['countries'][$pakke['country']].'" /></td>
					<td>'.$pakke['text'].'</td>
					<td>'.$pakke['ref'].'</td>
					<td>'.$pakke['bookingDate'].'</td>
					<td style="text-align:right">'.number_format(calcPricePart($pakke['country'], $pakke['product'], $pakke['kg'], $pakke['insurance'], $pakke['l'], $pakke['w'], $pakke['h']), 2, ',', '.').'</td>
					
				</tr>');
		}
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Quick &amp; complient PNL</title>
<script type="text/javascript" src="javascript.js"></script>
<link href="style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
thead * {
	font-weight:bold;
}
caption {
	font-size:14px;
	margin-top:15px;
}
img {
	border:0;
}
</style>
</head>
<body>
<div id="menu"><img src="http://www.pannordic.com/pnl_img/logo.gif" alt="PNL" width="145" height="70" title="" /><br />
    <a href="/pnl/">Ny pakke</a><br />
    <a href="/pnl/liste.php">Forsendelser</a></div>
<div id="content">
    <form action="" method="get" style="margin:0">
        <table>
            <thead>
                <tr>
                    <td>Afdeling</td>
                    <td>År</td>
                    <td>Måned</td>
                    <td>Status</td>
                    <td>Id</td>
                    <td>Modtager</td>
                    <td>Stregkode</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><select name="user">
                            <option value=""<?php if(@$_GET['user'] == '') { ?> selected="selected"<?php } ?>>Alle</option>
                            <option value="HH"<?php if(@$_GET['user'] == 'HH') { ?> selected="selected"<?php } ?>>7B</option>
                            <option value="52"<?php if(@$_GET['user'] == '52') { ?> selected="selected"<?php } ?>>52</option>
                            <option value="JF"<?php if(@$_GET['user'] == 'JF') { ?> selected="selected"<?php } ?>>JF</option>
                            <option value="AG"<?php if(@$_GET['user'] == 'AG') { ?> selected="selected"<?php } ?>>AG</option>
                        </select></td>
                    <td><select name="y">
                            <?php
		for($i=2009;$i<date('Y')+1;$i++) {
			?>
                            <option value="<?php echo($i) ?>"<?php if(@$_GET['y'] == $i || (@$_GET['y'] == '' && date('Y') == $i)) { ?> selected="selected"<?php } ?>><?php echo($i) ?></option>
                            <?php
		}
 ?>
                        </select></td>
                    <td><select name="m">
                            <option value=""<?php if(@$_GET['m'] == '') { ?> selected="selected"<?php } ?>>Alle</option>
                            <option value="1"<?php if(@$_GET['m'] == 1) { ?> selected="selected"<?php } ?>>Jan</option>
                            <option value="2"<?php if(@$_GET['m'] == 2) { ?> selected="selected"<?php } ?>>Feb</option>
                            <option value="3"<?php if(@$_GET['m'] == 3) { ?> selected="selected"<?php } ?>>Mar</option>
                            <option value="4"<?php if(@$_GET['m'] == 4) { ?> selected="selected"<?php } ?>>Apr</option>
                            <option value="5"<?php if(@$_GET['m'] == 5) { ?> selected="selected"<?php } ?>>Maj</option>
                            <option value="6"<?php if(@$_GET['m'] == 6) { ?> selected="selected"<?php } ?>>Jun</option>
                            <option value="7"<?php if(@$_GET['m'] == 7) { ?> selected="selected"<?php } ?>>Jul</option>
                            <option value="8"<?php if(@$_GET['m'] == 8) { ?> selected="selected"<?php } ?>>Aug</option>
                            <option value="9"<?php if(@$_GET['m'] == 9) { ?> selected="selected"<?php } ?>>Sep</option>
                            <option value="10"<?php if(@$_GET['m'] == 10) { ?> selected="selected"<?php } ?>>Oct</option>
                            <option value="11"<?php if(@$_GET['m'] == 11) { ?> selected="selected"<?php } ?>>Nov</option>
                            <option value="12"<?php if(@$_GET['m'] == 12) { ?> selected="selected"<?php } ?>>Dec</option>
                        </select></td>
                    <td><select name="arrived">
                            <option value=""<?php if(@$_GET['arrived'] == '') { ?> selected="selected"<?php } ?>>Alle</option>
                            <option value="1"<?php if(@$_GET['arrived'] == 1) { ?> selected="selected"<?php } ?>>Leveret</option>
                            <option value="0"<?php if(@$_GET['arrived'] === '0') { ?> selected="selected"<?php } ?>>Ikke leveret</option>
                        </select></td>
                    <td><input name="id" size="3" value="<?php echo($_GET['id']); ?>" /></td>
                    <td><input name="name" size="13" value="<?php echo($_GET['name']); ?>" /></td>
                    <td><input name="packageId" size="13" maxlength="13" value="<?php echo($_GET['packageId']); ?>" /></td>
                    <td><input type="submit" value="Hent" /></td>
                </tr>
            </tbody>
        </table>
    </form>
    <?php
echo('<table>');
getList('HH');
getList('52');
getList('JF');
getList('AG');
echo('</table>');
?>
</div>
</body>
</html>