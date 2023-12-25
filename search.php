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
   20 septembre 2005
 */
require_once('includes/calendrier.inc.php');
require_once 'conf/config.inc.php';
require_once 'includes/auth.inc.php';
require_once 'includes/calendar_auth.inc.php';

/* connexion à la db */
$db = new PDO($dsn, null, null, array(
  PDO::ATTR_PERSISTENT => true
));

/* instanciation des routines d'accès au calendrier */
if (isset($_GET['cal'])) {
	$calendrier = new Calendrier($db, $_GET['cal']);
	if (!$calendrier->isActive()) exit;

	$site_url .= '?cal=' . $_GET['cal'] . '&';
} else {
	$calendrier = new Calendrier($db);
	$site_url .= '?';
}

/* authentification */
$auth = new Auth($db);
$cal_auth = new CalendarAuth($calendrier, $auth);

if ($cal_auth->checkRead()) {

	$key = $_GET['key'];
	$date = $calendrier->getDateForKey($key);
	$date = strtr($date, '-', '/');

	$site_url .= $date;
}

header('Location: ' . $site_url);
?>
