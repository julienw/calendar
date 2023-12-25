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
   9 mai 2005

   Cette classe est un Helper pour définir à un seul endroit le contenu
   d'une ligne d'événément : partie de l'affichage textuelle d'un
   événement.
 */

require_once('node_writer.inc.php');

class Event_Text_Writer extends Node_Writer {

	function __construct() {
    $str = <<<'XML'
      <div id='lineofevent__ID__' class='event vevent'>
        <dt>
          <a
            href='actions.php?action=deleteData&amp;id=__ID__&amp;__DATE__'
            onclick='doDeleteData(__ID__); return false'
            class='icon delete'
            title='Effacer cet événement'>
          </a>
          <a
            href='actions.php?action=__TOGGLEACTION__&amp;id=__ID__&amp;__DATE__'
            onclick='togglePresenceUser(__ID__); return false'
            class='icon __MYCLASS__'
            id='addremoveicon__ID__'
            title="S'ajouter ou s'enlever de cet événement">
          </a>
          <div class='users' id='users__ID__'>[__USERS__]</div>
        </dt>
        <dd id='lineoftitle__ID__'>
          <a
            href='javascript: showInput(__ID__)'
            class='link summary'
            id='evttitle__ID__'
          >__DATA__</a>
        </dd>
      </div>
      XML;

    $div = new SimpleXMLElement($str);
		$this->nodes[] = $div;
	}
}
?>
