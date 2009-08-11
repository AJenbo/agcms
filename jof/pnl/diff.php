<?php
require 'countries.php';
require 'countries_pdk.php';
print_r(array_diff_assoc($pdkcountries, $countries));
?>