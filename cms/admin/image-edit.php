<?php
require_once '../inc/sajax.php';
require_once 'inc/config.php';

function saveImage($path, $cropX, $cropY, $cropW, $cropH, $maxW, $maxH, $flip, $rotate, $filename, $force) {
	require_once 'inc/image-functions.php';

	require_once 'inc/get_mime_type.php';
	$mimeType = get_mime_type($path);

	if($mimeType == 'image/jpeg')
		$output['type'] = 'jpg';
	else
		$output['type'] = 'png';
		
	$output['filename'] = $filename;
	$output['force'] = $force;

//		   generateImage($path, $cropX, $cropY, $cropW, $cropH, $maxW, $maxH, $flip, $rotate, $output[]);
	return generateImage($path, $cropX, $cropY, $cropW, $cropH, $maxW, $maxH, $flip, $rotate, $output);
	//TODO close and update image in explorer
}

$sajax_request_type = 'POST';
sajax_init();
//$sajax_debug_mode = 1;
sajax_export('saveImage');
//$sajax_remote_uri = "/ajax.php";
sajax_handle_client_request();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Rediger billed</title>
<script src="javascript/lib/prototype.js" type="text/javascript"></script>
<script src="javascript/lib/scriptaculous.js?load=effects,builder,dragdrop" type="text/javascript"></script>
<script src="javascript/lib/cropper/cropper.js" type="text/javascript"></script>
<style type="text/css">
#tools {
	cursor:default;
	text-align:center;
	width:256px;
	margin:auto;
}
#tools img {
	cursor:pointer;
}
#ruler {
	background-image:url('images/ruler.png');
	background-position:left;
	height:15px;
	margin:auto;
}
#textDiv {
	position: relative;
	margin:auto;
}
</style>
<script type="text/javascript"><!--
<?php sajax_show_javascript();
$imagesize = @getimagesize($_SERVER['DOCUMENT_ROOT'].$_GET['path']);

?>
var id = <?php echo($_GET['id']); ?>;
var mode = '<?php echo($_GET['mode']); ?>';
var filename = '<?php
	if($_GET['mode'] == 'thb') {
		$pathinfo = pathinfo($_GET['path']);
		echo($pathinfo['filename'].'-thb');
	}
?>';
var thumb_width = <?php echo($GLOBALS['_config']['thumb_width']); ?>;
var thumb_height = <?php echo($GLOBALS['_config']['thumb_height']); ?>;
var scale = 1;
var path = '<?php echo($_GET['path']); ?>';
var maxW = <?php echo($imagesize[0]); ?>;
var maxH = <?php echo($imagesize[1]); ?>;
--></script>
<script type="text/javascript" src="javascript/image-edit.js"></script>
</head>
<body>
<div id="tools">
<img id="ccw" onclick="rotateCCW()" src="images/shape_rotate_anticlockwise.png" alt="&lt;-" title="Roter mod uret" width="16" height="16" />
<img id="cw" onclick="rotateCW()" src="images/shape_rotate_clockwise.png" alt="-&gt;" title="Roter med uret" width="16" height="16" />
<img id="flipH" onclick="flipHorizontal()" src="images/shape_flip_horizontal.png" alt="|" title="Speglvend horisontalt" width="16" height="16" />
<img id="flipV" onclick="flipVertical()" src="images/shape_flip_vertical.png" alt="-" title="Speglvend verticalt" width="16" height="16" />
<img id="resetCropper" src="images/cut.png" alt="X" title="Klip" width="16" height="16" /><img id="removeCropper" src="images/cut.png" alt="X" title="Klip" width="16" height="16" style="display:none" />
<img id="save" onclick="saveImage();" src="images/disk.png" alt="Gem" title="Gem" width="16" height="16" style="display:none" /><img id="loading" src="images/loading.gif" width="16" height="16" alt="Indlæser" title="Indlæser" /></div>
<div id="ruler" style="width: <?php echo($GLOBALS['_config']['text_width']); ?>px;"><div style="width: <?php echo($GLOBALS['_config']['text_width']-1); ?>px; border-right:1px #FF0000 solid"><div style="width: <?php echo($GLOBALS['_config']['thumb_width']-1); ?>px; border-right:1px #0000FF solid"><div style="width: <?php echo($imagesize[0]-1); ?>px; border-right:1px #00FF00 solid">&nbsp;</div></div></div></div>
<div id="textDiv" style="width: <?php echo($GLOBALS['_config']['text_width']); ?>px;">
<?php
if(@$_GET['mode'] == 'thb') {
	?><img id="preview" src="image.php?path=<?php echo($_GET['path']); ?>&amp;maxW=<?php echo($GLOBALS['_config']['thumb_width']); ?>&amp;maxH=<?php echo($GLOBALS['_config']['thumb_height']); ?>" alt="" onload="resize()" /><?php
} else {
	?><img id="preview" src="<?php echo($_GET['path']); ?>" alt="" onload="resize()" /><?php
}
?><img id="resizeHandle" src="javascript/lib/cropper/resizehandle.gif" alt="" style="position: absolute; left: 16px; top: 16px; cursor: se-resize; margin:-16px 0 0 -16px; display:none">
<img id="original" src="<?php echo($_GET['path']); ?>" alt="" style="display:none;" />
</div>
</body>
</html>
