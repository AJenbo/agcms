<?php
//*
ini_set('display_errors', 1);
error_reporting(-1);
/**/

if(!empty($_COOKIE[session_name()]))
	unset($_COOKIE[session_name()]);

require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
	//TODO support wbmp
	//TODO $_GET login
header('HTTP/1.1 500 Internal Server Error');
if (!empty($_FILES['Filedata']['tmp_name']) && is_uploaded_file($_FILES['Filedata']['tmp_name'])) {
	//Mangler file-functions.php
	header('HTTP/1.1 501 Internal Server Error');
	require_once '../admin/inc/file-functions.php';
	$pathinfo = pathinfo($_FILES['Filedata']['name']);
	//Kunne ikke læse filnavn.
	header('HTTP/1.1 503 Internal Server Error');
	$name = genfilename($pathinfo['filename']).'.'.mb_strtolower($pathinfo['extension'], 'UTF-8');
	//Fejl under flytning af filen.
	header('HTTP/1.1 504 Internal Server Error');
	move_uploaded_file($_FILES['Filedata']['tmp_name'], $_SERVER['DOCUMENT_ROOT'].'/upload/temp/'.$name);
	//Kunne ikke give tilladelse til filen.
	header('HTTP/1.1 505 Internal Server Error');
	chmod($_SERVER['DOCUMENT_ROOT'].'/upload/temp/'.$name, 0644);
	//Mangler get_mime_type.php
	header('HTTP/1.1 510 Internal Server Error');
	require_once '../admin/inc/get_mime_type.php';
	$mime = get_mime_type('/upload/temp/'.$name);
	//Kunne ikke finde billed størelsen.
	header('HTTP/1.1 512 Internal Server Error');
	
	if((!@$_GET['x'] || !@$_GET['y']) && ($mime == 'image/jpeg' || $mime == 'image/gif' || $mime == 'image/png' || $mime == 'image/vnd.wap.wbmp'))
		$imagesize = getimagesize($_SERVER['DOCUMENT_ROOT'].'/upload/temp/'.$name);
	else {
		$imagesize[0] = $_GET['x'];
		$imagesize[1] = $_GET['y'];
	}
	if(!$imagesize)
		die();
	
	//TODO BUG IN SWF FILE!!!
	if(@$_GET['aspect'] == '4-9') {
		$_GET['aspect'] = "'4-3'";
	} elseif(@$_GET['aspect'] == '16-9') {
		$_GET['aspect'] = "'16-9'";
	} else {
		$_GET['aspect'] = 'NULL';
	}
	
	if(empty($_GET['type']))
		$_GET['type'] = '';

	require_once '../admin/inc/config.php';
	//TODO test if trim, resize or recompression is needed
	if(($_GET['type'] == 'image' && $mime != 'image/jpeg') ||
		(($_GET['type'] == 'image' || $_GET['type'] == 'lineimage') && $imagesize[0] > $GLOBALS['_config']['text_width']) ||
		(($_GET['type'] == 'image' || $_GET['type'] == 'lineimage') && $_FILES['Filedata']['size']/($imagesize[0]*$imagesize[1]) > 0.7) ||
		($_GET['type'] == 'lineimage' && $mime != 'image/png' && $mime != 'image/gif')) {

		function return_bytes($val) {
			$last = mb_strtolower($val{mb_strlen($val, 'UTF-8')-1}, 'UTF-8');
			switch($last) {
				case 'g':
					$val *= 1024;
				case 'm':
					$val *= 1024;
				case 'k':
					$val *= 1024;
			}
			return $val;
		}
		
		$memory_limit = return_bytes(ini_get('memory_limit'))-270336;
		
		if($imagesize[0]*$imagesize[1] > $memory_limit/9.92) {
		
			//Kunne ikke slette filen.
			header('HTTP/1.1 520 Internal Server Error');
			
			if(@unlink($_SERVER['DOCUMENT_ROOT'].'/upload/temp/'.$name))
				//Billedet er for stor.
				header('HTTP/1.1 521 Internal Server Error');
			
			die();
		}
		
		//Mangler image-functions.php
		header('HTTP/1.1 560 Internal Server Error');
		require_once '../admin/inc/image-functions.php';
		//Fejl under billed behandling.
		header('HTTP/1.1 561 Internal Server Error');

		if($_GET['type'] == 'lineimage')
			$output['type'] = 'png';
		else
			$output['type'] = 'jpg';

		$output['force'] = true;
		
//			generateImage($path, 						$cropX, $cropY, $cropW,        $cropH,        $maxW,                  $maxH,         $flip, $rotate, $output);
		$newfiledata = generateImage('/upload/temp/'.$name, 0,      0,      $imagesize[0], $imagesize[1], $GLOBALS['_config']['text_width'], $imagesize[1], 0,     0,       $output);

		$temppath = $newfiledata['path'];
		$width = $newfiledata['width'];
		$height = $newfiledata['height'];
		$destpath = pathinfo($newfiledata['path']);
		$destpath = $_GET['admin_dir'].'/'.$destpath['basename'];
		$mime = get_mime_type($temppath);
	} else {
		$temppath = '/upload/temp/'.$name;
		$width = $imagesize[0];
		$height = $imagesize[1];
		$destpath = $_GET['admin_dir'].'/'.$name;
	}
	
	rename($_SERVER['DOCUMENT_ROOT'].$temppath, $_SERVER['DOCUMENT_ROOT'].$destpath);
	
	//Mangler mysql-funktioner.php
	header('HTTP/1.1 540 Internal Server Error');
	require_once '../inc/config.php';
	require_once '../inc/mysqli.php';
	//Kunne ikke åbne database.
	header('HTTP/1.1 541 Internal Server Error');
	$mysqli = new simple_mysqli($GLOBALS['_config']['mysql_server'], $GLOBALS['_config']['mysql_user'], $GLOBALS['_config']['mysql_password'], $GLOBALS['_config']['mysql_database']);
	//MySQL DELETE fejl!
	header('HTTP/1.1 542 Internal Server Error');
	$mysqli->query('DELETE FROM files WHERE path = \''.$destpath."'");
	//If the image was edited it inserts it's info
	$mysqli->query('DELETE FROM files WHERE path = \''.$temppath."'");
	//MySQL INSERT fejl!
	header('HTTP/1.1 543 Internal Server Error');
	
	$mysqli->query('INSERT INTO files (path, mime, alt, width, height, size, aspect) VALUES (\''.$destpath."', '".$mime."', '".$_GET['alt']."', '".$width."', '".$height."', '".filesize($_SERVER['DOCUMENT_ROOT'].$destpath)."', ".$_GET['aspect'].")");

	header('HTTP/1.1 200 OK');
} else
	//Filen blev ikke sendt
	header('HTTP/1.1 404 Not Found');
?>