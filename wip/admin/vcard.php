<?php
/**
 * Import VCARD files.
 *
 * PHP version 5
 *
 * @category VCARD
 *
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  LGPL http://www.gnu.org/licenses/lgpl.html
 *
 * @see     http://anders.jenbo.dk/
 */
require_once '../inc/functions.php';

$filecontent = file_get_contents('paintballbaner.vcf');
$filecontent = mb_convert_encoding($filecontent, 'UTF-8', 'UTF-16');
preg_match_all('/BEGIN:VCARD.*?END:VCARD/isu', $filecontent, $vcards);
foreach ($vcards[0] as $vcard) {
    preg_match('/FN:(.*?)[\n|\r]/iu', $vcard, $navn);
    preg_match_all('/EMAIL.*?:(.*?)[\n|\r]/iu', $vcard, $emails);
    foreach ($emails[1] as $email) {
        if (valideMail($email)) {
            break;
        }
        $email = '';
    }
    //TODO search $contacts for email and continue; if found.

    preg_match_all('/TEL.*?:(.*?)[\n|\r]/iu', $vcard, $tlfs);
    preg_match('/[.]ADR.*?:(.*?)[\n|\r]/iu', $vcard, $adresse);

    $adresse = explode(';', $adresse[1]);
    $adresse = array_map('stripcslashes', $adresse);
    $contacts[] = [
        'navn' => $navn[1],
        'email' => $email,
        'tlf1' => $tlfs[1][0],
        'tlf2' => $tlfs[1][1],
        'adresse' => $adresse[2],
        'by' => $adresse[3],
        'post' => $adresse[5],
        'land' => $adresse[6],
    ];
}

foreach ($contacts as $contact) {
    //TODO escape values
    db()->query(
        "
        INSERT INTO `email` (
            `navn`,
            `email`,
            `adresse`,
            `land`,
            `post`,
            `by`,
            `tlf1`,
            `tlf2`,
            `kartotek`,
            `interests`,
            `dato`
        )
        VALUES (
            '" . $contact['navn'] . "',
            '" . $contact['email'] . "',
            '" . $contact['adresse'] . "',
            '" . $contact['land'] . "',
            '" . $contact['post'] . "',
            '" . $contact['by'] . "',
            '" . $contact['tlf1'] . "',
            '" . $contact['tlf2'] . "',
            '1',
            'Paintballbaner',
            NOW()
        );
        "
    );
}

?>Done.
