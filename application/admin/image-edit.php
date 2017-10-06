<?php

use AGCMS\Config;
use Sajax\Sajax;

require_once __DIR__ . '/logon.php';

Sajax::export(['saveImage' => ['method' => 'POST']]);
Sajax::handleClientRequest();

$imagesize = @getimagesize(_ROOT_ . $_GET['path']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('Edit picture'); ?></title>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/prototype/1.7.3.0/prototype.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/scriptaculous.js?load=effects,builder,dragdrop" type="text/javascript"></script>
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
<script type="text/javascript" src="javascript/lib/php.min.js"></script>
<script type="text/javascript" src="/javascript/sajax.js"></script>
<script type="text/javascript"><!--
    <?php Sajax::showJavascript(); ?>

var id = <?php echo (int) $_GET['id'] ?>;
var mode = '<?php echo $_GET['mode'] ?? '' ?>';
var filename = <?php
$fileName = '';
if ($_GET['mode'] ?? '' == 'thb') {
    $pathinfo = pathinfo($_GET['path']);
    $fileName = $pathinfo['filename'] . '-thb';
}
echo json_encode($fileName);
?>;
var thumb_width = <?php echo Config::get('thumb_width') ?>;
var thumb_height = <?php echo Config::get('thumb_height') ?>;
var scale = 1;
var path = '<?php echo $_GET['path'] ?>';
var maxW = <?php echo $imagesize[0] ?>;
var maxH = <?php echo $imagesize[1] ?>;
--></script>
<script type="text/javascript" src="javascript/image-edit.js"></script>
</head>
<body>
<div id="tools">
<img id="ccw" onclick="rotateCCW()" src="images/shape_rotate_anticlockwise.png" alt="&lt;-" title="<?php echo _('Rotate counterclockwise'); ?>" width="16" height="16" />
<img id="cw" onclick="rotateCW()" src="images/shape_rotate_clockwise.png" alt="-&gt;" title="<?php echo _('Rotate clockwise'); ?>" width="16" height="16" />
<img id="flipH" onclick="flipHorizontal()" src="images/shape_flip_horizontal.png" alt="|" title="<?php echo _('Flip horizontally'); ?>" width="16" height="16" />
<img id="flipV" onclick="flipVertical()" src="images/shape_flip_vertical.png" alt="-" title="<?php echo _('Flip Vertically'); ?>" width="16" height="16" />
<img id="resetCropper" src="images/cut.png" alt="X" title="<?php echo _('Clip'); ?>" width="16" height="16" /><img id="removeCropper" src="images/cut.png" alt="X" title="<?php echo _('Clip'); ?>" width="16" height="16" style="display:none" />
<img id="save" onclick="saveImage();" src="images/disk.png" alt="<?php echo _('Save'); ?>" title="<?php echo _('Save'); ?>" width="16" height="16" style="display:none" /><img id="loading" src="images/loading.gif" width="16" height="16" alt="<?php echo _('Loading'); ?>" title="<?php echo _('Loading'); ?>" /></div>
<div id="ruler" style="width: <?php echo Config::get('text_width'); ?>px;"><div style="width: <?php echo Config::get('text_width') - 1; ?>px; border-right:1px #FF0000 solid"><div style="width: <?php echo Config::get('thumb_width') - 1; ?>px; border-right:1px #0000FF solid"><div style="width: <?php echo $imagesize[0] - 1 ?>px; border-right:1px #00FF00 solid">&nbsp;</div></div></div></div>
<div id="textDiv" style="width: <?php echo Config::get('text_width'); ?>px;">
<?php
if ($_GET['mode'] ?? '' == 'thb') {
    ?><img id="preview" src="image.php?path=<?php echo $_GET['path'] ?>&amp;maxW=<?php echo Config::get('thumb_width'); ?>&amp;maxH=<?php echo Config::get('thumb_height'); ?>" alt="" onload="resize()" /><?php
} else {
    ?><img id="preview" src="<?php echo $_GET['path'] ?>" alt="" onload="resize()" /><?php
}
?><img id="resizeHandle" src="javascript/lib/cropper/resizehandle.gif" alt="" style="position: absolute; left: 16px; top: 16px; cursor: se-resize; margin:-16px 0 0 -16px; display:none">
<img id="original" src="<?php echo $_GET['path'] ?>" alt="" style="display:none;" />
</div>
</body>
</html>
