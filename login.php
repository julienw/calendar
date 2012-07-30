<?php

require_once 'conf/config.inc.php';
require_once 'includes/auth.inc.php';
require_once 'DB.php';

$db =& DB::connect($dsn);
if (PEAR::isError($db)) {
	die($db->getMessage());
}

$querystring = str_replace('logout&', '', $_SERVER['QUERY_STRING']);
$location = $site_url . $_SERVER['PATH_INFO'] . '?' . $querystring;

$auth =& new Auth($db);
if ((! $auth->check()) or (isset($_GET['logout']))) {
	$auth->sendAuthRequest();
	echo "<html><head><meta http-equiv='refresh' content='0;URL=$location' /></head></html>\n";
	exit;
}

header('Location: ' . $location);

?>
