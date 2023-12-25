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
   17 mai 2005

   Cette classe est un Helper pour d�finir � un seul endroit le contenu
   d'une ligne d'�v�n�ment : partie de l'affichage textuelle d'un
   �v�nement d'une case vide.
 */

require_once('XML/Tree.php');
require_once('node_writer.inc.php');

class Event_TextEmpty_Writer extends Node_Writer {

	function __construct() {
		$div = new XML_Tree_Node('div');
		$div->setAttribute('class', 'event vevent');

		/* 'dd' de l'�v�nement : intitul� */
		$users = new XML_Tree_Node('dd', '[__USERS__]');
		$data = new XML_Tree_Node('dd', '__DATA__');
		$data->setAttribute('class', 'summary');

		$div->addChild($users);
		$div->addChild($data);
		
		$this->nodes[] = $div;
	}
}
?>
