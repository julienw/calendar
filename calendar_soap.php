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
   Classe qui va instancier le serveur SOAP

   Julien Wajsberg <felash@gmail.com>
   8 mai 2005

   */

require_once 'conf/config.inc.php';
require_once 'includes/calendrier.inc.php';
require_once 'includes/calendrier_soap.inc.php';
require_once 'SOAP/Server.php';

require_once 'DB.php';

$db =& DB::connect($dsn);
if (PEAR::isError($db)) {
	die($db->getMessage());
}

$calendrier =& new Calendrier($db);

$calendar_soap =& new Calendrier_soap($calendrier);

// Switch off notices to all GET
error_reporting(E_ALL ^ E_NOTICE);

// Instantiate PEAR::SOAP SOAP_Server
//$soapServer=new SOAP_Server(array('use' => 'literal', 'style' => 'document'));
$soapServer = new SOAP_Server();

// Build the object map (using this instance) + add a namespace
$soapServer->addObjectMap($calendar_soap,'urn:ConcertCalendar');

if (isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD'] == 'POST') {
	$soapServer->service($GLOBALS['HTTP_RAW_POST_DATA']);
} else {
	// Deal with WSDL / Disco here
    require_once 'SOAP/Disco.php';

    // Create the Disco server
    $disco = new SOAP_DISCO_Server($soapServer,'CalendarServer');
    header("Content-type: text/xml");
    if (isset($_SERVER['QUERY_STRING']) &&
        strcasecmp($_SERVER['QUERY_STRING'],'wsdl')==0) {
        echo $disco->getWSDL(); // if we're talking http://www.example.com/index.php?wsdl
    } else {
        echo $disco->getDISCO();
    }
    exit;
}

?>
