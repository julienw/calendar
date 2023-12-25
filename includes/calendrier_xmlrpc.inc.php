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
   8 mai 2005
   */
/*
   Classe calendrier exposée par JPSpan

   C'est surtout un Wrapper vers la classe Calendrier

   */


class Calendrier_xmlrpc {
	var $calendrier;
	var $user_id;
	
	function __construct(&$calendrier, $user_id) {
		$this->calendrier =& $calendrier;
		$this->user_id = $user_id;
	}

	function writeData($data, $year, $month, $day) {
		return $this->calendrier->writeData($data, $year, $month, $day, $this->user_id);
	}

	function modifyData($data, $id) {
		return $this->calendrier->modifyData($data, $id);
	}

	function deleteData($id) {
		return $this->calendrier->deleteData($id);
	}

	function addUser($id) {
		return $this->calendrier->addUserToEvent($id, $this->user_id);
	}
	
	function removeUser($id) {
		return $this->calendrier->removeUserFromEvent($id, $this->user_id);
	}
}

?>
