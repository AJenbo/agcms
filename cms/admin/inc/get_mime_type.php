<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
require_once 'file-functions.php';

function get_mime_type($filepath) {
	$mime = '';
	if (function_exists('finfo_file')) {
		$mime = finfo_file($finfo = finfo_open(FILEINFO_MIME), $_SERVER['DOCUMENT_ROOT'].$filepath);
		finfo_close($finfo);
	}
	if (!$mime && function_exists('mime_content_type'))
		$mime = mime_content_type($_SERVER['DOCUMENT_ROOT'].$filepath);

	//Some types can't be trusted, and finding them via extension seams to give better resutls.
	if (!$mime || $mime == 'text/plain' || $mime == 'application/msword') {
		$pathinfo = pathinfo($filepath);
		$mimes = array(
		   'ez'=>'application/andrew-inset',
		   'csm'=>'application/cu-seeme',
		   'cu'=>'application/cu-seeme',
		   'tsp'=>'application/dsptype',
		   'spl'=>'application/x-futuresplash',
		   'hta'=>'application/hta',
		   'cpt'=>'image/x-corelphotopaint',
		   'hqx'=>'application/mac-binhex40',
		   'nb'=>'application/mathematica',
		   'mdb'=>'application/msaccess',
		   'doc'=>'application/msword',
		   'dot'=>'application/msword',
		   'bin'=>'application/octet-stream',
		   'oda'=>'application/oda',
		   'ogg'=>'application/ogg',
		   'prf'=>'application/pics-rules',
		   'key'=>'application/pgp-keys',
		   'pdf'=>'application/pdf',
		   'pgp'=>'application/pgp-signature',
		   'ps'=>'application/postscript',
		   'ai'=>'application/postscript',
		   'eps'=>'application/postscript',
		   'rar'=>'application/rar',
		   'rdf'=>'application/rdf+xml',
		   'rss'=>'application/rss+xml',
		   'rtf'=>'text/rtf',
		   'smi'=>'application/smil',
		   'smil'=>'application/smil',
		   'wp5'=>'application/wordperfect5.1',
		   'xht'=>'application/xhtml+xml',
		   'xhtml'=>'application/xhtml+xml',
		   'xml'=>'application/xml',
		   'xsl'=>'application/xml',
		   'zip'=>'application/zip',
		   'cdy'=>'application/vnd.cinderella',
		   'mif'=>'application/x-mif',
		   'xul'=>'application/vnd.mozilla.xul+xml',
		   'xls'=>'application/vnd.ms-excel',
		   'xlb'=>'application/vnd.ms-excel',
		   'xlt'=>'application/vnd.ms-excel',
		   'cat'=>'application/vnd.ms-pki.seccat',
		   'stl'=>'application/vnd.ms-pki.stl',
		   'ppt'=>'application/vnd.ms-powerpoint',
		   'pps'=>'application/vnd.ms-powerpoint',
		   'mmf'=>'application/vnd.smaf',
		   'sdc'=>'application/vnd.stardivision.calc',
		   'sda'=>'application/vnd.stardivision.draw',
		   'sdd'=>'application/vnd.stardivision.impress',
		   'sdp'=>'application/vnd.stardivision.impress',
		   'smf'=>'application/vnd.stardivision.math',
		   'sdw'=>'application/vnd.stardivision.writer',
		   'vor'=>'application/vnd.stardivision.writer',
		   'sgl'=>'application/vnd.stardivision.writer-global',
		   'sxc'=>'application/vnd.sun.xml.calc',
		   'stc'=>'application/vnd.sun.xml.calc.template',
		   'sxd'=>'application/vnd.sun.xml.draw',
		   'std'=>'application/vnd.sun.xml.draw.template',
		   'sxi'=>'application/vnd.sun.xml.impress',
		   'sti'=>'application/vnd.sun.xml.impress.template',
		   'sxm'=>'application/vnd.sun.xml.math',
		   'sxw'=>'application/vnd.sun.xml.writer',
		   'sxg'=>'application/vnd.sun.xml.writer.global',
		   'stw'=>'application/vnd.sun.xml.writer.template',
		   'sis'=>'application/vnd.symbian.install',
		   'vsd'=>'application/vnd.visio',
		   'wbxml'=>'application/vnd.wap.wbxml',
		   'wmlc'=>'application/vnd.wap.wmlc',
		   'wmlsc'=>'application/vnd.wap.wmlscriptc',
		   'wk'=>'application/x-123',
		   'dmg'=>'application/x-apple-diskimage',
		   'bcpio'=>'application/x-bcpio',
		   'torrent'=>'application/x-bittorrent',
		   'cdf'=>'application/x-cdf',
		   'vcd'=>'application/x-cdlink',
		   'pgn'=>'application/x-chess-pgn',
		   'chm'=>'application/x-chm',
		   'cpio'=>'application/x-cpio',
		   'csh'=>'text/x-csh',
		   'deb'=>'application/x-debian-package',
		   'dcr'=>'application/x-director',
		   'dir'=>'application/x-director',
		   'dxr'=>'application/x-director',
		   'wad'=>'application/x-doom',
		   'dms'=>'application/x-dms',
		   'dvi'=>'application/x-dvi',
		   'flac'=>'application/x-flac',
		   'pfa'=>'application/x-font',
		   'pfb'=>'application/x-font',
		   'gsf'=>'application/x-font',
		   'pcf'=>'application/x-font',
		   'pcf.Z'=>'application/x-font',
		   'gnumeric'=>'application/x-gnumeric',
		   'sgf'=>'application/x-go-sgf',
		   'gcf'=>'application/x-graphing-calculator',
		   'gtar'=>'application/x-gtar',
		   'tgz'=>'application/x-gtar',
		   'taz'=>'application/x-gtar',
		   'hdf'=>'application/x-hdf',
		   'phtml'=>'application/x-httpd-php',
		   'pht'=>'application/x-httpd-php',
		   'php'=>'application/x-httpd-php',
		   'phps'=>'application/x-httpd-php-source',
		   'php3'=>'application/x-httpd-php3',
		   'php3p'=>'application/x-httpd-php3-preprocessed',
		   'php4'=>'application/x-httpd-php4',
		   'ica'=>'application/x-ica',
		   'ins'=>'application/x-internet-signup',
		   'isp'=>'application/x-internet-signup',
		   'iii'=>'application/x-iphone',
		   'jar'=>'application/x-java-archive',
		   'jnlp'=>'application/x-java-jnlp-file',
		   'ser'=>'application/x-java-serialized-object',
		   'class'=>'application/x-java-vm',
		   'js'=>'application/x-javascript',
		   'chrt'=>'application/x-kchart',
		   'kil'=>'application/x-killustrator',
		   'kpr'=>'application/x-kpresenter',
		   'kpt'=>'application/x-kpresenter',
		   'skp'=>'application/x-koan',
		   'skd'=>'application/x-koan',
		   'skt'=>'application/x-koan',
		   'skm'=>'application/x-koan',
		   'ksp'=>'application/x-kspread',
		   'kwd'=>'application/x-kword',
		   'kwt'=>'application/x-kword',
		   'latex'=>'application/x-latex',
		   'lha'=>'application/x-lha',
		   'lzh'=>'application/x-lzh',
		   'lzx'=>'application/x-lzx',
		   'frm'=>'application/x-maker',
		   'maker'=>'application/x-maker',
		   'frame'=>'application/x-maker',
		   'fm'=>'application/x-maker',
		   'fb'=>'application/x-maker',
		   'book'=>'application/x-maker',
		   'fbdoc'=>'application/x-maker',
		   'wmz'=>'application/x-ms-wmz',
		   'wmd'=>'application/x-ms-wmd',
		   'com'=>'application/x-msdos-program',
		   'exe'=>'application/x-msdos-program',
		   'bat'=>'application/x-msdos-program',
		   'dll'=>'application/x-msdos-program',
		   'msi'=>'application/x-msi',
		   'nc'=>'application/x-netcdf',
		   'pac'=>'application/x-ns-proxy-autoconfig',
		   'nwc'=>'application/x-nwc',
		   'o'=>'application/x-object',
		   'oza'=>'application/x-oz-application',
		   'p7r'=>'application/x-pkcs7-certreqresp',
		   'crl'=>'application/x-pkcs7-crl',
		   'pyc'=>'application/x-python-code',
		   'pyo'=>'application/x-python-code',
		   'qtl'=>'application/x-quicktimeplayer',
		   'rpm'=>'application/x-redhat-package-manager',
		   'shar'=>'application/x-shar',
		   'fla'=>'application/octet-stream',
		   'swf'=>'application/x-shockwave-flash',
		   'swfl'=>'application/x-shockwave-flash',
		   'sh'=>'text/x-sh',
		   'sit'=>'application/x-stuffit',
		   'sv4cpio'=>'application/x-sv4cpio',
		   'sv4crc'=>'application/x-sv4crc',
		   'tar'=>'application/x-tar',
		   'tcl'=>'text/x-tcl',
		   'gf'=>'application/x-tex-gf',
		   'pk'=>'application/x-tex-pk',
		   'texinfo'=>'application/x-texinfo',
		   'texi'=>'application/x-texinfo',
		   '~'=>'application/x-trash',
		   '%'=>'application/x-trash',
		   'bak'=>'application/x-trash',
		   'old'=>'application/x-trash',
		   'sik'=>'application/x-trash',
		   't'=>'application/x-troff',
		   'tr'=>'application/x-troff',
		   'roff'=>'application/x-troff',
		   'man'=>'application/x-troff-man',
		   'me'=>'application/x-troff-me',
		   'ms'=>'application/x-troff-ms',
		   'ustar'=>'application/x-ustar',
		   'src'=>'application/x-wais-source',
		   'wz'=>'application/x-wingz',
		   'crt'=>'application/x-x509-ca-cert',
		   'xcf'=>'application/x-xcf',
		   'fig'=>'application/x-xfig',
		   'au'=>'audio/basic',
		   'snd'=>'audio/basic',
		   'mid'=>'audio/midi',
		   'midi'=>'audio/midi',
		   'kar'=>'audio/midi',
		   'mpga'=>'audio/mpeg',
		   'mpega'=>'audio/mpeg',
		   'mp2'=>'audio/mpeg',
		   'mp3'=>'audio/mpeg',
		   'm4a'=>'audio/mpeg',
		   'm3u'=>'audio/x-mpegurl',
		   'sid'=>'audio/prs.sid',
		   'aif'=>'audio/x-aiff',
		   'aiff'=>'audio/x-aiff',
		   'aifc'=>'audio/x-aiff',
		   'gsm'=>'audio/x-gsm',
		   'wma'=>'audio/x-ms-wma',
		   'wax'=>'audio/x-ms-wax',
		   'ra'=>'audio/x-realaudio',
		   'rm'=>'audio/x-pn-realaudio',
		   'ram'=>'audio/x-pn-realaudio',
		   'pls'=>'audio/x-scpls',
		   'sd2'=>'audio/x-sd2',
		   'wav'=>'audio/x-wav',
		   'pdb'=>'chemical/x-pdb',
		   'xyz'=>'chemical/x-xyz',
		   'gif'=>'image/gif',
		   'ief'=>'image/ief',
		   'jpeg'=>'image/jpeg',
		   'jpg'=>'image/jpeg',
		   'jpe'=>'image/jpeg',
		   'pcx'=>'image/pcx',
		   'png'=>'image/png',
		   'svg'=>'image/svg+xml',
		   'svgz'=>'image/svg+xml',
		   'tiff'=>'image/tiff',
		   'tif'=>'image/tiff',
		   'djvu'=>'image/vnd.djvu',
		   'djv'=>'image/vnd.djvu',
		   'wbmp'=>'image/vnd.wap.wbmp',
		   'wbm'=>'image/vnd.wap.wbmp',
		   'ras'=>'image/x-cmu-raster',
		   'cdr'=>'image/x-coreldraw',
		   'pat'=>'image/x-coreldrawpattern',
		   'cdt'=>'image/x-coreldrawtemplate',
		   'ico'=>'image/x-icon',
		   'art'=>'image/x-jg',
		   'jng'=>'image/x-jng',
		   'bmp'=>'image/x-ms-bmp',
		   'psd'=>'image/x-photoshop',
		   'pnm'=>'image/x-portable-anymap',
		   'pbm'=>'image/x-portable-bitmap',
		   'pgm'=>'image/x-portable-graymap',
		   'ppm'=>'image/x-portable-pixmap',
		   'rgb'=>'image/x-rgb',
		   'xbm'=>'image/x-xbitmap',
		   'xpm'=>'image/x-xpixmap',
		   'xwd'=>'image/x-xwindowdump',
		   'igs'=>'model/iges',
		   'iges'=>'model/iges',
		   'msh'=>'model/mesh',
		   'mesh'=>'model/mesh',
		   'silo'=>'model/mesh',
		   'wrl'=>'x-world/x-vrml',
		   'vrml'=>'x-world/x-vrml',
		   'ics'=>'text/calendar',
		   'icz'=>'text/calendar',
		   'csv'=>'text/comma-separated-values',
		   'css'=>'text/css',
		   '323'=>'text/h323',
		   'htm'=>'text/html',
		   'html'=>'text/html',
		   'shtml'=>'text/html',
		   'uls'=>'text/iuls',
		   'mml'=>'text/mathml',
		   'asc'=>'text/plain',
		   'txt'=>'text/plain',
		   'text'=>'text/plain',
		   'diff'=>'text/plain',
		   'pot'=>'text/plain',
		   'rtx'=>'text/richtext',
		   'sct'=>'text/scriptlet',
		   'wsc'=>'text/scriptlet',
		   'tm'=>'text/texmacs',
		   'ts'=>'text/texmacs',
		   'tsv'=>'text/tab-separated-values',
		   'jad'=>'text/vnd.sun.j2me.app-descriptor',
		   'wml'=>'text/vnd.wap.wml',
		   'wmls'=>'text/vnd.wap.wmlscript',
		   'h++'=>'text/x-c++hdr',
		   'hpp'=>'text/x-c++hdr',
		   'hxx'=>'text/x-c++hdr',
		   'hh'=>'text/x-c++hdr',
		   'c++'=>'text/x-c++src',
		   'cpp'=>'text/x-c++src',
		   'cxx'=>'text/x-c++src',
		   'cc'=>'text/x-c++src',
		   'h'=>'text/x-chdr',
		   'c'=>'text/x-csrc',
		   'java'=>'text/x-java',
		   'moc'=>'text/x-moc',
		   'p'=>'text/x-pascal',
		   'pas'=>'text/x-pascal',
		   'gcd'=>'text/x-pcs-gcd',
		   'pl'=>'text/x-perl',
		   'pm'=>'text/x-perl',
		   'py'=>'text/x-python',
		   'etx'=>'text/x-setext',
		   'tk'=>'text/x-tcl',
		   'tex'=>'text/x-tex',
		   'ltx'=>'text/x-tex',
		   'sty'=>'text/x-tex',
		   'cls'=>'text/x-tex',
		   'vcs'=>'text/x-vcalendar',
		   'vcf'=>'text/x-vcard',
		   'dl'=>'video/dl',
		   'fli'=>'video/fli',
		   'gl'=>'video/gl',
		   'mpeg'=>'video/mpeg',
		   'mpg'=>'video/mpeg',
		   'mpe'=>'video/mpeg',
		   'mp4'=>'video/mp4',
		   'qt'=>'video/quicktime',
		   'mov'=>'video/quicktime',
		   'mxu'=>'video/vnd.mpegurl',
		   'dif'=>'video/x-dv',
		   'dv'=>'video/x-dv',
		   'lsf'=>'video/x-la-asf',
		   'lsx'=>'video/x-la-asf',
		   'mng'=>'video/x-mng',
		   'asf'=>'video/x-ms-asf',
		   'asx'=>'video/x-ms-asf',
		   'wm'=>'video/x-ms-wm',
		   'wmv'=>'video/x-ms-wmv',
		   'wmx'=>'video/x-ms-wmx',
		   'wvx'=>'video/x-ms-wvx',
		   'avi'=>'video/x-msvideo',
		   'movie'=>'video/x-sgi-movie',
		   'flv'=>'video/x-flv',
		   'ice'=>'x-conference/x-cooltalk',
		   'vrm'=>'x-world/x-vrml',
		   '7z'=>'application/x-7z-compressed',
		   'gz'=>'application/x-gzip'
			);
			$mime = empty($mimes[mb_strtolower(@$pathinfo['extension'], 'UTF-8')]) ? 'application/octet-stream' : $mimes[mb_strtolower(@$pathinfo['extension'], 'UTF-8')];
		}

	$mime = explode(';', $mime);
	$mime = $mime[0];

    return $mime;
}
?>
