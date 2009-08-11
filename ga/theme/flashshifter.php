<?php
function swfimages($filename) {
	if(preg_match('/[.]jpg$/ui', $filename))
		return true;
	return false;
}
function loadedimg($filename) {
	if('/images/front-shift/'.$filename == $_POST['img'])
		return false;
	return true;
}

$files = array_filter(scandir('../images/front-shift/'), 'swfimages');
if(count($files) > 1)
	$files = array_filter($files, 'loadedimg');

$file = $files[array_rand($files)];
$size = getimagesize('../images/front-shift/'.$file);
echo('?&img=/images/front-shift/'.$file.'&height='.$size[1]);
?>