<?php

/*
   Copyright 2005 Julien Wajsberg <felash@gmail.com>
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
   4 octobre 2005

   Actions sur le calendrier lorsqu'il n'y a pas de Javascript
 */

require_once 'includes/setup.php';
// conf
require_once 'conf/config.inc.php';

// ma classe
require_once('includes/calendrier.inc.php');
require_once('includes/calendrier_xmlrpc.inc.php');
// auth
require_once('includes/auth.inc.php');
require_once('includes/calendar_auth.inc.php');

$parameters = array('id', 'data', 'day', 'action');

function redirect() {
	global $site_url;
	global $parameters;

	$myParameters = $parameters;
	$myParameters[] = 'inputnew\d+';
	$querystring = preg_replace('/(' . join('|', $myParameters) . ')(?:=[^&]*&(?:amp;)?)?/', '', $_SERVER['QUERY_STRING']);

	$location = $site_url . ($_SERVER['PATH_INFO'] ?? "") . '?' . $querystring;
	
	header('Location: ' . $location);
	exit;
}

function extractDate() {
	$querystring = $_SERVER['QUERY_STRING'];
	$date = substr(strrchr($querystring, '&'), 1);
	$nbmatchs = preg_match(',^(\d+)(?:/|%2F)(\d+),', $date, $matches);
	if ($nbmatchs == 0) return null;
	$year = $matches[1];
	$month = $matches[2];

	return array($year, $month);
}

function extractNewData($day) {
	return $_GET['inputnew' . $day];
}

function extractData($id) {
	return $_GET['input' . $id];
}

$db = new PDO($dsn, null, null, array(
  PDO::ATTR_PERSISTENT => true
));

/* instanciation du calendrier */
$cal = 0;
if (isset($_GET['cal'])) {
	$cal = $_GET['cal'];
}

$calendrier = new Calendrier($db, $cal);
if (! $calendrier->isActive()) {
	redirect();
}

// authentification
$auth = new Auth($db);
$cal_auth = new CalendarAuth($calendrier, $auth);
if (!$cal_auth->checkWrite()) {
	redirect();
}

$calendrier = new Calendrier_xmlrpc($calendrier, $auth->getId());

foreach ($parameters as $var) {
	if (isset($_GET[$var]) && !empty($_GET[$var])) {
		$$var = $_GET[$var];
	}
}

if (isset($_GET['inputnew' . $day])) {
	$data = $_GET['inputnew' . $day];
}

if (!isset($action)) {
	redirect();
}

switch ($action) {
	case 'deleteData':
	case 'addUser':
	case 'removeUser':
		$calendrier->$action($id);
		break;
	case 'writeData':
		$date = extractDate();
		$data = extractNewData($day);
		if ($date != null && $data != null) {
			list ($year, $month) = $date;
			$calendrier->writeData($data, $year, $month, $day);
		}
		break;
	case 'modifyData':
		$data = extractDate($id);
		$calendrier->modifyData($data, $id);
		break;
}

redirect();
?>
