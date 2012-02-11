<?php

//Get last update time for table
function getUpdateTime($table) {
	global $mysqli;
	if (!@$GLOBALS['cache']['updatetime'][$table]) {
		$updatetime = $mysqli->fetch_array("SHOW TABLE STATUS LIKE '".$table."'");
		$GLOBALS['cache']['updatetime'][$table] = strtotime($updatetime[0]['Update_time']);
	}
}

//er der sider bundet til katagorien
function skriv($id) {
	global $mysqli;

	if (@$GLOBALS['cache']['kats'][$id]['skriv']) {
		return true;
	} elseif (@$GLOBALS['cache']['kats'][$id]['skriv'] === false) {
		return false;
	}
	
	//er der en side på denne kattegori
	if ($sider = $mysqli->fetch_array('SELECT id FROM bind WHERE kat = '.$id)) {
		getUpdateTime('bind');
		$GLOBALS['cache']['kats'][$id]['skriv'] = true;
		return true;
	}
	
	//ellers kig om der er en under kattegori med en side
	$kat = $mysqli->fetch_array("SELECT kat.id, bind.id as skriv FROM kat JOIN bind ON bind.kat = kat.id WHERE kat.bind = $id GROUP BY kat.id");

	getUpdateTime('kat');
	
	//cache all results
	foreach ($kat as $value) {
		if ($value['skriv']) {
			$GLOBALS['cache']['kats'][$value['id']]['skriv'] = true;
			$return = true;
			//return true if there was a hit, but wait untill foreach is done so we don't risk more SQL if some of the fetched results will be needed.
		}
	}

	if ($return = false) {
		$GLOBALS['cache']['kats'][$id]['skriv'] = true;
		return true;
	}
	
	//Search deeper if a result wasn't found yet
	foreach ($kat as $value) {
		if (skriv($value['id'])) {
			$GLOBALS['cache']['kats'][$value['id']]['skriv'] = true;
			return true;
		} else {
			//This category is empty or only contains empty categorys
			$GLOBALS['cache']['kats'][$value['id']]['skriv'] = false;
			return false;
		}
	}
}

//Har denne katagori under katagorier med indhould
//Afhngi af skriv()
function subs($kat) {
	global $mysqli;

	$sub = $mysqli->fetch_array("SELECT id FROM kat WHERE bind = $kat ORDER BY navn");
	
	getUpdateTime('kat');
	
	foreach ($sub as $value) {
		//er der sider bundet til katagorien
		if (skriv($value['id'])) {
			return 1;       
		}
	}
}

function clear_file_name($name) {
	$search = array('/[&?\/:*"<>|%\s-_#\\\\]+/u', '/^\s+|\s+$/u', '/\s+/u');
	$replace = array(' ', '', '-');
	return preg_replace($search, $replace, $name);
}

function array_natsort($aryData, $strIndex, $strSortBy, $strSortType=false) {
	//Make sure the sort by is a string
	$strSortBy .= '';
	//Make sure the index is a string
	$strIndex .= '';
	
	//if the parameters are invalid
	if (!is_array($aryData) || $strIndex === '' || $strSortBy === '')
		return $aryData;
	
	//ignore
	$match = array();
	$replace = '';
	
	//create our temporary arrays
	$arySort = $aryResult = array();
	//print_r($aryData);
	
	//loop through the array
	foreach ($aryData as $aryRow)
		//set up the value in the array
		$arySort[$aryRow[$strIndex]] = str_replace($match, $replace, $aryRow[$strSortBy]);
	
	//apply the natural sort
	natcasesort($arySort);
	
	//if the sort type is descending
	if ($strSortType == 'desc' || $strSortType == '-' )
	//reverse the array
		arsort($arySort);
	
	//loop through the sorted and original data
	foreach ($arySort as $arySortKey => $arySorted)
		foreach ($aryData as $aryOriginal)
		//if the key matches
			if ($aryOriginal[$strIndex]==$arySortKey) {
				//add it to the output array
				array_push($aryResult, $aryOriginal);
				break;
			}
	
	//return the result
	return $aryResult;
}

function array_listsort($aryData, $strIndex, $strSortBy, $strSortType=false, $intSortingOrder) {
	global $mysqli;
	
	//Open database
	if (!isset($mysqli))
		$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
		

	if (!is_array($aryData) || !$strIndex || !$strSortBy)
		return $aryData;
	
	if ($kaliber = $mysqli->fetch_array('SELECT text FROM `tablesort` WHERE id = '.$intSortingOrder))
		$kaliber = explode('<', $kaliber[0]['text']);
	
	getUpdateTime('tablesort');
	
	$arySort = $aryResult = array();
	
	foreach ($aryData as $aryRow) {
		$arySort[$aryRow[$strIndex]] = -1;
		foreach ($kaliber as $kalKey => $kalSort)
			if ($aryRow[$strSortBy]==$kalSort) {
				$arySort[$aryRow[$strIndex]] = $kalKey;
					break;
			}
	}				
	
	natcasesort($arySort);
	
	echo $strSortType;
	
	if ($strSortType=="desc" || $strSortType=="-" )
		arsort($arySort);
	
	foreach ($arySort as $arySortKey => $arySorted)
		foreach ($aryData as $aryOriginal)
			if ($aryOriginal[$strIndex]==$arySortKey) {
				array_push($aryResult, $aryOriginal);
				break;
			}
	
	return $aryResult;
}

//Quick function to trim arrays
function trim_value(&$value) { 
   $value = trim($value); 
}

function trim_array($totrim) {
   if (is_array($totrim)) {
       $totrim = array_map("trim_array", $totrim);
   } else {
       $totrim = trim($totrim);
   }
   return $totrim;
}

//return html for a sorted table
function get_table($listid, $bycell, $current_kat) {
	global $mysqli;
				
	$html = '';
	
	getUpdateTime('lists');
	$lists = $mysqli->fetch_array('SELECT * FROM `lists` WHERE id = '.$listid);
	
	getUpdateTime('list_rows');
	if ($rows = $mysqli->fetch_array('SELECT * FROM `list_rows` WHERE `list_id` = '.$listid)) {
	
		//Explode sorts
		$lists[0]['sorts'] = explode('<', $lists[0]['sorts']);
		$lists[0]['cells'] = explode('<', $lists[0]['cells']);
		$lists[0]['cell_names'] = explode('<', $lists[0]['cell_names']);
		
		if (!$bycell && $bycell !== '0') {
			$bycell = $lists[0]['sort'];
		}
	
		//Explode cells
		foreach ($rows as $row) {
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
		if ($lists[0]['sorts'][$bycell] < 1)
			$rows = array_natsort($rows, 'id' , $bycell);
		else
			$rows = array_listsort($rows, 'id', $bycell, NULL, $lists[0]['sorts'][$bycell]);
		
		//unset temp holder for rows
		
		$html .= '<table class="tabel">';
		if ($lists[0]['title']) {
			$html .= '<caption>'.$lists[0]['title'].'</caption>';
		}
		$html .= '<thead><tr>';
		foreach ($lists[0]['cell_names'] as $key => $cell_name) {
			$html .= '<td><a href="" onclick="x_get_table(\''.$lists[0]['id'].'\', \''.$key.'\', '.$current_kat.', inject_html);return false;">'.$cell_name.'</a></td>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ($rows as $i => $row) {
			$html .= '<tr';
			if ($i % 2)
				$html .= ' class="altrow"';
			$html .= '>';
			if ($row['link']) {
				getUpdateTime('sider');
				getUpdateTime('kat');
				$sider = $mysqli->fetch_array('SELECT `sider`.`navn`, `kat`.`navn` AS `kat_navn` FROM `sider` JOIN `kat` ON `kat`.`id` = '.$current_kat.' WHERE `sider`.`id` = '.$row['link'].' LIMIT 1');
				$row['link'] = '<a href="/kat'.$current_kat.'-'.clear_file_name($sider[0]['kat_navn']).'/side'.$row['link'].'-'.clear_file_name($sider[0]['navn']).'.html">';
			}
			foreach ($lists[0]['cells'] as $key => $type) {
				if (empty($row[$key])) {
                	$row[$key] = '';
                }

				switch($type) {
					case 0:
						//Plain text
						$html .= '<td>';
						if ($row['link'])
							$html .= $row['link'];
						$html .= $row[$key];
						if ($row['link'])
							$html .= '</a>';
						$html .= '</td>';
						break;
					case 1:
						//number
						$html .= '<td style="text-align:right;">';
						if ($row['link'])
							$html .= $row['link'];
						$html .= $row[$key];
						if ($row['link'])
							$html .= '</a>';
						$html .= '</td>';
						break;
					case 2:
						//price
						$html .= '<td style="text-align:right;" class="Pris">';
						if ($row['link'])
							$html .= $row['link'];
						if (is_numeric(@$row[$key]))
							$html .= str_replace(',00', ',-', number_format($row[$key], 2, ',', '.'));
						else
							$html .= @$row[$key];
						if ($row['link'])
							$html .= '</a>';
						$html .= '</td>';
						$GLOBALS['generatedcontent']['has_product_table'] = true;
						break;
					case 3:
						//new price
						$html .= '<td style="text-align:right;" class="NyPris">';
						if ($row['link'])
							$html .= $row['link'];
						if (is_numeric(@$row[$key]))
							$html .= str_replace(',00', ',-', number_format($row[$key], 2, ',', '.'));
						else
							$html .= @$row[$key];
						if ($row['link'])
							$html .= '</a>';
						$html .= '</td>';
						$GLOBALS['generatedcontent']['has_product_table'] = true;
						break;
					case 4:
						//pold price
						$html .= '<td style="text-align:right;" class="XPris">';
						if ($row['link'])
							$html .= $row['link'];
						if (is_numeric(@$row[$key]))
							$html .= str_replace(',00', ',-', number_format($row[$key], 2, ',', '.'));
						if ($row['link'])
							$html .= '</a>';
						$html .= '</td>';
						break;
					case 5:
						//image
						$html .= '<td>';
						$files = $mysqli->fetch_array('SELECT * FROM `files` WHERE path = '.$row[$key].' LIMIT 1');
						
						getUpdateTime('files');
						
						//TODO make image tag
						if ($row['link'])
							$html .= $row['link'];
						$html .= '<img src="'.$row[$key].'" alt="'.$files[0]['alt'].'" title="" width="'.$files[0]['width'].'" height="'.$files[0]['height'].'" />';
						if ($row['link'])
							$html .= '</a>';
						$html .= '</td>';
						break;
						
				}
			}
			if (@$GLOBALS['generatedcontent']['has_product_table'])
				$html .= '<td class="addtocart"><a href="/bestilling/?add_list_item='.$row['id'].'"><img src="/theme/images/cart_add.png" title="'._('Add to shopping cart').'" alt="+" /></a></td>';
			$html .= '</tr>';
		}
		
		$html .= '</tbody></table>';
	}
	
	
	$updatetime = 0;
	$included_files = get_included_files();
	foreach ($included_files as $filename) {
		$GLOBALS['cache']['updatetime']['filemtime'] = max($GLOBALS['cache']['updatetime']['filemtime'], filemtime($filename));
	}
	foreach ($GLOBALS['cache']['updatetime'] as $time) {
		$updatetime = max($updatetime, $time);
	}
	if ($updatetime < 1)
		$updatetime = time();
	
	doConditionalGet($updatetime);

	return array('id' => 'table'.$listid, 'html' => $html);
}

//print out the table
function echo_table($sideid, $mansort, $desc) {
	global $mysqli;

	$tablesort = $mysqli->fetch_array('SELECT `navn`, `text` FROM `tablesort` ORDER BY `id`');
	
	getUpdateTime('tablesort');
	
	foreach ($tablesort as $value) {
		$GLOBALS['tablesort_navn'][] = $value['navn'];
		$GLOBALS['tablesort'][] = trim_array(explode(',', $value['text']));
	}
	//----------------------------------

	$lists = $mysqli->fetch_array('SELECT id FROM `lists` WHERE `page_id` = '.$sideid);
	
	getUpdateTime('lists');
	
	foreach ($lists as $list) {
		$html = '<div id="table'.$list['id'].'">';
		
		$table_html = get_table($list['id'], NULL, $GLOBALS['generatedcontent']['activmenu']);
		$html .= $table_html['html'];
		$html .= '</div>';
	}
	
	if (!isset($html))
		$html = '';

	return $html;
}

//Find stien til katagorien
function kats($id) {
	global $mysqli;

	$kat = $mysqli->fetch_array('SELECT bind FROM kat WHERE id = '.$id);
	
	getUpdateTime('kat');
	
	if ($kat) {
		$data =  kats($kat[0]['bind']);
		$nr = count($data);
		$kats[0] = $id;
		foreach ($data as $value){
			$kats[] = $value;
		}
	}
	
	if (!isset($kats))
		$kats = array();
	
	return $kats;
}

//Search for root.
function binding($bind) {
	global $mysqli;

	if ($bind > 0) {
		$sog_kat = $mysqli->fetch_array("SELECT `bind` FROM `kat` WHERE id = '".$bind."'");
		
		getUpdateTime('kat');
		
		return binding($sog_kat[0]['bind']);
	} else
		return $bind;
}

//Used with array_filter() to make a 2d array uniqe
function uniquecol($array) {
  static $idlist = array();

  if (in_array($array['id'], $idlist))
    return false;

  $idlist[] = $array['id'];
  return true;
}

?>
