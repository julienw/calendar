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
   Classe exposée en SOAP

   Julien Wajsberg <felash@gmail.com>
   8 mai 2005

   */

class Calendrier_soap {
	var $calendrier;
	var $__typedef = array(
			'events' => array(
				'id' => 'xsd:string',
				'event' => 'xsd:string',
				'horaire' => 'xsd:string',
				'jour'	=> 'xsd:string',
				),
			'ArrayOfEvents' => array(array('tns:events')),
			);

	var $__dispatch_map = array(
			'getEventsForUserId' => array(
				'in' => array(
					'user_id' => 'string',
					'year' => 'string',
					'month' => 'string',
					),
				'out' => array(
					'retour' => 'tns:ArrayOfEvents',
					),
				),
			);

	function Calendrier_soap (& $calendrier) {
		$this->calendrier =& $calendrier;
	}

	function & getEventsForUserId ($user_id, $year, $month) {
		return $this->calendrier->readDataForUserId($user_id, $year, $month);
	}

}
?>
