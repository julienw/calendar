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

class CalendarAuth {
	var $calendar;
	var $auth;

	function CalendarAuth(&$calendar, &$auth) {
		$this->calendar =& $calendar;
		$this->auth =& $auth;
	}

	/* fonction générique */
	function check($right) {
		$this->auth->check(); // pour initialiser l'objet
		/* tout le monde a le droit de modifier */
		if ($this->calendar->getGuestsRights() >= $right) {
			return true;
		}

		if (! $this->auth->check()) return false;
		/* un user enregistré a le droit de modifier */
		if ($this->calendar->getAllUsersRights() >= $right) return true;

		/* un user enregistré qui se trouve ds la table de croisement a le droit de modifier) */
		$uid = $this->auth->getId();
		if ($this->calendar->getSubscribedUsersRights() >= $right
			&& $this->calendar->isSubscribedUser($uid)) return true;

		return false;
	}

	/* vérifie le droit d'écriture */
	function checkWrite() {
		return $this->check(CALENDRIER_PUB_READWRITE);
	}

	function checkRead() {
		return $this->check(CALENDRIER_PUB_READ);
	}
}
