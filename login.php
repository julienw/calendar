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
if (isset($_GET['logout'])) {
  $auth->logout();
} else if (!$auth->check()) {
  http_response_code(403);
  echo <<<HTML
    <!doctype html>
    <html>
      <body>
        <p>Login incorrect.</p>
        <p><a href="$location">Retourner à l'accueil</a></p>
      </body>
    </html>
    HTML;
  exit;
}

header('Location: ' . $location);

?>
