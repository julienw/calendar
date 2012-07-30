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

require_once('XML/Tree.php');
require_once('node_writer.inc.php');

class Event_Text_Writer extends Node_Writer {

	function Event_Text_Writer() {
		/* partie texte */
		$cell1 =& new XML_Tree_Node('dt');

		$trash =& new XML_Tree_Node('a');
		$trash->setAttribute('href', 'actions.php?action=deleteData&amp;id=__ID__&amp;__DATE__');
		$trash->setAttribute('onclick', 'doDeleteData(__ID__); return false');
		$trash->setAttribute('class', 'icon delete');
		$trash->setAttribute('title', 'Effacer cet &eacute;v&eacute;nement');
		$trash->setContent(' ');
		$cell1->addChild($trash);

		$addremove =& new XML_Tree_Node('a');
		$addremove->setAttribute('href', 'actions.php?action=__TOGGLEACTION__&amp;id=__ID__&amp;__DATE__');
		$addremove->setAttribute('onclick', 'togglePresenceUser(__ID__); return false');
		$addremove->setAttribute('class', 'icon __MYCLASS__');
		$addremove->setAttribute('id', 'addremoveicon__ID__');
		$addremove->setAttribute('title', 'S\'ajouter ou s\'enlever de cet &eacute;v&eacute;nement');
		$addremove->setContent(' ');
		$cell1->addChild($addremove);

		$notsure =& new XML_Tree_Node('a');
		$notsure->setAttribute('href', 'javascript: toggleNotSure(__ID__)');
		$notsure->setAttribute('class', 'icon question');
		$notsure->setAttribute('title', 'Je viens ou je ne viens pas ?');
		$notsure->setContent(' ');
//		$cell1->addChild($notsure);
		
		$users =& new XML_Tree_Node('div', '[__USERS__]');
		$users->setAttribute('class', 'users');
		$users->setAttribute('id', 'users__ID__');
		$cell1->addChild($users);

		$cell2 =& new XML_Tree_Node('dd');
		$cell2->setAttribute('id', 'lineoftitle__ID__');

		/* lien du nouvel événement pour permettre sa modification */
		$newnodelink =& new XML_Tree_Node('a', '__DATA__');
		$newnodelink->setAttribute('href', 'javascript: showInput(__ID__)');
		$newnodelink->setAttribute('class', 'link summary');
		$newnodelink->setAttribute('id', 'evttitle__ID__');
		$cell2->addChild($newnodelink);

		/* div non semantique; c'est lui qu'on va supprimer a la deletion d'un event */
		$div =& new XML_Tree_Node('div');
		$div->setAttribute('id', 'lineofevent__ID__');
		$div->setAttribute('class', 'event vevent');

		$div->addChild($cell1);
		$div->addChild($cell2);

		$this->nodes[] =& $div;
	}
}
?>
