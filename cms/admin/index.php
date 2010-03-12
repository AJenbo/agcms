<?php
/*
ini_set('display_errors', 1);
error_reporting(-1);
/**/
date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

ini_set('zlib.output_compression', 1);

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
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
			
			array("'", chr(10), chr(13), '?', '?'),
			array("&#39;", ' ', ' ', ' ', ' '), $text);
	}
	
	function search($text) {
		if(!@$text)
			return array('error' => _('You must enter a search word.'));
		
		global $mysqli;
		
		$sider = $mysqli->fetch_array("SELECT id, navn, MATCH(navn, text, beskrivelse) AGAINST ('".$text."') AS score FROM sider WHERE MATCH (navn, text, beskrivelse) AGAINST('".$text."') > 0 ORDER BY `score` DESC");
		
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
		
		$html = '<div id="headline">'.('Rediger kategori').'</div><form action="" onsubmit="return updateKat('.$id.')"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />
		<div>'._('Name:').' <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" src="';
			
		if(!$kat[0]['icon'])
			$html .= 'images/folder.png';
		else
			$html .= $kat[0]['icon'];
		
		$html .= '" title="" alt="Billeder" id="iconthb" /> <input id="navn" style="width:256px;" maxlength="64" value="'.$kat[0]['navn'].'" /> <br /> '._('Icon:').' <input id="icon" style="width:247px;" maxlength="128" type="hidden" value="'.$kat[0]['icon'].'" /> <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" width="16" height="16" src="images/folder_image.png" title="'._('Find pictures').'" alt="'._('Pictures').'" /> <img style="cursor:pointer;vertical-align:bottom" onclick="setThb(\'icon\',\'\',\'images/folder.png\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" height="16" width="16" /><br /><br />';
		
		if($subkats = $mysqli->fetch_array('SELECT id, navn, icon FROM `kat` WHERE bind = '.$id.' ORDER BY `order`, `navn`')) {
			$html .= _('Sort subcategories:').'<select id="custom_sort_subs" onchange="displaySubMenus(this.value);" onblur="displaySubMenus(this.value);"><option value="0">'._('Alphabetically').'</option><option value="1"';
			if($kat[0]['custom_sort_subs'])
				$html .= ' selected="selected"';
			$html .= '>'._('Manually').'</option></select><br /><ul id="subMenus" style="width:'.$GLOBALS['_config']['text_width'].'px;';
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
		$html .= _('Contact:').' <select id="email">';
		foreach($GLOBALS['_config']['email'] as $value) {
			$html .= '<option value="'.$value.'"';
			if($kat[0]['email'] == $value)
				$html .= ' selected="selected"';
			$html .= '>'.$value.'</option>';
		}
		$html .= '</select>';
		
		//Visning
		$html .= '<br />'._('Display:').' <select id="vis"><option value="0"';
		if($kat[0]['vis'] == 0)
			$html .= ' selected="selected"';
		$html .= '>'._('Hide').'</option><option value="1"';
		if($kat[0]['vis'] == 1)
			$html .= ' selected="selected"';
		$html .= '>'._('Gallery').'</option><option value="2"';
		if($kat[0]['vis'] == 2)
			$html .= ' selected="selected"';
		$html .= '>'._('List').'</option></select>';
		
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
			return '<div id="headline">'._('The page does not exist').'</div>';

		$html = '<div id="headline">'._('Edit page #').$id.'</div><form action="" method="post" onsubmit="return updateSide('.$id.');"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><div><script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
//--></script><input type="hidden" name="id" id="id" value="'.$id.'" /><input class="admin_name" type="text" name="navn" id="navn" value="'.htmlspecialchars($sider[0]['navn']).'" maxlength="127" size="127" style="width:'.$GLOBALS['_config']['text_width'].'px" /><script type="text/javascript"><!--
writeRichText("text", \''.rtefsafe($sider[0]['text']).'\', "", '.($GLOBALS['_config']['text_width']+32).', 420, true, false, false);
//--></script>';
		$html .= _('Search word (separate search words with a comma \'Emergency Blanket, Emergency Blanket\'):').'<br /><textarea name="keywords" id="keywords" style="width:'.$GLOBALS['_config']['text_width'].'px;max-width:'.$GLOBALS['_config']['text_width'].'px" rows="2" cols="">'.htmlspecialchars($sider[0]['keywords']).'</textarea>';
//Beskrivelse start
		$html .= '<div class="toolbox"><a class="menuboxheader" id="beskrivelseboxheader" style="width:'.($GLOBALS['_config']['thumb_width']+14).'px" onclick="showhide(\'beskrivelsebox\',this);">'._('Description:').' </a><div style="text-align:center;width:'.($GLOBALS['_config']['thumb_width']+34).'px" id="beskrivelsebox"><br /><input type="hidden" value="';
		if($sider[0]['billed']) {
            $html .= $sider[0]['billed'];
		} else {
            $html .= _('/images/web/intet-foto.jpg');
		}
		$html .= '" id="billed" name="billed" /><img id="billedthb" src="';

		if($sider[0]['billed']) {
            $html .= $sider[0]['billed'];
		} else {
            $html .= _('/images/web/intet-foto.jpg');
		}
		$html .= '" alt="" onclick="explorer(\'thb\', \'billed\')" /><br /><img onclick="explorer(\'thb\', \'billed\')" src="images/folder_image.png" width="16" height="16" alt="'._('Pictures').'" title="'._('Find image').'" /><a onclick="setThb(\'billed\',\'\',\''._('/images/web/intet-foto.jpg').'\')"><img src="images/cross.png" alt="X" title="'._('Remove picture').'" width="16" height="16" /></a>';
		$html .= '<script type="text/javascript"><!--
writeRichText("beskrivelse", \''.rtefsafe($sider[0]['beskrivelse']).'\', "", '.($GLOBALS['_config']['thumb_width']+32).', 115, false, false, false);
//--></script>';
		$html .= '</div></div>';
//Beskrivelse end
//Pris start
		$html .= '<div class="toolbox"><a class="menuboxheader" id="priserheader" style="width:230px" onclick="showhide(\'priser\',this);">'._('Price:').' </a><div style="width:250px;" id="priser"><table style="width:100%"><tr><td><select name="burde" id="burde">
		<option value="0"';
		if($sider[0]['burde'] == 0)
			$html .= ' selected="selected"';
		$html .= '>'._('Before').'</option>
		<option value="1"';
		if($sider[0]['burde'] == 1)
			$html .= ' selected="selected"';
		$html .= '>'._('Indicative price').'</option>
		<option value="2"';
		if($sider[0]['burde'] == 2)
			$html .= ' selected="selected"';
		$html .= '>'._('Should cost').'</option>
		</select></td><td style="text-align:right"><input class="XPris" onkeypress="return checkForInt(event)" onchange="prisHighlight()" value="'.$sider[0]['for'].'" name="for" id="for" size="11" maxlength="11" style="width:100px;text-align:right" />,-</td></tr>';
		$html .= '<tr><td><select name="fra" id="fra">
		<option value="0"';
		if($sider[0]['fra'] == 0)
			$html .= ' selected="selected"';
		$html .= '>'._('Price').'</option>
		<option value="1"';
		if($sider[0]['fra'] == 1)
			$html .= ' selected="selected"';
		$html .= '>'._('From').'</option>
		<option value="2"';
		if($sider[0]['fra'] == 2)
			$html .= ' selected="selected"';
		$html .= '>'._('Used').'</option></select></td><td style="text-align:right"><input value="'.$sider[0]['pris'].'" class="';
		if($sider[0]['for'])
			$html .= 'NyPris';
		else
			$html .= 'Pris';
		$html .= '" name="pris" id="pris" size="11" maxlength="11" style="width:100px;text-align:right" onkeypress="return checkForInt(event)" onchange="prisHighlight()" />,-';
		$html .= '</td></tr></table></div></div>';
//Pris end
//misc start
		$html .= '<div class="toolbox"><a class="menuboxheader" id="miscboxheader" style="width:201px" onclick="showhide(\'miscbox\',this);">'._('Other:').' </a><div style="width:221px" id="miscbox">'._('SKU:').' <input type="text" name="varenr" id="varenr" maxlength="63" style="text-align:right;width:128px" value="'.htmlspecialchars($sider[0]['varenr']).'" /><br /><img src="images/page_white_key.png" width="16" height="16" alt="" /><select id="krav" name="krav"><option value="0">'._('None').'</option>';
		$krav = $mysqli->fetch_array('SELECT id, navn FROM `krav` ORDER BY navn');
		$krav_nr = count($krav);
		for($i=0;$i<$krav_nr;$i++) {
			$html .= '<option value="'.$krav[$i]['id'].'"';
			if($sider[0]['krav'] == $krav[$i]['id'])
				$html .= ' selected="selected"';
			$html .= '>'.htmlspecialchars($krav[$i]['navn']).'</option>';
		}
		$html .= '</select><br /><img width="16" height="16" alt="" src="images/page_white_medal.png"/><select id="maerke" name="maerke" multiple="multiple" size="15"><option value="0">'._('All others').'</option>';

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
	$html .= '<div class="toolbox"><a class="menuboxheader" id="listboxheader" style="width:'.($GLOBALS['_config']['text_width']-20+32).'px" onclick="showhide(\'listbox\',this);">'._('Lists:').' </a><div style="width:'.($GLOBALS['_config']['text_width']+32).'px" id="listbox">';
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
			$html .= '<td><img src="images/link.png" alt="'._('Link').'" title="" width="16" height="16" /></td>';
		$html .= '<td style="width:32px;"></td>';
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
		$html .= '<td><img onclick="listInsertRow('.$list['id'].');" src="images/disk.png" alt="'._('Edit').'" title="'._('Edit').'" width="16" height="16" /></td>';
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
			if(empty($bycell) || $lists[0]['sorts'][$bycell] < 1)
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
				$html .= '<td><img onclick="listEditRow('.$list['id'].', '.$row['id'].');" src="images/application_edit.png" alt="'._('Edit').'" title="'._('Edit').'" width="16" height="16" /><img onclick="listUpdateRow('.$list['id'].', '.$row['id'].');" style="display:none" src="images/disk.png" alt="'._('Edit').'" title="'._('Edit').'" width="16" height="16" /><img src="images/cross.png" alt="X" title="'._('Delete row').'" onclick="listRemoveRow('.$list['id'].', '.$row['id'].')" /></td>';
				$html .= '</tr>';
			}
		}
		$html .= '</tbody></table><script type="text/javascript"><!--

Event.observe(window, \'load\', function() { listSizeFooter('.$list['id'].'); });
listlink['.$list['id'].'] = '.$list['link'].';
--></script>';
	}
	$html .= '</div></div>';
//list end

$html .= '</div></form>';

//bind start
		$html .= '<form action="" method="post" onsubmit="return bind('.$id.');">
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
			
			$html .= '<p id="bind'.$bind[$i]['id'].'"> <img onclick="slet(\'bind\', \''.addslashes($kattree_html).'\', '.$bind[$i]['id'].')" src="images/cross.png" alt="X" title="'._('Remove binding').'" width="16" height="16" /> ';
			$html .= $kattree_html.'</p>';
		}
	}
    $html .= '</div>';
	
	if(@$_COOKIE['activekat'] >= -1)
		$html .= katlist(@$_COOKIE['activekat']);
	else
		$html .= katlist(-1);
		
	$html .= '<br /><input type="submit" value="'._('Create binding').'" accesskey="b" />';
    
	$html .= '</div></div></form>';
//bind end

//tilbehor start
		$html .= '<form action="" method="post" onsubmit="return tilbehor('.$id.');">
<div class="toolbox"><a class="menuboxheader" id="tilbehorsheader" style="width:593px;" onclick="showhide(\'tilbehor\',this);">'._('Accessories:').' </a><div style="width:613pxpx;" id="tilbehor"><div id="tilbehore"><br />';
	$tilbehor = $mysqli->fetch_array('SELECT id, tilbehor FROM `tilbehor` WHERE `side` = '.$id);
	$tilbehor_nr = count($tilbehor);
	for($i=0;$i<$tilbehor_nr;$i++) {
		if($tilbehor[$i]['id'] != -1) {
			$kattree = kattree($tilbehor[$i]['kat']);
			$kattree_nr = count($kattree);
			$kattree_html = '';
			for($kattree_i=0;$kattree_i<$kattree_nr;$kattree_i++) {
				$kattree_html .= '/'.trim($kattree[$kattree_i]['navn']);
			}
			$kattree_html .= '/';
			
			$html .= '<p id="tilbehor'.$tilbehor[$i]['id'].'"> <img onclick="slet(\'tilbehor\', \''.addslashes($kattree_html).'\', '.$tilbehor[$i]['id'].')" src="images/cross.png" alt="X" title="'._('Remove binding').'" width="16" height="16" /> ';
			$html .= $kattree_html.'</p>';
		}
	}
    $html .= '</div>';
    $html .= '<div><iframe src="pagelist.php" width="100%" height="300"></iframe></div>';
		
	$html .= '<br /><input type="submit" value="'._('Add accessories').'" accesskey="a" />';
    
	$html .= '</div></div></form>';
//tilbehor end
	
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
		return '<div id="headline">'._('The page does not exist').'</div>';

	$html = '';
	$html .= '<div id="headline">'._('Edit frontpage').'</div><form action="" method="post" onsubmit="return updateForside();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />';

	$subkats = $mysqli->fetch_array('SELECT id, navn, icon FROM `kat` WHERE bind = 0 ORDER BY `order`, `navn`');
	
	$html .= _('Sort maincategories:');
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
		return '<div id="headline">'._('The page does not exist').'</div>';

	$html .= '<div id="headline">'.sprintf(_('Edit %s'), $special[0]['navn']).'</div><form action="" method="post" onsubmit="return updateSpecial('.$id.');"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" />';

	$html .= '<input type="hidden" id="id" />';

	$html .= '<script type="text/javascript"><!--
//Usage: initRTE(imagesPath, includesPath, cssFile, genXHTML)
initRTE("/admin/rtef/images/", "/admin/rtef/", "/theme/rtef-text.css", true);
writeRichText("text", \''.rtefsafe($special[0]['text']).'\', "", '.($GLOBALS['_config']['text_width']+32).', 572, true, false, false);
//--></script></form>';
	
	return $html;
}
	
function getnykrav() {
	$html = '<div id="headline">'._('Create new requirement').'</div><form action="" method="post" onsubmit="return savekrav();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><input type="hidden" name="id" id="id" value="" /><input class="admin_name" type="text" name="navn" id="navn" value="" maxlength="127" size="127" style="width:'.$GLOBALS['_config']['text_width'].'px" /><script type="text/javascript"><!--
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
		
		$html = '<div id="headline">'.sprintf(_('Edit %s sorting'), $liste[0]['navn']).'</div><div>';
		
		$html .= _('Name:').' <input id="listOrderNavn" value="'.$liste[0]['navn'].'"><form action="" method="post" onsubmit="addNewItem(); return false;">'._('New Item:').' <input id="newItem"> <input type="submit" value="tilføj" accesskey="t"></form>';
		
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
		$html = '<div id="headline">'._('List sorting').'</div><div>';
		$html .= '<a href="#" onclick="makeNewList(); return false;">'._('Create new sorting').'</a><br /><br />';
		
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
	
	$html = '<div id="headline">'._('Address Book').'</div><div>';
	$html .= '<table id="addressbook"><thead><tr><td></td><td>'._('Name').'</td><td>'._('E-mail').'</td><td>'._('Phone').'</td></tr></thead><tbody>';
	
	foreach($addresses as $i => $addres) {
		if(!$addres['tlf1'] && $addres['tlf2'])
			$addres['tlf1'] = $addres['tlf2'];
		
		$html .= '<tr id="contact'.$addres['id'].'"';
		
		if($i % 2)
			$html .= ' class="altrow"';
		
		$html .= '><td><a href="?side=editContact&id='.$addres['id'].'"><img width="16" height="16" src="images/vcard_edit.png" alt="R" title="'._('Edit').'" /></a><img onclick="x_deleteContact('.$addres['id'].', removeTagById)" width="16" height="16" src="images/cross.png" alt="X" title="'._('Delete').'" /></td>';
		$html .= '<td>'.$addres['navn'].'</td><td>'.$addres['email'].'</td><td>'.$addres['tlf1'].'</td></tr>';
	}
	
	$html .= '</tbody></table></div>';
	
	return $html;
}

function editContact($id) {
	global $mysqli;
	
	$address = $mysqli->fetch_array('SELECT * FROM `email` WHERE `id` = '.$id);
	
	$html = '<div id="headline">'._('Edit contact person').'</div>';
	$html .= '<form method="post" action="" onsubmit="updateContact('.$_GET['id'].'); return false;"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><table border="0" cellspacing="0"><tbody><tr><td>'._('Name:').'</td><td colspan="2"><input value="'.
	$address[0]['navn'].'" id="navn" /></td></tr><tr><td>'._('E-mail:').'</td><td colspan="2"><input value="'.
	$address[0]['email'].'" id="email" /></td></tr><tr><td>'._('Address:').'</td><td colspan="2"><input value="'.
	$address[0]['adresse'].'" id="adresse" /></td></tr><tr><td>'._('Country:').'</td><td colspan="2"><input value="'.
	$address[0]['land'].'" id="land" /></td></tr><tr><td width="1%">'._('Postal Code:').'</td><td width="1%"><input maxlength="8" size="8" id="post" value="'.
	$address[0]['post'].'" /></td><td align="left" nowrap="nowrap">'._('City:').'<input size="8" id="by" value="'.
	$address[0]['by'].'" /></td></tr><tr><td nowrap="nowrap">'._('Private phone:').'</td><td colspan="2"><input maxlength="11" size="15" id="tlf1" value="'.
	$address[0]['tlf1'].'" /></td></tr><tr><td nowrap="nowrap">'._('Mobile phone:').'</td><td colspan="2"><input maxlength="11" size="15" id="tlf2" value="'.
	$address[0]['tlf2'].'" /></td></tr><tr><td colspan="5"><br /><label for="kartotek"><input value="1" id="kartotek" type="checkbox"';
	if($address[0]['kartotek'])
		$html .= ' checked="checked"';
	$html .= ' />'._('Receive newsletters.').'</label><br />
	<strong>'._('Interests:').'</strong>';
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

	$html = '<div id="headline">'._('Maintenance').'</div><div>
	<div>';
		$html .= '<script type=""><!--
		function set_db_errors(result) {
			if(result != \'\')
				$(\'errors\').innerHTML = $(\'errors\').innerHTML+result;
		}
		
		function scan_db() {
			$(\'loading\').style.visibility = \'\';
			$(\'errors\').innerHTML = \'\';
			
			var starttime = new Date().getTime();
			
			$(\'status\').innerHTML = \''._('Removing news subscribers without contact information').'\';
			x_remove_bad_submisions(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Removing bindings to pages that do not exist').'\';
			x_remove_bad_bindings(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Removing accessories that do not exist').'\';
			x_remove_bad_accessories(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Searching for pages without bindings').'\';
			x_get_orphan_pages(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Searching for pages with illegal bindings').'\';
			x_get_pages_with_mismatch_bindings(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Searching for orphaned lists').'\';
			x_get_orphan_lists(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Searching for orphaned rows').'\';
			x_get_orphan_rows(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Searching for orphaned categories').'\';
			x_get_orphan_cats(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Searching for cirkalur linked categories').'\';
			x_get_looping_cats(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Searching for illegal e-mail adresses').'\';
			x_get_subscriptions_with_bad_emails(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Removes not existing files from the database').'\';
			x_remove_none_existing_files(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Checking the file names').'\';
			x_check_file_names(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Checking the folder names').'\';
			x_check_file_paths(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Deleting temporary files').'\';
			x_delete_tempfiles(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Retrieving the size of the files').'\';
			x_get_size_of_files(function(){});
	
			$(\'status\').innerHTML = \''._('Optimizing the database').'\';
			x_optimize_tables(set_db_errors);
			
			$(\'status\').innerHTML = \''._('Getting Database Size').'\';
			x_get_db_size(function(){});
			
			$(\'status\').innerHTML = \'\';
			$(\'loading\').style.visibility = \'hidden\';
			$(\'errors\').innerHTML = $(\'errors\').innerHTML+\'<br />\'+(\''._('The scan took %d seconds.').'\'.replace(/[%]d/g, Math.round((new Date().getTime()-starttime)/1000).toString()));
		}
		
		
		var mailbox_size = 0;
		function get_mailbox_list_r(result) {
			for(mail=0; mail<result.length; mail++) {
				for(mailbox=0; mailbox<result[mail].length; mailbox++) {
					$(\'status\').innerHTML = \'Læser indholdet i \'+result[mail][mailbox];
					x_get_mailbox_size(mail, result[mail][mailbox], get_mailbox_size_r);
				}
			}
			$(\'mailboxsize\').innerHTML = Math.round(mailbox_size/1024/1024)+\''._('MB').'\';
			$(\'status\').innerHTML = \'\';
			$(\'loading\').style.visibility = \'hidden\';
		}
		
		function get_mailbox_size_r(size) {
			mailbox_size += size;
		}
		--></script><div><b>'._('Server consumption').'</b> - '._('E-mail:').' <span id="mailboxsize"><button onclick="$(\'loading\').style.visibility = \'\'; x_get_mailbox_list(get_mailbox_list_r);">'._('Get e-mail consumption').'</button></span> '._('DB:').' <span id="dbsize">'.number_format(get_db_size(), 1, ',', '')._('MB').'</span> '._('WWW').': <span id="wwwsize">'.number_format(get_size_of_files(), 1, ',', '')._('MB').'</span></div><div id="status"></div><button onclick="scan_db();">'._('Scan database').'</button><div id="errors"></div>';
	
	$emailsCount = $mysqli->fetch_one("SELECT count(*) as 'count' FROM `emails`");
	$emails = $mysqli->fetch_one("SHOW TABLE STATUS LIKE 'emails'");
	
	
	$html .= '<div>'.sprintf(_('Delayed e-mails %d/%d'), $emailsCount['count'], $emails['Auto_increment'] - 1).'</div>';
	
	$html .= '</div>';
	$html .= '</div>';

	return $html;
}
	
//is the email valid
function valide_mail($email) {
	if(preg_match('/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i', $email) && getmxrr(preg_replace('/.+?@(.?)/u', '$1', $email), $dummy))
		return true;
	else
		return false;
}

function get_subscriptions_with_bad_emails() {
	global $mysqli;
	
	$html = '';
	$errors = 0;
	$emails = $mysqli->fetch_array("SELECT `id`, `email` FROM `email` WHERE `email` != ''");
	foreach($emails as $email) {
		if(!valide_mail($email['email'])) {
			$html .= '<a href="?side=editContact&id='.$email['id'].'">'.sprintf(_('E-mail: %s #%d is not valid'), $email['email'], $email['id']).'</a><br />';
		}
	}
	if($html)
		$html = '<b>'._('The following e-mail addresses are not valid').'</b><br />'.$html;
	return $html;
}

function get_orphan_rows() {
	global $mysqli;
	
	$html = '';
	$error = $mysqli->fetch_array('SELECT * FROM `list_rows` WHERE list_id NOT IN (SELECT id FROM lists);');
	if($error) {
		$html .= '<br /><b>'._('The following rows have no lists:').'</b><br />';
		foreach($error as $value) {
			$html .= $value['id'].': '.$value['cells'].' '.$value['link'].'<br />';
		}
	}
	if($html)
		$html = '<b>'._('The following pages have no binding').'</b><br />'.$html;
	return $html;
}

function get_orphan_cats() {
	global $mysqli;
	
	$html = '';
	$error = $mysqli->fetch_array('SELECT `id`, `navn` FROM `kat` WHERE `bind` != 0 AND `bind` != -1 AND `bind` NOT IN (SELECT `id` FROM `kat`);');
	if($error) {
		$html .= '<br /><b>'._('The following categories are orphans:').'</b><br />';
		foreach($error as $value) {
			$html .= '<a href="?side=redigerkat&id='.$value['id'].'">'.$value['id'].': '.$value['navn'].'</a><br />';
		}
	}
	if($html)
		$html = '<b>'._('The following categories have no binding').'</b><br />'.$html;
	return $html;
}

function get_looping_cats() {
	global $mysqli;
	
	$error = $mysqli->fetch_array('SELECT id, bind, navn FROM `kat` WHERE bind != 0 AND bind != -1;');
	
	$html = '';
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
		$html .= '<br /><b>'._('The following categories are tied in itself:').'</b><br />'.$temp_html;

	if($html)
		$html = '<b>'._('The following categories are tied in itself:').'</b><br />'.$html;
	return $html;
}

function check_file_names() {
	global $mysqli;
	$html = '';
	$error = $mysqli->fetch_array('SELECT path FROM `files` WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+[^/]+$\' ORDER BY `path` ASC');
	if($error) {
		if($mysqli->affected_rows > 1) {
			$html .= '<br /><b>'.sprintf(_('The following %d files must be renamed:'), $mysqli->affected_rows).'</b><br /><a onclick="explorer(\'\',\'\');">';
		} else {
			$html .= '<br /><br /><a onclick="explorer(\'\',\'\');">';
		}
		foreach($error as $value) {
			$html .= $value['path'].'<br />';
		}
		$html .= '</a>';
	}
	if($html)
		$html = '<b>'._('The following files must be renamed').'</b><br />'.$html;
	return $html;
}

function check_file_paths() {
	global $mysqli;
	$html = '';
	$error = $mysqli->fetch_array('SELECT path FROM `files` WHERE `path` COLLATE UTF8_bin REGEXP \'[A-Z|_"\\\'`:%=#&+?*<>{}\\]+.*[/]+\' ORDER BY `path` ASC');
	if($error) {
		if($mysqli->affected_rows > 1) {
			$html .= '<br /><b>'.sprintf(_('The following %d files are in a folder that needs to be renamed:'), $mysqli->affected_rows).'</b><br /><a onclick="explorer(\'\',\'\');">';
		} else {
			$html .= '<br /><br /><a onclick="explorer(\'\',\'\');">';
		}
		//TODO only repport one error per folder
		foreach($error as $value) {
			$html .= $value['path'].'<br />';
		}
		$html .= '</a>';
	}
	if($html)
		$html = '<b>'._('The following folders must be renamed').'</b><br />'.$html;
	return $html;
}

function get_size_of_files() {
	global $mysqli;
	$files = $mysqli->fetch_array("SELECT count( * ) AS `count`, sum( `size` ) /1024 /1024 AS `filesize` FROM `files`");
	
	return $files[0]['filesize'];
}

function get_mailbox_list() {
	$mailboxes = array();
	require_once "../inc/imap.inc.php";
	$imap = new IMAPMAIL;
	$imap->open($GLOBALS['_config']['imap'], $GLOBALS['_config']['imapport']);
	
	foreach($GLOBALS['_config']['email'] as $i => $email) {
		$imap->login($email, $GLOBALS['_config']['emailpasswords'][$i]);
		$mailboxes[] = $imap->list_mailbox();
	}
	$imap->close();
	
	return $mailboxes;
}

/*
	
	//todo remove missing maerke from sider->maerke	
	
	
	//TODO test for missing alt="" in img under sider
	//preg_match_all('/<img[^>]+/?>/ui', $value, $matches);
	
	$size = 0;
	$mailsTotal = 0;
	
	require_once "../inc/imap.inc.php";
	$imap = new IMAPMAIL;
	$imap->open($GLOBALS['_config']['imap'], $GLOBALS['_config']['imapport']);
	
	foreach($GLOBALS['_config']['email'] as $i => $email) {
		$imap->login($email, $GLOBALS['_config']['emailpasswords'][$i]);
			
		$mailboxList = $imap->list_mailbox();
		foreach($mailboxList as $mailbox) {
			$mailboxStatus = $imap->open_mailbox($mailbox, true);
			preg_match('/([0-9]+)\sEXISTS/', $mailboxStatus, $mails);
			if(!empty($mails[1])) {
				$mailsTotal += $mails[1];
				preg_match_all('/SIZE\s([0-9]+)/', $imap->fetch_mail('1:'.$mails[1].'', 'FAST'), $mailSizes);
				$size += array_sum($mailSizes[1]);
			}
		}
	}
	$imap->close();
	
	$html .= '<br /><b>Total forbrug '.number_format($siteSize, 1, ',', '').' MB / '.number_format(100/3000*$siteSize, 1, ',', '').'% </b></a><br />';
		*/


function get_orphan_lists() {
	global $mysqli;
	
	$error = $mysqli->fetch_array('SELECT id FROM `lists` WHERE page_id NOT IN (SELECT id FROM sider);');
 	$html = '';
	if($error) {
		$html .= '<br /><b>'._('The following lists are orphans:').'</b><br />';
		foreach($error as $value) {
			$html .= $value['id'].': '.$value['navn'].' '.$value['cell1'].' '.$value['cell2'].' '.$value['cell3'].' '.$value['cell4'].' '.$value['cell5'].' '.$value['cell6'].' '.$value['cell7'].' '.$value['cell8'].' '.$value['cell9'].' '.$value['img'].' '.$value['link'].'<br />';
		}
	}
	if($html)
		$html = '<b>'._('The following lists are not tied to any page').'</b><br />'.$html;
	return $html;
}

function get_db_size() {
	global $mysqli;
	
	$tabels = $mysqli->fetch_array("SHOW TABLE STATUS");
	$dbsize = 0;
	foreach($tabels as $tabel) {
		$dbsize += $tabel['Data_length'];
		$dbsize += $tabel['Index_length'];
	}
	return $dbsize/1024/1024;
}

function get_orphan_pages() {
	global $mysqli;
	
	$html = '';
	$sider = $mysqli->fetch_array("SELECT `id`, `navn`, `varenr` FROM `sider` WHERE `id` NOT IN(SELECT `side` FROM `bind`);");
	foreach($sider as $side) {
		$html .= '<a href="?side=redigerside&amp;id='.$side['id'].'">'.$side['id'].': '.$side['navn'].'</a><br />';
	}
	
	if($html)
		$html = '<b>'._('The following pages have no binding').'</b><br />'.$html;
	return $html;
}

function get_pages_with_mismatch_bindings() {
	global $mysqli;
	
	$sider = $mysqli->fetch_array("SELECT `id`, `navn`, `varenr` FROM `sider`;");
	$html = '';
	foreach($sider as $value) {
		$bind = $mysqli->fetch_array("SELECT `kat` FROM `bind` WHERE `side` = ".$value['id']);
		//Add active pages that has a list that links to this page
		$listlinks = $mysqli->fetch_array("SELECT `bind`.`kat` FROM `list_rows` JOIN `lists` ON `list_rows`.`list_id` = `lists`.`id` JOIN `bind` ON `lists`.`page_id` = `bind`.`side` WHERE `list_rows`.`link` = ".$value['id']);
		foreach($listlinks as $listlink) {
			if(binding($listlink['kat']) == 0) {
				$bind[]['kat'] = $listlink['kat'];
			}
		}
		
		//Is there any mismatches of the root bindings
		if(count($bind) > 1) {
			$binding = binding($bind[0]['kat']);
			foreach($bind as $enbind) {
				if($binding != binding($enbind['kat'])) {
					$html .= '<a href="?side=redigerside&amp;id='.$value['id'].'">'.$value['id'].': '.$value['navn'].'</a><br />';
					continue 2;
				}
			}
		}
	}
	
	if($html)
		$html = '<b>'._('The following pages are both active and inactive').'</b><br />'.$html;
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
	//Søge ord (separere søge ord med et komma "Emergency Blanket, Redningstæppe"):
	$html .= _('Search word (separate search words with a comma \'Emergency Blanket, Rescue Blanket\'):').'<br /><textarea name="keywords" id="keywords" style="width:'.$GLOBALS['_config']['text_width'].'px;max-width:'.$GLOBALS['_config']['text_width'].'px" rows="2" cols=""></textarea>';
	//Beskrivelse start
	$html .= '<div class="toolbox"><a class="menuboxheader" id="beskrivelseboxheader" style="width:'.($GLOBALS['_config']['thumb_width']+14).'px" onclick="showhide(\'beskrivelsebox\',this);">'._('Description:').' </a><div style="text-align:center;width:'.($GLOBALS['_config']['thumb_width']+34).'px" id="beskrivelsebox"><br /><input type="hidden" value="'._('/images/web/intet-foto.jpg').'" id="billed" name="billed" /><img id="billedthb" src="'._('/images/web/intet-foto.jpg').'" alt="" onclick="explorer(\'thb\', \'billed\')" /><br /><img onclick="explorer(\'thb\', \'billed\')" src="images/folder_image.png" width="16" height="16" alt="'._('Pictures').'" title="'._('Find image').'" /><img onclick="setThb(\'billed\', \'\', \''._('/images/web/intet-foto.jpg').'\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" width="16" height="16" /><script type="text/javascript"><!--
writeRichText("beskrivelse", \'\', "", '.($GLOBALS['_config']['thumb_width']+32).', 115, false, false, false);
//--></script></div></div>';
	//Beskrivelse end
	//Pris start
	$html .= '<div class="toolbox"><a class="menuboxheader" id="priserheader" style="width:230px" onclick="showhide(\'priser\',this);">'._('Price:').' </a><div style="width:250px;" id="priser"><table style="width:100%"><tr><td><select name="burde" id="burde"><option value="0">'._('Before').'</option><option value="1">'._('Indicative price').'</option></select></td><td style="text-align:right"><input class="XPris" onkeypress="return checkForInt(event)" onchange="prisHighlight()" value="" name="for" id="for" size="11" maxlength="11" style="width:100px;text-align:right" />,-</td></tr><tr><td><select name="fra" id="fra"><option value="0">'._('Price').'</option><option value="1">'._('From').'</option></select></td><td style="text-align:right"><input value="" class="Pris" name="pris" id="pris" size="11" maxlength="11" style="width:100px;text-align:right" onkeypress="return checkForInt(event)" onchange="prisHighlight()" />,-</td></tr></table></div></div>';
	//Pris end
	//misc start
	$html .= '<div class="toolbox"><a class="menuboxheader" id="miscboxheader" style="width:201px" onclick="showhide(\'miscbox\',this);">'._('Other:').' </a><div style="width:221px" id="miscbox">'._('SKU:').' <input type="text" name="varenr" id="varenr" maxlength="63" style="text-align:right;width:128px" value="" /><br /><img src="images/page_white_key.png" width="16" height="16" alt="" /><select id="krav" name="krav"><option value="0">'._('None').'</option>';
	$krav = $mysqli->fetch_array('SELECT id, navn FROM `krav`');
	$krav_nr = count($krav);
	for($i=0;$i<$krav_nr;$i++) {
		$html .= '<option value="'.$krav[$i]['id'].'"';
		$html .= '>'.htmlspecialchars($krav[$i]['navn']).'</option>';
	}
	$html .= '</select><br /><img width="16" height="16" alt="" src="images/page_white_medal.png"/><select id="maerke" name="maerke" multiple="multiple" size="10"><option value="0">'._('All others').'</option>';
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
		$kattree[count($kattree)-1]['navn'] = _('Frontpage');
	} else {
		$kattree[]['id'] = -1;
		$kattree[count($kattree)-1]['navn'] = _('Inactive');
	}
	return array_reverse($kattree);
}

function katspath($id) {
	$kattree = kattree($id);
	$nr = count($kattree);
	$html = _('Select location:').' ';
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
		$html .= _('Select location:').' ';
	}
	$html .= '</a><div style="width:'.($GLOBALS['_config']['text_width']+24).'px;';
	if(@$_COOKIE['hidekats']) {
		$html .= 'display:none;';
	}
	$html .= '" id="kats"><div>';
	$kattree = kattree($id);
	foreach($kattree as $i => $value) {
		$kattree[$i] = $value['id'];
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
	$html .= ' /><img src="images/folder.png" width="16" height="16" alt="" /> '._('Inactive').'</a><div id="kat-1content" style="margin-left:16px">';
	if(array_search(-1, $openkat) || false !== array_search('-1', $kattree)) {
		$temp = kat_expand(-1, true);
		$html .= $temp['html'];
	}
	$html .= '</div></div><div>';
	if($mysqli->fetch_array('SELECT id FROM `kat` WHERE bind = 0 LIMIT 1')) {
		$html .= '<img style="';
		if(array_search(0, $openkat) || false !== array_search('0', $kattree)) { $html .= 'display:none;'; }
		$html .= '" src="images/+.gif" id="kat0expand" onclick="kat_expand(0, true, kat_expand_r);" height="16" width="16" alt="+" title="" /><img style="';
		if(!array_search(0, $openkat) && false === array_search('0', $kattree)) { $html .= 'display:none;'; }
		$html .= '" src="images/-.gif" id="kat0contract" onclick="kat_contract(\'0\');" height="16" width="16" alt="-" title="" /><a';
	} else {
		$html .= '<a style="margin-left:16px"';
	} 
	$html .= ' onclick="this.firstChild.checked=true;setCookie(\'activekat\', 0, 360);"><input type="radio" name="kat" value="0"';
	if(!$kattree[count($kattree)-1]) {
		$html .= ' checked="checked"';
	}
	$html .= ' /><img src="images/folder.png" width="16" height="16" alt="" /> '._('Frontpage').'</a><div id="kat0content" style="margin-left:16px">';
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
		foreach($kattree as $i => $value) {
			$kattree[$i] = $value['id'];
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
	$html .= '><img src="images/folder.png" width="16" height="16" alt="" /> '._('Inactive').'</a><div id="kat-1content" style="margin-left:16px">';
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
	$html .= ' href="?side=redigerFrontpage"><img src="images/page.png" width="16" height="16" alt="" /> '._('Frontpage').'</a><div id="kat0content" style="margin-left:16px">';
	if(array_search(0, $openkat) || false !== array_search('0', $kattree)) {
		$temp = siteList_expand(0);
		$html .= $temp['html'];
	}
	$html .= '</div></div>';
	return $html;
}

function pages_expand($id) {
	global $mysqli;
	$html = '';

	$temp = kat_expand($id, false);
	$html .= $temp['html'];
	$sider = $mysqli->fetch_array('SELECT sider.id, sider.varenr, bind.id as bind, navn FROM `bind` LEFT JOIN sider on bind.side = sider.id WHERE `kat` = '.$id.' ORDER BY sider.navn');
	$nr = count($sider);
	foreach($sider as $side) {
			$html .= '<div id="bind'.$side['bind'].'" class="side'.$side['id'].'"><a style="margin-left:16px" class="side">
			<a class="kat" onclick="this.firstChild.checked=true;"><input name="side" type="radio" value="'.$side['id'].'" />
			<img src="images/page.png" width="16" height="16" alt="" /> '.strip_tags($side['navn'],'<img>');
			if($side['varenr'])
				$html .= ' <em>#:'.$side['varenr'].'</em>';
			$html .= '</a></div>';
	}
	return array('id' => $id, 'html' => $html);
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
	$html = '<div id="headline">'._('Create category').'</div><form action="" onsubmit="return save_ny_kat()"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><div>'._('Name:').' <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" src="images/folder.png" title="" alt="'._('Pictures').'" id="iconthb" /> <input id="navn" style="width:256px;" maxlength="64" /> <br /> '._('Icon:').' <input id="icon" style="width:247px;" maxlength="128" type="hidden" /> <img style="cursor:pointer;vertical-align:bottom" onclick="explorer(\'thb\',\'icon\')" width="16" height="16" src="images/folder_image.png" title="'._('Find pictures').'" alt="'._('Pictures').'" /> <img style="cursor:pointer;vertical-align:bottom" onclick="setThb(\'icon\',\'\',\'images/folder.png\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" height="16" width="16" /><br /><br />';
	
	//Email
	$html .= _('Contact:').' <select id="email">';
	foreach($GLOBALS['_config']['email'] as $email) {
		$html .= '<option value="'.$email.'">'.$email.'</option>';
	}
	$html .= '</select>';
	
	//Visning
	$html .= '<br />'._('Display:').' <select id="vis"><option value="0">'._('Hide').'</option><option value="1" selected="selected">'._('Gallery').'</option><option value="2">'._('List').'</option></select>';
	
	//binding
	if(@$_COOKIE['activekat'] >= -1)
		$html .= katlist(@$_COOKIE['activekat']);
	else
		$html .= katlist(-1);
	
	$html .= '<br /></div></form>';
	return array('id' => 'canvas', 'html' => $html);
}

function getSiteTree() {
	$html = '<div id="headline">'._('Overview').'</div><div>';
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
		return array('error' => _('You must enter a name and choose a location for the new category.'));
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
		return array('error' => _('You must enter a name and a text of the requirement.'));
		
}

function getsogogerstat() {
	echo '<div id="headline">'._('Find and replace').'</div><form onsubmit="sogogerstat(document.getElementById(\'sog\').value,document.getElementById(\'erstat\').value,inject_html); return false;"><img src="images/error.png" width="16" height="16" alt="" > '._('This function affects all pages.').'<table cellspacing="0"><tr><td>'._('Find:').' </td><td><input id="sog" style="width:256px;" maxlength="64" /></td></tr><tr><td>'._('Replace:').' </td><td><input id="erstat" style="width:256px;" maxlength="64" /></td></tr></table><br /><br /><input value="'._('Find and replace').'" type="submit" accesskey="r" /></form>';
}

function sogogerstat($sog, $erstat) {
	global $mysqli;

	$mysqli->query('UPDATE sider SET text = REPLACE(text,\''.$sog.'\',\''.$erstat.'\')');

	return $mysqli->affected_rows;
}

function getmaerker() {
	global $mysqli;

	$html = '<div id="headline">'._('List of brands').'</div><form action="" id="maerkerform" onsubmit="x_save_ny_maerke(document.getElementById(\'navn\').value,document.getElementById(\'link\').value,document.getElementById(\'ico\').value,inject_html); return false;"><table cellspacing="0"><tr style="height:21px"><td>'._('Name:').' </td><td><input id="navn" style="width:256px;" maxlength="64" /></td><td rowspan="4"><img id="icoimage" src="" style="display:none" alt="" /></td></tr><tr style="height:21px"><td>'._('Link:').' </td><td><input id="link" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>'._('Logo:').' </td>
	<td style="text-align:center"><input type="hidden" value="'._('/images/web/intet-foto.jpg').'" id="ico" name="ico" /><img id="icothb" src="'._('/images/web/intet-foto.jpg').'" alt="" onclick="explorer(\'thb\', \'ico\')" /><br /><img onclick="explorer(\'thb\', \'ico\')" src="images/folder_image.png" width="16" height="16" alt="'._('Pictures').'" title="'._('Find image').'" /><img onclick="setThb(\'ico\', \'\', \''._('/images/web/intet-foto.jpg').'\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" width="16" height="16" /></td>
	</tr><tr><td></td></tr></table><p><input value="'._('Add brand').'e" type="submit" accesskey="s" /><br /><br /></p><div id="imagelogo" style="display:none; position:absolute;"></div>';
	$mærker = $mysqli->fetch_array('SELECT * FROM `maerke` ORDER BY navn');
	$nr = count($mærker);
	for($i=0;$i<$nr;$i++) {
		$html .= '<div id="maerke'.$mærker[$i]['id'].'"><a href="" onclick="slet(\'maerke\',\''.addslashes($mærker[$i]['navn']).'\','.$mærker[$i]['id'].');"><img src="images/cross.png" alt="X" title="'._('Delete').' '.htmlspecialchars($mærker[$i]['navn']).'!" width="16" height="16"';
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
	
	$html = '<div id="headline">'.sprintf(_('Edit the brand %d'), $mærker[0]['navn']).'</div><form onsubmit="x_updatemaerke('.$id.',document.getElementById(\'navn\').value,document.getElementById(\'link\').value,document.getElementById(\'ico\').value,inject_html); return false;"><table cellspacing="0"><tr style="height:21px"><td>'._('Name:').' </td><td><input value="'.htmlspecialchars($mærker[0]['navn']).'" id="navn" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>Link: </td><td><input value="'.htmlspecialchars($mærker[0]['link']).'" id="link" style="width:256px;" maxlength="64" /></td></tr><tr style="height:21px"><td>'._('Logo:').' </td>
	<td style="text-align:center"><input type="hidden" value="'.htmlspecialchars($mærker[0]['ico']).'" id="ico" name="ico" /><img id="icothb" src="';
	if($mærker[0]['ico'])
		$html .= $mærker[0]['ico'];
	else
		$html .= _('/images/web/intet-foto.jpg');
	$html .= '" alt="" onclick="explorer(\'thb\', \'ico\')" /><br /><img onclick="explorer(\'thb\', \'ico\')" src="images/folder_image.png" width="16" height="16" alt="'._('Pictures').'" title="'._('Find image').'" /><img onclick="setThb(\'ico\', \'\', \''._('/images/web/intet-foto.jpg').'\')" src="images/cross.png" alt="X" title="'._('Remove picture').'" width="16" height="16" /></td>
	</tr><tr><td></td></tr></table><br /><br /><input value="'._('Save brand').'" type="submit" accesskey="s" /><br /><br /><div id="imagelogo" style="display:none; position:absolute;"></div></form>';
	return $html;
}

function updatemaerke($id, $navn, $link, $ico) {
	global $mysqli;

	if($navn) {
		$mysqli->query('UPDATE maerke SET navn = \''.$navn.'\', link = \''.$link.'\', ico = \''.$ico.'\' WHERE id = '.$id);
		return array('id' => 'canvas', 'html' => getmaerker());
	} else
		return array('error' => _('You must enter a name.'));
}

function save_ny_maerke($navn, $link, $ico) {
	global $mysqli;

	if($navn) {
		$mysqli->query('INSERT INTO `maerke` (`navn` , `link` , `ico` ) VALUES (\''.$navn.'\', \''.$link.'\', \''.$ico.'\')');
		return array('id' => 'canvas', 'html' => getmaerker());
	} else
		return array('error' => _('You must enter a name.'));
}

function getkrav() {
	global $mysqli;

	$html = '<div id="headline">'._('Requirements list').'</div><div style="margin:16px;"><a href="?side=nykrav">Tilføj krav</a>';
	$krav = $mysqli->fetch_array('SELECT id, navn FROM `krav` ORDER BY navn');
	$nr = count($krav);
	for($i=0;$i<$nr;$i++) {
		$html .= '<div id="krav'.$krav[$i]['id'].'"><a href="" onclick="slet(\'krav\',\''.addslashes($krav[$i]['navn']).'\','.$krav[$i]['id'].');"><img src="images/cross.png" title="Slet '.$krav[$i]['navn'].'!" width="16" height="16" /></a><a href="?side=editkrav&amp;id='.$krav[$i]['id'].'">'.$krav[$i]['navn'].'</a></div>';
	}
	$html .= '</div>';
	return $html;
}


function editkrav($id) {
	global $mysqli;
	
	$krav = $mysqli->fetch_array('SELECT navn, text FROM `krav` WHERE id = '.$id);
	
	$html = '<div id="headline">'.sprintf(_('Edit %s'), $krav[0]['navn']).'</div><form action="" method="post" onsubmit="return savekrav();"><input type="submit" accesskey="s" style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" /><input type="hidden" name="id" id="id" value="'.$id.'" /><input class="admin_name" type="text" name="navn" id="navn" value="'.$krav[0]['navn'].'" maxlength="127" size="127" style="width:587px" /><script type="text/javascript"><!--
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
		return array('error' => _('The binding does not exist.'));
	$mysqli->query('DELETE FROM `bind` WHERE `id` = '.$id.' LIMIT 1');
	$delete[0]['id'] = $id;
	if(!$mysqli->fetch_array('SELECT id FROM `bind` WHERE `side` = '.$bind[0]['side'].' LIMIT 1')) {
		$mysqli->query('INSERT INTO `bind` (`side` ,`kat`) VALUES (\''.$bind[0]['side'].'\', \'-1\')');
	
		global $mysqli;
		$added['id'] = $mysqli->insert_id;
		$added['path'] = '/'._('Inactive').'/';
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
		return array('error' => _('The binding already exists.'));
	
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
							array('/&lt;/u', '/&gt;/u', '/&amp;/u', '/[?]/u', '/\s+/u'),
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
						//Double encode importand encodings, to survive html_entity_decode and remove white space
						preg_replace(
							array('/&lt;/u',  '/&gt;/u',  '/&amp;/u',  '/[?]/u', '/\s+/'),
							array('&amp;lt;', '&amp;gt;', '&amp;amp;', ' ',    ' '),
							trim($text)
						),
						ENT_QUOTES,
						'UTF-8'
					)
				)
			)
		);
	}
}

function updateSide($id, $navn, $keywords, $pris, $billed, $beskrivelse, $for, $text, $varenr, $burde, $fra, $krav, $maerke) {
	global $mysqli;
	$mysqli->query("UPDATE `sider` SET `dato` = now(), `navn` = '".addcslashes($navn, "'\\")."', `keywords` = '".addcslashes($keywords, "'\\")."', `pris` = '".addcslashes($pris, "'\\")."', `text` = '".htmlUrlDecode($text)."', `varenr` = '".addcslashes($varenr, "'\\")."', `for` = '".addcslashes($for, "'\\")."', `beskrivelse` = '".htmlUrlDecode($beskrivelse)."', `krav` = '".addcslashes($krav, "'\\")."', `maerke` = '".addcslashes($maerke, "'\\")."', `billed` = '".addcslashes($billed, "'\\")."', `fra` = ".addcslashes($fra, "'\\").", `burde` = ".addcslashes($burde, "'\\")." WHERE `id` = ".addcslashes($id, "'\\")." LIMIT 1");
	return true;
}

function updateKat($id, $navn, $bind, $icon, $vis, $email, $custom_sort_subs, $subsorder) {
	$bindtree = kattree($bind);
	foreach($bindtree as $bindbranch)
		if($id == $bindbranch['id'])
			return array('error' => _('The category can not be placed under itself.'));
	
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

	$mysqli->query('INSERT INTO `sider` (`dato` ,`navn` ,`keywords` ,`pris` ,`text` ,`varenr` ,`for` ,`beskrivelse` ,`krav` ,`maerke` ,`billed` ,`fra` ,`burde` ) VALUES (now(), \''.addcslashes($navn, "'\\").'\', \''.addcslashes($keywords, "'\\").'\', \''.addcslashes($pris, "'\\").'\', \''.htmlUrlDecode($text).'\', \''.addcslashes($varenr, "'\\").'\', \''.addcslashes($for, "'\\").'\', \''.htmlUrlDecode($beskrivelse).'\', \''.addcslashes($krav, "'\\").'\', \''.addcslashes($maerke, "'\\").'\', \''.addcslashes($billed, "'\\").'\', '.addcslashes($fra, "'\\").', '.addcslashes($burde, "'\\").')');
	
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
	array('name' => 'getSiteTree', 'method' => 'GET'),
	array('name' => 'get_db_size', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'optimize_tables', 'uri' => '/maintain.php', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'remove_bad_bindings', 'uri' => '/maintain.php', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'remove_bad_accessories', 'uri' => '/maintain.php', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'remove_bad_submisions', 'uri' => '/maintain.php', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'get_orphan_pages', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'get_pages_with_mismatch_bindings', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'get_orphan_lists', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'get_orphan_rows', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'get_orphan_cats', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'get_looping_cats', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'get_subscriptions_with_bad_emails', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'remove_none_existing_files', 'uri' => '/maintain.php', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'check_file_names', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'check_file_paths', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'delete_tempfiles', 'uri' => '/maintain.php', 'method' => 'POST', "asynchronous" => false),
	array('name' => 'get_size_of_files', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'get_mailbox_list', 'method' => 'GET', "asynchronous" => false),
	array('name' => 'get_mailbox_size', 'uri' => 'get_mailbox_size.php', 'method' => 'GET', "asynchronous" => false)
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
var JSON = JSON || {};
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
