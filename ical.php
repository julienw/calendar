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
   Export iCal
   Julien Wajsberg <felash@gmail.com>
   22 sept 2005
   */

require_once('DB.php');
require_once('includes/calendrier.inc.php');

require_once 'conf/config.inc.php';
require_once 'includes/auth.inc.php';
require_once 'includes/calendar_auth.inc.php';

/* connexion à la db */
$db =& DB::connect($dsn);
if (PEAR::isError($db)) {
	    die($db->getMessage());
}

/* instanciation des routines d'accès au calendrier */
if (isset($_GET['cal'])) {
	$calendrier = new Calendrier($db, $_GET['cal']);
	if (! $calendrier->isActive()) {
		exit;
	}
} else {
	$calendrier = new Calendrier($db);
}

/* authentification */
$auth = new Auth($db);
$cal_auth = new CalendarAuth($calendrier, $auth);
if (! $cal_auth->checkRead()) exit;

if (!empty($_GET['user'])) {
	$all =& $calendrier->readAllDataForUser($_GET['user']);
} else {
	$all =& $calendrier->readData();
}


header ("Content-Type: text/calendar");
?>
BEGIN:VCALENDAR
CALSCALE:GREGORIAN
X-WR-CALNAME;VALUE=TEXT:<?php echo $calendrier->getName() ?>

X-WR-RELCALID;VALUE=TEXT:<?php echo $site_url ?>
VERSION:2.0

<?php
foreach ($all as $row) {
	list($year, $month, $day) = explode('-', $row['jour']);
	$date = str_replace('-', '', $row['jour']);
	$datestart = $date . 'T200000';
	$dateend = $date. 'T230000';
/*	$datestart = date("Ymd", mktime(0, 0, 0, $month, $day, $year));
	$dateend = date("Ymd", mktime(0, 0, 0, $month, $day + 1, $year)); */
	$datestamp = date("Ymd\THis\Z", $row['timestamp']);
	$location = strchr($row['event'], '-');
	if ($location) {
		$location = trim(substr($location, 1));
	} else {
		$location = "Inconnu";
	}
	

echo <<<ICAL
BEGIN:VEVENT
SUMMARY:{$row['event']}
UID:concert{$row['id']} @ $site_url
DTSTAMP:$datestamp
DTSTART;VALUE=DATE-TIME:$datestart
DTEND;VALUE=DATE-TIME:$dateend
LOCATION:$location
DESCRIPTION:{$row['event']}
URL:$site_url?$year/$month/$day
STATUS:CONFIRMED
END:VEVENT

ICAL;
}
?>
END:VCALENDAR
