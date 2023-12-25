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
	24 avril 2005
	
	La plus grosse partie de ce fichier provient de
	http://jpspan.sourceforge.net/wiki/doku.php?id=examples:inahurry

 */

// conf
require_once 'conf/config.inc.php';

// ma classe
require_once('includes/calendrier.inc.php');
require_once('includes/calendrier_xmlrpc.inc.php');
// auth
require_once('includes/auth.inc.php');
require_once('includes/calendar_auth.inc.php');
// log
require_once('includes/my_log.inc.php');

$db = new PDO($dsn, null, null, array(
  PDO::ATTR_PERSISTENT => true
));

$log = new MyLog($db, 'calendar_server');
$log->debug("Entrée dans calendar_server");

// authentification
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

/* real stuff */
if (!$cal_auth->checkWrite()) {
	die('Non autorisé');
}

require_once 'HTML/AJAX/Server.php';
$server = new HTML_AJAX_Server();

// Register your class with it...
$calendrier_rpc = new Calendrier_xmlrpc($calendrier, $auth->getId());
$server->registerClass($calendrier_rpc);
$server->handleRequest();
?>
