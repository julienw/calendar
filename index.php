<?php
/*
   Copyright 2005, 2006 Julien Wajsberg <felash@gmail.com>
   This file is part of Concert Calendar.

   Concert Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   Concert Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with Concert Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/*
   Julien Wajsberg <felash@gmail.com>
   26 avril 2005
 */
session_start();

require_once 'conf/config.inc.php';

require_once 'Calendar/Month/Weekdays.php';
require_once 'Calendar/Util/Uri.php';
require_once 'Calendar/Util/Textual.php';
require_once 'includes/event.inc.php';
require_once 'includes/event_writer.inc.php';
require_once('includes/calendrier.inc.php');
require_once('includes/auth.inc.php');
require_once('includes/calendar_auth.inc.php');
require_once 'includes/my_log.inc.php';

/* pour les noms des mois (TODO : rendre configurable) */
setlocale(LC_TIME, 'fr_FR');


$db = new PDO($dsn, null, null, array(
  PDO::ATTR_PERSISTENT => true
));

$log = new MyLog($db, 'index');
$log->debug("Entrée dans index");

/* vérification de l'authentification */
$auth = new Auth($db);

/* instanciation des routines d'accès au calendrier */
$cal = 0;
if (isset($_GET['cal'])) {
	$cal = htmlspecialchars($_GET['cal'], ENT_QUOTES);
}

$calendrier = new Calendrier($db, $cal);

if (! $calendrier->isActive()) {
	$cal = 0;
	$calendrier = new Calendrier($db);
}

/* authentification du calendrier */
$cal_auth = new CalendarAuth($calendrier, $auth);

/* liens rss */
$rss_titles = $calendrier->getRssTitles();
$rss = array (
		htmlspecialchars($rss_titles[0], ENT_QUOTES) => 'rss.php?cal=' . $cal,
		htmlspecialchars($rss_titles[1], ENT_QUOTES) => 'rss.php?next&amp;cal=' . $cal,
);
if ($auth->check()) {
	$rss[htmlspecialchars($rss_titles[2], ENT_QUOTES)] = 'rss.php?next&amp;user=' .  htmlspecialchars($auth->getUsername()) . '&amp;cal=' . $cal ;
}

/* récupération du mois à afficher */
/* (TODO : récupérer avec un path_info) */
foreach ($_GET as $key => $value) {
	if (strpos($key, '/') !== false) {
		$path_infos = explode('/', $key);
		break;
	}
	if (is_numeric($key)) {
		$path_infos = array($key);
		break;
	}
}

/* $path_infos est un tableau de 2 éléments : 1er = annee, 2e = mois */
if (isset($path_infos) && count($path_infos) > 0) {
	$year = $path_infos[0];

	/* année incorrecte */
	if (! is_numeric($year)) unset($year);
	if ($year < 1980 || $year > 2500) unset($year);

	/* si le mois est spécifié, on l'utilise */
	if (count($path_infos) > 1) {
		$month = $path_infos[1];
		
		/* mois incorrect */
		if (! is_numeric($month)) unset($month);
		if ($month < 1 || $month > 12) unset($month);

		if (count($path_infos) > 2) {
			$day = $path_infos[2];

			/* jour incorrect */
			if ((! is_numeric($day)) || $day < 1 || $day > 31) $day = 1;
		}
	}

	/* si le mois n'est pas spécifié, ou si le mois était incorrect,
	   on prend le mois de janvier */
	if (! isset($month)) {
		$month = 1;
		$day = 1;
	}
}

$thisYear = date('Y');
$thisMonth = date('n');
$thisDay = date('j');

/* si l'année n'est pas spécifiée, c'est à dire :
   - soit il n'y a pas d'info de date passée en argument
   - soit ce qui est passé en argument etait invalide
   alors on utilise le mois actuel */
if (! isset($year)) {
	$year = $thisYear; 
	$month = $thisMonth;
	$day = $thisDay;
}

if (isset($_GET['debug'])) {
	echo "\n<!-- year = $year ; month = $month ; day = $day -->\n";
	echo "<!-- thisYear = $thisYear ; thisMonth = $thisMonth ; thisDay = $thisDay -->\n";
}

$login_page = "login.php?" . $_SERVER['QUERY_STRING'];
$logout_page = "login.php?logout&amp;" . $_SERVER['QUERY_STRING'];

/* instanciation du mois correspondant, et création des jours */
$Month = new Calendar_Month_Weekdays($year, $month);
$Month->build();

/* classe utilitaire de génération des liens */
$Uri = new Calendar_Util_Uri('year', 'month');
$Uri->separator = '/';
$Uri->scalar = true;

/* classe utilitaire d'affichage du mois en toutes lettres */
$Textual = new Calendar_Util_Textual();

/* unicode à cause de mysql 4 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title><?php echo $calendrier->getName() ?></title>
<?php /* unicode à cause de mysql 4 */ ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="initial-scale=1"/>
<?php
foreach ($rss as $title => $url) {
	echo "<link rel='alternate' type='application/rss+xml' title='RSS $title' href='$url' />\n";
}
?>
<link type='text/css' rel='stylesheet' href='style/style.css' />

<?php

if ($cal_auth->checkWrite()) {
?>
<script type='text/javascript' src='calendar_server.php?client=all&amp;cal=<?php echo $cal ?>'></script>
<script type='text/javascript' src='calendar_server.php?stub=all&amp;cal=<?php echo $cal ?>'></script>
<script type='text/javascript' src='js/behaviour.js.php'></script>
<script type='text/javascript' language='JavaScript'>
<!--
/* initialisations */
var year = <?php echo $year ?>;
var month = <?php echo $month ?>;
var currentuser = '<?php echo ucfirst(htmlspecialchars($auth->getUsername())) ?>';
var daysInMonth = <?php echo date('t', $Month->getTimestamp()) ?>;
// -->
</script>

<?php
}
?>
</head>
<div id='nav' class='banner'>
<?php /* navigation temporelle */ ?>
<ul id='temporel' class='liens'>
<li><ul>
<?php
for ($i = $year - 3; $i <= $year + 3; $i++) {
	if ($i == $year) {
		echo "  <li>$i</li>\n";
	} else {
		echo "  <li><a href='" . $_SERVER['PHP_SELF']  ."?cal=$cal&amp;$i'>$i</a></li>\n";
	}
}
?>
</ul></li>
<li><ul>
<?php
$months = array(
		'01' => array(
			'Jan', 'Janvier'
			),
		'02' => array(
			'F&eacute;v', 'F&eacute;vrier'
			),
		'03' => array(
			'Mar', 'Mars'
			),
		'04' => array(
			'Avr', 'Avril'
			),
		'05' => array(
			'Mai', 'Mai'
			),
		'06' => array(
			'Jun', 'Juin'
			),
		'07' => array(
			'Jul', 'Juillet'
			),
		'08' => array(
			'Ao&ucirc;', 'Ao&ucirc;t'
			),
		'09' => array(
			'Sept', 'Septembre'
			),
		'10' => array(
			'Oct', 'Octobre'
			),
		'11' => array(
			'Nov', 'Novembre'
			),
		'12' => array(
			'D&eacute;c', 'D&eacute;cembre'
			)
);

foreach ($months as $monthnb => $monthname) {
	echo "  <li><a href='" . $_SERVER['PHP_SELF']  ."?$year/$monthnb&amp;cal=$cal'>
		<abbr title='{$monthname[1]}'>{$monthname[0]}</abbr></a></li>\n";
}
?>
</ul></li>
<li><ul>
<li><a href='<?php echo $_SERVER['PHP_SELF'] . '?cal=' . $cal . '&amp;' . $Uri->prev($Month, 'month') ?>'>Mois pr&eacute;c&eacute;dent</a></li>
<li><a href='<?php echo $_SERVER['PHP_SELF'] . '?cal=' . $cal ?>'>Aujourd'hui</a></li>
<li><a href='<?php echo $_SERVER['PHP_SELF'] . '?cal=' . $cal . '&amp;' . $Uri->next($Month, 'month') ?>'>Mois suivant</a></li>
</ul></li></ul>
  <ul id='rss' class='liens'>
<?php
foreach ($rss as $title => $url) {
	echo "    <li><a href='$url'>RSS&nbsp;: $title</a></li>\n";
}
?>
</ul>
<?php /* liens : connexion, recherche */ ?>
<ul id='autres' class='liens'>
<?php
if ($auth->check()) {
	echo <<<HTML
	<li><a href='$logout_page'>D&eacute;connexion</a></li>
	<li><a href='motdepasse.php'>Changement de mot de passe</a></li>
	<li>iCal&nbsp;: <ul><li><a href='ical.php?cal=$cal'>Tous les concerts</a></li>
HTML;
	echo " <li><a href='ical.php?user=" . htmlspecialchars($auth->getUsername()) . "&amp;cal=$cal'>Mes concerts</a></li>\n";
	echo "</ul></li>\n";
} else {
	echo <<<HTML
	<li><a href='$login_page'>Connexion</a></li>
	<li><a href='motdepasse.php'>Mot de passe oubli&eacute;</a></li>
	<li><a href='ical.php?cal=$cal'>Export iCal</a></li>
HTML;
}
?>
  <li>
  <form id='search' action='search.php' method='get'>
  <div>
    <input type='hidden' name='cal' value='<?php echo $cal ?>' />
    <input type='text' name='key' />
    <input type='submit' value='Envoyer' />
  </div>
  </form>
  </li>
</ul>
<?php
$array_calendars = Calendrier::getSubscribedCalendars($db, ($auth->check()) ? $auth->getId() : -1);
if (count($array_calendars) > 0) {
?>
<div id='calendriers' class='liens'>
  <h2>Les calendriers</h2>
  <ul>
<?php
$querystring = preg_replace('/cal=\d+&?/', '', $_SERVER['QUERY_STRING']);
foreach($array_calendars as $cal) {
	$thisquerystring = 'cal=' . $cal->getId() . '&amp;' . $querystring;
	echo "    <li><a href='" . $_SERVER['PHP_SELF'] . '?' . $thisquerystring ."'>" . $cal->getName() . "</a></li>\n";
}
?>
  </ul>
</div>
<?php
}
?>
<h1>
<?php
/* affichage du mois en toutes lettres */
print iconv('iso-8859-15', 'utf-8', $Textual->thisMonthName($Month));
print " ";
print $year;
?></h1>
<hr class='separator' />
</div>
<?php
if (! $cal_auth->checkRead()) {
	echo '<h2>Accès interdit au calendrier ' . $calendrier->getName() .'</h2>';
} else {
?>
<!-- tableau du calendrier -->
<table id='calendrier'>

<?php
foreach($Month->fetchAll() as $curDay) {
	echo "\n<!-- we're at : " . $curDay->thisDay() . "/" . $curDay->thisMonth() . "/" .  $curDay->thisYear() . " -->\n";
	if ((isset($day)
		&& $curDay->thisDay() == $day
		&& $curDay->thisMonth() == $month
		&& $curDay->thisYear() == $year)
		|| ((! isset($day))
		&& $curDay->thisDay() == $thisDay
		&& $curDay->thisMonth() == $thisMonth
		&& $curDay->thisYear() == $thisYear)) {
		if (isset($_GET['debug'])) {
			echo "<!-- selected " . $curDay->thisDay() . "/" . $curDay->thisMonth() . "/" .  $curDay->thisYear() . " -->\n";
		}
		$curDay->setSelected(true);
	}
	$Event = new Event($curDay, $calendrier, ($cal_auth->checkWrite()) ? ($auth->getUsername()) : false);
	
	/* nouvelle semaine -> nouvelle ligne */
	if ($Event->isFirst()) {
		echo " <tr>\n";
	}

	echo $Event->thisCell();

	/* fin de la semaine -> fin de la ligne */
	if ($Event->isLast()) {
		echo " </tr>\n";
	}
}

?>

</table>
<?php
} /* else (checkRead) */
?>
<div id='motdepasse' class='popup'>
</div>
</body>
</html>
