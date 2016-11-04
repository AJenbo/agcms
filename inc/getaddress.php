<?php
/**
 * Function for getting user address
 *
 * PHP version 5
 *
 * @category AGCMS
 * @package  AGCMS
 * @author   Anders Jenbo <anders@jenbo.dk>
 * @license  GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link     http://www.arms-gallery.dk/
 */

/**
 * Get address from phone number
 *
 * @param string $phoneNumber Phone number
 *
 * @return array Array with address fitting the post table format
 */
function getAddress(string $phoneNumber): array
{
    $default['recName1'] = '';
    $default['recAddress1'] = '';
    $default['recZipCode'] = '';
    $default['recCVR'] = '';
    $default['recAttPerson'] = '';
    $default['recAddress2'] = '';
    $default['recPostBox'] = '';
    $default['email'] = '';

    $dbs[0]['mysql_server'] = 'jagtogfiskerimagasinet.dk.mysql';
    $dbs[0]['mysql_user'] = 'jagtogfiskerima';
    $dbs[0]['mysql_password'] = 'GxYqj5EX';
    $dbs[0]['mysql_database'] = 'jagtogfiskerima';
    $dbs[1]['mysql_server'] = 'huntershouse.dk.mysql';
    $dbs[1]['mysql_user'] = 'huntershouse_dk';
    $dbs[1]['mysql_password'] = 'sabbBFab';
    $dbs[1]['mysql_database'] = 'huntershouse_dk';
    $dbs[2]['mysql_server'] = 'arms-gallery.dk.mysql';
    $dbs[2]['mysql_user'] = 'arms_gallery_dk';
    $dbs[2]['mysql_password'] = 'hSKe3eDZ';
    $dbs[2]['mysql_database'] = 'arms_gallery_dk';
    $dbs[3]['mysql_server'] = 'geoffanderson.com.mysql';
    $dbs[3]['mysql_user'] = 'geoffanderson_c';
    $dbs[3]['mysql_password'] = '2iEEXLMM';
    $dbs[3]['mysql_database'] = 'geoffanderson_c';

    include_once $_SERVER['DOCUMENT_ROOT'].'/inc/mysqli.php';

    foreach ($dbs as $db) {
        $mysqli_ext = new Simple_Mysqli(
            $db['mysql_server'],
            $db['mysql_user'],
            $db['mysql_password'],
            $db['mysql_database']
        );

        //try packages
        $post = $mysqli_ext->fetchArray(
            "
            SELECT recName1, recAddress1, recZipCode
            FROM `post`
            WHERE `recipientID` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            LIMIT 1
            "
        );
        if ($post) {
            $return = array_merge($default, $post[0]);
            if ($return != $default) {
                return $return;
            }
        }

        //Try katalog orders
        $email = $mysqli_ext->fetchArray(
            "
            SELECT navn, email, adresse, post
            FROM `email`
            WHERE `tlf1` LIKE '" . $phoneNumber . "'
               OR `tlf2` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            LIMIT 1
            "
        );
        if ($email) {
            $return['recName1'] = $email[0]['navn'];
            $return['recAddress1'] = $email[0]['adresse'];
            $return['recZipCode'] = $email[0]['post'];
            $return['email'] = $email[0]['email'];
            $return = array_merge($default, $return);

            if ($return != $default) {
                return $return;
            }
        }

        //Try fakturas
        $fakturas = $mysqli_ext->fetchArray(
            "
            SELECT navn, email, att, adresse, postnr, postbox
            FROM `fakturas`
            WHERE `tlf1` LIKE '" . $phoneNumber . "'
               OR `tlf2` LIKE '" . $phoneNumber . "'
            ORDER BY id DESC
            LIMIT 1
            "
        );
        if ($fakturas) {
            $return['recName1'] = $fakturas[0]['navn'];
            $return['recAddress1'] = $fakturas[0]['adresse'];
            $return['recZipCode'] = $fakturas[0]['postnr'];
            $return['recAttPerson'] = $fakturas[0]['att'];
            $return['recPostBox'] = $fakturas[0]['postbox'];
            $return['email'] = $fakturas[0]['email'];
            $return = array_merge($default, $return);

            if ($return != $default) {
                return $return;
            }
        }
    }

    //Addressen kunde ikke findes.
    return array('error' => _('The address could not be found.'));
}

