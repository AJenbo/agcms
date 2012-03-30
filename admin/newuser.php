<?php

date_default_timezone_set('Europe/Copenhagen');
setlocale(LC_ALL, 'da_DK');
bindtextdomain("agcms", $_SERVER['DOCUMENT_ROOT'].'/theme/locale');
bind_textdomain_codeset("agcms", 'UTF-8');
textdomain("agcms");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo _('Create account'); ?></title>
<link type="text/css" rel="stylesheet" href="style/style.css" />
<script type="text/javascript"><!--
function validate()
{
    if (document.getElementById('fullname').value == '' || document.getElementById('name').value == '' || document.getElementById('password').value == '') {
        alert('<?php echo _('All fields must be filled.'); ?>');
        return false;
    }
    if (document.getElementById('password').value != document.getElementById('password2').value) {
        alert('<?php echo _('The passwords does not match.'); ?>');
        return false;
    }
}
--></script>
<style type="text/css"><!--
table, td, tr {
    border-collapse:separate;
}
--></style>
</head>
<body style="margin: 20px;"><?php


?><form action="" method="post" onsubmit="return validate();">
    <table style="background-color: #DDDDDD; border: 1px solid #AAAAAA; padding: 7px; margin: auto;">
        <tr>
            <td><?php echo _('Fullname:'); ?></td>
            <td><input id="fullname" name="fullname" /></td></tr>
        <tr>
            <td><?php echo _('Username:'); ?></td>
            <td><input id="name" name="name" /></td></tr>
        <tr>
            <td><?php echo _('Password:'); ?></td>
            <td><input id="password" name="password" type="password" /></td></tr>
        <tr>
            <td><?php echo _('Repeat password:'); ?></td>
            <td><input id="password2" name="password2" type="password" /></td></tr>
        <tr>
            <td colspan="2" align="center"><input type="submit" style="margin-top:6px; width:52; height:24;" value="<?php echo _('Create account'); ?>" /></td></tr>
    </table>
</form><?php

if ($_POST) {
    if (empty($_POST['fullname']) || empty($_POST['name']) || empty($_POST['password'])) {
        die('<p style="text-align: center; margin-top: 20px;">'._('All fields must be filled.').'</p></body></html>');
    }
    if ($_POST['password'] != $_POST['password2']) {
        die('<p style="text-align: center; margin-top: 20px;">'._('The passwords does not match.').'</p></body></html>');
    }

    include_once $_SERVER['DOCUMENT_ROOT'].'/inc/config.php';
    include_once $_SERVER['DOCUMENT_ROOT'].'/inc/mysqli.php';

    //Open database
    $mysqli = new simple_mysqli(
        $GLOBALS['_config']['mysql_server'],
        $GLOBALS['_config']['mysql_user'],
        $GLOBALS['_config']['mysql_password'],
        $GLOBALS['_config']['mysql_database']
    );

    if ($mysqli->fetch_array('SELECT id FROM users WHERE name = \''.addcslashes($_POST['name'], "'").'\'')) {
        die('<p style="text-align: center; margin-top: 20px;">'._('Username already taken.').'</p></body></html>');
    }

    $mysqli->query('INSERT INTO users SET name = \''.addcslashes($_POST['name'], "'").'\', password = \''.addcslashes(crypt($_POST['password']), "'").'\', fullname = \''.addcslashes($_POST['fullname'], "'").'\'');

    $emailbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'._('New user').'</title></head>
<body><p>'.$_POST['fullname']._(' has created an account for the administrator page. An administrator needs to confirm the accound or reject it.').'</p>

<p>Sincerely the computer</p></body>
</html>';

    include_once $_SERVER['DOCUMENT_ROOT'].'/inc/phpMailer/class.phpmailer.php';
    $mail = new PHPMailer();
    $mail->SetLanguage(_('en'));
    $mail->IsSMTP();
    if ($GLOBALS['_config']['emailpassword'] !== false) {
        $mail->SMTPAuth   = true; // enable SMTP authentication
        $mail->Username   = $GLOBALS['_config']['email'][0];
        $mail->Password   = $GLOBALS['_config']['emailpassword'];
    } else {
        $mail->SMTPAuth   = false;
    }
    $mail->Host       = $GLOBALS['_config']['smtp'];      // sets the SMTP server
    $mail->Port       = $GLOBALS['_config']['smtpport'];  // set the SMTP port for the server
    $mail->CharSet    = 'utf-8';
    $mail->AddReplyTo($GLOBALS['_config']['email'][0], $GLOBALS['_config']['site_name']);
    $mail->From       = $GLOBALS['_config']['email'][0];
    $mail->FromName   = $GLOBALS['_config']['site_name'];
    $mail->Subject    = _('New user');

    $mail->MsgHTML($emailbody, $_SERVER['DOCUMENT_ROOT']);

    $mail->AddAddress($GLOBALS['_config']['email'][0], $GLOBALS['_config']['site_name']);
    if ($mail->Send()) {
        //Upload email to the sent folder via imap
        if ($GLOBALS['_config']['imap']) {
            include_once $_SERVER['DOCUMENT_ROOT'].'/inc/imap.php';
            $emailnr = array_search($GLOBALS['_config']['email'][0], $GLOBALS['_config']['email']);
            $imap = new IMAP(
                $GLOBALS['_config']['email'][0],
                $GLOBALS['_config']['emailpasswords'][$emailnr ? $emailnr : 0],
                $GLOBALS['_config']['imap'],
                $GLOBALS['_config']['imapport']
            );
            $imap->append($GLOBALS['_config']['emailsent'], $mail->CreateHeader().$mail->CreateBody(), '\Seen');
        }
    } else {
        //TODO secure this against injects and <; in the email and name
        $mysqli->query("INSERT INTO `emails` (`subject`, `from`, `to`, `body`, `date`) VALUES ('".$mail->Subject."', '".$GLOBALS['_config']['site_name']."<".$GLOBALS['_config']['email'][0].">', '".$GLOBALS['_config']['site_name']."<".$GLOBALS['_config']['email'][0].">', '".$emailbody."', NOW());");
    }

    echo '<p style="text-align: center; margin-top: 20px;">'._('Your account has been created. An administrator will evaluate it shortly.').'</p>'
}
?>
</body>
</html>
