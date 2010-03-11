<?php

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
require_once '../inc/header.php';

//Check if file is in use
function isinuse($path) {
	global $mysqli;
	
	if($mysqli->fetch_array("(SELECT id FROM `sider` WHERE `text` LIKE '%$path%' OR `beskrivelse` LIKE '%$path%' OR `billed` LIKE '$path' LIMIT 1)
	UNION (SELECT id FROM `template` WHERE `text` LIKE '%$path%' OR `beskrivelse` LIKE '%$path%' OR `billed` LIKE '$path' LIMIT 1)
	UNION (SELECT id FROM `special` WHERE `text` LIKE '%$path%' LIMIT 1)
	UNION (SELECT id FROM `krav` WHERE `text` LIKE '%$path%' LIMIT 1)
	UNION (SELECT id FROM `maerke` WHERE `ico` LIKE '$path' LIMIT 1)
	UNION (SELECT id FROM `list_rows` WHERE `cells` LIKE '%$path%' LIMIT 1)
	UNION (SELECT id FROM `kat` WHERE `navn` LIKE '%$path%' OR `icon` LIKE '$path' LIMIT 1) LIMIT 1"))
		return true;
	else
		return false;
}


//Delete unused file
function deletefile($id, $path) {
	global $mysqli;

	if(isinuse($path))
		return array('error' => _('The file can not be deleted because it is used on a page.'));
	if(@unlink($_SERVER['DOCUMENT_ROOT'].$path)) {
		    $mysqli->query("DELETE FROM files WHERE `path` = '".$path."'");
		return array('id' => $id);
	} else
		return array('error' => _('There was an error deleting the file, the file may be in use.'));
}

//Scan folder and get list of files and folders in it
if(!function_exists('scandir')) {
    function scandir($dir, $sortorder = 0) {
        if(is_dir($dir) && $listdirs = @opendir($dir)) {
            while(($file = readdir($listdirs)) !== false) {
                $files[] = $file;
            }
            closedir($listdirs);
            ($sortorder == 0) ? asort($files) : rsort($files); // arsort was replaced with rsort
            return $files;
        } else return false;
    }
}

//Takes a string and changes it to comply with file name restrictions in windows, linux, mac and urls (UTF8)
// .|"'´`:%=#&\/+?*<>{}-_

function genfilename($filename) {
	// .|"'´`:%=#&\/+?*<>{}-_
	$search = array('/[.&?\/:*"\'´`<>{}|%\s-_=+#\\\\]+/u', '/^\s+|\s+$/u', '/\s+/u');
	$replace = array(' ', '', '-');
	return mb_strtolower(preg_replace($search, $replace, $filename), 'UTF-8');
}

//return tru for directorys and fall for every thing else
function is_dirs($str_file) {
	global $temp;
	if(is_file($_SERVER['DOCUMENT_ROOT'].$temp.'/'.$str_file) || $str_file == '.' || $str_file == '..')
		return false;
	return true;
}

//return list of folders in a folder
function sub_dirs($dir) {
	global $temp;
	$temp = $dir;
	if($dirs = scandir($_SERVER['DOCUMENT_ROOT'].$dir)) {
		$dirs = array_filter($dirs, 'is_dirs');
		natcasesort($dirs);
		$dirs = array_values($dirs);
	}
	return $dirs;
}

//TODO document type does not allow element "input" here; missing one of "p", "h1", "h2", "h3", "h4", "h5", "h6", "div", "pre", "address", "fieldset", "ins", "del" start-tag.
//Display a list of directorys for the explorer

function listdirs($dir, $mode=0) {
	$subdirs = sub_dirs($dir);
	$html = '';
	foreach($subdirs as $subdir) {
		$html .= '<div id="dir_'.preg_replace('#/#u','.',$dir.'/'.$subdir).'">';
		if(sub_dirs($dir.'/'.$subdir)) {
			$html .= '<img';
			if(@$_COOKIE[$dir.'/'.$subdir]) { $html .= ' style="display:none"'; }
			$html .= ' src="images/+.gif"';
			$html .= ' onclick="dir_expand(this,'.$mode.');"';
			$html .= ' height="16" width="16" alt="+" title="" /><img';
			if(!@$_COOKIE[$dir.'/'.$subdir]) { $html .= ' style="display:none"'; }
			$html .= ' src="images/-.gif"';
			$html .= ' onclick="dir_contract(this);"';
			$html .= ' height="16" width="16" alt="-" title="" /><a';
			if($dir.'/'.$subdir == @$_COOKIE['admin_dir'])
				$html .= ' class="active"';
			if($mode == 0) {
				$html .= ' onclick="showfiles(\''.$dir.'/'.$subdir.'\', 0);this.className=\'active\'" ondblclick="showdirname(this)" title="'.$subdir.'"><img src="images/folder.png" height="16" width="16" alt="" /> <span>'.$subdir.'</span></a><form action="" method="get" onsubmit="document.getElementById(\'files\').focus();return false;" style="display:none"><p style="display: inline; margin-left: 3px;"><img width="16" height="16" alt="" src="images/folder.png"/><input style="display:inline;" onblur="renamedir(this);" maxlength="'.(254-mb_strlen($dir, 'UTF-8')).'" value="'.$subdir.'" name="'.$dir.'/'.$subdir.'" /></p></form>';
			} elseif($mode == 1) {
				$html .= ' onclick="movefile(\''.$dir.'/'.$subdir.'\')" title="'.$subdir.'"><img src="images/folder.png" height="16" width="16" alt="" /> '.$subdir.' </a>';
			}

			$html .= '<div>';
			if(@$_COOKIE[$dir.'/'.$subdir]) {
				$listdirs = listdirs($dir.'/'.$subdir, $mode);
				$html .= $listdirs['html'];
			}
			$html .= '</div></div>';
		} else {
			$html .= '<a style="margin-left:16px"';
			if($dir.'/'.$subdir == @$_COOKIE['admin_dir'])
				$html .= ' class="active"';
			if($mode == 0) {
				$html .= ' onclick="showfiles(\''.$dir.'/'.$subdir.'\', 0);this.className=\'active\'" ondblclick="showdirname(this)" title="'.$subdir.'"><img src="images/folder.png" height="16" width="16" alt="" /> <span>'.$subdir.'</span></a><form action="" method="get" onsubmit="document.getElementById(\'files\').focus();return false;" style="display:none"><p style="display: inline; margin-left: 19px;"><img width="16" height="16" alt="" src="images/folder.png"/><input style="display:inline;" onblur="renamedir(this);" maxlength="'.(254-mb_strlen($dir, 'UTF-8')).'" value="'.$subdir.'" name="'.$dir.'/'.$subdir.'" /></p></form></div>';
			} elseif($mode == 1) {
				$html .= ' onclick="movefile(\''.$dir.'/'.$subdir.'\')" title="'.$subdir.'"><img src="images/folder.png" height="16" width="16" alt="" /> '.$subdir.' </a></div>';
			}
		}

	}
	return array('id' => $dir, 'html' => $html);
}
?>
