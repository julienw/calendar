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
   26 avril 2005
 */

require_once 'Calendar/Decorator.php';
require_once('event_writer.inc.php');

/*
   classe qui met en forme chaque cellule du calendrier.
   Eventuellement à retravailler pour pouvoir faire plusieurs
   vues

   Elle est instanciée pour chaque case (jour) (c'est peut-être
   justement ça qu'il faut changer)
 */
class Event extends Calendar_Decorator {
	var $calendrier;
	var $events;
	var $event_writer;
	var $authenticated;
	
	function Event (& $Calendar, & $Calendrier, $authenticated = false) {
		parent::Calendar_Decorator($Calendar);
		$this->calendrier =& $Calendrier;
		$this->event_writer =& Event_Writer::getInstance();
		$this->authenticated = $authenticated;
	}

	/* retourne le code html correspondant à une cellule de calendrier */
	function thisCell() {
		$Day = parent::thisDay();

		if (parent::isEmpty()) {
			/* si c'est pas un jour du mois, c'est un jour vide */
			$class='empty ';
		} else {
			$class='full ';
		}

		$class .= strtolower(date('l', $this->getTimestamp()));

		if (parent::isSelected()) {
			$class .= " today";
		}

		$str = "  <td class='$class'";
		if (!$this->isEmpty()) {
			$str .= " id='td$Day'";
		}
		$str .= ">\n";

		/* chaque cellule est un 'dl', dont le 'dt' est le jour de la semaine */
		$str .= "<dl class='day' ";
		if (!$this->isEmpty()) {
			$str .= "id='day$Day'";
		}
		$str .= "><dt class='daytitle'>$Day</dt>";

		/* et dont le dd est un autre dl, contenant tous les événements proprement dits */
		if (! isset($this->events)) {
			// mise en cache
			$this->cacheEvents();
		}

		if (! empty($this->events)) {
			$str .= "<dd><dl class='events'";
			if (!$this->isEmpty()) {
				$str .= " id='dl$Day'";
			}
			$str .=">";

			/* affichage de tous les événéments */
			while ($event = $this->thisEvent()) {
				$str .= $event;
			}
			$str .= "</dl></dd>\n";

		}
		$str .= "</dl>\n";

		/* si c'est un jour de ce mois, afficher le formulaire d'ajout */
		if (! $this->isEmpty()) {
			$str .= $this->writeFormNewEvent();
		}
		$str .="</td>\n";

		return $str;
	}

	function isEmpty() {
		if ($this->authenticated && ! parent::isEmpty()) {
			return false;
		}
		return true;
	}

	function cacheEvents() {
		/* mise en cache de tous les événements d'un jour donné */
		$Day = parent::thisDay();
		$Month = parent::thisMonth();
		$Year = parent::thisYear();
		$this->events =& $this->calendrier->readData($Year, $Month, $Day);
		reset($this->events);
	}
	
	/* affichage d'un élément à chaque appel */
	function thisEvent() {
		$tuple = each($this->events);
		if ($tuple === false) return false;

		$tuple = $tuple['value'];
		$tuple['users'] = $this->calendrier->readUsersForEvent($tuple['id']);
		/* création du code html proprement dit */
		return $this->writeEvent($tuple);
	}

	/* écriture d'un événement */
	/* en argument : un tableau associatif avec les clés correspondant
	   aux champs sql : event, horaire, id */
	function writeEvent($event) {
		$value = htmlspecialchars($event['event'], ENT_QUOTES);
		$horaire = htmlspecialchars($event['horaire'], ENT_QUOTES);

		/* si pas d'horaire, alors on le met à 00:00 (arbitraire) */
		if (empty($horaire)) $horaire = '00:00';
		$id = htmlspecialchars($event['id'], ENT_QUOTES);

		$class ='add';
		$action = 'addUser';

		if (! empty($event['users'])) {
			$users = implode(', ', $event['users']);
			$users = ucwords($users);
			$users = htmlspecialchars($users, ENT_QUOTES);

			/* si l'user est authentifié et qu'il est un participant, on met un "-" */
			if (in_array($this->authenticated, $event['users'])) {
				$class = 'remove';
				$action = 'removeUser';
			}
		}

		$date = parent::thisYear() . '/' . parent::thisMonth();


		if ($this->isEmpty()) {
			$line = $this->event_writer->get_empty_as_html($id, $horaire, $value, $users);
		} else {
			$line = $this->event_writer->get_as_html($id, $horaire, $value, $users, $class, $action, $date);
		}

		return $line;
	}

	/* affichage du formulaire pour l'ajout d'un nouvel événement */
	function writeFormNewEvent () {
		$day = parent::thisDay();
		$year = parent::thisYear();
		$month = parent::thisMonth();
		
		$str .= "<form action='actions.php' id='formnew$day' class='new'>\n";
		$str .= "<input type='text' name='inputnew$day' id='inputnew$day' value='" .
			$this->calendrier->getNewEventLabel() . "' />\n";
		$str .= "<input type='hidden' name='action' value='writeData' />\n";
		$str .= "<input type='hidden' name='day' value='$day' />\n";
		$str .= "<input type='hidden' name='$year/$month' value='' />\n";
		$str .= "</form>\n";

		return $str;
	}

}


