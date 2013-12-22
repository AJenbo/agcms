<?php
$merchantId = "508069";
$token = "dR*8N5s/";
$wsdl="https://epayment.nets.eu/Netaxept.svc?wsdl";
$path_parts = pathinfo($_SERVER["PHP_SELF"]);
$redirect_url = "http://" . $_SERVER["HTTP_HOST"] . $path_parts['dirname'] . "/Process.php";
$terminal = "https://epayment.nets.eu/terminal/default.aspx";
?>
