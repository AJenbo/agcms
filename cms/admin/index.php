<?php
	date_default_timezone_set('Europe/Copenhagen');
	require_once 'inc/config.php'; 
	require_once '../inc/sajax.php';
	require_once '../inc/config.php';
	require_once '../inc/mysqli.php';
	require_once '../inc/functions.php';
	require_once 'inc/emails.php';
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	$sajax_request_type = "POST";
	
	function rtefsafe($text) {
		return str_replace(
			
			array("'",		chr(10),	chr(13), ' ', ' '),
			array("&#39;",	" ",		" ",	' ', ' '), $text);
			/*
			array("'",	chr(10),	chr(13)),
			array('&#39;',	' ',	' '), $text);
			*/
	}
	
	function search($text) {
		if(!@$text)
			return array('error' => 'Du skal indtaste et søge ord.');
		
		global $mysqli;
		
		$sider = $mysqli->fetch_array("SELECT id, navn, MATCH(navn, text, beskrivelse) AGAINST ('".$text."') AS score FROM sider WHERE MATCH (navn, text, beskrivelse) AGAINST('".$q."') > 0 ORDER BY `score` DESC");
		
		//fulltext search dosn't catch things like 3 letter words and some other combos
		$qsearch = array ("/ /","/'/","/´/","/`/");
		$qreplace = array ("%","_","_","_");
		$simpleq = preg_replace($qsearch, $qreplace, $text);
		$sidersimple = $mysqli->fetch_array("SELECT id, navn FROM `sider` WHERE (`navn` LIKE '%".$simpleq."%' OR `text` LIKE '%".$simpleq."%' OR `beskrivelse` LIKE '%".$simpleq."%')");

		//join $sidersimple to $sider
		foreach($sidersimple as $value) {
			$match = false;

			foreach($sider as $sider_value) {
				if(@$sider_value['side'] == $value['id']) {
					$match = true;
					break;
				}
			}
			unset($sider_value);
			if(!$match)
				$sider[] = $value;
		}
		
		$html = '<div id="headline">Søgning</div><div><div><span style="margin-left: 16px;"><img src="images/folder.png" width="16" height="16" alt="" /> &quot;'.$text.'&quot;</span><div style="margin-left:16px">';
		foreach($sider as $value) {
			$html .= '<div class="side'.$value['id'].'"><a style="margin-left:16px" class="side" href="?side=redigerside&amp;id='.$value['id'].'"><img src="images/page.png" width="16" height="16" alt="" /> '.$value['navn'].'</a></div>';
		}
		$html .= '</div></div></div>';
		
		return array('id' => 'canvas', 'html' => $html);
	}

	function redigerkat($id) {
		global $mysqli;
		
		if($id)
			$kat = $mysqli->fetch_array('SELECT * FROM `kat` WHERE id = '.$id.' LIMIT 1');
		
		$html = '<div id="headline">Rediger kategori</div><form action="" onsubmit="return updateKat('.$id.')"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />
		<div>Navn: <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" src="';
			
		if(!$kat[0]['icon'])
			$html .= 'images/folder.png';
		else
			$html .= $kat[0]['icon'];
		
		$html .= '" title="" alt="Billeder" id="iconthb" /> <input id="navn" style="width:256px;" maxlength="64" value="'.$kat[0]['navn'].'" /> <br /> Icon: <input id="icon" style="width:247px;" maxlength="128" type="hidden" value="'.$kat[0]['icon'].'" /> <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" width="16" height="16" src="images/folder_image.png" title="Find billeder" alt="Billeder" /> <img style="cursor:pointer;vertical-align:bottom" onclick="setThb(\'icon\',\'\',\'images/folder.png\')" src="images/cross.png" alt="X" title="Fjern billed" height="16" width="16" /><br /><br />';
		
		if($subkats = $mysqli->fetch_array('SELECT id, navn, icon FROM `kat` WHERE bind = '.$id.' ORDER BY `order`, `navn`')) {
			$html .= 'Sorter under kategorier:<select id="custom_sort_subs" onchange="displaySubMenus(this.value);" onblur="displaySubMenus(this.value);"><option value="0">Alfabetisk</option><option value="1"';
			if($kat[0]['custom_sort_subs'])
				$html .= ' selected="selected"';
			$html .= '>Manuelt</option></select><br /><ul id="subMenus" style="width:'.$GLOBALS['_config']['text_width'].'px;';
			if(!$kat[0]['custom_sort_subs'])
				$html .= 'display:none;';
			$html .= '">';
			
			foreach($subkats as $value) {
				$html .= '<li id="item_'.$value['id'].'"><img src="';
				if($value['icon'])
					$html .= $value['icon'];
				else
					$html .= 'images/folder.png';
				$html .= '" alt=""> '.$value['navn'].'</li>';
			}
	
			$html .= '</ul><input type="hidden" id="subMenusOrder" value="" /><script type="text/javascript"><!--
Sortable.create(\'subMenus\',{ghosting:false,constraint:false,hoverclass:\'over\',
onChange:function(element){
var newOrder = Sortable.serialize(element.parentNode);
newOrder = newOrder.replace(/subMenus\\[\\]=/g,"");
newOrder = newOrder.replace(/&/g,",");
$(\'subMenusOrder\').value = newOrder;
}
});
var newOrder = Sortable.serialize($(\'subMenus\'));
newOrder = newOrder.replace(/subMenus\\[\\]=/g,"");
newOrder = newOrder.replace(/&/g,",");
$(\'subMenusOrder\').value = newOrder;
--></script>';
		} else {
			$html .= '<input type="hidden" id="subMenusOrder" /><input type="hidden" id="custom_sort_subs" />';
		}

		//Email
		$html .= 'Kontakt: <select id="email">';
		foreach($GLOBALS['_config']['email'] as $value) {
			$html .= '<option value="'.$value.'"';
			if($kat[0]['email'] == $value)
				$html .= ' selected="selected"';
			$html .= '>'.$value.'</option>';
		}
		$html .= '</select>';
		
		//Visning
		$html .= '<br />Visning: <select id="vis"><option value="0"';
		if($kat[0]['vis'] == 0)
			$html .= ' selected="selected"';
		$html .= '>Skjul</option><option value="1"';
		if($kat[0]['vis'] == 1)
			$html .= ' selected="selected"';
		$html .= '>Galleri</option><option value="2"';
		if($kat[0]['vis'] == 2)
			$html .= ' selected="selected"';
		$html .= '>Liste</option></select>';
		
		//Binding
		//TODO init error, vælger fra cookie i stedet for $kat[0]['bind']
		$html .= katlist($kat[0]['bind']);
		
		$html .= '<br /></div><p style="display:none;"></p></form>';
		return $html;
	}

	function redigerside($id) {
		global $mysqli;

		if($id)
			$sider = $mysqli->fetch_array('SELECT * FROM `sider` WHERE id = '.$id.' LIMIT 1');
		if(!$sider)
			return '<div id="headline">Siden eksistere ikke</div>';

		$html = '<div id="headline">Rediger side #'.$id.'</div><form action="" method="post" onsubmit="return updateSide('.$id.');"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><div><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
//--></script><input type="hidden" name="id" id="id" value="'.$id.'" /><input class="admin_name" type="text" name="navn" id="navn" value="'.htmlspecialchars($sider[0]['navn']).'" maxlength="127" size="127" style="width:'.$GLOBALS['_config']['text_width'].'px" /><script type="text/javascript"><!--
writeRichText("text", \''.rtefsafe($sider[0]['text']).'\', "", '.($GLOBALS['_config']['text_width']+32).', 420, true, false, false);
//--></script>';
		$html .= 'Søge ord (seperere søge ord med et komma "Emergency Blanket, Redningstæppe"):<br /><textarea name="keywords" id="keywords" style="width:'.$GLOBALS['_config']['text_width'].'px;max-width:'.$GLOBALS['_config']['text_width'].'px" rows="2" cols="">'.htmlspecialchars($sider[0]['keywords']).'</textarea>';
//Beskrivelse start
		$html .= '<div class="toolbox"><a class="menuboxheader" id="beskrivelseboxheader" style="width:'.($GLOBALS['_config']['thumb_width']+14).'px" onclick="showhide(\'beskrivelsebox\',this);">Beskrivelse: </a><div style="text-align:center;width:'.($GLOBALS['_config']['thumb_width']+34).'px" id="beskrivelsebox"><br /><input type="hidden" value="';
		if($sider[0]['billed']) {
            $html .= $sider[0]['billed'];
		} else {
            $html .= '/images/web/intet-foto.jpg';
		}
		$html .= '" id="billed" name="billed" /><img id="billedthb" src="';

		if($sider[0]['billed']) {
            $html .= $sider[0]['billed'];
		} else {
            $html .= '/images/web/intet-foto.jpg';
		}
		$html .= '" alt="" onclick="explorer(\'thb\', \'billed\')" /><br /><img onclick="explorer(\'thb\', \'billed\')" src="images/folder_image.png" width="16" height="16" alt="Billeder" title="Find billed" /><a onclick="setThb(\'billed\',\'\',\'/images/web/intet-foto.jpg\')"><img src="images/cross.png" alt="X" title="Fjern billed" width="16" height="16" /></a>';
		$html .= '<script type="text/javascript"><!--
writeRichText("beskrivelse", \''.rtefsafe($sider[0]['beskrivelse']).'\', "", '.($GLOBALS['_config']['thumb_width']+32).', 115, false, false, false);
//--></script>';
		$html .= '</div></div>';
//Beskrivelse end
//Pris start
		$html .= '<div class="toolbox"><a class="menuboxheader" id="priserheader" style="width:230px" onclick="showhide(\'priser\',this);">Pris: </a><div style="width:250px;" id="priser"><table style="width:100%"><tr><td><select name="burde" id="burde">
		<option value="0"';
		if($sider[0]['burde'] == 0)
			$html .= ' selected="selected"';
		$html .= '>Før</option>
		<option value="1"';
		if($sider[0]['burde'] == 1)
			$html .= ' selected="selected"';
		$html .= '>Vejledende pris</option>
		<option value="2"';
		if($sider[0]['burde'] == 2)
			$html .= ' selected="selected"';
		$html .= '>Burde koste</option>
		</select></td><td style="text-align:right"><input class="XPris" onkeypress="return checkForInt(event)" onchange="prisHighlight()" value="'.$sider[0]['for'].'" name="for" id="for" size="11" maxlength="11" style="width:100px;text-align:right" />,-</td></tr>';
		$html .= '<tr><td><select name="fra" id="fra">
		<option value="0"';
		if($sider[0]['fra'] == 0)
			$html .= ' selected="selected"';
		$html .= '>Pris</option>
		<option value="1"';
		if($sider[0]['fra'] == 1)
			$html .= ' selected="selected"';
		$html .= '>Fra</option>
		<option value="2"';
		if($sider[0]['fra'] == 2)
			$html .= ' selected="selected"';
		$html .= '>Brugt</option></select></td><td style="text-align:right"><input value="'.$sider[0]['pris'].'" class="';
		if($sider[0]['for'])
			$html .= 'NyPris';
		else
			$html .= 'Pris';
		$html .= '" name="pris" id="pris" size="11" maxlength="11" style="width:100px;text-align:right" onkeypress="return checkForInt(event)" onchange="prisHighlight()" />,-';
		$html .= '</td></tr></table></div></div>';
//Pris end
//misc start
		$html .= '<div class="toolbox"><a class="menuboxheader" id="miscboxheader" style="width:201px" onclick="showhide(\'miscbox\',this);">Andet: </a><div style="width:221px" id="miscbox">Varenummer: <input type="text" name="varenr" id="varenr" maxlength="63" style="text-align:right;width:128px" value="'.htmlspecialchars($sider[0]['varenr']).'" /><br /><img src="images/page_white_key.png" width="16" height="16" alt="" /><select id="krav" name="krav"><option value="0">Ingen</option>';
		$krav = $mysqli->fetch_array('SELECT id, navn FROM `krav` ORDER BY navn');
		$krav_nr = count($krav);
		for($i=0;$i<$krav_nr;$i++) {
			$html .= '<option value="'.$krav[$i]['id'].'"';
			if($sider[0]['krav'] == $krav[$i]['id'])
				$html .= ' selected="selected"';
			$html .= '>'.htmlspecialchars($krav[$i]['navn']).'</option>';
		}
		$html .= '</select><br /><img width="16" height="16" alt="" src="images/page_white_medal.png"/><select id="maerke" name="maerke" multiple="multiple" size="15"><option value="0">Alle de andre</option>';

		$maerker = explode(',',$sider[0]['maerke']);

		$maerke = $mysqli->fetch_array('SELECT id, navn FROM `maerke` ORDER BY navn');
		$maerke_nr = count($maerke);
		for($i=0;$i<$maerke_nr;$i++) {
			$html .= '<option value="'.$maerke[$i]['id'].'"';
			if(in_array($maerke[$i]['id'], $maerker))
				$html .= ' selected="selected"';
			$html .= '>'.htmlspecialchars($maerke[$i]['navn']).'</option>';
		}
		$html .= '</select></div></div>';
//misc end
//list start
	$html .= '<div class="toolbox"><a class="menuboxheader" id="listboxheader" style="width:'.($GLOBALS['_config']['text_width']-20+32).'px" onclick="showhide(\'listbox\',this);">Lister: </a><div style="width:'.($GLOBALS['_config']['text_width']+32).'px" id="listbox">';
	$lists = $mysqli->fetch_array('SELECT * FROM `lists` WHERE page_id = '.$id);
	foreach($lists as $list) {
		$html .= '<table>';
		
		$list['cells'] = explode('<', $list['cells']);
		$list['cell_names'] = explode('<', $list['cell_names']);
		$list['sorts'] = explode('<', $list['sorts']);
		
		
		$html .= '<thead><tr>';
		foreach($list['cell_names'] as $name) {
			$html .= '<td>'.$name.'</td>';
		}
		if($list['link'])
			$html .= '<td><img src="images/link.png" alt="Link" title="" width="16" height="16" /></td>';
		$html .= '<td></td>';
		$html .= '</tr></thead><tfoot><tr id="list'.$list['id'].'footer">';
		foreach($list['cells'] as $key => $type) {
			if($list['sorts'][$key] == 0) {
				if($type != 0) {
					$html .= '<td><input style="display:none;text-align:right;" /></td>';
				} else {
					$html .= '<td><input style="display:none;" /></td>';
				}
			} else {
				if(!$options[$list['sorts'][$key]]) {
					$options[$list['sorts'][$key]] = $mysqli->fetch_array('SELECT `text` FROM `tablesort` WHERE id = '.$list['sorts'][$key]);
					$options[$list['sorts'][$key]] = explode('<', $options[$list['sorts'][$key]][0]['text']);
				}
				
				$html .= '<td><select style="display:none;"><option value=""></option>';
				foreach($options[$list['sorts'][$key]] as $option) {
					$html .= '<option value="'.$option.'">'.$option.'</option>';
				}
				$html .= '</select></td>';
			}
		}
		if($list['link'])
			$html .= '<td><input style="display:none;text-align:right;" /></td>';
		$html .= '<td><img onclick="listInsertRow('.$list['id'].');" src="images/disk.png" alt="Rediger" title="Rediger" width="16" height="16" /></td>';
		$html .= '</tr></tfoot>';
		$html .= '<tbody id="list'.$list['id'].'rows">';
		
		if($rows = $mysqli->fetch_array('SELECT * FROM `list_rows` WHERE list_id = '.$list['id'])) {
		
			//Explode cells
			foreach($rows as $row) {
				$cells = explode('<', $row['cells']);
				$cells['id'] = $row['id'];
				$cells['link'] = $row['link'];
				$rows_cells[] = $cells;
			}
			$rows = $rows_cells;
			unset($row);
			unset($cells);
			unset($rows_cells);
			
			//Sort rows
			if($lists[0]['sorts'][$bycell] < 1)
				$rows = array_natsort($rows, 'id' , $lists[0]['sort']);
			else
				$rows = array_listsort($rows, 'id', $lists[0]['sort'], NULL, $list[0]['sorts'][$lists[0]['sort']]);
			
			foreach($rows as $i => $row) {
				if($i % 2)
					$html .= '<tr id="list_row'.$row['id'].'" class="altrow">';
				else
					$html .= '<tr id="list_row'.$row['id'].'">';
				foreach($list['cells'] as $key => $type) {
					if($list['sorts'][$key] == 0) {
						if($type != 0) {
							$html .= '<td style="text-align:right;"><input value="'.$row[$key].'" style="display:none;text-align:right;" /><span>'.$row[$key].'</span></td>';
						} else {
							$html .= '<td><input value="'.$row[$key].'" style="display:none;" /><span>'.$row[$key].'</span></td>';
						}
					} else {
						if(!$options[$list['sorts'][$key]]) {
							$options[$list['sorts'][$key]] = $mysqli->fetch_array('SELECT `text` FROM `tablesort` WHERE id = '.$list['sorts'][$key]);
							$options[$list['sorts'][$key]] = explode('<', $options[$list['sorts'][$key]][0]['text']);
						}
						
						$html .= '<td><select style="display:none"><option value=""></option>';
						foreach($options[$list['sorts'][$key]] as $option) {
							$html .= '<option value="'.$option.'"';
							if($row[$key] == $option)
								$html .= ' selected="selected"';
							$html .= '>'.$option.'</option>';
						}
						$html .= '</select><span>'.$row[$key].'</span></td>';
					}
				}
				if($list['link'])
					$html .= '<td style="text-align:right;"><input value="'.$row['link'].'" style="display:none;text-align:right;" /><span>'.$row['link'].'</span></td>';
				//TODO change to right click
				$html .= '<td><img onclick="listEditRow('.$list['id'].', '.$row['id'].');" src="images/application_edit.png" alt="Rediger" title="Rediger" width="16" height="16" /><img onclick="listUpdateRow('.$list['id'].', '.$row['id'].');" style="display:none" src="images/disk.png" alt="Rediger" title="Rediger" width="16" height="16" /><img src="images/cross.png" alt="X" title="Slet række" onclick="listRemoveRow('.$list['id'].', '.$row['id'].')" /></td>';
				$html .= '</tr>';
			}
		}
		$html .= '</tbody></table><script type="text/javascript"><!--
listSizeFooter('.$list['id'].');
listlink['.$list['id'].'] = '.$list['link'].';
--></script>';
	}
	$html .= '</div></div>';
//list end

//bind start
		$html .= '</div></form>
<form action="" method="post" onsubmit="return bind('.$id.');">
<div class="toolbox"><a class="menuboxheader" id="bindingheader" style="width:593px;" onclick="showhide(\'binding\',this);">Bindinger: </a><div style="width:613pxpx;" id="binding"><div id="bindinger"><br />';
	$bind = $mysqli->fetch_array('SELECT id, kat FROM `bind` WHERE `side` = '.$id);
	$bind_nr = count($bind);
	for($i=0;$i<$bind_nr;$i++) {
		if($bind[$i]['id'] != -1) {
			$kattree = kattree($bind[$i]['kat']);
			$kattree_nr = count($kattree);
			$kattree_html = '';
			for($kattree_i=0;$kattree_i<$kattree_nr;$kattree_i++) {
				$kattree_html .= '/'.trim($kattree[$kattree_i]['navn']);
			}
			$kattree_html .= '/';
			
			$html .= '<p id="bind'.$bind[$i]['id'].'"> <img onclick="slet(\'bind\', \''.addslashes($kattree_html).'\', '.$bind[$i]['id'].')" src="images/cross.png" alt="X" title="Fjern binding" width="16" height="16" /> ';
			$html .= $kattree_html.'</p>';
		}
	}
    $html .= '</div>';
//bind end
	
	if(@$_COOKIE['activekat'] >= -1)
		$html .= katlist(@$_COOKIE['activekat']);
	else
		$html .= katlist(-1);
		
	$html .= '<br /><input type="submit" value="Opret binding" accesskey="b" />';
    
	$html .= '</div></div></form>';
	
	return $html;
}

function listRemoveRow($list_id, $row_id) {
	global $mysqli;

	$mysqli->query('DELETE FROM `list_rows` WHERE `id` = '.$row_id.' LIMIT 1');
	
	return array('listid' => $list_id, 'rowid' => $row_id);
}

function listSavetRow($list_id, $cells, $link, $row_id) {
	global $mysqli;
	
	if(!$row_id) {
		$mysqli->query('INSERT INTO `list_rows`(`list_id`, `cells`, `link`) VALUES ('.$list_id.', \''.$cells.'\', \''.$link.'\')');
		$row_id = $mysqli->insert_id;
	} else {
		$mysqli->query('UPDATE `list_rows` SET `list_id` = \''.$list_id.'\', `cells` = \''.$cells.'\', `link` = \''.$link.'\' WHERE id = '.$row_id);
	}

	return array('listid' => $list_id, 'rowid' => $row_id);
}

function redigerFrontpage() {
	global $mysqli;

	$special = $mysqli->fetch_array('SELECT `text` FROM `special` WHERE id = 1 LIMIT 1');
	if(!$special)
		return '<div id="headline">Siden eksistere ikke</div>';

	$html .= '<div id="headline">Rediger Forsiden</div><form action="" method="post" onsubmit="return updateForside();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />';

	$subkats = $mysqli->fetch_array('SELECT id, navn, icon FROM `kat` WHERE bind = 0 ORDER BY `order`, `navn`');
	
	$html .= 'Sorter hoved kategorier:';
	$html .= '<ul id="subMenus" style="width:'.$GLOBALS['_config']['text_width'].'px;">';
	
	foreach($subkats as $value) {
		$html .= '<li id="item_'.$value['id'].'"><img src="';
				if($value['icon'])
					$html .= $value['icon'];
				else
					$html .= 'images/folder.png';
				$html .= '" alt=""> '.$value['navn'].'</li>';
	}

	$html .= '</ul><input type="hidden" id="subMenusOrder" /><script type="text/javascript"><!--
Sortable.create(\'subMenus\',{ghosting:false,constraint:false,hoverclass:\'over\',
onChange:function(element){
var newOrder = Sortable.serialize(element.parentNode);
newOrder = newOrder.replace(/subMenus\\[\\]=/g,"");
newOrder = newOrder.replace(/&/g,",");
$(\'subMenusOrder\').value = newOrder;
}
});
var newOrder = Sortable.serialize($(\'subMenus\'));
newOrder = newOrder.replace(/subMenus\\[\\]=/g,"");
newOrder = newOrder.replace(/&/g,",");
$(\'subMenusOrder\').value = newOrder;
--></script><br />';

	$html .= '<script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", \''.rtefsafe($special[0]['text']).'\', "", '.($GLOBALS['_config']['frontpage_width']+32).', 572, true, false, false);
//--></script></form>';
	
	return $html;
}

function redigerSpecial($id) {
	global $mysqli;

	$special = $mysqli->fetch_array('SELECT * FROM `special` WHERE id = '.$id.' LIMIT 1');
	if(!$special)
		return '<div id="headline">Siden eksistere ikke</div>';

	$html .= '<div id="headline">Rediger '.$special[0]['navn'].'</div><form action="" method="post" onsubmit="return updateSpecial('.$id.');"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />';

	$html .= '<input type="hidden" id="id" />';

	$html .= '<script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", \''.rtefsafe($special[0]['text']).'\', "", '.($GLOBALS['_config']['text_width']+32).', 572, true, false, false);
//--></script></form>';
	
	return $html;
}
	
function getnykrav() {
	$html = '<div id="headline">Opret nyt krav</div><form action="" method="post" onsubmit="return savekrav();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><input type="hidden" name="id" id="id" value="" /><input class="admin_name" type="text" name="navn" id="navn" value="" maxlength="127" size="127" style="width:'.$GLOBALS['_config']['text_width'].'px" /><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", "", "", '.$GLOBALS['_config']['text_width'].', 420, true, false, false);
//--></script></form>';
	
	return $html;
}

function listsort($id = NULL) {
	global $mysqli;
	
	if($id) {
		$liste = $mysqli->fetch_array('SELECT * FROM `tablesort` WHERE `id` = '.$id);
		
		$html = '<div id="headline">Rediger '.$liste[0]['navn'].' sortering</div><div>';
		
		$html .= 'Navn: <input id="listOrderNavn" value="'.$liste[0]['navn'].'"><form action="" method="post" onsubmit="addNewItem(); return false;">Nyt punkt: <input id="newItem"> <input type="submit" value="tilføj" accesskey="t"></form>';
		
		$html .= '<ul id="listOrder" style="width:'.$GLOBALS['_config']['text_width'].'px;">';
		$liste[0]['text'] = explode('<', $liste[0]['text']);
		
		foreach($liste[0]['text'] as $key => $value) {
			$html .= '<li id="item_'.$key.'">'.$value.'</li>';
		}
		
		$html .= '</ul><input type="hidden" id="listOrderValue" value="" /><script type="text/javascript"><!--
var items = '.count($liste[0]['text']).';
Sortable.create(\'listOrder\',{ghosting:false,constraint:false,hoverclass:\'over\'});
--></script></div>';
	} else {
		$html = '<div id="headline">Liste sorttering</div><div>';
		$html .= '<a href="#" onclick="makeNewList(); return false;">Opret ny sortering</a><br /><br />';
		
		$lists = $mysqli->fetch_array('SELECT id, navn FROM `tablesort`');
		
		foreach($lists as $value) {
			$html .= '<a href="?side=listsort&amp;id='.$value['id'].'"><img src="images/shape_align_left.png" width="16" height="16" alt="" /> '.$value['navn'].'</a><br />';
		}
		$html .= '</div>';
	}
	
	return $html;
}

function getaddressbook() {
	global $mysqli;
	
	$addresses = $mysqli->fetch_array('SELECT * FROM `email` ORDER BY `navn`');
	
	$html = '<div id="headline">Adressebog</div><div>';
	$html .= '<table id="addressbook"><thead><tr><td></td><td>Navn</td><td>eMail</td><td>Tlf.</td></tr></thead><tbody>';
	
	foreach($addresses as $i => $addres) {
		if(!$addres['tlf1'] && $addres['tlf2'])
			$addres['tlf1'] = $addres['tlf2'];
		
		$html .= '<tr id="contact'.$addres['id'].'"';
		
		if($i % 2)
			$html .= ' class="altrow"';
		
		$html .= '><td><a href="?side=editContact&id='.$addres['id'].'"><img width="16" height="16" src="images/vcard_edit.png" alt="R" title="Rediger" /></a><img onclick="x_deleteContact('.$addres['id'].', removeTagById)" width="16" height="16" src="images/cross.png" alt="X" title="Slet" /></td>';
		$html .= '<td>'.$addres['navn'].'</td><td>'.$addres['email'].'</td><td>'.$addres['tlf1'].'</td></tr>';
	}
	
	$html .= '</tbody></table></div>';
	
	return $html;
}

function editContact($id) {
	global $mysqli;
	
	$address = $mysqli->fetch_array('SELECT * FROM `email` WHERE `id` = '.$id);
	
	$html = '<div id="headline">Redigere kontakt person</div>';
	$html .= '<form method="post" action="" onsubmit="updateContact('.$_GET['id'].'); return false;"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><table border="0" cellspacing="0"><tbody><tr><td>Navn:</td><td colspan="2"><input value="'.
	$address[0]['navn'].'" id="navn" /></td></tr><tr><td>E-mail:</td><td colspan="2"><input value="'.
	$address[0]['email'].'" id="email" /></td></tr><tr><td>Adresse:</td><td colspan="2"><input value="'.
	$address[0]['adresse'].'" id="adresse" /></td></tr><tr><td>Land:</td><td colspan="2"><input value="'.
	$address[0]['land'].'" id="land" /></td></tr><tr><td width="1%">Post nr.:</td><td width="1%"><input maxlength="8" size="8" id="post" value="'.
	$address[0]['post'].'" /></td><td align="left" nowrap="nowrap">By:<input size="8" id="by" value="'.
	$address[0]['by'].'" /></td></tr><tr><td nowrap="nowrap">Privat tlf.:</td><td colspan="2"><input maxlength="11" size="15" id="tlf1" value="'.
	$address[0]['tlf1'].'" /></td></tr><tr><td nowrap="nowrap">Mobil tlf.:</td><td colspan="2"><input maxlength="11" size="15" id="tlf2" value="'.
	$address[0]['tlf2'].'" /></td></tr><tr><td colspan="5"><br /><label for="kartotek"><input value="1" id="kartotek" type="checkbox"';
	if($address[0]['kartotek'])
		$html .= ' checked="checked"';
	$html .= ' />Modtag nyhedsbreve.</label><br />
	<strong>Interesse:</strong>';
	$html .= '<div id="interests">';
	$address[0]['interests_array'] = explode('<', $address[0]['interests']);
	foreach($GLOBALS['_config']['interests'] as $interest) {
		$html .= '<label for="'.$interest.'"><input';
		if(false !== array_search($interest, $address[0]['interests_array']))
			$html .= ' checked="checked"';
		$html .= ' type="checkbox" value="'.$interest.'" id="'.$interest.'" /> '.$interest.'</label> ';
	}
	$html .= '</div></td></tr></tbody></table></form>';
	
	return $html;
}

function updateContact($id, $navn, $email, $adresse, $land, $post, $by, $tlf1, $tlf2, $kartotek, $interests) {
	global $mysqli;
	$mysqli->query("UPDATE `email` SET `navn` = '".$navn."', `email` = '".$email."', `adresse` = '".$adresse."', `land` = '".$land."', `post` = '".$post."', `by` = '".$by."', `tlf1` = '".$tlf1."', `tlf2` = '".$tlf2."', `kartotek` = '".$kartotek."', `interests` = '".$interests."' WHERE id = ".$id);
	return true;
}

function deleteContact($id) {
	global $mysqli;
	$mysqli->query('DELETE FROM `email` WHERE `id` = '.$id);
	return 'contact'.$id;
}

function makeNewList($navn) {
	global $mysqli;
	$mysqli->query('INSERT INTO `tablesort` (`navn`) VALUES (\''.$navn.'\')');
	return array('id' => $mysqli->insert_id, 'name' => $navn);
}

function saveListOrder($id, $navn, $text) {
	global $mysqli;
	$mysqli->query('UPDATE `tablesort` SET navn = \''.$navn.'\', text = \''.$text.'\' WHERE id = '.$id);
	return true;
}
	
function get_db_error() {
	global $mysqli;

	$html = '<div id="headline">Database scanning</div><div>';
	
	$tabels = $mysqli->fetch_array("SHOW TABLE STATUS");
	$dbsize = 0;
	foreach($tabels as $tabel)
		$dbsize += $tabel['Data_length'];
	$dbsize = $dbsize/1024/1024;
	
	$html .= '<br /><b>Database størelse før optimering: '.number_format($dbsize, 1, ',', '').' MB</b><br />';
	
	//Remove bad bindings
	$mysqli->query('DELETE FROM `bind` WHERE (kat != 0 AND kat != -1 AND kat NOT IN (SELECT id FROM kat)) OR side NOT IN ( SELECT id FROM sider );');
	
	if($mysqli->affected_rows > 1) {
		$html .= 'Slettede ';
		if($mysqli->affected_rows > 1) {
			$html .= $mysqli->affected_rows.' løse bindinger.<br />';
		} else {
			$html .= 'en løs binding.<br />';
		}
	}
	
	//Remove bad tilbehor bindings
	$mysqli->query('DELETE FROM `tilbehor` WHERE side NOT IN ( SELECT id FROM sider ) OR tilbehor NOT IN ( SELECT id FROM sider );');
		
	if($mysqli->affected_rows > 1) {
		$html .= 'Slettede ';
		if($mysqli->affected_rows > 1) {
			$html .= $mysqli->affected_rows.' forældet tilbehør.<br />';
		} else {
			$html .= 'et styk forældet tilbehør.<br />';
		}
	}
	
	//is the email valid
	function valide_mail($email) {
		if(preg_match('/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i', $email) && getmxrr(preg_replace('/.+?@(.?)/u', '$1', $email), $dummy))
			return true;
		else
			return false;
	}
	
	//Print error on orphan pages
	$errors = 0;
	$emails = $mysqli->fetch_array("SELECT `id`, `email` FROM `email` WHERE `email` != ''");
	foreach($emails as $email) {
		if(valide_mail($email['email'])) {
			continue;
		}
		$mysqli->query("UPDATE `email` SET `email` = '' WHERE `id` = ".$email['id']." LIMIT 1;");
		$errors++;
	}
	if($errors) {
		$html .= 'Rettede '.$errors.' nyheds tildmældinger med udløbet email adresse.<br />';
	}
	unset($emails);
	unset($email);
	unset($errors);
	
	//Remove bad newsletter submisions
	$mysqli->query("DELETE FROM `email` WHERE `email` = '' AND `adresse` = '' AND `tlf1` = '' AND `tlf2` = '';");
	
	if($mysqli->affected_rows > 1) {
		$html .= 'Slettede ';
		if($mysqli->affected_rows > 1) {
			$html .= $mysqli->affected_rows.' nyheds tildmældinger uden modtager.<br />';
		} else {
			$html .= 'en nykeds tildmælding uden modtager.<br />';
		}
	}
	
	//todo remove missing maerke from sider->maerke	
	
	//Print error on orphan pages
	$error = $mysqli->fetch_array('SELECT id FROM `sider` WHERE id NOT IN ( SELECT side FROM bind );');
	if($error) {
		$html .= '<br /><b>Følgende sider er løse:</b><br />';
		foreach($error as $value) {
			$html .= '<a href="?side=redigerside&amp;id='.$value['id'].'">'.$value['id'].': '.$value['navn'].'</a><br />';
		}
	}
	
	//Print error on orphan pages
	$error = $mysqli->fetch_array('SELECT id, navn FROM `sider`');
	foreach($error as $value) {
		$bind = $mysqli->fetch_array('SELECT kat FROM `bind` WHERE `side` = '.$value['id']);
		//Add active pages that has a list that links to this page
		$listlinks = $mysqli->fetch_array("SELECT bind.kat FROM `list_rows` JOIN lists ON list_rows.list_id = lists.id JOIN bind ON lists.page_id = bind.side WHERE `list_rows`.`link` = ".$value['id']);
		foreach($listlinks as $listlink) {
			if(binding($listlink['kat']) == 0) {
				$bind[]['kat'] = $listlink['kat'];
			}
		}
		
		//Is there any mis matches of the root bindings
		if(count($bind) > 1) {
			$binding = binding($bind[0]['kat']);
			foreach($bind as $enbind) {
				if($binding != binding($enbind['kat'])) {
					$temp_html .= '<a href="?side=redigerside&amp;id='.$value['id'].'">'.$value['id'].': '.$value['navn'].'</a><br />';
					continue 2;
				}
			}
		}
	}
	if($temp_html) {
		$html .= '<br /><b>Følgende sider er både indaktive og aktive:</b><br />';
		$html .= $temp_html;
		$temp_html = '';
	}
	
	//Print error on orphan lists
	$error = $mysqli->fetch_array('SELECT id FROM `lists` WHERE page_id NOT IN (SELECT id FROM sider);');
	if($error) {
		$html .= '<br /><b>Følgende lister er løse:</b><br />';
		foreach($error as $value) {
			$html .= $value['id'].': '.$value['navn'].' '.$value['cell1'].' '.$value['cell2'].' '.$value['cell3'].' '.$value['cell4'].' '.$value['cell5'].' '.$value['cell6'].' '.$value['cell7'].' '.$value['cell8'].' '.$value['cell9'].' '.$value['img'].' '.$value['link'].'<br />';
		}
	}
	
	//Print error on orphan rows
	$error = $mysqli->fetch_array('SELECT * FROM `list_rows` WHERE list_id NOT IN (SELECT id FROM lists);');
	if($error) {
		$html .= '<br /><b>Følgende klonder er uden lister:</b><br />';
		foreach($error as $value) {
			$html .= $value['id'].': '.$value['cells'].' '.$value['link'].'<br />';
		}
	}
	
	//Print error on orphan catagoris
	$error = $mysqli->fetch_array('SELECT id, navn FROM `kat` WHERE bind != 0 AND bind != -1 AND bind NOT IN (SELECT id FROM kat);');
	if($error) {
		$html .= '<br /><b>Følgende kategorier er løse:</b><br />';
		foreach($error as $value) {
			$html .= '<a href="?side=redigerkat&id='.$value['id'].'">'.$value['id'].': '.$value['navn'].'</a><br />';
		}
	}
	
	$error = $mysqli->fetch_array('SELECT id, bind, navn FROM `kat` WHERE bind != 0 AND bind != -1;');
	
	$temp_html = '';
	foreach($error as $kat) {
		$bindtree = kattree($kat['bind']);
		foreach($bindtree as $bindbranch) {
			if($kat['id'] == $bindbranch['id']) {
				$temp_html .= '<a href="?side=redigerkat&id='.$kat['id'].'">'.$kat['id'].': '.$kat['navn'].'</a><br />';
				continue;
			}
		}
	}
	if($temp_html)
		$html .= '<br /><b>Følgende kategorier er bundet under sig selv:</b><br />'.$temp_html;
	
	//Remove non existing files
	$files = $mysqli->fetch_array('SELECT id, path FROM `files`');

	$deleted = 0;
	foreach($files as $files) {
		if(!is_file($_SERVER['DOCUMENT_ROOT'].$files['path'])) {
			$mysqli->query("DELETE FROM `files` WHERE `id` = ".$files['id']);
			$deleted++;
		}
	}
	unset($files);
	if($deleted) {
		$html .= '<br /><b>Slettede '.$deleted.' gamle fil referencer.</b><br />';
		$deleted = 0;
	}
	
	//Check file names
	$error = $mysqli->fetch_array('SELECT path FROM `files` WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+[^/]+$\' ORDER BY `path` ASC');
	if($error) {
		if($mysqli->affected_rows > 1) {
			$html .= '<br /><b>Følgende '.$mysqli->affected_rows.' filer skal omdøbes:</b><br /><a onclick="explorer(\'\',\'\');">';
		} else {
			$html .= '<br /><b>Denne fil skal omdøbes:</b><br /><a onclick="explorer(\'\',\'\');">';
		}
		foreach($error as $value) {
			$html .= $value['path'].'<br />';
		}
		$html .= '</a>';
	}
	
	//Check file paths
	$error = $mysqli->fetch_array('SELECT path FROM `files` WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+.*[/]+\' ORDER BY `path` ASC');
	if($error) {
		if($mysqli->affected_rows > 1) {
			$html .= '<br /><b>Følgende '.$mysqli->affected_rows.' filer er i en mappe der skal omdøbes:</b><br /><a onclick="explorer(\'\',\'\');">';
		} else {
			$html .= '<br /><b>Denne fil er i en mappe der skal omdøbes:</b><br /><a onclick="explorer(\'\',\'\');">';
		}
		foreach($error as $value) {
			$html .= $value['path'].'<br />';
		}
		$html .= '</a>';
	}
	
	//Delete stuck temp files
	$files = scandir($_SERVER['DOCUMENT_ROOT'].'/upload/temp');
	foreach($files as $file) {
		if(is_file($_SERVER['DOCUMENT_ROOT'].'/upload/temp/'.$file)) {
			@unlink($_SERVER['DOCUMENT_ROOT'].'/upload/temp/'.$file);
			$deleted++;
		}
	}
	unset($files);
	unset($file);
	if($deleted) {
		$html .= '<br /><b>Slettede '.$deleted.' midlertidige filer.</b><br />';
		$deleted = 0;
	}
	
	//TODO test for missing alt="" in img under sider
	/*preg_match_all('/<img[^>]+/?>/ui', $value, $matches);*/
	$tables = $mysqli->fetch_array("SHOW TABLE STATUS");
	foreach($tables as $table)
		$mysqli->query("OPTIMIZE TABLE `".$table['Name']."`");
	
	$html .= '<br /><b>Optimerede databasen.</b><br />';
	
	$tabels = $mysqli->fetch_array("SHOW TABLE STATUS");
	$dbsize = 0;
	foreach($tabels as $tabel)
		$dbsize += $tabel['Data_length'];
	$dbsize = $dbsize/1024/1024;
	
	$html .= '<br /><b>Database størelse efter optimering: '.number_format($dbsize, 1, ',', '').' MB</b><br />';
	$files = $mysqli->fetch_array("SELECT sum(`size`)/1024/1024 AS `filesize` FROM `files` ");
	$html .= '<br /><a onclick="explorer(\'\',\'\');"><b>Samlet størelse af filer og billeder: '.number_format($files[0]['filesize'], 1, ',', '').' MB</b></a><br />';
	$html .= '</div>';

	return $html;
}
	
function getnyside() {
	global $mysqli;

	$html = '<div id="headline">Opret ny side</div><form action="" method="post" onsubmit="return opretSide();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><div><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
//--></script><input type="hidden" name="id" id="id" value="" /><input class="admin_name" type="text" name="navn" id="navn" value="" maxlength="127" size="127" style="width:'.$GLOBALS['_config']['text_width'].'px" /><script type="text/javascript"><!--
writeRichText("text", \'\', "", '.($GLOBALS['_config']['text_width']+32).', 420, true, false, false);
//--></script>';
	$html .= 'Søge ord (seperere søge ord med et komma "Emergency Blanket, Redningstæppe"):<br /><textarea name="keywords" id="keywords" style="width:'.$GLOBALS['_config']['text_width'].'px;max-width:'.$GLOBALS['_config']['text_width'].'px" rows="2" cols=""></textarea>';
	//Beskrivelse start
	$html .= '<div class="toolbox"><a class="menuboxheader" id="beskrivelseboxheader" style="width:'.($GLOBALS['_config']['thumb_width']+14).'px" onclick="showhide(\'beskrivelsebox\',this);">Beskrivelse: </a><div style="text-align:center;width:'.($GLOBALS['_config']['thumb_width']+34).'px" id="beskrivelsebox"><br /><input type="hidden" value="/images/web/intet-foto.jpg" id="billed" name="billed" /><img id="billedthb" src="/images/web/intet-foto.jpg" alt="" onclick="explorer(\'thb\', \'billed\')" /><br /><img onclick="explorer(\'thb\', \'billed\')" src="images/folder_image.png" width="16" height="16" alt="Billeder" title="Find billed" /><img onclick="setThb(\'billed\', \'\', \'/images/web/intet-foto.jpg\')" src="images/cross.png" alt="X" title="Fjern billed" width="16" height="16" /><script type="text/javascript"><!--
writeRichText("beskrivelse", \'\', "", '.($GLOBALS['_config']['thumb_width']+32).', 115, false, false, false);
//--></script></div></div>';
	//Beskrivelse end
	//Pris start
	$html .= '<div class="toolbox"><a class="menuboxheader" id="priserheader" style="width:230px" onclick="showhide(\'priser\',this);">Pris: </a><div style="width:250px;" id="priser"><table style="width:100%"><tr><td><select name="burde" id="burde"><option value="0">Før</option><option value="1">Vejledende pris</option></select></td><td style="text-align:right"><input class="XPris" onkeypress="return checkForInt(event)" onchange="prisHighlight()" value="" name="for" id="for" size="11" maxlength="11" style="width:100px;text-align:right" />,-</td></tr><tr><td><select name="fra" id="fra"><option value="0">Pris</option><option value="1">Fra</option></select></td><td style="text-align:right"><input value="" class="Pris" name="pris" id="pris" size="11" maxlength="11" style="width:100px;text-align:right" onkeypress="return checkForInt(event)" onchange="prisHighlight()" />,-</td></tr></table></div></div>';
	//Pris end
	//misc start
	$html .= '<div class="toolbox"><a class="menuboxheader" id="miscboxheader" style="width:201px" onclick="showhide(\'miscbox\',this);">Andet: </a><div style="width:221px" id="miscbox">Varenummer: <input type="text" name="varenr" id="varenr" maxlength="63" style="text-align:right;width:128px" value="" /><br /><img src="images/page_white_key.png" width="16" height="16" alt="" /><select id="krav" name="krav"><option value="0">Ingen</option>';
	$krav = $mysqli->fetch_array('SELECT id, navn FROM `krav`');
	$krav_nr = count($krav);
	for($i=0;$i<$krav_nr;$i++) {
		$html .= '<option value="'.$krav[$i]['id'].'"';
		$html .= '>'.htmlspecialchars($krav[$i]['navn']).'</option>';
	}
	$html .= '</select><br /><img width="16" height="16" alt="" src="images/page_white_medal.png"/><select id="maerke" name="maerke" multiple="multiple" size="10"><option value="0">Alle de andre</option>';
	$maerke = $mysqli->fetch_array('SELECT id, navn FROM `maerke` ORDER BY navn');
	$maerke_nr = count($maerke);
	for($i=0;$i<$maerke_nr;$i++) {
		$html .= '<option value="'.$maerke[$i]['id'].'"';
		$html .= '>'.htmlspecialchars($maerke[$i]['navn']).'</option>';
	}
	$html .= '</select></div></div></div>';
	//misc end
	//bind start
	if(@$_COOKIE['activekat'] >= -1)
		$html .= katlist(@$_COOKIE['activekat']);
	else
		$html .= katlist(-1);
	
	return $html;
}
	
function kattree($id) {
	global $mysqli;

	$kat = $mysqli->fetch_array('SELECT id, navn, bind FROM `kat` WHERE id = '.$id.' LIMIT 1');

	if($kat) {
		$id = $kat[0]['bind'];
		$kattree[0]['id'] = $kat[0]['id'];
		$kattree[0]['navn'] = $kat[0]['navn'];
	}

	while(@$kat[0]['bind'] > 0) {
		$kat = $mysqli->fetch_array('SELECT id, navn, bind FROM `kat` WHERE id = \''.$kat[0]['bind'].'\' LIMIT 1');
		$id = $kat[0]['bind'];
		$kattree[]['id'] = $kat[0]['id'];
		$kattree[count($kattree)-1]['navn'] = $kat[0]['navn'];
	}
	
	if(!$id) {
		$kattree[]['id'] = 0;
		$kattree[count($kattree)-1]['navn'] = 'Forsiden';
	} else {
		$kattree[]['id'] = -1;
		$kattree[count($kattree)-1]['navn'] = 'Indaktive';
	}
	return array_reverse($kattree);
}

function katspath($id) {
	$kattree = kattree($id);
	$nr = count($kattree);
	$html = 'Vælg placering: ';
	for($i=0;$i<$nr;$i++) {
		$html .= '/'.trim($kattree[$i]['navn']);
	}
	$html .= '/';
	return array('id' => 'katsheader', 'html' => $html);
}

function katlist($id) {
	global $mysqli;
	global $kattree;

	$html = '<a class="menuboxheader" id="katsheader" style="width:'.$GLOBALS['_config']['text_width'].'px;clear:both" onclick="showhidekats(\'kats\',this);">';
	if(@$_COOKIE['hidekats']) {
		$temp = katspath($id);
		$html .= $temp['html'];
	} else {
		$html .= 'Vælg placering: ';
	}
	$html .= '</a><div style="width:'.($GLOBALS['_config']['text_width']+24).'px;';
	if(@$_COOKIE['hidekats'])
		$html .= 'display:none;';
	$html .= '" id="kats"><div>';
	$kattree = kattree($id);
	$nr = count($kattree);
	for($i=0;$i<$nr;$i++) {
		$kattree[$i] = $kattree[$i]['id'];
	}
	$openkat = explode('<', @$_COOKIE['openkat']);
	if($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = -1 LIMIT 1')) {
		$html .= '<img';
		 if(array_search(-1, $openkat) || false !== array_search('-1', $kattree)) { $html .= ' style="display:none"'; }
		 $html .= ' src="images/+.gif" id="kat-1expand" onclick="kat_expand(-1, true, kat_expand_r);" height="16" width="16" alt="+" title="" /><img';
		 if(!array_search(-1, $openkat) && false === array_search('-1', $kattree)) { $html .= ' style="display:none"'; }
		 $html .= ' src="images/-.gif" id="kat-1contract" onclick="kat_contract(-1);" height="16" width="16" alt="-" title="" /><a';
	} else {
		$html .= '<a style="margin-left:16px"';
	}
	$html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', -1, 360);"><input name="kat" type="radio" value="-1"';
	if($kattree[count($kattree)-1] == -1) {
		$html .= ' checked="checked"';
	}
	$html .= ' /><img src="images/folder.png" width="16" height="16" alt="" /> Indaktive</a><div id="kat-1content" style="margin-left:16px">';
	if(array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
		$temp = kat_expand(-1, true);
		$html .= $temp['html'];
	}
	$html .= '</div></div><div>';
	if($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = 0 LIMIT 1')) {
		$html .= '<img style="display:';
		if(array_search(0, $openkat) || false !== array_search('0', $kattree)) { $html .= 'none'; }
		$html .= '" src="images/+.gif" id="kat0expand" onclick="kat_expand(0, true, kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="display:';
		if(!array_search(0, $openkat) && false === array_search('0', $kattree)) { $html .= 'none'; }
		$html .= '" src="images/-.gif" id="kat0contract" onclick="kat_contract(\'0\');" height="16" width="16" alt="-" title="" /><a';
	} else {
		$html .= '<a style="margin-left:16px"';
	} 
	$html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', 0, 360);"><input type="radio" name="kat" value="0"';
	if(!$kattree[count($kattree)-1]) {
		$html .= ' checked="checked"';
	}
	$html .= ' /><img src="images/folder.png" width="16" height="16" alt="" /> Forsiden</a><div id="kat0content" style="margin-left:16px">';
	if(array_search(0, $openkat) || false !== array_search('0', $kattree)) {
		$temp = kat_expand(0, true);
		$html .= $temp['html'];
	}
	$html .= '</div></div></div>';
	return $html;
}

function siteList($id) {
	global $mysqli;
	global $kattree;
	
	$html = '<div>';
	
	$kattree = array();
	if($id !== null) {
		$kattree = kattree($id);
		$nr = count($kattree);
		for($i=0;$i<$nr;$i++) {
			$kattree[$i] = $kattree[$i]['id'];
		}
	}
	
	$openkat = explode('<', @$_COOKIE['openkat']);
	if($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = -1 LIMIT 1') || $mysqli->fetch_array('SELECT id FROM `bind` WHERE kat = -1 LIMIT 1')) {
		$html .= '<img';
		 if(array_search(-1, $openkat) || false !== array_search('-1', $kattree)) { $html .= ' style="display:none"'; }
		 $html .= ' src="images/+.gif" id="kat-1expand" onclick="siteList_expand(-1, kat_expand_r);" height="16" width="16" alt="+" title="" /><img';
		 if(!array_search(-1, $openkat) && false === array_search('-1', $kattree)) { $html .= ' style="display:none"'; }
		 $html .= ' src="images/-.gif" id="kat-1contract" onclick="kat_contract(-1);" height="16" width="16" alt="-" title="" /><a';
	} else {
		$html .= '<a style="margin-left:16px"';
	}
	$html .= '><img src="images/folder.png" width="16" height="16" alt="" /> Indaktive</a><div id="kat-1content" style="margin-left:16px">';
	if(array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
		$temp = siteList_expand(-1);
		$html .= $temp['html'];
	}
	$html .= '</div></div><div>';
	if($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = 0 LIMIT 1') || $mysqli->fetch_array('SELECT id FROM `bind` WHERE kat = 0 LIMIT 1')) {
		$html .= '<img style="';
		if(array_search(0, $openkat) || false !== array_search('0', $kattree)) { $html .= 'display:none;'; }
		$html .= '" src="images/+.gif" id="kat0expand" onclick="siteList_expand(0, kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="';
		if(!array_search(0, $openkat) && false === array_search('0', $kattree)) { $html .= 'display:none;'; }
		$html .= '" src="images/-.gif" id="kat0contract" onclick="kat_contract(\'0\');" height="16" width="16" alt="-" title="" /><a';
	} else {
		$html .= '<a style="margin-left:16px"';
	} 
	$html .= ' href="?side=redigerFrontpage"><img src="images/page.png" width="16" height="16" alt="" /> Forsiden</a><div id="kat0content" style="margin-left:16px">';
	if(array_search(0, $openkat) || false !== array_search('0', $kattree)) {
		$temp = siteList_expand(0);
		$html .= $temp['html'];
	}
	$html .= '</div></div>';
	return $html;
}

function siteList_expand($id) {
	global $mysqli;
	$html = '';

	$temp = kat_expand($id, false);
	$html .= $temp['html'];
	$sider = $mysqli->fetch_array('SELECT sider.id, sider.varenr, bind.id as bind, navn FROM `bind` LEFT JOIN sider on bind.side = sider.id WHERE `kat` = '.$id.' ORDER BY sider.navn');
	$nr = count($sider);
	for($i=0;$i<$nr;$i++) {
			$html .= '<div id="bind'.$sider[$i]['bind'].'" class="side'.$sider[$i]['id'].'"><a style="margin-left:16px" class="side" href="?side=redigerside&amp;id='.$sider[$i]['id'].'"><img src="images/page.png" width="16" height="16" alt="" /> '.strip_tags($sider[$i]['navn'],'<img>');
			if($sider[$i]['varenr'])
				$html .= ' <em>#:'.$sider[$i]['varenr'].'</em>';
			$html .= '</a></div>';
	}
	return array('id' => $id, 'html' => $html);
}

function getnykat() {
	$html = '<div id="headline">Opret kategori</div><form action="" onsubmit="return save_ny_kat()"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><div>Navn: <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" src="images/folder.png" title="" alt="Billeder" id="iconthb" /> <input id="navn" style="width:256px;" maxlength="64" /> <br /> Icon: <input id="icon" style="width:247px;" maxlength="128" type="hidden" /> <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" width="16" height="16" src="images/folder_image.png" title="Find billeder" alt="Billeder" /> <img style="cursor:pointer;vertical-align:bottom" onclick="setThb(\'icon\',\'\',\'images/folder.png\')" src="images/cross.png" alt="X" title="Fjern billed" height="16" width="16" /><br /><br />';
	
	//Email
	$html .= 'Kontakt: <select id="email">';
	foreach($GLOBALS['_config']['email'] as $value) {
		$html .= '<option value="'.$value.'">'.$value.'</option>';
	}
	$html .= '</select>';
	
	//Visning
	$html .= '<br />Visning: <select id="vis"><option value="0">Skjul</option><option value="1" selected="selected">Galleri</option><option value="2">Liste</option></select>';
	
	//binding
	if(@$_COOKIE['activekat'] >= -1)
		$html .= katlist(@$_COOKIE['activekat']);
	else
		$html .= katlist(-1);
	
	$html .= '<br /></div></form>';
	return array('id' => 'canvas', 'html' => $html);
}

function getSiteTree() {
	$html = '<div id="headline">Oversigt</div><div>';
	$html .= siteList(@$_COOKIE['activekat']);
	
	global $mysqli;
	$specials = $mysqli->fetch_array('SELECT `id`, `navn` FROM `special` WHERE `id` > 1 ORDER BY `navn`');
	foreach($specials as $special)
		$html .= '<div style="margin-left: 16px;"><a href="?side=redigerSpecial&id='.$special['id'].'"><img height="16" width="16" alt="" src="images/page.png"/> '.$special['navn'].'</a></div>';
		
	return $html.'</div>';
}

function kat_expand($id, $input=true) {
	global $mysqli;
	global $kattree;
	$html = '';
	
	$kat = $mysqli->fetch_array('SELECT * FROM `kat` WHERE bind = '.$id.' ORDER BY `order`, `navn`');
	$nr = count($kat);
	for($i=0;$i<$nr;$i++) {
		if($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = '.$kat[$i]['id'].' LIMIT 1') || (!$input && $mysqli->fetch_array('SELECT id FROM `bind` WHERE kat = '.$kat[$i]['id'].' LIMIT 1'))) {
			$openkat = explode('<', @$_COOKIE['openkat']);
			$html .= '<div id="kat'.$kat[$i]['id'].'"><img style="display:';
			if(array_search($kat[$i]['id'], $openkat) || false !== array_search($kat[$i]['id'], $kattree)) {
				$html .= 'none';
			}
			$html .= '" src="images/+.gif" id="kat'.$kat[$i]['id'].'expand" onclick="';
			if($input)
				$html .= 'kat_expand('.$kat[$i]['id'].', \'true\'';
			else
				$html .= 'siteList_expand('.$kat[$i]['id'];
			$html .= ', kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="display:';
			
			if(!array_search($kat[$i]['id'], $openkat) && false === array_search($kat[$i]['id'], $kattree)) {
				$html .= 'none';
			}
			$html .= '" src="images/-.gif" id="kat'.$kat[$i]['id'].'contract" onclick="kat_contract('.$kat[$i]['id'].');" height="16" width="16" alt="-" title="" /><a class="kat"';
			
			if($input) {
				$html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', '.$kat[$i]['id'].', 360);"><input name="kat" type="radio" value="'.$kat[$i]['id'].'"';
				if(@$kattree[count($kattree)-1] == $kat[$i]['id'])
					$html .= ' checked="checked"';
				$html .= ' />';
			} else
				$html .= ' href="?side=redigerkat&id='.$kat[$i]['id'].'">';

			$html .= '<img src="';
			if($kat[$i]['icon'])
				$html .= $kat[$i]['icon'];
			else
				$html .= 'images/folder.png';
			$html .= '" alt="" /> '.strip_tags($kat[$i]['navn'],'<img>').'</a><div id="kat'.$kat[$i]['id'].'content" style="margin-left:16px">';
			if(array_search($kat[$i]['id'], $openkat) || false !== array_search($kat[$i]['id'], $kattree)) {
				if($input)
					$temp = kat_expand($kat[$i]['id'], true);
				else
					$temp = siteList_expand($kat[$i]['id']);
				$html .= $temp['html'];
			}
			$html .= '</div></div>';
		} else {
			$html .= '<div id="kat'.$kat[$i]['id'].'"><a class="kat" style="margin-left:16px"';
			if($input) {
				$html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', '.$kat[$i]['id'].', 360);"><input type="radio" name="kat" value="'.$kat[$i]['id'].'"';
				if(@$kattree[count($kattree)-1] == $kat[$i]['id']) {
					$html .= ' checked="checked"';
				}
				$html .= ' />';
			} else
				$html .= ' href="?side=redigerkat&id='.$kat[$i]['id'].'">';
			$html .= '<img src="';
			if($kat[$i]['icon'])
				$html .= $kat[$i]['icon'];
			else
				$html .= 'images/folder.png';
			$html .= '" alt="" /> '.strip_tags($kat[$i]['navn'],'<img>').'</a></div>';
		}
	}
	return array('id' => $id, 'html' => $html);
}

function save_ny_kat($navn, $kat, $icon, $vis, $email) {
	global $mysqli;
	
	if($navn != '' && $kat != '') {
		$mysqli->query('INSERT INTO `kat` (`navn`, `bind`, `icon`, `vis`, `email`) VALUES (\''.$navn.'\', \''.$kat.'\', \''.$icon.'\', \''.$vis.'\', \''.$email.'\')');
		
//			$html = "INSERT INTO `kat` (`navn`, `bind`, `icon` ) VALUES ('$navn', '$kat', '$icon')".'side funktion';
		return true;
	} else
		return array('error' => 'Du skal indtaste et navn og vælge en placering til den nye kategori.');
}

function savekrav($id, $navn, $text) {
	global $mysqli;
	
	if($navn != '' && $text != '') {
		if(!$id)
			$mysqli->query('INSERT INTO `krav` (`navn`, `text` ) VALUES (\''.$navn.'\', \''.$text.'\')');
		else
			$mysqli->query('UPDATE krav SET navn = \''.$navn.'\', text = \''.$text.'\' WHERE id = '.$id);
		
		$html = 'INSERT INTO `krav` (`navn`, `text` ) VALUES (\''.$navn.'\', \''.$text.'\')';
		return array('id' => 'canvas', 'html' => getkrav());
	} else
		return array('error' => 'Du skal indtaste et navn og en tekst til kravet.');
		
}

function getsogogerstat() {
	echo '<div id="headline">Søg og erstat</div><form onsubmit="sogogerstat(document.getElementById(\'sog\').value,document.getElementById(\'erstat\').value,inject_html); return false;"><img src="images/error.png" width="16" height="16" alt="" > denne funktion påvirker alle sider.<table cellspacing="0"><tr><td>Søg: </td><td><input id="sog" style="width:256px;" maxlength="64" /></td></tr><tr><td>Erstat: </td><td><input id="erstat" style="width:256px;" maxlength="64" /></td></tr></table><br /><br /><input value="Søg og erstat" type="submit" accesskey="r" /></form>';
}

function sogogerstat($sog, $erstat) {
	global $mysqli;

	$mysqli->query('UPDATE sider SET text = REPLACE(text,\''.$sog.'\',\''.$erstat.'\')');

	return $mysqli->affected_rows;
}

function getmaerker() {
	global $mysqli;

	$html = '<div id="headline">Mærkeliste</div><form action="" id="maerkerform" onsubmit="x_save_ny_maerke(document.getElementById(\'navn\').value,document.getElementById(\'link\').value,document.getElementById(\'ico\').value,inject_html); return false;"><table cellspacing="0"><tr style="height:21px"><td>Navn: </td><td><input id="navn" style="width:256px;" maxlength="64" /></td><td rowspan="4"><img id="icoimage" src="" style="display:none" alt="" /></td></tr><tr style="height:21px"><td>Link: </td><td><input id="link" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>Logo: </td>
	<td style="text-align:center"><input type="hidden" value="/images/web/intet-foto.jpg" id="ico" name="ico" /><img id="icothb" src="/images/web/intet-foto.jpg" alt="" onclick="explorer(\'thb\', \'ico\')" /><br /><img onclick="explorer(\'thb\', \'ico\')" src="images/folder_image.png" width="16" height="16" alt="Billeder" title="Find billed" /><img onclick="setThb(\'ico\', \'\', \'/images/web/intet-foto.jpg\')" src="images/cross.png" alt="X" title="Fjern billed" width="16" height="16" /></td>
	</tr><tr><td></td></tr></table><p><input value="Tilføj mærke" type="submit" accesskey="s" /><br /><br /></p><div id="imagelogo" style="display:none; position:absolute;"></div>';
	$mærker = $mysqli->fetch_array('SELECT * FROM `maerke` ORDER BY navn');
	$nr = count($mærker);
	for($i=0;$i<$nr;$i++) {
		$html .= '<div id="maerke'.$mærker[$i]['id'].'"><a href="javascript:slet(\'maerke\',\''.addslashes($mærker[$i]['navn']).'\','.$mærker[$i]['id'].');"><img src="images/cross.png" alt="X" title="Slet '.htmlspecialchars($mærker[$i]['navn']).'!" width="16" height="16"';
		if(!$mærker[$i]['link'] && !$mærker[$i]['ico'])
			$html .= ' style="margin-right:32px"';
		elseif(!$mærker[$i]['link'])
			$html .= ' style="margin-right:16px"';
		$html .= ' /></a><a href="?side=updatemaerke&amp;id='.$mærker[$i]['id'].'">';
		if($mærker[$i]['link']) {
			$html .= '<img src="images/link.png" alt="W" width="16" height="16" title="'.htmlspecialchars($mærker[$i]['link']).'"';
			if(!$mærker[$i]['ico'])
				$html .= ' style="margin-right:16px"';
			$html .= ' />';
		}
		if($mærker[$i]['ico'])
			$html .= '<img alt="icon" title="" src="images/picture.png" width="16" height="16" onmouseout="document.getElementById(\'imagelogo\').style.display = \'none\'" onmouseover="showimage(this,\''.addslashes($mærker[$i]['ico']).'\')" />';
		$html .= ' '.htmlspecialchars($mærker[$i]['navn']).'</a></div>';
	}
	$html .= '</form>';
	return $html;
}

function getupdatemaerke($id) {
	global $mysqli;

	$mærker = $mysqli->fetch_array('SELECT navn, link, ico FROM `maerke` WHERE id = '.$id);
	
	$html = '<div id="headline">Rediger mærket '.$mærker[0]['navn'].'</div><form onsubmit="x_updatemaerke('.$id.',document.getElementById(\'navn\').value,document.getElementById(\'link\').value,document.getElementById(\'ico\').value,inject_html); return false;"><table cellspacing="0"><tr style="height:21px"><td>Navn: </td><td><input value="'.htmlspecialchars($mærker[0]['navn']).'" id="navn" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>Link: </td><td><input value="'.htmlspecialchars($mærker[0]['link']).'" id="link" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>Logo: </td>
	<td style="text-align:center"><input type="hidden" value="'.htmlspecialchars($mærker[0]['ico']).'" id="ico" name="ico" /><img id="icothb" src="';
	if($mærker[0]['ico'])
		$html .= $mærker[0]['ico'];
	else
		$html .= '/images/web/intet-foto.jpg';
	$html .= '" alt="" onclick="explorer(\'thb\', \'ico\')" /><br /><img onclick="explorer(\'thb\', \'ico\')" src="images/folder_image.png" width="16" height="16" alt="Billeder" title="Find billed" /><img onclick="setThb(\'ico\', \'\', \'/images/web/intet-foto.jpg\')" src="images/cross.png" alt="X" title="Fjern billed" width="16" height="16" /></td>
	</tr><tr><td></td></tr></table><br /><br /><input value="Gem mærke" type="submit" accesskey="s" /><br /><br /><div id="imagelogo" style="display:none; position:absolute;"></div></form>';
	return $html;
}

function updatemaerke($id, $navn, $link, $ico) {
	global $mysqli;

	if($navn) {
		$mysqli->query('UPDATE maerke SET navn = \''.$navn.'\', link = \''.$link.'\', ico = \''.$ico.'\' WHERE id = '.$id);
		return array('id' => 'canvas', 'html' => getmaerker());
	} else
		return array('error' => 'Du skal indtaste et navn.');
}

function save_ny_maerke($navn, $link, $ico) {
	global $mysqli;

	if($navn) {
		$mysqli->query('INSERT INTO `maerke` (`navn` , `link` , `ico` ) VALUES (\''.$navn.'\', \''.$link.'\', \''.$ico.'\')');
		return array('id' => 'canvas', 'html' => getmaerker());
	} else
		return array('error' => 'Du skal indtaste et navn.');
}

function getkrav() {
	global $mysqli;

	$html = '<div id="headline">Kravliste</div><div style="margin:16px;"><a href="?side=nykrav">Tilføj krav</a>';
	$krav = $mysqli->fetch_array('SELECT id, navn FROM `krav` ORDER BY navn');
	$nr = count($krav);
	for($i=0;$i<$nr;$i++) {
		$html .= '<div id="krav'.$krav[$i]['id'].'"><a href="javascript:slet(\'krav\',\''.addslashes($krav[$i]['navn']).'\','.$krav[$i]['id'].');"><img src="images/cross.png" title="Slet '.$krav[$i]['navn'].'!" width="16" height="16" /></a><a href="?side=editkrav&amp;id='.$krav[$i]['id'].'">'.$krav[$i]['navn'].'</a></div>';
	}
	$html .= '</div>';
	return $html;
}


function editkrav($id) {
	global $mysqli;
	
	$krav = $mysqli->fetch_array('SELECT navn, text FROM `krav` WHERE id = '.$id);
	
	$html = '<div id="headline">Rediger '.$krav[0]['navn'].'</div><form action="" method="post" onsubmit="return savekrav();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><input type="hidden" name="id" id="id" value="'.$id.'" /><input class="admin_name" type="text" name="navn" id="navn" value="'.$krav[0]['navn'].'" maxlength="127" size="127" style="width:587px" /><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", \''.rtefsafe($krav[0]['text']).'\', "", '.$GLOBALS['_config']['text_width'].', 420, true, false, false);
//--></script></form>';
	
	return $html;
}

function sletmaerke($id) {
	global $mysqli;

	$mysqli->query('DELETE FROM `maerke` WHERE `id` = '.$id.' LIMIT 1');
	return array('node' => 'maerke'.$id);
}

function sletkrav($id) {
	global $mysqli;

	$mysqli->query('DELETE FROM `krav` WHERE `id` = '.$id.' LIMIT 1');
	return array('id' => 'krav'.$id);
}

function sletkat($id) {
	global $mysqli;

	$mysqli->query('DELETE FROM `kat` WHERE `id` = '.$id.' LIMIT 1');
	if($kats = $mysqli->fetch_array('SELECT id FROM `kat` WHERE `bind` = '.$id)) {
		foreach($kats as $kat) {
			sletkat($kat['id']);
		}
	}
	if($bind = $mysqli->fetch_array('SELECT side FROM `bind` WHERE `kat` = '.$id)) {
		$mysqli->query('DELETE FROM `bind` WHERE `kat` = '.$id);
		foreach($bind as $side) {
			if(!$mysqli->fetch_array('SELECT id FROM `bind` WHERE `side` = '.$side['side'].' LIMIT 1')) {
				sletSide($side['side']);
			}
		}
	}
	return array('id' => 'kat'.$id);
}

function movekat($id, $toId) {
	global $mysqli;

	$mysqli->query('UPDATE `kat` SET `bind` = '.$toId.' WHERE `id` = '.$id.' LIMIT 1');

	if($mysqli->affected_rows)
		return array('id' => 'kat'.$id, 'update' => $toId);
	else
		return false;
}

function renamekat($id, $name) {
	global $mysqli;

	$mysqli->query('UPDATE `kat` SET `navn` = \''.$name.'\' WHERE `id` = '.$id.' LIMIT 1');
	return array('id' => 'kat'.$id, 'name' => $name);
}

function sletbind($id) {
	global $mysqli;

	if(!$bind = $mysqli->fetch_array('SELECT side FROM `bind` WHERE `id` = '.$id.' LIMIT 1'))
		return array('error' => 'Bindingen eksistere ikke.');
	$mysqli->query('DELETE FROM `bind` WHERE `id` = '.$id.' LIMIT 1');
	$delete[0]['id'] = $id;
	if(!$mysqli->fetch_array('SELECT id FROM `bind` WHERE `side` = '.$bind[0]['side'].' LIMIT 1')) {
		$mysqli->query('INSERT INTO `bind` (`side` ,`kat`) VALUES (\''.$bind[0]['side'].'\', \'-1\')');
	
		global $mysqli;
		$added['id'] = $mysqli->insert_id;
		$added['path'] = '/Indaktive/';
		$added['kat'] = -1;
		$added['side'] = $bind[0]['side'];
	} else {
		$added = false;
	}
	return array('deleted' => $delete, 'added' => $added);
}

function bind($id, $kat) {
	global $mysqli;

	if($mysqli->fetch_array('SELECT id FROM `bind` WHERE `side` = '.$id.' AND `kat` = '.$kat.' LIMIT 1'))
		return array('error' => 'Bindingen eksistere allerede.');
	
	$katRoot = $kat;
	while($katRoot > 0) {
		$katRoot = $mysqli->fetch_array("SELECT bind FROM `kat` WHERE id = '".$katRoot."' LIMIT 1");
		$katRoot = $katRoot[0]['bind'];
	}

	//Delete any binding not under $katRoot
	$binds = $mysqli->fetch_array('SELECT id, kat FROM `bind` WHERE `side` = '.$id);
	foreach($binds as $bind) {
		$bindRoot = $bind['kat'];
		while($bindRoot > 0) {
			$bindRoot = $mysqli->fetch_array("SELECT bind FROM `kat` WHERE id = '".$bindRoot."' LIMIT 1");
			$bindRoot = $bindRoot[0]['bind'];
		}
		if($bindRoot != $katRoot) {
			$mysqli->query('DELETE FROM `bind` WHERE `id` = '.$bind['id'].' LIMIT 1');
			$delete[] = $bind['id'];
		}
	}
	
	$mysqli->query('INSERT INTO `bind` (`side` ,`kat`) VALUES (\''.$id.'\', \''.$kat.'\')');
	
	$added['id'] = $mysqli->insert_id;
	$added['kat'] = $kat;
	$added['side'] = $id;
	
	$kattree = kattree($kat);
	$kattree_nr = count($kattree);
	for($i=0;$i<$kattree_nr;$i++) {
		$added['path'] .= '/'.trim($kattree[$i]['navn']);
	}
	$added['path'] .= '/';
	
	return array('deleted' => $delete, 'added' => $added);
}

function htmlUrlDecode($text) {
	global $mysqli;
	//TODO is this needed now that AJAX is used?
	if (get_magic_quotes_gpc()) {
		return $mysqli->real_escape_string(
			//atempt to make relative paths (generated by Firefox when copy pasting) in to absolute
			preg_replace('/="[.]{2}\//iu', '="/',
				//TODO is this needed now that AJAX is used?
				stripslashes(
					//Decode Firefox style urls
					rawurldecode(
						//Decode IE style urls
						html_entity_decode(
							//Double encode importand encodings, to survive next step and remove white space
							preg_replace(
							array('/&lt;/u', '/&gt;/u', '/&amp;/u', '/ /u', '/\s+/u'),
							array('&amp;lt;', '&amp;gt;', '&amp;amp;', ' ', ' '),
								trim($text)),
							ENT_QUOTES,
							'UTF-8')
					)
				)
			)
		);
	} else {
		return $mysqli->real_escape_string(
			//atempt to make relative paths (generated by Firefox when copy pasting) in to absolute
			preg_replace(array('/="[.]{2}\/(images)/iu','/="[.]{2}\/(files)/iu'), '="/$1',
				//Decode Firefox style urls
				rawurldecode(
					//Decode IE style urls
					html_entity_decode(
						//Double encode importand encodings, to survive next step and remove white space
						preg_replace(
							array('/&lt;/u', '/&gt;/u', '/&amp;/u', '/ /u', '/\s+/'),
							array('&amp;lt;', '&amp;gt;', '&amp;amp;', ' ', ' '),
							trim($text)),
						ENT_QUOTES,
						'UTF-8')
				)
			)
		);
	}
}

function updateSide($id, $navn, $keywords, $pris, $billed, $beskrivelse, $for, $text, $varenr, $burde, $fra, $krav, $maerke) {
	global $mysqli;

	$mysqli->query('UPDATE `sider` SET `dato` = now(), `navn` = \''.$navn.'\', `keywords` = \''.$keywords.'\', `pris` = \''.$pris.'\', `text` = \''.htmlUrlDecode($text).'\', `varenr` = \''.$varenr.'\', `for` = \''.$for.'\', `beskrivelse` = \''.htmlUrlDecode($beskrivelse).'\', `krav` = \''.$krav.'\', `maerke` = \''.$maerke.'\', `billed` = \''.$billed.'\', `fra` = '.$fra.', `burde` = '.$burde.' WHERE `id` = '.$id.' LIMIT 1');
	return true;
}

function updateKat($id, $navn, $bind, $icon, $vis, $email, $custom_sort_subs, $subsorder) {
	$bindtree = kattree($bind);
	foreach($bindtree as $bindbranch)
		if($id == $bindbranch['id'])
			return array('error' => 'Kategorien kan ikke plaseres under sig selv.');
	
	global $mysqli;
	
	//Set the order of the subs
	if($custom_sort_subs) {
		updateKatOrder($subsorder);
	}
	
	//Update kat
	$mysqli->query('UPDATE `kat` SET `navn` = \''.$navn.'\', `bind` = \''.$bind.'\', `icon` = \''.$icon.'\', `vis` = \''.$vis.'\', `email` = \''.$email.'\', `custom_sort_subs` = \''.$custom_sort_subs.'\' WHERE `id` = '.$id.' LIMIT 1');
	return true;
}

function updateKatOrder($subsorder) {
	global $mysqli;
	
	$orderquery = $mysqli->prepare('UPDATE `kat` SET `order` = ? WHERE `id` = ? LIMIT 1');
	$orderquery->bind_param('ii', $key, $value);
	
	$subsorder = explode(',', $subsorder);
	
	foreach($subsorder as $key => $value) {
		$orderquery->execute(); 
	}
	
	$orderquery->close(); 
}

function updateForside($id, $text, $subsorder) {
	updateSpecial($id, $text);
	updateKatOrder($subsorder);
	return true;
}

function updateSpecial($id, $text) {
	global $mysqli;

	$mysqli->query('UPDATE `special` SET `dato` = now(), `text` = \''.htmlUrlDecode($text).'\' WHERE `id` = '.$id.' LIMIT 1');
	return true;
}

function opretSide($kat, $navn, $keywords, $pris, $billed, $beskrivelse, $for, $text, $varenr, $burde, $fra, $krav, $maerke) {
	global $mysqli;

	$mysqli->query('INSERT INTO `sider` (`dato` ,`navn` ,`keywords` ,`pris` ,`text` ,`varenr` ,`for` ,`beskrivelse` ,`krav` ,`maerke` ,`billed` ,`fra` ,`burde` ) VALUES (now(), \''.$navn.'\', \''.$keywords.'\', \''.$pris.'\', \''.htmlUrlDecode($text).'\', \''.$varenr.'\', \''.$for.'\', \''.htmlUrlDecode($beskrivelse).'\', \''.$krav.'\', \''.$maerke.'\', \''.$billed.'\', '.$fra.', '.$burde.')');
	
	$id = $mysqli->insert_id;
	$mysqli->query('INSERT INTO `bind` (`side` ,`kat` ) VALUES (\''.$id.'\', \''.$kat.'\')');
	return array('id' => $id);
}

//Delete a page and all it's relations from the database
function sletSide($sideId) {
	global $mysqli;

	$lists = $mysqli->fetch_array('SELECT id FROM `lists` WHERE `page_id` = '.$sideId);
	if($lists) {
		for($i=0;$i<count($lists);$i++) {
			if($i) {
				$tableWhere .= ' OR';
				$listsWhere .= ' OR';
			}
			$tableWhere .= ' list_id = '.$lists[$i]['id'];
			$listsWhere .= ' id = '.$lists[$i]['id'];
		}
		$mysqli->query('DELETE FROM `list_rows` WHERE'.$tableWhere);
		$mysqli->query('DELETE FROM `lists` WHERE `sideId` = '.$sideId);
	}
	$mysqli->query('DELETE FROM `list_rows` WHERE `link` = '.$sideId);
	$mysqli->query('DELETE FROM `bind` WHERE side = '.$sideId);
	$mysqli->query('DELETE FROM `tilbehor` WHERE side = '.$sideId.' OR tilbehor ='.$sideId);
	$mysqli->query('DELETE FROM `sider` WHERE id = '.$sideId);
	
	return array('class' => 'side'.$sideId);
}

$kattree = array();

$sajax_debug_mode = 0;
sajax_export(
	array('name' => 'updateKat', 'method' => 'POST'),
	array('name' => 'search', 'method' => 'GET'),
	array('name' => 'sletSide', 'method' => 'POST'),
	array('name' => 'updateSpecial', 'method' => 'POST'),
	array('name' => 'movekat', 'method' => 'POST'),
	array('name' => 'listRemoveRow', 'method' => 'POST'),
	array('name' => 'listSavetRow', 'method' => 'POST'),
	array('name' => 'updateForside', 'method' => 'POST'),
	array('name' => 'makeNewList', 'method' => 'POST'),
	array('name' => 'saveListOrder', 'method' => 'POST'),
	array('name' => 'countEmailTo', 'method' => 'GET'),
	array('name' => 'sendEmail', 'method' => 'POST'),
	array('name' => 'saveEmail', 'method' => 'POST'),
	array('name' => 'updateContact', 'method' => 'POST'),
	array('name' => 'deleteContact', 'method' => 'POST'),
	array('name' => 'bind', 'method' => 'POST'),
	array('name' => 'sletbind', 'method' => 'POST'),
	array('name' => 'renamekat', 'method' => 'POST'),
	array('name' => 'opretSide', 'method' => 'POST'),
	array('name' => 'sletkat', 'method' => 'POST'),
	array('name' => 'opretSide', 'method' => 'POST'),
	array('name' => 'updateSide', 'method' => 'POST'),
	array('name' => 'updatemaerke', 'method' => 'POST'),
	array('name' => 'save_ny_kat', 'method' => 'POST'),
	array('name' => 'sogogerstat', 'method' => 'POST'),
	array('name' => 'save_ny_maerke', 'method' => 'POST'),
	array('name' => 'sletmaerke', 'method' => 'POST'),
	array('name' => 'sletkrav', 'method' => 'POST'),
	array('name' => 'savekrav', 'method' => 'POST'),
	array('name' => 'getnykat', 'method' => 'GET'),
	array('name' => 'katspath', 'method' => 'GET'),
	array('name' => 'siteList_expand', 'method' => 'GET'),
	array('name' => 'kat_expand', 'method' => 'GET'),
	array('name' => 'getSiteTree', 'method' => 'GET')
);
//	$sajax_remote_uri = '/ajax.php';
sajax_handle_client_request();
	
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style/style.css" rel="stylesheet" type="text/css" />
<link href="/theme/admin.css" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Administrator menu</title>
<script type="text/javascript"><!--
<?php sajax_show_javascript(); ?>
--></script>
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="javascript/lib/prototype.js"></script>
<script type="text/javascript"><!--
JSON = JSON || {};
JSON.stringify = function(value) { return value.toJSON(); };
JSON.parse = JSON.parse || function(jsonsring) { return jsonsring.evalJSON(true); };
//-->
</script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript" src="javascript/lib/scriptaculous.js"></script>
<script type="text/javascript" src="javascript/lib/protomenu/proto.menu.js"></script>
<link rel="stylesheet" href="style/proto.menu.css" type="text/css" media="screen" />
<script type="text/javascript" src="javascript/javascript.js"></script>
<script type="text/javascript" src="javascript/index.js"></script>
<script type="text/javascript" src="javascript/list.js"></script>
<!-- RTEF -->
<script type="text/javascript" src="rtef/lang/dk.js"></script>
<script type="text/javascript" src="rtef/xhtml.js"></script>
<script type="text/javascript" src="rtef/richtext.js"></script>
</head>
<body onload="init()">
<div id="canvas">
  <?php
	switch(@$_GET['side']) {
		case 'emaillist':
			echo getEmailList();
		break;
		case 'newemail':
			echo getNewEmail();
		break;
		case 'viewemail':
		case 'editemail':
			echo getEmail($_GET['id']);
		break;
		case 'sogogerstat':
			echo getsogogerstat();
		break;
		case 'maerker':
			echo getmaerker();
		break;
		case 'krav':
			echo getkrav();
		break;
		case 'nyside':
			echo getnyside();
		break;
		case 'nykat':
			$temp = getnykat();
			echo $temp['html'];
		break;
		case 'search':
			$temp = search($_GET['text']);
			echo $temp['html'];
		break;
		case 'editkrav':
			echo editkrav($_GET['id']);
		break;
		case 'nykrav':
			echo getnykrav();
		break;
		case 'updatemaerke';
			echo getupdatemaerke($_GET['id']);
		break;
		case 'redigerside';
			echo redigerside($_GET['id']);
		break;
		case 'redigerkat';
			echo redigerkat($_GET['id']);
		break;
		case 'getSiteTree';
			echo getSiteTree();
		break;
		case 'redigerSpecial';
			echo redigerSpecial($_GET['id']);
		break;
		case 'redigerFrontpage';
			echo redigerFrontpage();
		break;
		case 'get_db_error';
			echo get_db_error();
		break;
		case 'listsort';
			echo listsort($_GET['id']);
		break;
		case 'editContact';
			echo editContact($_GET['id']);
		break;
		case 'addressbook';
			echo getaddressbook();
		break;
	}
?>
</div>
<?php
require 'mainmenu.php';
?>
</body>
</html>
