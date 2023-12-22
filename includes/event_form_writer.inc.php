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
   d'une ligne d'événément : partie du formulaire de modification de
   l'événément.
 */

require_once('XML/Tree.php');
require_once('node_writer.inc.php');

class Event_Form_Writer extends Node_Writer {

	function Event_Form_Writer() {

		/* partie formulaire */
		$form = new XML_Tree_Node('form');
		$form->setAttribute('action', 'actions.php');
		$form->setAttribute('id', 'form__ID__');
		$form->setAttribute('class', 'event');
		$form->setAttribute('onsubmit', 'submitOneForm(__ID__); return false');

		/* zone d'input */
		$input = new XML_Tree_Node('input');
		$input->setAttribute('type', 'text');
		$input->setAttribute('id', 'input__ID__');
		$input->setAttribute('name', 'input__ID__');
		$input->setAttribute('value', '__DATA__');
		$input->setAttribute('onclick', 'dontPropagate(event); return true');
		
		$hidden = new XML_Tree_Node('input');
		$hidden->setAttribute('type', 'hidden');
		$hidden->setAttribute('name', 'id');
		$hidden->setAttribute('value', '__ID__');
		
		$form->addChild($input);
		$form->addChild($hidden);
		$this->nodes[] = $form;
		
	}
}
?>
