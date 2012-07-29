<?php

require_once("banner.inc.php");

$directory = '../../images/';

$banner = new Banner();
$banner->outputBanner($directory, $_GET['banner']);
?>
