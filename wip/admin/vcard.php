<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body><?php
function file_get_contents_utf8($fn) {
    $content = file_get_contents($fn);
    return mb_convert_encoding($content, 'UTF-8', 'UTF-16');
}

//is the email valid
function valide_mail($email) {
    if(preg_match('/^([a-z0-9_\.\-])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+$/i', $email) && getmxrr(preg_replace('/.+?@(.?)/u', '$1', $email), $dummy))
        return true;
    else
        return false;
}

$filecontent = file_get_contents_utf8('paintballbaner.vcf');
preg_match_all('/BEGIN:VCARD.*?END:VCARD/isu', $filecontent, $vcards);
foreach($vcards[0] as $vcard) {
    preg_match('/FN:(.*?)[\n|\r]/iu', $vcard, $navn);
    preg_match_all('/EMAIL.*?:(.*?)[\n|\r]/iu', $vcard, $emails);
    foreach($emails[1] as $email) {
        if(valide_mail($email)) {
            break;
        }
        $email = '';
    }
    //TODO search $contacts for email and continue; if found.

    preg_match_all('/TEL.*?:(.*?)[\n|\r]/iu', $vcard, $tlfs);
    preg_match('/[.]ADR.*?:(.*?)[\n|\r]/iu', $vcard, $adresse);

    $adresse = explode(';', $adresse[1]);
    $adresse = array_map('stripcslashes', $adresse);
    $contacts[] = array('navn' => $navn[1], 'email' => $email, 'tlf1' => $tlfs[1][0], 'tlf2' => $tlfs[1][1], 'adresse' => $adresse[2], 'by' => $adresse[3], 'post' => $adresse[5], 'land' => $adresse[6]);
}

require_once 'inc/config.php';
require_once 'inc/mysqli.php';

//Open database
$mysqli = new simple_mysqli(
    $GLOBALS['_config']['mysql_server'],
    $GLOBALS['_config']['mysql_user'],
    $GLOBALS['_config']['mysql_password'],
    $GLOBALS['_config']['mysql_database']
);

foreach ($contacts as $contact) {
    //TODO escape values
    $mysqli->query("INSERT INTO `email` (`navn`, `email`, `adresse`, `land`, `post`, `by`, `tlf1`, `tlf2`, `kartotek`, `interests`, `dato`) VALUES ('".$contact['navn']."', '".$contact['email']."', '".$contact['adresse']."', '".$contact['land']."', '".$contact['post']."', '".$contact['by']."', '".$contact['tlf1']."', '".$contact['tlf2']."', '1', 'Paintballbaner', NOW());");
}

?>File imported.</body>
</html>
