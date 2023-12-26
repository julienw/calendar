<?php

require_once 'includes/setup.php';
require_once 'conf/config.inc.php';
require_once 'includes/auth.inc.php';

$db = new PDO($dsn, null, null, array(
  PDO::ATTR_PERSISTENT => true
));

$querystring = str_replace('logout&', '', $_SERVER['QUERY_STRING']);
$location = $site_url . '?' . $querystring;

$auth = new Auth($db);
if ((! $auth->check()) or (isset($_GET['logout']))) {
	$auth->sendAuthRequest();
	echo "<html><head><meta http-equiv='refresh' content='0;URL=$location' /></head></html>\n";
	exit;
}

header('Location: ' . $location);

?>
